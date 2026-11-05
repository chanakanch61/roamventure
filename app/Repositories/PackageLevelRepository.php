<?php

namespace App\Repositories;

use App\Support\ResolvesServices;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class PackageLevelRepository
{
    use ResolvesServices;

    public $error = '';

    public function all()
    {
        return DB::table('package_levels')->orderBy('sort_order')->orderBy('id')->get()->all();
    }

    public function find($id)
    {
        return DB::table('package_levels')->where('id', (int) $id)->first();
    }

    // Catalog writers and program saves acquire this lock before tour/departure locks.
    public function lock()
    {
        return DB::select('SELECT * FROM package_levels ORDER BY id FOR UPDATE');
    }

    public function resolve($rate, $levels = null)
    {
        if (isset($rate['level_id']) && ! is_scalar($rate['level_id'])) {
            return null;
        }
        foreach ($levels ?? $this->all() as $level) {
            if (! empty($rate['level_id']) ? (string) $rate['level_id'] === (string) $level['id'] : ($rate['level'] ?? null) === $level['name']) {
                return $level;
            }
        }

        return null;
    }

    public function hydrate($dep, $levels = null)
    {
        if (empty($dep['private_rates'])) {
            return $dep;
        }
        $levels = $levels ?? $this->all();
        $rates = json_decode($dep['private_rates'], true) ?: [];
        foreach ($rates as &$rate) {
            $level = $this->resolve($rate, $levels);
            if ($level) {
                $rate['level_id'] = (int) $level['id'];
                $rate['level'] = $level['name'];
            }
        }
        unset($rate);
        $dep['private_rates'] = json_encode($rates, JSON_UNESCAPED_UNICODE);

        return $dep;
    }

    public function usages($level)
    {
        $ids = [];
        foreach (DB::table('departures')->select('tour_id', 'private_rates')->whereNotNull('private_rates')->get()->all() as $dep) {
            foreach (json_decode($dep['private_rates'], true) ?: [] as $rate) {
                if (! empty($rate['level_id']) ? (int) $rate['level_id'] === (int) $level['id'] : ($rate['level'] ?? '') === $level['name']) {
                    $ids[$dep['tour_id']] = true;
                }
            }
        }

        return count($ids);
    }

    private function fail($message)
    {
        $this->error = $message;
        DB::rollBack();

        return false;
    }

    private function valid($name, $sort)
    {
        if (! is_string($name) || trim($name) === '' || mb_strlen(trim($name)) > 100 || ! is_int($sort) || $sort < 0 || $sort > 9999) {
            $this->error = 'กรอกชื่อระดับ 1–100 ตัวอักษร และลำดับ 0–9999';

            return false;
        }

        return true;
    }

    public function save($id, $name, $sort)
    {
        if (! $this->valid($name, $sort)) {
            return false;
        }
        $name = trim($name);
        DB::beginTransaction();
        $this->lock();
        if ($id && ! $this->find($id)) {
            return $this->fail('ไม่พบระดับบริการ');
        }
        $duplicate = DB::table('package_levels')->where('name', $name)->where('id', '!=', (int) $id)->first();
        if ($duplicate) {
            return $this->fail('ชื่อระดับนี้มีอยู่แล้ว');
        }
        $data = ['name' => $name, 'sort_order' => $sort];
        try {
            if ($id) {
                DB::table('package_levels')->where('id', $id)->update($data);
            } else {
                $id = DB::table('package_levels')->insertGetId($data);
            }
        } catch (QueryException $e) {
            return $this->fail('บันทึกไม่สำเร็จ กรุณาลองใหม่');
        }
        DB::commit();

        return $id;
    }

    // All rows are saved together; one invalid or duplicate name saves nothing.
    public function save_many($rows)
    {
        if (! is_array($rows) || ! $rows) {
            $this->error = 'ไม่มีระดับบริการให้บันทึก';

            return false;
        }
        $data = [];
        foreach ($rows as $row) {
            $name = is_array($row) ? ($row['name'] ?? null) : null;
            $sort = is_array($row) ? filter_var($row['sort_order'] ?? null, FILTER_VALIDATE_INT) : false;
            if (! $this->valid($name, $sort)) {
                return false;
            }
            $name = trim($name);
            $key = mb_strtolower($name);
            if (isset($data[$key])) {
                $this->error = 'ชื่อระดับซ้ำกันในรายการ: '.$name;

                return false;
            }
            $data[$key] = ['name' => $name, 'sort_order' => $sort];
        }
        DB::beginTransaction();
        $this->lock();
        foreach ($data as $level) {
            if (DB::table('package_levels')->where('name', $level['name'])->first()) {
                return $this->fail('ชื่อระดับนี้มีอยู่แล้ว: '.$level['name']);
            }
            try {
                DB::table('package_levels')->insert($level);
            } catch (QueryException $e) {
                return $this->fail('บันทึกไม่สำเร็จ กรุณาลองใหม่');
            }
        }
        DB::commit();

        return count($data);
    }

    public function delete($id)
    {
        DB::beginTransaction();
        $this->lock();
        $level = $this->find($id);
        if (! $level) {
            return $this->fail('ไม่พบระดับบริการ');
        }
        if ($this->usages($level)) {
            return $this->fail('ระดับนี้ใช้อยู่ในโปรแกรม กรุณานำออกจากพีเรียดก่อนลบ');
        }
        try {
            DB::table('package_levels')->where('id', $id)->delete();
        } catch (QueryException $e) {
            return $this->fail('ลบไม่สำเร็จ กรุณาลองใหม่');
        }
        DB::commit();

        return true;
    }
}
