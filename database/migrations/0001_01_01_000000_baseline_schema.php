<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The schema as it stood when the application moved to Laravel. Databases carried
     * over from the previous version already have it and are left untouched.
     */
    public function up(): void
    {
        if (Schema::hasTable('tours')) {
            return;
        }

        DB::unprepared(file_get_contents(database_path('schema/baseline.sql')));
    }

    public function down(): void
    {
        // Deliberately irreversible: this would drop every table of the application.
    }
};
