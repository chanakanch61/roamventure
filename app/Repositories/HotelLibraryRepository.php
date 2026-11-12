<?php

namespace App\Repositories;

use App\Support\ResolvesServices;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class HotelLibraryRepository
{
    use ResolvesServices;

    public const FOLDER = 'public/uploads/hotel-library/';

    public const IMAGE = '~^public/uploads/hotel-library/[a-f0-9]+\.(jpg|jpeg|png|webp)$~D';

    // Photos a program's day may hold, so saving one of them to the library can copy it.
    public const PROGRAM_IMAGE = '~^public/uploads/(tours/[a-z0-9-]+/images|hotel-library)/[a-f0-9]+\.(jpg|jpeg|png|webp)$~D';

    public $error = '';

    public function all()
    {
        return array_map([$this, 'row'], DB::table('hotel_library')->orderBy('name')->get()->all());
    }

    public function find($id)
    {
        $row = DB::table('hotel_library')->where('id', (int) $id)->first();

        return $row ? $this->row($row) : null;
    }

    public function save($id, $name, array $images)
    {
        $this->error = '';
        if (DB::table('hotel_library')->where('name', $name)->where('id', '!=', (int) $id)->exists()) {
            $this->error = 'มีโรงแรมชื่อนี้ในคลังแล้ว';

            return false;
        }
        $old = $id ? $this->find($id) : null;
        $data = ['name' => $name, 'images_json' => json_encode(array_values($images), JSON_UNESCAPED_SLASHES), 'updated_at' => date('Y-m-d H:i:s')];
        try {
            if ($id) {
                DB::table('hotel_library')->where('id', $id)->update($data);
            } else {
                $id = DB::table('hotel_library')->insertGetId($data + ['created_at' => $data['updated_at']]);
            }
        } catch (QueryException $e) {
            $this->error = 'บันทึกโรงแรมไม่สำเร็จ กรุณาลองใหม่';

            return false;
        }
        if ($old) {
            $this->trips->remove_replaced_files(array_diff($old['images'], $images));
        }

        return $id;
    }

    // Programs keep their copied hotel. Photos stay while any program still uses them.
    public function delete($id)
    {
        $this->error = '';
        $hotel = $this->find($id);
        if (! $hotel) {
            $this->error = 'ไม่พบโรงแรมในคลัง';

            return false;
        }
        DB::table('hotel_library')->where('id', $hotel['id'])->delete();
        $this->trips->remove_replaced_files($hotel['images']);

        return true;
    }

    // Library photos are shared as is. A program's own photo gets a copy, so the program can drop it without affecting the library.
    public function copy_image($path)
    {
        if (preg_match(self::IMAGE, $path)) {
            return $path;
        }
        $dir = base_path(self::FOLDER);
        if (! is_dir($dir) && ! @mkdir($dir, 0755, true)) {
            return null;
        }
        $target = self::FOLDER.bin2hex(random_bytes(16)).'.'.pathinfo($path, PATHINFO_EXTENSION);

        return @copy(base_path($path), base_path($target)) ? $target : null;
    }

    private function row($row)
    {
        $images = json_decode($row['images_json'] ?? '', true);

        return ['id' => (int) $row['id'], 'name' => $row['name'], 'images' => is_array($images) ? array_values(array_filter($images, 'is_string')) : []];
    }
}
