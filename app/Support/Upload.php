<?php

namespace App\Support;

use finfo;

/**
 * Validates and stores one entry of $_FILES. Controllers assemble synthetic $_FILES entries
 * for nested and multiple uploads, so this works on that array rather than UploadedFile.
 */
class Upload
{
    private const MIMES = [
        'jpg' => ['image/jpeg', 'image/pjpeg'],
        'jpeg' => ['image/jpeg', 'image/pjpeg'],
        'png' => ['image/png', 'image/x-png'],
        'webp' => ['image/webp'],
        'pdf' => ['application/pdf', 'application/force-download', 'application/x-download', 'binary/octet-stream'],
    ];

    private const IMAGE_TYPES = ['jpg', 'jpeg', 'png', 'webp'];

    private array $config = [];

    private array $errors = [];

    private array $data = [];

    public function initialize(array $config): static
    {
        $this->config = $config + ['upload_path' => '', 'allowed_types' => '', 'max_size' => 0, 'max_width' => 0, 'max_height' => 0, 'encrypt_name' => false, 'file_ext_tolower' => false];
        $this->errors = [];
        $this->data = [];

        return $this;
    }

    public function do_upload(string $field): bool
    {
        $file = $_FILES[$field] ?? null;

        if (! is_array($file) || ! is_string($file['name'] ?? null)) {
            return $this->fail('You did not select a file to upload.');
        }
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || ! is_uploaded_file($file['tmp_name'])) {
            return $this->fail(match ($file['error'] ?? UPLOAD_ERR_NO_FILE) {
                UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the maximum allowed size in your PHP configuration file.',
                UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the maximum size allowed by the submission form.',
                UPLOAD_ERR_PARTIAL => 'The file was only partially uploaded.',
                UPLOAD_ERR_NO_FILE => 'You did not select a file to upload.',
                UPLOAD_ERR_NO_TMP_DIR => 'The temporary folder is missing.',
                UPLOAD_ERR_CANT_WRITE => 'The file could not be written to disk.',
                UPLOAD_ERR_EXTENSION => 'The file upload was stopped by extension.',
                default => 'You did not select a file to upload.',
            });
        }

        $tmp = $file['tmp_name'];
        $original = basename(str_replace('\\', '/', $file['name']));
        $extension = strtolower(pathinfo($original, PATHINFO_EXTENSION));
        $allowed = explode('|', $this->config['allowed_types']);
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($tmp) ?: '';
        $image = in_array($extension, self::IMAGE_TYPES, true) ? @getimagesize($tmp) : false;

        if (! in_array($extension, $allowed, true)
            || ! in_array($mime, self::MIMES[$extension] ?? [], true)
            || (in_array($extension, self::IMAGE_TYPES, true) && $image === false)) {
            return $this->fail('The filetype you are attempting to upload is not allowed.');
        }

        $sizeKb = round($file['size'] / 1024, 2);
        if ($this->config['max_size'] > 0 && $sizeKb > $this->config['max_size']) {
            return $this->fail('The file you are attempting to upload is larger than the permitted size.');
        }
        if ($image !== false && (($this->config['max_width'] > 0 && $image[0] > $this->config['max_width']) || ($this->config['max_height'] > 0 && $image[1] > $this->config['max_height']))) {
            return $this->fail('The image you are attempting to upload does not fit into the allowed dimensions.');
        }

        $directory = rtrim(str_replace('\\', '/', $this->config['upload_path']), '/').'/';
        if (! is_dir($directory) || ! is_writable($directory)) {
            return $this->fail('The upload destination folder does not appear to be writable.');
        }

        $name = $this->config['encrypt_name'] ? bin2hex(random_bytes(16)).'.'.$extension : $this->availableName($directory, $original);
        if (! @move_uploaded_file($tmp, $directory.$name)) {
            return $this->fail('A problem was encountered while attempting to move the uploaded file to the final destination.');
        }

        $this->data = [
            'file_name' => $name,
            'file_type' => $mime,
            'file_path' => $directory,
            'full_path' => $directory.$name,
            'raw_name' => pathinfo($name, PATHINFO_FILENAME),
            'orig_name' => $original,
            'client_name' => $file['name'],
            'file_ext' => '.'.$extension,
            'file_size' => $sizeKb,
            'is_image' => $image !== false,
            'image_width' => $image === false ? '' : $image[0],
            'image_height' => $image === false ? '' : $image[1],
        ];

        return true;
    }

    public function data(): array
    {
        return $this->data;
    }

    public function display_errors(string $open = '<p>', string $close = '</p>'): string
    {
        return implode('', array_map(fn ($error) => $open.$error.$close, $this->errors));
    }

    private function fail(string $error): bool
    {
        $this->errors[] = $error;

        return false;
    }

    private function availableName(string $directory, string $original): string
    {
        $name = str_replace(' ', '_', preg_replace('/[^\pL\pN._ -]+/u', '', $original));
        $stem = pathinfo($name, PATHINFO_FILENAME);
        $extension = pathinfo($name, PATHINFO_EXTENSION);
        for ($i = 1; is_file($directory.$name); $i++) {
            $name = $stem.$i.'.'.$extension;
        }

        return $name;
    }
}
