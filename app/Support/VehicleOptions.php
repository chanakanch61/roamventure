<?php

namespace App\Support;

class VehicleOptions
{
    public const TEMPLATE_FOLDER = 'public/uploads/vehicle-templates/';

    public const TEMPLATE_IMAGE = '~^public/uploads/vehicle-templates/[a-f0-9]+\.(jpg|jpeg|png|webp)$~D';

    public static function types()
    {
        return [
            'car' => ['label' => 'รถยนต์', 'image' => 'public/images/vehicles/car.svg'],
            'mpv' => ['label' => 'รถ SUV / MPV', 'image' => 'public/images/vehicles/mpv.svg'],
            'van' => ['label' => 'รถตู้', 'image' => 'public/images/vehicles/van.svg'],
            'minibus' => ['label' => 'มินิบัส', 'image' => 'public/images/vehicles/minibus.svg'],
            'coach' => ['label' => 'รถโค้ช', 'image' => 'public/images/vehicles/coach.svg'],
        ];
    }

    // Starting point for the editor's fill button. Check it against the supplier before publishing.
    public static function sample()
    {
        return [
            ['min' => 2, 'max' => 3, 'type' => 'car', 'seats' => 5],
            ['min' => 4, 'max' => 5, 'type' => 'mpv', 'seats' => 7],
            ['min' => 6, 'max' => 7, 'type' => 'van', 'seats' => 9],
            ['min' => 8, 'max' => 9, 'type' => 'minibus', 'seats' => 14],
            ['min' => 10, 'max' => 12, 'type' => 'minibus', 'seats' => 19],
        ];
    }

    // Returns every submitted row, valid or not, so a failed save can redisplay what was typed.
    public static function parse($rows, &$errors, $folder = '', array $oldImages = [])
    {
        $own = '~^public/uploads/tours/'.preg_quote((string) $folder, '~').'/images/[a-f0-9]+\.(jpg|jpeg|png|webp)$~D';
        $result = [];
        foreach (is_array($rows) ? $rows : [] as $row) {
            if (! is_array($row)) {
                $errors[] = 'ข้อมูลรถไม่ถูกต้อง';

                continue;
            }
            $numbers = [];
            foreach (['min', 'max', 'seats'] as $field) {
                $numbers[$field] = is_string($row[$field] ?? null) ? trim($row[$field]) : '';
            }
            $image = is_string($row['image'] ?? null) ? trim($row['image']) : '';
            if (implode('', $numbers) === '' && $image === '') {
                continue;
            }
            if ($image !== '' && (! (in_array($image, $oldImages, true) || preg_match($own, $image) || preg_match(self::TEMPLATE_IMAGE, $image)) || ! is_file(base_path($image)))) {
                $errors[] = 'รูปรถไม่ถูกต้อง กรุณาอัปโหลดใหม่';
                $image = '';
            }
            $type = $row['type'] ?? '';
            if (! is_string($type) || ! isset(self::types()[$type])) {
                $type = array_key_first(self::types());
                $errors[] = 'กรุณาเลือกประเภทรถทุกแถว';
            }
            foreach ($numbers as $field => $value) {
                $numbers[$field] = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 99]]);
            }
            $row = ['min' => (int) $numbers['min'], 'max' => (int) $numbers['max'], 'type' => $type, 'seats' => (int) $numbers['seats']] + ($image !== '' ? ['image' => $image] : []);
            if (! $numbers['min'] || ! $numbers['max'] || ! $numbers['seats']) {
                $errors[] = 'จำนวนคนและจำนวนที่นั่งของรถต้องเป็นเลข 1–99 ครบทุกช่อง';
            } elseif ($row['max'] < $row['min']) {
                $errors[] = 'จำนวนคนสูงสุดของรถต้องไม่น้อยกว่าจำนวนขั้นต่ำ';
            } elseif ($row['seats'] < $row['max']) {
                $errors[] = 'รถ '.$row['seats'].' ที่นั่งรับ '.$row['max'].' คนไม่ได้';
            }
            foreach ($result as $other) {
                if ($row['min'] <= $other['max'] && $row['max'] >= $other['min']) {
                    $errors[] = 'ช่วงจำนวนคนของรถซ้อนกัน';
                }
            }
            $result[] = $row;
        }
        if (count($result) > 12) {
            $errors[] = 'เพิ่มรถได้สูงสุด 12 แถว';
        }
        usort($result, static fn ($a, $b) => $a['min'] <=> $b['min']);

        return $result;
    }

    public static function encode(array $rows)
    {
        return $rows ? json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null;
    }

    public static function decode($json)
    {
        return is_string($json) ? (json_decode($json, true) ?: []) : [];
    }

    // Uploaded photos only. A row without one falls back to its type's illustration.
    public static function images(array $rows)
    {
        return array_values(array_filter(array_column($rows, 'image')));
    }

    public static function label(array $row)
    {
        return (self::types()[$row['type']]['label'] ?? '').' '.(int) $row['seats'].' ที่นั่ง';
    }
}
