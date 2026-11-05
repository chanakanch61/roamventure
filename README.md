# RoamVenture

เว็บขายทัวร์และแพ็คเกจส่วนตัว พร้อมหลังบ้านจัดการโปรแกรม การจอง การชำระเงิน และเนื้อหา

- Laravel 13 บน PHP 8.3 (ตรงกับ PHP 8.3.1 ของ MAMP ในเครื่องนี้)
- MySQL 5.7 ของ MAMP (ใช้กับ MariaDB 10.4 ได้เช่นกัน)
- กติกาธุรกิจและรายละเอียดแต่ละส่วนอยู่ที่ [docs/features.md](docs/features.md)

## เปิดในเครื่อง

1. เปิด MAMP แล้ว Start Servers (PHP 8.3.1, Apache พอร์ต 8888, MySQL พอร์ต 8890)
2. เปิด <http://roamventure.com:8888/> และหลังบ้านที่ <http://roamventure.com:8888/admin>

`roamventure.com` ชี้มาที่เครื่องนี้ผ่านไฟล์ `hosts` อยู่แล้ว ส่วน <http://roamventure.com> ที่ไม่มีพอร์ตคือเวอร์ชัน CodeIgniter เดิมบน XAMPP

ค่าที่ตั้งไว้นอกโปรเจกต์:

| ไฟล์ | สิ่งที่เพิ่ม |
|---|---|
| `C:\MAMP\conf\php8.3.1\php.ini` | `extension=php_fileinfo.dll` |
| `C:\MAMP\conf\apache\httpd.conf` | บรรทัด `Include` ของ vhost ท้ายไฟล์ |
| `C:\MAMP\conf\apache\roamventure-vhost.conf` | vhost ที่มี DocumentRoot เป็น `public/` |

## คำสั่ง

PHP บน `PATH` ของเครื่องนี้เป็นคนละเวอร์ชันกับ MAMP ให้เรียกผ่าน PHP ของ MAMP:

```powershell
$php = 'C:\MAMP\bin\php\php8.3.1\php.exe'
& $php -c C:\MAMP\conf\php8.3.1\php.ini artisan about
```

| คำสั่ง | ใช้ทำอะไร |
|---|---|
| `artisan roamventure:install [--seed-demo]` | เตรียมฐานข้อมูลใหม่ ไม่ล้างข้อมูลเดิม ไม่เปลี่ยนรหัสผ่านเดิม รันซ้ำได้ |
| `artisan roamventure:reset-admin` | ตั้งรหัสผ่านผู้ดูแลจาก `ADMIN_EMAIL` และ `ADMIN_PASSWORD` (อย่างน้อย 12 ตัวอักษร) |
| `artisan roamventure:sync-code-prefix` | ใช้ตัวย่อรหัสที่บันทึกไว้กับรหัสทัวร์ทั้งหมด และเก็บลิงก์เดิมไว้ |
| `artisan roamventure:env production\|develop` | สลับ `APP_ENV`, `APP_DEBUG`, `LOG_LEVEL` ใน `.env` แล้วล้าง config cache ไม่แตะ `APP_URL`, ฐานข้อมูล และ `SESSION_SECURE_COOKIE` |

`composer` ในเครื่องรันด้วย PHP รุ่นใหม่กว่า แต่ `config.platform.php` ใน `composer.json` ล็อกไว้ที่ 8.3.1 จึงไม่ติดตั้งแพ็กเกจที่ใช้กับ MAMP ไม่ได้ เมื่ออัปเกรด PHP ของ MAMP ให้แก้ค่านี้ตาม

## ติดตั้งใหม่

```powershell
composer install
copy .env.example .env
& $php -c C:\MAMP\conf\php8.3.1\php.ini artisan key:generate
# สร้างฐานข้อมูลว่างชื่อตาม DB_DATABASE (utf8mb4 / utf8mb4_unicode_ci) ก่อน
$env:ADMIN_EMAIL='admin@example.com'; $env:ADMIN_PASSWORD='<อย่างน้อย 12 ตัวอักษร>'
& $php -c C:\MAMP\conf\php8.3.1\php.ini artisan roamventure:install
```

ถ้าไม่กำหนดรหัสผ่าน ระบบจะสุ่มให้และแสดงเพียงตอนสร้างผู้ดูแลครั้งแรก

ไฟล์ใน `public/uploads` และ `storage/{passports,slips,tours,tour-pdfs,destination-pdfs}` ไม่อยู่ใน git เมื่อย้ายเครื่องต้องคัดลอกไปด้วย

## นำขึ้นเซิร์ฟเวอร์จริง

