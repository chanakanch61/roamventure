<?php

namespace App\Support;

use RuntimeException;

/**
 * Turns one entry of $_FILES into the JPEG shown when a page is shared on Facebook, LINE or X.
 * Images are center-cropped and scaled down, never up, so a shared card is not left blurry.
 */
class ShareImage
{
    public const WIDTH = 1200;

    public const HEIGHT = 630;

    private const MAX_BYTES = 5 * 1024 * 1024;

    private const MAX_SIDE = 8000;

    private const QUALITY = 85;

    private const DECODERS = [IMAGETYPE_JPEG => 'imagecreatefromjpeg', IMAGETYPE_PNG => 'imagecreatefrompng', IMAGETYPE_WEBP => 'imagecreatefromwebp'];

    /** Clockwise rotations recorded by cameras, as the counterclockwise angle imagerotate() expects. */
    private const ROTATIONS = [3 => 180, 6 => -90, 8 => 90];

    /**
     * @return string Path relative to the project root
     *
     * @throws RuntimeException With a message safe to show to the admin
     */
    public static function store(array $file): string
    {
        $tmp = $file['tmp_name'] ?? null;
        if (! is_string($tmp) || ($file['error'] ?? -1) !== UPLOAD_ERR_OK || ! is_uploaded_file($tmp)) {
            throw new RuntimeException('อัปโหลดรูปไม่สำเร็จ กรุณาลองใหม่ (ไฟล์ต้องไม่เกิน 5 MB)');
        }
        $info = @getimagesize($tmp);
        if (! $info || ! isset(self::DECODERS[$info[2]]) || filesize($tmp) > self::MAX_BYTES || $info[0] > self::MAX_SIDE || $info[1] > self::MAX_SIDE) {
            throw new RuntimeException('ใช้รูป JPG, PNG หรือ WebP ไม่เกิน 5 MB และ 8000 × 8000 px');
        }
        $decoder = self::DECODERS[$info[2]];
        if (! function_exists($decoder)) {
            throw new RuntimeException('เซิร์ฟเวอร์ยังไม่รองรับการปรับขนาดรูปประเภทนี้ (PHP GD)');
        }
        $angle = self::ROTATIONS[$info[2] === IMAGETYPE_JPEG && function_exists('exif_read_data') ? (int) (@exif_read_data($tmp)['Orientation'] ?? 1) : 1] ?? 0;
        [$width, $height] = abs($angle) === 90 ? [$info[1], $info[0]] : [$info[0], $info[1]];
        if ($width < self::WIDTH || $height < self::HEIGHT) {
            throw new RuntimeException('รูปเล็กเกินไป ต้องมีขนาดอย่างน้อย '.self::WIDTH.' × '.self::HEIGHT.' px (รูปนี้ '.$width.' × '.$height.' px)');
        }
        // Running out of memory while decoding is a fatal error that cannot be caught, so check first.
        $limit = ini_parse_quantity(ini_get('memory_limit'));
        if ($limit > 0 && memory_get_usage() + $info[0] * $info[1] * ($angle ? 10 : 5) > $limit) {
            throw new RuntimeException('รูปมีความละเอียดสูงเกินไป กรุณาย่อรูปให้เล็กลงแล้วลองใหม่');
        }
        $source = @$decoder($tmp);
        if (! $source) {
            throw new RuntimeException('อ่านไฟล์รูปไม่ได้ กรุณาบันทึกรูปใหม่แล้วลองอีกครั้ง');
        }
        if ($angle) {
            $source = imagerotate($source, $angle, 0);
        }

        $scale = max(self::WIDTH / $width, self::HEIGHT / $height);
        $cropWidth = (int) round(self::WIDTH / $scale);
        $cropHeight = (int) round(self::HEIGHT / $scale);
        $canvas = imagecreatetruecolor(self::WIDTH, self::HEIGHT);
        // JPEG has no transparency, so transparent PNG and WebP areas become white.
        imagefill($canvas, 0, 0, imagecolorallocate($canvas, 255, 255, 255));
        imagecopyresampled($canvas, $source, 0, 0, (int) (($width - $cropWidth) / 2), (int) (($height - $cropHeight) / 2), self::WIDTH, self::HEIGHT, $cropWidth, $cropHeight);
        imageinterlace($canvas, true);

        $path = 'public/uploads/'.bin2hex(random_bytes(16)).'.jpg';
        if (! @imagejpeg($canvas, base_path($path), self::QUALITY)) {
            throw new RuntimeException('บันทึกรูปภาพไม่สำเร็จ');
        }

        return $path;
    }
}
