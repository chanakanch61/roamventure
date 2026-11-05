# อ่านก่อน Deploy

ขั้นตอนนำโค้ดขึ้น production ของ <https://www.roamventure.co>

## ห้ามทำ

- **ห้ามอัปโหลด** `.env`, `bootstrap/cache/`, `vendor/`, `public/uploads/` และไฟล์ข้อมูลใน `storage/` (ดูเหตุผลใน [ไฟล์ที่ห้ามอัปโหลด](#ไฟล์ที่ห้ามอัปโหลด))
- **ห้ามรัน** `migrate:fresh`, `migrate:refresh`, `migrate:reset` และ `db:wipe` บน production เพราะข้อมูลจะหาย
- **ห้ามรัน** `route:cache` เว็บนี้เลิกใช้ route cache แล้ว
- **ห้ามใช้คำสั่ง `php` เฉย ๆ** และห้ามเปลี่ยน PHP หลักของเครื่อง PHP หลักคือ 7.3 ซึ่งเว็บอื่นบน server อาจใช้อยู่ เว็บนี้ต้องเรียก `/usr/local/php83/bin/php` เต็ม path เท่านั้น
- **ห้ามรันคำสั่งด้วย `root`** ให้ใช้ user `admin` ไม่อย่างนั้นเว็บอาจเขียนไฟล์ที่ `root` สร้างไว้ไม่ได้

## ขั้นตอนทุกครั้งที่ deploy

### 1. อัปโหลดไฟล์ที่แก้

### 2. เข้าโฟลเดอร์โปรเจกต์และล้าง route cache

```bash
cd /home/admin/domains/roamventure.co/roamventure
/usr/local/php83/bin/php artisan route:clear
```

ถ้ารัน command ไม่ได้ ให้ลบไฟล์ `bootstrap/cache/routes-v7.php` ผ่าน File Manager แทน

### 3. เช็กว่ามี migration ใหม่ไหม

```bash
/usr/local/php83/bin/php artisan migrate:status
```

- **ทุกแถวเป็น `Ran`:** ข้ามไปข้อ 4
- **มีแถวเป็น `Pending`:** ทำตาม [เมื่อมี migration ใหม่](#เมื่อมี-migration-ใหม่) ก่อน

### 4. ตรวจหน้าเว็บ

- เปิดหน้าแรก และล็อกอินเข้า `/admin`
- ลองใช้ฟีเจอร์ที่เพิ่ง deploy
- ถ้ามี error ให้ดู log:

```bash
tail -n 50 storage/logs/laravel.log
```

## ต้องรันอะไรเพิ่ม

| สิ่งที่เปลี่ยน | สิ่งที่ต้องทำ |
|---|---|
| Controller, Repository, view, CSS, JS | ไม่ต้องทำเพิ่ม |
| `routes/web.php` | ไม่ต้องทำเพิ่ม ถ้าทำข้อ 2 แล้ว |
| ไฟล์ใหม่ใน `database/migrations/` | [Backup แล้ว migrate](#เมื่อมี-migration-ใหม่) |
| `.env` หรือ `config/*` | `/usr/local/php83/bin/php artisan config:cache` |
| `composer.json` หรือ `composer.lock` | อัปทั้งสองไฟล์ แล้วรัน `/usr/local/php83/bin/php $(command -v composer) install --no-dev --optimize-autoloader` |

## เมื่อมี migration ใหม่

อัปไฟล์จาก `database/migrations/` ขึ้น server ก่อน แล้วทำตามลำดับนี้

**1. ดูชื่อฐานข้อมูลและ user**

```bash
grep -E '^DB_(DATABASE|USERNAME)=' .env
```

**2. Backup ฐานข้อมูล** โดยแทน `DB_USERNAME` และ `DB_DATABASE` ด้วยค่าจากข้อ 1 แล้วกรอกรหัสผ่านเมื่อระบบถาม

```bash
mkdir -p /home/admin/backups
mysqldump --single-transaction -u DB_USERNAME -p DB_DATABASE > /home/admin/backups/roamventure-$(date +%F-%H%M).sql
ls -lh /home/admin/backups
```

ไฟล์ backup ต้องมีขนาดมากกว่า 0 ถ้าเป็น 0 ห้าม migrate ต่อ

**3. Migrate**

```bash
/usr/local/php83/bin/php artisan migrate --force
/usr/local/php83/bin/php artisan migrate:status
```

ทุกแถวต้องเป็น `Ran`

ถ้า migrate ล้มเหลว ให้หยุดและอ่าน error ก่อน อย่ารันซ้ำโดยยังไม่รู้สาเหตุ เพราะ migration อาจทำไปแล้วบางส่วน ถ้าต้องกู้ข้อมูลจาก backup ให้ใช้คำสั่งนี้ ซึ่งจะเขียนทับข้อมูลปัจจุบัน:

```bash
mysql -u DB_USERNAME -p DB_DATABASE < /home/admin/backups/ชื่อไฟล์.sql
```

## เมื่อเป็นฐานข้อมูลใหม่ที่ยังว่าง

ใช้กรณีตั้งเว็บครั้งแรกหรือย้ายไปฐานข้อมูลเปล่าเท่านั้น

1. สร้างฐานข้อมูลแบบ `utf8mb4` / `utf8mb4_unicode_ci`
2. ใส่ `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` ใน `.env` แล้วรัน `config:cache`
3. รันคำสั่งนี้ รหัสผ่านต้องยาวอย่างน้อย 12 ตัวอักษร

```bash
ADMIN_EMAIL='อีเมลผู้ดูแล' ADMIN_PASSWORD='รหัสผ่าน' /usr/local/php83/bin/php artisan roamventure:install
```

คำสั่งนี้รัน migration ให้แล้ว ไม่ต้องรัน `migrate` ซ้ำ และห้ามใส่ `--seed-demo` บน production เพราะจะเพิ่มโปรแกรมตัวอย่างลงไป

## ไฟล์ที่ห้ามอัปโหลด

| ไฟล์หรือโฟลเดอร์ | ถ้าอัปโหลดจะเกิดอะไร |
|---|---|
| `.env` | ค่าเครื่อง dev ทับ production ทำให้เชื่อมฐานข้อมูลไม่ได้และ session หลุด |
| `bootstrap/cache/` | ไฟล์จากเครื่อง dev อ้างถึงแพ็กเกจที่ production ไม่ได้ติดตั้ง เว็บอาจล่มทั้งเว็บ |
| `vendor/` | ทับแพ็กเกจของ production ด้วยชุดที่มีแพ็กเกจ dev |
| `public/uploads/` | รูปจากเครื่อง dev อาจทับหรือปนกับรูปจริง |
| `storage/passports`, `storage/slips`, `storage/tours`, `storage/tour-pdfs`, `storage/destination-pdfs` | เอกสารจริงของลูกค้าและโปรแกรมอาจถูกทับ |

## แก้ปัญหาที่เจอบ่อย

| อาการ | สาเหตุที่น่าจะเป็น | วิธีแก้ |
|---|---|---|
| หน้าใหม่หรือปุ่มใหม่ขึ้น 404 | ยังไม่ได้อัป `routes/web.php` หรือยังมี route cache เก่า | อัปไฟล์ แล้วรัน `route:clear` และเช็กด้วย `artisan route:list --path=คำใน-url` |
| 403 ไม่มีสิทธิ์ ทั้งที่เป็นแอดมิน | action ใหม่ยังไม่อยู่ใน `route_permission()` หรือยังไม่ได้อัป `app/Repositories/AdminAccessRepository.php` | เพิ่มชื่อ action หรืออัปไฟล์นี้ |
| 500 หลัง deploy | ดูสาเหตุจาก log | `tail -n 50 storage/logs/laravel.log` |
| 500 ว่าไม่พบ column หรือ table | มี migration ที่ยังไม่ได้รัน | ทำตาม [เมื่อมี migration ใหม่](#เมื่อมี-migration-ใหม่) |
| `Class ... not found` ที่ชื่อ `Pail` หรือ `Collision` | อัป `bootstrap/cache/` จากเครื่อง dev ขึ้นไป | ลบ `bootstrap/cache/packages.php` และ `services.php` แล้วรัน `artisan package:discover` |
| แก้ `.env` แล้วค่าไม่เปลี่ยน | ยังใช้ config cache เก่า | `artisan config:cache` |
| อัปไฟล์แล้วหน้าเว็บไม่เปลี่ยน | opcache ยังถือไฟล์เก่า หรืออัปผิดโฟลเดอร์ | เช็ก path ไฟล์ แล้ว reload PHP-FPM ของ `/usr/local/php83` เท่านั้น |

ตารางนี้ย่อคำสั่งเป็น `artisan ...` เวลาใช้จริงให้รันเป็น `/usr/local/php83/bin/php artisan ...` ในโฟลเดอร์โปรเจกต์