- **DocumentRoot ต้องชี้ไปที่ `public/` เท่านั้น** ห้ามชี้ที่รากโปรเจกต์ เพราะจะเปิดโอกาสให้เข้าถึง `.env`, `storage/passports` และซอร์สโค้ด
- PHP 8.3 ขึ้นไป พร้อม `fileinfo`, `mbstring`, `openssl`, `pdo_mysql`, `tokenizer`, `ctype`, `xml`
- `.env`:
  ```
  APP_ENV=production
  APP_DEBUG=false
  APP_URL=https://โดเมนจริง
  SESSION_SECURE_COOKIE=true
  LOG_LEVEL=warning
  DB_HOST=... DB_PORT=... DB_DATABASE=... DB_USERNAME=... DB_PASSWORD=...
  ```
  และสร้าง `APP_KEY` ใหม่ด้วย `artisan key:generate` ห้ามใช้ค่าจากเครื่อง dev
- หลังอัปโหลดโค้ด: ทำตาม [00-READ-BEFORE-DEPLOY.md](00-READ-BEFORE-DEPLOY.md) เว็บนี้ไม่ใช้ route cache ห้ามรัน `artisan route:cache`
- โฟลเดอร์ที่ต้องเขียนได้: `storage/`, `bootstrap/cache/`, `public/uploads/`
- Apache ต้องเปิด `mod_rewrite` และ `AllowOverride All` หากใช้ Nginx ต้องเขียนกฎเองสองข้อ: ส่ง `/public/...` ไปยังไฟล์ใน `public/` และห้ามรัน PHP ใน `public/uploads` (ดู `public/.htaccess` และ `public/uploads/.htaccess`)
- `upload_max_filesize` อย่างน้อย 10M และ `post_max_size` มากกว่าผลรวมไฟล์ต่อฟอร์ม `max_input_vars` ตั้งไว้ 10000 ใน `public/.htaccess` ถ้าใช้ PHP-FPM ต้องตั้งใน `php.ini` แทน
- ฐานข้อมูลที่ย้ายมาจากเวอร์ชันเดิมต้องรัน `tools/migrate_*.php` เดิมครบก่อน migration ตั้งต้นจะข้ามตัวเองเมื่อมีตาราง `tours` อยู่แล้ว และไม่ตรวจว่าคอลัมน์ครบหรือไม่

## โครงสร้างโค้ด

| ส่วน | ที่อยู่ | หมายเหตุ |
|---|---|---|
| Routes | `routes/web.php` | ทุก URL ระบุชัดเจน ลำดับมีผล แต่ละ action แยก GET/POST เอง |
| Controllers | `app/Http/Controllers` | `boot()` รันก่อนทุก action ใช้ตรวจสิทธิ์และตั้ง header |
| ฐานข้อมูล | `app/Repositories` | Query builder คืนแถวเป็น array และค่าเป็น string ทั้งหมด |
| Views | `resources/views` | PHP ล้วน ไม่ใช่ Blade เรียกกันด้วย `load_view()` |
| Helpers | `app/Support/helpers.php` | รวม `redirect_to()`, `show_error()`, `set_header()`, `validate_post()` |
| Schema | `database/schema/baseline.sql` | schema ตั้งต้น การเปลี่ยนแปลงต่อไปใช้ migration ปกติ |

จุดที่ต้องระวังเมื่อแก้โค้ด:

- `redirect_to()` และ `show_error()` จบ request ทันที แต่ `redirect()` ของ Laravel ไม่จบ ต้อง `return` ถ้าเขียนไว้เฉย ๆ โค้ดบรรทัดถัดไปจะยังทำงาน
- `load_view()` ส่งต่อตัวแปรจาก view ก่อนหน้าให้ partial ทุกตัวในคำขอเดียวกัน
- URL รูปและไฟล์ขึ้นต้นด้วย `/public/...` เพราะ path ในฐานข้อมูลเก็บแบบนั้น `public/.htaccess` จะแปลงให้เอง
- `APP_ENV=production` จะบันทึก PHP warning ลง log แล้วทำงานต่อ ส่วนโหมดอื่นจะขึ้น error เพื่อให้เห็นและแก้ ควรตรวจ `storage/logs/laravel.log` เป็นระยะ
- การเปลี่ยน `APP_KEY` ทำให้ session เดิมหลุดและฟอร์มจองที่เปิดค้างต้องโหลดใหม่ แต่ไม่กระทบรหัสผ่านผู้ใช้

## ความปลอดภัย

- Session และ CSRF ใช้ของ Laravel ทุก POST ต้องมี token รหัสผ่านเก็บเป็น bcrypt
- พาสปอร์ต สลิป และ PDF ต้นฉบับอยู่นอก `public/` เปิดผ่าน controller ที่ตรวจสิทธิ์เท่านั้น
- PHP 8.3 ได้รับ security fix ถึง 31 ธ.ค. 2027 ควรใช้ patch ล่าสุดของ 8.3 เสมอ

ภาพตัวอย่างดาวน์โหลดจาก Unsplash (แหล่งที่มาดูใน docs/features.md) Laravel และ CodeIgniter ใช้สัญญาอนุญาต MIT
#   r o a m v e n t u r e  
 