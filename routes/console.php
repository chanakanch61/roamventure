<?php

use App\Repositories\PackageLevelRepository;
use App\Repositories\TripRepository;
use Database\Seeders\DemoSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

// Prepares a new database. Never clears existing data, changes an existing administrator's password, or seeds over existing tours.
Artisan::command('roamventure:install {--seed-demo : Add six demonstration programs to an empty database}', function () {
    $this->call('migrate', ['--force' => true]);

    DB::statement("INSERT IGNORE INTO admin_roles(name,description,is_super) VALUES ('Super Admin','ผู้ดูแลสูงสุด เข้าถึงทุกส่วนของระบบ',1)");

    if (! DB::table('package_levels')->exists()) {
        foreach (['Normal', 'Luxury', 'Premium'] as $i => $name) {
            DB::table('package_levels')->insert(['name' => $name, 'sort_order' => $i]);
        }
    }

    if (! DB::table('admins')->exists()) {
        $email = getenv('ADMIN_EMAIL') ?: 'admin@zeedytrip.local';
        $password = getenv('ADMIN_PASSWORD') ?: bin2hex(random_bytes(10));
        if (strlen($password) < 12) {
            $this->error('ADMIN_PASSWORD must contain at least 12 characters');

            return 1;
        }
        DB::table('admins')->insert([
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'name' => 'Zeedytrip Admin',
            'role_id' => DB::table('admin_roles')->where('name', 'Super Admin')->where('is_super', 1)->value('id'),
        ]);
        $this->info("Created local administrator\nEmail: {$email}\nPassword: {$password}");
    }

    foreach (['company_name' => 'Zeedytrip', 'line_id' => '', 'phone' => '', 'email' => '', 'demo_mode' => '1'] as $key => $value) {
        DB::table('settings')->insertOrIgnore(['setting_key' => $key, 'setting_value' => $value]);
    }

    $this->info('Schema ready: '.DB::getDatabaseName());

    if ($this->option('seed-demo')) {
        if (DB::table('tours')->exists()) {
            $this->info('Demo skipped: tours already exist.');

            return 0;
        }
        $this->callSilently('db:seed', ['--class' => DemoSeeder::class, '--force' => true]);
        $this->info('Created 6 sample programs. All dates, prices and itineraries are demonstration data.');
    }

    return 0;
})->purpose('Prepare a new RoamVenture database');

Artisan::command('roamventure:reset-admin', function () {
    $email = getenv('ADMIN_EMAIL');
    $password = getenv('ADMIN_PASSWORD');
    if (! $email || ! $password || strlen($password) < 12) {
        $this->error('Set ADMIN_EMAIL and ADMIN_PASSWORD (at least 12 characters).');

        return 1;
    }
    $updated = DB::table('admins')->where('email', $email)->update(['password_hash' => password_hash($password, PASSWORD_DEFAULT)]);
    $this->info($updated ? 'Password updated.' : 'Administrator not found.');

    return 0;
})->purpose('Set an administrator password from ADMIN_EMAIL and ADMIN_PASSWORD');

Artisan::command('roamventure:sync-code-prefix', function (TripRepository $trips, PackageLevelRepository $packageLevels) {
    DB::beginTransaction();
    try {
        $packageLevels->lock();
        $prefix = code_prefix($trips->settings());
        $count = $trips->sync_tour_codes($prefix);
    } catch (QueryException $e) {
        DB::rollBack();
        $this->error('Sync failed');

        return 1;
    }
    if ($count === false) {
        DB::rollBack();
        $this->error($trips->save_error ?: 'Sync failed');

        return 1;
    }
    DB::commit();
    $this->info('Updated '.$count.' tour codes to '.$prefix.'; preserved previous URLs.');

    return 0;
})->purpose('Apply the saved code prefix to every tour code');

// Changes runtime behaviour only. APP_URL, the database and SESSION_SECURE_COOKIE belong to the machine, so they stay as they are.
Artisan::command('roamventure:env {mode : production or develop}', function () {
    $values = [
        'production' => ['APP_ENV' => 'production', 'APP_DEBUG' => 'false', 'LOG_LEVEL' => 'warning'],
        'develop' => ['APP_ENV' => 'local', 'APP_DEBUG' => 'true', 'LOG_LEVEL' => 'debug'],
    ][$this->argument('mode')] ?? null;
    if (! $values) {
        $this->error('Mode must be production or develop.');

        return 1;
    }

    $path = $this->laravel->environmentFilePath();
    $env = file_get_contents($path);
    foreach ($values as $key => $value) {
        $env = preg_replace("/^{$key}=[^\r\n]*/m", "{$key}={$value}", $env, 1, $count);
        if (! $count) {
            $this->error("{$key} not found in .env; nothing changed.");

            return 1;
        }
    }
    file_put_contents($path, $env);
    $this->callSilently('config:clear');
    $this->info('Switched to '.$this->argument('mode').': APP_ENV='.$values['APP_ENV'].', APP_DEBUG='.$values['APP_DEBUG'].', LOG_LEVEL='.$values['LOG_LEVEL']);

    return 0;
})->purpose('Switch .env between production and develop behaviour');
