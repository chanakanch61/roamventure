<?php

namespace App\Repositories;

use App\Support\ResolvesServices;
use App\Support\VehicleOptions;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class VehicleTemplateRepository
{
    use ResolvesServices;

    public $error = '';

    public function all()
    {
        return DB::table('vehicle_templates')->orderBy('name')->get()->all();
    }

    public function find($id)
    {
        return DB::table('vehicle_templates')->where('id', (int) $id)->first();
    }

    public function save($id, $name, array $rows)
    {
        $this->error = '';
        if (DB::table('vehicle_templates')->where('name', $name)->where('id', '!=', (int) $id)->exists()) {
            $this->error = 'ชื่อ template นี้มีอยู่แล้ว';

            return false;
        }
        $old = $id ? $this->find($id) : null;
        $data = ['name' => $name, 'vehicle_rows' => VehicleOptions::encode($rows), 'updated_at' => date('Y-m-d H:i:s')];
        try {
            if ($id) {
                DB::table('vehicle_templates')->where('id', $id)->update($data);
            } else {
                $id = DB::table('vehicle_templates')->insertGetId($data + ['created_at' => $data['updated_at']]);
            }
        } catch (QueryException $e) {
            $this->error = 'บันทึก template ไม่สำเร็จ กรุณาลองใหม่';

            return false;
        }
        if ($old) {
            $this->trips->remove_replaced_files(array_diff(VehicleOptions::images(VehicleOptions::decode($old['vehicle_rows'])), VehicleOptions::images($rows)));
        }

        return $id;
    }

    // Programs keep their copied rows. Photos stay while any program or other template still uses them.
    public function delete($id)
    {
        $this->error = '';
        $template = $this->find($id);
        if (! $template) {
            $this->error = 'ไม่พบ template';

            return false;
        }
        DB::table('vehicle_templates')->where('id', $template['id'])->delete();
        $this->trips->remove_replaced_files(VehicleOptions::images(VehicleOptions::decode($template['vehicle_rows'])));

        return true;
    }

    // Gives a program's photo its own copy, so the program can later replace it without affecting the template.
    public function copy_image($path)
    {
        if (preg_match(VehicleOptions::TEMPLATE_IMAGE, $path)) {
            return $path;
        }
        $dir = base_path(VehicleOptions::TEMPLATE_FOLDER);
        if (! is_dir($dir) && ! @mkdir($dir, 0755, true)) {
            return null;
        }
        $target = VehicleOptions::TEMPLATE_FOLDER.bin2hex(random_bytes(16)).'.'.pathinfo($path, PATHINFO_EXTENSION);

        return @copy(base_path($path), base_path($target)) ? $target : null;
    }
}
