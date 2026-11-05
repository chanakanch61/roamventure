<?php

namespace App\Http\Controllers;

use App\Support\PrivatePackage;
use DateTime;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    protected function boot(): void
    {
        if ($this->action !== 'login') {
            $this->require_admin();
        }
    }

    public function login()
    {
        if (session('admin_id')) {
            redirect_to(base_url().('admin'));
        }
        $error = '';
        if (request_method() === 'post') {
            $ip = request()->ip();
            $since = date('Y-m-d H:i:s', time() - 900);
            $count = DB::table('login_attempts')->where('ip', $ip)->where('attempted_at', '>=', $since)->count();
            if ($count >= 8) {
                $error = 'ลองเข้าสู่ระบบหลายครั้งเกินไป กรุณารอ 15 นาที';
            } else {
                $email = request()->post('email');
                $password = request()->post('password');
                $admin = is_string($email) ? DB::table('admins')->where('email', trim($email))->first() : null;
                if ($admin && ! empty($admin['is_active']) && ! empty($admin['role_id']) && is_string($password) && password_verify($password, $admin['password_hash'])) {
                    session()->regenerate(true);
                    session()->put(['admin_id' => $admin['id'], 'admin_name' => $admin['name'], 'admin_auth' => hash('sha256', $admin['password_hash'])]);
                    // In legacy production a failed bookkeeping write did not stop the login.
                    try {
                        DB::table('admins')->where('id', $admin['id'])->update(['last_login_at' => date('Y-m-d H:i:s')]);
                    } catch (QueryException $e) {
                    }
                    try {
                        DB::table('login_attempts')->where('ip', $ip)->delete();
                    } catch (QueryException $e) {
                    }
                    redirect_to(base_url().('admin'));
                }
                try {
                    DB::table('login_attempts')->insert(['ip' => $ip, 'attempted_at' => date('Y-m-d H:i:s')]);
                } catch (QueryException $e) {
                }
                $error = 'อีเมลหรือรหัสผ่านไม่ถูกต้อง';
            }
        }

        return $this->render('admin/login', ['title' => 'เข้าสู่ระบบหลังบ้าน', 'error' => $error]);
    }

    public function logout()
    {
        $this->post_only();
        session()->invalidate();
        redirect_to(base_url().('admin/login'));
    }

    public function index()
    {
        if (! $this->access->can('tours.manage') || ! $this->access->can('bookings.manage')) {
            return $this->render('admin/access_home', ['title' => 'พื้นที่ทำงาน'], true);
        }
        $stats = ['tours' => DB::table('tours')->count(), 'published' => DB::table('tours')->where('status', 'published')->count(), 'new' => DB::table('bookings')->where('status', 'new')->count(), 'bookings' => DB::table('bookings')->count()];

        return $this->render('admin/dashboard', ['title' => 'ภาพรวม', 'stats' => $stats, 'bookings' => DB::table('bookings')->orderBy('id', 'DESC')->limit(8)->get()->all()], true);
    }

    public function tours()
    {
        $tours = $this->places->attach(DB::table('tours')->orderBy('updated_at', 'DESC')->get()->all());
        $bookings = array_column(DB::select('SELECT tour_id,COUNT(*) total FROM bookings GROUP BY tour_id'), 'total', 'tour_id');
        foreach ($tours as &$tour) {
            $tour['booking_count'] = (int) ($bookings[$tour['id']] ?? 0);
        }
        unset($tour);

        return $this->render('admin/tours', ['title' => 'โปรแกรมทัวร์', 'tours' => $tours], true);
    }

    public function delete($id)
    {
        $this->post_only();
        $id = (int) $id;
        $ok = $this->trips->delete($id);
        if ($ok) {
            $this->remove_empty_tour_folders($id);
        }
        session()->flash($ok ? 'success' : 'error', $ok ? 'ลบโปรแกรมแล้ว' : $this->trips->save_error);
        redirect_to(base_url().'admin/tours');
    }

    public function preview($id)
    {
        $tour = $this->trips->find((int) $id);
        if (! $tour) {
            show_404();
        }

return $this->render('detail', ['title' => 'ตัวอย่าง: '.$tour['title'], 'tour' => $tour, 'preview' => true, 'related_tours' => $this->trips->related($tour)]);
    }

    private function valid_date($date)
    {
        $d = DateTime::createFromFormat('!Y-m-d', $date);

        return $d && $d->format('Y-m-d') === $date;
    }

    public function hotel_photo()
    {
        $json = function ($body, $status) {
            return response(json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), $status, ['Content-Type' => 'application/json; charset=UTF-8']);
        };
        if (request_method() !== 'post') {
            return $json(['error' => 'ไม่รองรับคำขอนี้'], 405);
        }
        $folder = request()->post('folder');
        $known = is_string($folder) && (preg_match('/^new-[a-f0-9]{16}$/D', $folder) || (ctype_digit($folder) && $this->trips->find((int) $folder)));
        if (! $known) {
            return $json(['error' => 'ไม่พบโปรแกรมสำหรับเก็บรูป กรุณาโหลดหน้าใหม่'], 422);
        }
        $errors = [];
        $path = $this->upload('photo', $folder, $errors);
        if (! $path) {
            return $json(['error' => 'ใช้รูป JPG, PNG หรือ WebP ไม่เกิน 5 MB และ 8000 × 8000 px'], 422);
        }

        return $json(['path' => $path], 200);
    }

    private function posted_day_hotels($raw, $folder, $oldPaths, $dayNumber, &$errors)
    {
        $posted = is_string($raw) ? json_decode($raw, true) : null;
        if (! is_array($posted)) {
            return [];
        }
        $own = '~^public/uploads/tours/'.preg_quote((string) $folder, '~').'/images/[a-f0-9]+\.(jpg|jpeg|png|webp)$~D';
        $hotels = [];
        foreach ($posted as $hotel) {
            $name = is_array($hotel) && is_string($hotel['name'] ?? null) ? trim($hotel['name']) : '';
            $images = is_array($hotel) && is_array($hotel['images'] ?? null) ? array_values($hotel['images']) : [];
            if ($name === '' && ! $images) {
                continue;
            }
            if ($name === '' || mb_strlen($name) > 200) {
                $errors[] = 'วันที่ '.$dayNumber.': กรุณาใส่ชื่อโรงแรมไม่เกิน 200 ตัวอักษร';
            }
            foreach ($images as $path) {
                if (! is_string($path) || ! (in_array($path, $oldPaths, true) || preg_match($own, $path)) || ! is_file(base_path($path))) {
                    $errors[] = 'วันที่ '.$dayNumber.': รูปโรงแรมไม่ถูกต้อง กรุณาอัปโหลดใหม่';
                    $images = [];
                    break;
                }
            }
            $hotels[] = ['name' => $name, 'images' => $images];
        }

        return $hotels;
    }

    private function upload($field, $folder, &$errors)
    {
        if (empty($_FILES[$field]['name'])) {
            return null;
        }
        $relative = 'public/uploads/tours/'.$folder.'/images/';
        $dir = base_path($relative);
        if (! is_dir($dir) && ! @mkdir($dir, 0755, true)) {
            $errors[] = 'ไม่สามารถสร้างที่เก็บรูปภาพ';

            return null;
        }
        $this->upload->initialize(['upload_path' => $dir, 'allowed_types' => 'jpg|jpeg|png|webp', 'max_size' => 5120, 'max_width' => 8000, 'max_height' => 8000, 'encrypt_name' => true, 'file_ext_tolower' => true]);
        if (! $this->upload->do_upload($field)) {
            $errors[] = strip_tags($this->upload->display_errors());

            return null;
        }
        $file = $this->upload->data();
        if (! @getimagesize($file['full_path'])) {
            @unlink($file['full_path']);
            $errors[] = 'ไฟล์ต้องเป็นรูปภาพ';

            return null;
        }

        return $relative.$file['file_name'];
    }

    // PDFs stay outside public/ so only logged-in admins can download them through source_pdf().
    private function upload_pdf($folder, &$errors)
    {
        if (empty($_FILES['source_pdf_file']['name'])) {
            return null;
        }
        $relative = 'storage/tours/'.$folder.'/doc/';
        $dir = base_path($relative);
        if (! is_dir($dir) && ! @mkdir($dir, 0755, true)) {
            $errors[] = 'ไม่สามารถสร้างที่เก็บ PDF';

            return null;
        }
        $this->upload->initialize(['upload_path' => $dir, 'allowed_types' => 'pdf', 'max_size' => 10240, 'encrypt_name' => true, 'file_ext_tolower' => true]);
        if (! $this->upload->do_upload('source_pdf_file')) {
            $errors[] = strip_tags($this->upload->display_errors());

            return null;
        }
        $file = $this->upload->data();
        if (file_get_contents($file['full_path'], false, null, 0, 5) !== '%PDF-') {
            @unlink($file['full_path']);
            $errors[] = 'ไฟล์ต้นฉบับต้องเป็น PDF';

            return null;
        }

        return $relative.$file['file_name'];
    }

    private function remove_empty_tour_folders($folder)
    {
        foreach (['public/uploads/tours/'.$folder.'/images', 'storage/tours/'.$folder.'/doc'] as $dir) {
            @rmdir(base_path($dir));
            @rmdir(base_path(dirname($dir)));
        }
    }

    public function source_pdf($id)
    {
        $tour = $this->trips->find((int) $id);
        $path = $tour['source_pdf'] ?? '';
        if (! preg_match('~^storage/(tour-pdfs|tours/[a-z0-9-]+/doc)/[a-f0-9]+\.pdf$~D', $path) || ! is_file(base_path($path))) {
            show_404();
        }

        return response(file_get_contents(base_path($path)), 200, ['Content-Type' => 'application/pdf; charset=UTF-8', 'Content-Disposition' => 'attachment; filename="tour-'.(int) $id.'.pdf"']);
    }

    public function edit($id = 0)
    {
        $id = (int) $id;
        $existing = $id ? $this->trips->find($id) : null;
        if ($id && ! $existing) {
            show_404();
        }
        $tour = $existing ?: ['id' => 0, 'status' => 'draft', 'days' => 3, 'nights' => 2, 'itinerary' => [], 'departures' => [], 'images' => []];
        $errors = [];
        // A new program has no id yet, so hotel photos uploaded before saving share one folder name across the request.
        $postedFolder = request()->post('hotel_folder');
        $hotelFolder = $id ?: (is_string($postedFolder) && preg_match('/^new-[a-f0-9]{16}$/D', $postedFolder) ? $postedFolder : 'new-'.bin2hex(random_bytes(8)));
        if (request_method() === 'post') {
            if (request()->post('form_complete') !== '1') {
                show_error('ข้อมูลแบบฟอร์มไม่ครบ อาจเกินขนาดอัปโหลดหรือจำนวนช่องที่เซิร์ฟเวอร์รับได้ กรุณาลดจำนวนรูปหรือรอบต่อครั้ง', 400);
            }
            $data = [];
            $type = request()->post('program_type') ?? 'fixed';
            $data['program_type'] = in_array($type, ['fixed', 'private'], true) ? $type : 'fixed';
            $isPrivate = $data['program_type'] === 'private';
            $data['flight_included'] = request()->post('flight_included') === '1' ? 1 : 0;
            foreach (['airline', 'title', 'summary', 'highlights', 'hotel_details', 'transport_details', 'guide_details', 'photographer_details', 'inclusions', 'exclusions', 'payment_terms', 'cancellation_terms', 'know_before_go', 'terms', 'seo_description', 'seo_title', 'seo_keywords'] as $field) {
                $value = request()->post($field);
                $data[$field] = is_string($value) ? trim($value) : '';
            }
            $routeRows = [];
            $postedRoutes = request()->post('destinations');
            $seenRoutes = [];
            if (! is_array($postedRoutes) || count($postedRoutes) < 1 || count($postedRoutes) > 60) {
                $errors[] = 'กรุณาเลือกจุดหมาย 1–60 รายการ';
            } else {
                foreach (array_values($postedRoutes) as $row) {
                    if (! is_array($row)) {
                        $errors[] = 'ข้อมูลจุดหมายไม่ถูกต้อง';

                        continue;
                    }
                    $routeId = filter_var($row['route_id'] ?? null, FILTER_VALIDATE_INT);
                    $zoneId = filter_var($row['zone_id'] ?? null, FILTER_VALIDATE_INT);
                    $countryId = filter_var($row['country_id'] ?? null, FILTER_VALIDATE_INT);
                    $selectedRoute = $routeId ? $this->places->route($routeId) : null;
                    if (! $selectedRoute || (int) $selectedRoute['zone_id'] !== $zoneId || (int) $selectedRoute['country_id'] !== $countryId) {
                        $errors[] = 'กรุณาเลือก Zone, Country และ Route ให้สัมพันธ์กันทุกแถว';
                    }
                    if (in_array($routeId, $seenRoutes, true)) {
                        $errors[] = 'ไม่สามารถเลือก Route ซ้ำในทัวร์เดียวกัน';
                    }
                    $seenRoutes[] = $routeId;
                    $routeRows[] = ['route_id' => (int) $routeId, 'zone_id' => (int) $zoneId, 'country_id' => (int) $countryId];
                }
            }
            $firstRoute = $routeRows ? $this->places->route($routeRows[0]['route_id']) : null;
            $data['route_id'] = $firstRoute['id'] ?? null;
            $data['destination'] = $firstRoute['name'] ?? '';
            foreach (['airline' => 255, 'title' => 200, 'destination' => 100, 'seo_description' => 300, 'seo_title' => 200, 'seo_keywords' => 300, 'summary' => 5000, 'highlights' => 5000, 'hotel_details' => 3000, 'transport_details' => 3000, 'guide_details' => 3000, 'photographer_details' => 3000, 'inclusions' => 5000, 'exclusions' => 5000, 'payment_terms' => 5000, 'cancellation_terms' => 5000, 'know_before_go' => 5000, 'terms' => 5000] as $field => $max) {
                if (mb_strlen($data[$field]) > $max) {
                    $errors[] = $field.' ยาวเกินกำหนด';
                }
            }
            if (! $data['title'] || ! $data['destination']) {
                $errors[] = 'กรุณากรอกชื่อและจุดหมาย';
            }
            $data['code'] = $existing['code'] ?? '';
            $data['slug'] = $existing['slug'] ?? '';
            if (filter_var(request()->post('days'), FILTER_VALIDATE_INT) === false || filter_var(request()->post('nights'), FILTER_VALIDATE_INT) === false) {
                $errors[] = 'กรุณาระบุจำนวนวันและคืนเป็นจำนวนเต็ม';
            }
            $data['days'] = (int) request()->post('days');
            $data['nights'] = (int) request()->post('nights');
            if ($data['days'] < 1 || $data['days'] > 60 || $data['nights'] < 0 || $data['nights'] >= $data['days']) {
                $errors[] = 'จำนวนวันต้องเป็น 1–60 และจำนวนคืนน้อยกว่าจำนวนวัน';
            }
            $data['status'] = request()->post('status');
            if (! in_array($data['status'], ['draft', 'published', 'archived'], true)) {
                $data['status'] = 'draft';
            }
            $data['featured'] = request()->post('featured') ? 1 : 0;
            $data['sort_order'] = max(0, min(9999, (int) request()->post('sort_order')));
            foreach (['hotel', 'transport', 'guide', 'photographer'] as $s) {
                $v = request()->post($s.'_status');
                $data[$s.'_status'] = in_array($v, ['included', 'extra', 'none'], true) ? $v : null;
            }
            $data['source_pdf'] = $existing['source_pdf'] ?? '';
            $data['cover'] = $existing['cover'] ?? '';
            $deposit = request()->post('deposit_per_person') ?? '0';
            if (! is_string($deposit) || ! preg_match('/^\d{1,8}(\.\d{1,2})?$/D', $deposit)) {
                $errors[] = 'มัดจำต่อคนต้องเป็นจำนวนเงินตั้งแต่ 0 ทศนิยมไม่เกิน 2 ตำแหน่ง';
            }
            $data['deposit_per_person'] = is_string($deposit) ? (float) $deposit : 0;
            $days = [];
            $dayKeys = [];
            $posted = request()->post('itinerary');
            $allowedDayImages = [];
            $oldHotelPaths = [];
            foreach ($existing['itinerary'] ?? [] as $oldDay) {
                foreach (day_photos($oldDay) as $photo) {
                    $allowedDayImages[] = $photo['path'];
                }
                foreach (day_hotels($oldDay) as $oldHotel) {
                    $oldHotelPaths = array_merge($oldHotelPaths, $oldHotel['images']);
                }
            }
            $dayPictures = [];
            $pictureKeys = [];
            if (is_array($posted) && count($posted) > 60) {
                $errors[] = 'กำหนดแผนรายวันได้สูงสุด 60 วัน';
            }
            if (is_array($posted)) {
                foreach (array_slice($posted, 0, 60, true) as $dayKey => $day) {
                    if (! is_array($day)) {
                        continue;
                    }
                    $row = [];
                    foreach (['title', 'description', 'hotel'] as $f) {
                        $row[$f] = isset($day[$f]) && is_string($day[$f]) ? trim($day[$f]) : '';
                    }
                    if (mb_strlen($row['title']) > 200 || mb_strlen($row['hotel']) > 200 || mb_strlen($row['description']) > 10000) {
                        $errors[] = 'รายละเอียดรายวันยาวเกินกำหนด';
                    }
                    $row['hotels_json'] = json_encode($this->posted_day_hotels($day['hotels_json'] ?? '', $hotelFolder, $oldHotelPaths, count($dayKeys) + 1, $errors), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    foreach (['breakfast', 'lunch', 'dinner'] as $f) {
                        $meal = $day[$f] ?? '';
                        if (! in_array($meal, ['', '0', '1'], true)) {
                            $errors[] = 'ข้อมูลหรือไฟล์ไม่ถูกต้อง กรุณาตรวจสอบแล้วลองใหม่';
                        } $row[$f] = $meal === '' ? null : (int) $meal;
                    }
                    $pictures = $day['pictures'] ?? [];
                    $clean = [];
                    $keys = [];
                    if (! is_array($pictures) || count($pictures) > 6) {
                        $errors[] = 'เพิ่มภาพรายวันได้สูงสุด 6 รูปต่อวัน';
                        $pictures = [];
                    }
                    foreach ($pictures as $pictureKey => $picture) {
                        if (! is_array($picture)) {
                            $errors[] = 'ข้อมูลภาพรายวันไม่ถูกต้อง';

                            continue;
                        }
                        $path = $picture['path'] ?? '';
                        $caption = $picture['caption'] ?? '';
                        if (! is_string($path) || ($path !== '' && ! in_array($path, $allowedDayImages, true))) {
                            $errors[] = 'รูปภาพรายวันไม่ถูกต้อง กรุณาเลือกไฟล์อัปโหลด';
                            $path = '';
                        }
                        if (! is_string($caption) || mb_strlen($caption) > 200) {
                            $errors[] = 'คำบรรยายภาพรายวันต้องไม่เกิน 200 ตัวอักษร';
                            $caption = '';
                        }
                        $clean[] = ['path' => $path, 'caption' => trim($caption)];
                        $keys[] = $pictureKey;
                    }
                    $dayPictures[] = $clean;
                    $pictureKeys[] = $keys;
                    $dayKeys[] = $dayKey;
                    $days[] = $row;
                }
            }
            $departures = [];
            $posted = request()->post('departures');
            $dateKeys = [];
            if (is_array($posted) && count($posted) > 60) {
                $errors[] = 'กำหนดพีเรียดได้สูงสุด 60 รายการ';
            }
            if (is_array($posted)) {
                foreach (array_slice($posted, 0, 60) as $dep) {
                    if (! is_array($dep)) {
                        continue;
                    }
                    $start = is_string($dep['start_date'] ?? null) ? $dep['start_date'] : '';
                    $end = is_string($dep['end_date'] ?? null) ? $dep['end_date'] : '';
                    if (! $this->valid_date($start) || ! $this->valid_date($end) || $end < $start) {
                        $errors[] = 'วันเดินทางไม่ถูกต้อง';
                    } elseif (! $isPrivate && (int) (new DateTime($start))->diff(new DateTime($end))->days + 1 !== $data['days']) {
                        $errors[] = 'ช่วงวันเดินทางต้องตรงกับจำนวนวันของโปรแกรม';
                    }
                    if (in_array($start, $dateKeys, true)) {
                        $errors[] = 'วันเริ่มเดินทางซ้ำ';
                    } $dateKeys[] = $start;
                    $rates = [];
                    $blackouts = [];
                    $lead = 0;
                    if ($isPrivate) {
                        $rates = PrivatePackage::rates($dep['rates'] ?? [], $errors);
                        $lead = filter_var($dep['lead_days'] ?? 0, FILTER_VALIDATE_INT);
                        if ($lead === false || $lead < 0 || $lead > 3650) {
                            $errors[] = 'วันจองล่วงหน้าต้องอยู่ระหว่าง 0–3650';
                        }
                        $blocked = $dep['blackout_dates'] ?? '';
                        if (! is_string($blocked) || strlen($blocked) > 12000) {
                            $errors[] = 'วันงดเดินทางไม่ถูกต้อง';
                        } else {
                            foreach (preg_split('/[\s,]+/', trim($blocked), -1, PREG_SPLIT_NO_EMPTY) as $date) {
                                if (! PrivatePackage::date($date) || $date < $start || $date > $end) {
                                    $errors[] = 'วันงดเดินทางต้องเป็น YYYY-MM-DD และอยู่ภายในพีเรียด';
                                } else {
                                    $blackouts[] = $date;
                                }
                            }
                        }
                        if (PrivatePackage::date($start) && PrivatePackage::date($end) && (int) (new DateTime($start))->diff(new DateTime($end))->days + 1 < $data['days']) {
                            $errors[] = 'ช่วงวันต้องยาวพอสำหรับโปรแกรม';
                        }
                        foreach ($departures as $other) {
                            if ($start <= $other['end_date'] && $end >= $other['start_date']) {
                                $errors[] = 'ช่วงเดินทางแพ็คเกจส่วนตัวต้องไม่ซ้อนกัน';
                            }
                        }
                        $dep['capacity'] = $rates ? max(array_column($rates, 'max')) : 1;
                        $dep['price_per_person'] = $rates ? min(array_column($rates, 'price')) : 0;
                    }
                    $capacity = filter_var($dep['capacity'] ?? '', FILTER_VALIDATE_INT);
                    $raw = $dep['price_per_person'] ?? '';
                    $price = is_scalar($raw) ? (float) $raw : 0;
                    if (! $capacity || $capacity < 1 || $capacity > 500 || ! is_scalar($raw) || ! preg_match('/^\d{1,8}(\.\d{1,2})?$/', (string) $raw) || $price <= 0) {
                        $errors[] = 'ไซส์กรุ๊ปต้องเป็น 1–500 คน และราคาต่อคนต้องมากกว่า 0';
                    }
                    $single = $dep['single_supplement'] ?? '';
                    if ($single !== '' && (! is_scalar($single) || ! preg_match('/^\d{1,8}(\.\d{1,2})?$/', (string) $single))) {
                        $errors[] = 'ค่าพักเดี่ยวต้องเป็น 0 หรือจำนวนเงินบวก ทศนิยมไม่เกิน 2 ตำแหน่ง';
                    }
                    $departures[] = ['id' => (int) ($dep['id'] ?? 0), 'start_date' => $start, 'end_date' => $end, 'active' => empty($dep['active']) ? 0 : 1, 'capacity' => (int) $capacity, 'price_per_person' => $price, 'single_supplement' => $single === '' ? null : (is_scalar($single) ? (float) $single : null)];
                    $departures[count($departures) - 1] += ['private_rates' => $isPrivate ? json_encode($rates, JSON_UNESCAPED_UNICODE) : null, 'lead_days' => (int) $lead, 'blackout_dates' => $isPrivate ? json_encode(array_values(array_unique($blackouts))) : null];
                    $cutoff = filter_var($dep['deposit_cutoff_days'] ?? 30, FILTER_VALIDATE_INT);
                    $balance = filter_var($dep['balance_due_days'] ?? 14, FILTER_VALIDATE_INT);
                    if ($cutoff === false || $cutoff < 0 || $cutoff > 3650 || $balance === false || $balance < 0 || $balance > 3650 || (! empty($dep['deposit_enabled']) && ($data['deposit_per_person'] <= 0 || $balance > $cutoff))) {
                        $errors[] = 'ตรวจมัดจำ: ต้องมียอดต่อคน และวันชำระส่วนที่เหลือต้องไม่มากกว่าวันเปิดรับมัดจำ (0–3650 วัน)';
                    }
                    $departures[count($departures) - 1] += ['deposit_enabled' => empty($dep['deposit_enabled']) ? 0 : 1, 'deposit_cutoff_days' => (int) $cutoff, 'balance_due_days' => (int) $balance, 'flight_set_key' => is_string($dep['flight_set'] ?? null) ? $dep['flight_set'] : ''];
                }
            }
            $flightSets = $this->posted_flight_sets($isPrivate, $id, $errors);
            $flightSetKeys = array_column($flightSets, 'key');
            foreach ($departures as &$dep) {
                // With a single set there is nothing to choose, so every round flies it.
                if (! in_array($dep['flight_set_key'], $flightSetKeys, true) || count($flightSetKeys) === 1) {
                    $dep['flight_set_key'] = count($flightSetKeys) === 1 ? $flightSetKeys[0] : '';
                }
            }
            unset($dep);
            $images = [];
            $posted = request()->post('images');
            $allowed = array_column($existing['images'] ?? [], 'path');
            if (is_array($posted)) {
                foreach ($posted as $img) {
                    if (! is_array($img) || ! in_array($img['path'] ?? '', $allowed, true) || ! empty($img['remove'])) {
                        continue;
                    }
                    $caption = is_string($img['caption'] ?? null) ? mb_substr($img['caption'], 0, 200) : '';
                    $images[] = ['path' => $img['path'], 'caption' => $caption];
                }
            }
            if ($data['status'] === 'published') {
                if (! $data['summary'] || ! ($data['terms'] || $data['payment_terms'] || $data['cancellation_terms'])) {
                    $errors[] = 'ก่อนเผยแพร่ต้องมีคำโปรยและเงื่อนไขการจอง';
                }
                if (count($days) !== $data['days']) {
                    $errors[] = 'กรุณาลงโปรแกรมรายวันให้ครบตามจำนวนวัน';
                }
                foreach ($days as $day) {
                    if (! $day['title'] || ! $day['description']) {
                        $errors[] = 'โปรแกรมแต่ละวันต้องมีหัวข้อและรายละเอียด';
                        break;
                    }
                }
                $future = array_filter($departures, function ($d) use ($isPrivate, $data) {
                    return $d['active'] && ($isPrivate ? PrivatePackage::first_date($d + ['days' => $data['days']]) !== null : $d['start_date'] >= date('Y-m-d'));
                });
                if (! $future) {
                    $errors[] = 'ก่อนเผยแพร่ต้องมีรอบอนาคตที่เปิดรับคำขอและกำหนดราคาแล้ว';
                }
            }
            $uploaded = [];
            $replaced = [];
            $folder = $hotelFolder;
            if (! $errors) {
                $pdf = $this->upload_pdf($folder, $errors);
                if ($pdf) {
                    $replaced[] = $data['source_pdf'];
                    $data['source_pdf'] = $pdf;
                    $uploaded[] = $pdf;
                }
                $cover = $this->upload('cover_file', $folder, $errors);
                if ($cover) {
                    $replaced[] = $data['cover'];
                    $data['cover'] = $cover;
                    $uploaded[] = $cover;
                }
                for ($i = 0; $i < 6; $i++) {
                    $path = $this->upload('gallery_'.$i, $folder, $errors);
                    if ($path) {
                        $images[] = ['path' => $path, 'caption' => ''];
                        $uploaded[] = $path;
                    }
                }
                foreach ($dayKeys as $i => $dayKey) {
                    foreach ($pictureKeys[$i] as $j => $pictureKey) {
                        $file = [];
                        foreach (['name', 'type', 'tmp_name', 'error', 'size'] as $key) {
                            $file[$key] = $_FILES['itinerary'][$key][$dayKey]['pictures'][$pictureKey]['file'] ?? null;
                        }
                        if ($file['error'] === null || $file['error'] === UPLOAD_ERR_NO_FILE) {
                            if (! empty(request()->post('itinerary')[$dayKey]['pictures'][$pictureKey]['has_upload'])) {
                                $errors[] = 'ไฟล์อัปโหลดมาไม่ครบ กรุณาลดจำนวนไฟล์แล้วลองใหม่';
                            }

                            continue;
                        }
                        if (! is_string($file['name']) || ! is_string($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
                            $errors[] = 'อัปโหลดรูปวันที่ '.($i + 1).' ไม่สำเร็จ กรุณาตรวจขนาดไฟล์';

                            continue;
                        }
                        $_FILES['_day_photo'] = $file;
                        $path = $this->upload('_day_photo', $folder, $errors);
                        unset($_FILES['_day_photo']);
                        if ($path) {
                            $replaced[] = $dayPictures[$i][$j]['path'];
                            $dayPictures[$i][$j]['path'] = $path;
                            $uploaded[] = $path;
                        }
                    }
                }
                if ($data['status'] === 'published' && ! $data['cover']) {
                    $errors[] = 'กรุณาเพิ่มรูปปกก่อนเผยแพร่';
                }
            }
            foreach ($days as $i => &$day) {
                $day['images_json'] = json_encode(array_values(array_filter($dayPictures[$i], function ($p) {
                    return $p['path'] !== '';
                })), JSON_UNESCAPED_UNICODE);
                $day['image_path'] = '';
                $day['image_caption'] = '';
            }
            unset($day);
            if (! $errors) {
                $saved = $this->trips->save($id, $data, $days, $departures, $images, $routeRows, $flightSets);
                if ($saved) {
                    if (! $id) {
                        $this->trips->move_upload_folder($saved, $folder);
                    }
                    $keptHotelPaths = [];
                    foreach ($days as $savedDay) {
                        foreach (day_hotels($savedDay) as $savedHotel) {
                            $keptHotelPaths = array_merge($keptHotelPaths, $savedHotel['images']);
                        }
                    }
                    $this->trips->remove_replaced_files(array_merge($replaced, array_diff($oldHotelPaths, $keptHotelPaths)));
                    $noFlights = $isPrivate || ! $data['flight_included'] ? 0 : count(array_filter($departures, function ($d) {
                        return $d['active'] && $d['flight_set_key'] === '';
                    }));
                    session()->flash('success', 'บันทึกโปรแกรมเรียบร้อยแล้ว'.($noFlights ? ' · ทัวร์รวมตั๋วนี้ยังมี '.$noFlights.' รอบที่ไม่ได้เลือกชุดเที่ยวบิน' : ''));
                    redirect_to(base_url().('admin/tours/'.$saved));
                }
                $errors[] = $this->trips->save_error ?: 'บันทึกไม่สำเร็จ กรุณาลองอีกครั้ง';
            }
            foreach ($uploaded as $path) {
                if (is_file(base_path($path))) {
                    @unlink(base_path($path));
                }
            }
            $this->remove_empty_tour_folders($folder);
            foreach ($days as $i => &$day) {
                foreach ($dayPictures[$i] as $j => &$photo) {
                    if (in_array($photo['path'], $uploaded, true)) {
                        $previous = request()->post('itinerary')[$dayKeys[$i]]['pictures'][$pictureKeys[$i][$j]]['path'] ?? '';
                        $photo['path'] = in_array($previous, $allowedDayImages, true) ? $previous : '';
                    }
                }
                unset($photo);
                $day['images_json'] = json_encode($dayPictures[$i], JSON_UNESCAPED_UNICODE);
            }
            unset($day);
            if ($uploaded) {
                $data['source_pdf'] = $existing['source_pdf'] ?? '';
                $data['cover'] = $existing['cover'] ?? '';
                $images = array_values(array_filter($images, function ($img) use ($uploaded) {
                    return ! in_array($img['path'], $uploaded, true);
                }));
                $errors[] = 'กรุณาเลือกไฟล์รูปภาพและ PDF ใหม่หลังแก้ไขข้อมูล';
            }
            foreach ($departures as &$dep) {
                $current = $dep['id'] ? $this->trips->departure($dep['id']) : null;
                $dep['booked_seats'] = $current['booked_seats'] ?? 0;
                $dep['seats_available'] = max(0, $dep['capacity'] - $dep['booked_seats']);
            }
            unset($dep);
            $tour = array_merge($data, ['id' => $id, 'itinerary' => $days, 'departures' => $departures, 'images' => $images, 'destinations' => $routeRows, 'flight_sets' => $flightSets]);
        }

        return $this->render('admin/editor', ['title' => $id ? 'แก้ไขโปรแกรม' : 'เพิ่มโปรแกรม', 'tour' => $tour, 'hotel_folder' => $hotelFolder, 'errors' => $errors, 'destination_nodes' => $this->places->all(), 'airline_options' => $this->airlines->options($id)], true);
    }

    private function posted_flight_sets($isPrivate, $tourId, &$errors)
    {
        $posted = request()->post('flight_sets');
        if ($isPrivate || ! is_array($posted)) {
            return [];
        }
        if (count($posted) > 20) {
            $errors[] = 'กำหนดชุดเที่ยวบินได้สูงสุด 20 ชุด';
        }
        $airlineIds = array_map('intval', array_column($this->airlines->options($tourId), 'id'));
        $legTypes = flight_leg_types();
        $sets = [];
        foreach (array_slice($posted, 0, 20) as $set) {
            if (! is_array($set)) {
                continue;
            }
            $name = is_string($set['name'] ?? null) ? trim($set['name']) : '';
            $key = is_string($set['key'] ?? null) && preg_match('/^[sn][0-9]{1,10}$/D', $set['key']) ? $set['key'] : '';
            if ($key === '' || in_array($key, array_column($sets, 'key'), true)) {
                $errors[] = 'ข้อมูลชุดเที่ยวบินไม่ถูกต้อง กรุณาโหลดหน้าใหม่';

                continue;
            }
            if (mb_strlen($name) > 120) {
                $errors[] = 'ชื่อชุดเที่ยวบินต้องไม่เกิน 120 ตัวอักษร';
            }
            $flights = [];
            $rows = is_array($set['flights'] ?? null) ? $set['flights'] : [];
            if (count($rows) > 12) {
                $errors[] = 'ชุดเที่ยวบินหนึ่งชุดมีได้สูงสุด 12 เที่ยวบิน';
            }
            foreach (array_slice($rows, 0, 12) as $row) {
                if (! is_array($row)) {
                    continue;
                }
                $text = [];
                foreach (['flight_number', 'from_airport', 'to_airport', 'depart_time', 'arrive_time', 'duration'] as $field) {
                    $text[$field] = is_string($row[$field] ?? null) ? trim($row[$field]) : '';
                }
                // A row left completely empty is an unused line, not a mistake.
                if (implode('', $text) === '' && empty($row['airline_id'])) {
                    continue;
                }
                $airlineId = filter_var($row['airline_id'] ?? null, FILTER_VALIDATE_INT);
                $day = trim((string) (is_scalar($row['day_number'] ?? null) ? $row['day_number'] : ''));
                $leg = is_string($row['leg_type'] ?? null) && isset($legTypes[$row['leg_type']]) ? $row['leg_type'] : 'outbound';
                if (! in_array($airlineId, $airlineIds, true)) {
                    $errors[] = 'กรุณาเลือกสายการบินให้ครบทุกเที่ยวบิน';
                }
                if (! preg_match('/^[A-Za-z0-9][A-Za-z0-9 -]{1,11}$/D', $text['flight_number'])) {
                    $errors[] = 'เลขเที่ยวบินต้องเป็นตัวอักษรอังกฤษหรือตัวเลข 2–12 ตัว เช่น KY8370';
                }
                if ($text['from_airport'] === '' || $text['to_airport'] === '' || mb_strlen($text['from_airport']) > 40 || mb_strlen($text['to_airport']) > 40) {
                    $errors[] = 'กรุณาระบุต้นทางและปลายทางของเที่ยวบิน ไม่เกิน 40 ตัวอักษร';
                }
                foreach (['depart_time', 'arrive_time'] as $time) {
                    if (! preg_match('/^([01][0-9]|2[0-3]):[0-5][0-9]$/D', $text[$time])) {
                        $errors[] = 'กรุณาระบุเวลาออกและเวลาถึงของเที่ยวบินให้ครบ';
                    }
                }
                if ($day !== '' && (! ctype_digit($day) || (int) $day < 1 || (int) $day > 60)) {
                    $errors[] = 'วันที่ของทริปต้องเป็นตัวเลข 1–60';
                }
                $minutes = null;
                if ($text['duration'] !== '') {
                    if (preg_match('/^([0-9]{1,2}):([0-5][0-9])$/D', $text['duration'], $parts)) {
                        $minutes = (int) $parts[1] * 60 + (int) $parts[2];
                    } else {
                        $errors[] = 'ระยะเวลาบินต้องอยู่ในรูปแบบ ชม:นาที เช่น 2:15';
                    }
                }
                $flights[] = ['airline_id' => (int) $airlineId, 'leg_type' => $leg, 'day_number' => ctype_digit($day) ? (int) $day : null, 'flight_number' => strtoupper($text['flight_number']), 'from_airport' => $text['from_airport'], 'to_airport' => $text['to_airport'], 'depart_time' => $text['depart_time'], 'arrive_time' => $text['arrive_time'], 'arrive_next_day' => empty($row['arrive_next_day']) ? 0 : 1, 'duration_minutes' => $minutes, 'included' => ($row['included'] ?? '') === '1' ? 1 : 0];
            }
            if (! $flights && $name === '') {
                continue;
            }
            if (! $flights) {
                $errors[] = 'ชุดเที่ยวบิน “'.$name.'” ต้องมีอย่างน้อย 1 เที่ยวบิน';
            }
            $sets[] = ['key' => $key, 'name' => $name !== '' ? $name : 'ชุดเที่ยวบิน '.(count($sets) + 1), 'flights' => $flights];
        }

        return $sets;
    }

    public function bookings()
    {
        $paymentFilter = request()->query('payment');
        $query = DB::table('bookings')->selectRaw("bookings.*, (SELECT COUNT(*) FROM payments p WHERE p.booking_id=bookings.id AND p.status='pending') pending_slips, (SELECT COALESCE(SUM(p.reported_amount),0) FROM payments p WHERE p.booking_id=bookings.id AND p.status='approved') paid_amount");
        if ($paymentFilter === 'pending') {
            $query->whereRaw("EXISTS (SELECT 1 FROM payments p WHERE p.booking_id=bookings.id AND p.status='pending')");
        }
        if ($paymentFilter === 'overdue') {
            $query->whereRaw("bookings.status='confirmed' AND EXISTS (SELECT 1 FROM invoices i WHERE i.booking_id=bookings.id AND (CASE WHEN EXISTS (SELECT 1 FROM payments p WHERE p.booking_id=bookings.id AND p.status='approved') THEN i.balance_due_at ELSE i.payment_due_at END) < ? AND i.total_amount > (SELECT COALESCE(SUM(p.reported_amount),0) FROM payments p WHERE p.booking_id=bookings.id AND p.status='approved'))", [date('Y-m-d H:i:s')]);
        }
        $status = request()->query('status');
        if (is_string($status) && in_array($status, ['new', 'contacted', 'confirmed', 'cancelled'], true)) {
            $query->where('status', $status);
        }

        return $this->render('admin/bookings', ['title' => 'คำขอจอง', 'bookings' => $query->orderBy('id', 'DESC')->get()->all(), 'filter_status' => is_string($status) ? $status : ''], true);
    }

    public function new_booking()
    {
        if (request_method() === 'post') {
            foreach (['customer_name', 'phone', 'email', 'line_id', 'note', 'consent', 'admin_note', 'status', 'request_token'] as $field) {
                if (is_array(request()->post($field))) {
                    show_error('ข้อมูลแบบฟอร์มไม่ถูกต้อง', 400);
                }
            }
            $token = request()->post('request_token');
            if (! is_string($token) || ! preg_match('/^[0-9a-f]{48}$/D', $token)) {
                show_error('คำขอไม่ถูกต้อง กรุณาเปิดหน้าใหม่', 400);
            }
            // A resubmitted form opens the booking it already created.
            $existing = DB::table('bookings')->where('request_token', $token)->first();
            if ($existing) {
                redirect_to(base_url().('admin/bookings/'.$existing['id']));
            }
            $departureId = request()->post('departure_id');
            $rate = is_scalar($departureId) ? $this->trips->departure((int) $departureId, true) : null;
            $count = filter_var(request()->post('traveler_count'), FILTER_VALIDATE_INT);
            $quote = request()->post('quote');
            $privateStart = request()->post('travel_date');
            $privateLevel = request()->post('package_level');
            if ($rate && $rate['program_type'] === 'private') {
                $rate = PrivatePackage::offer($rate, $privateStart, $count, $privateLevel);
            }
            $rooms = $this->trips->booking_rooms(request()->post());
            $status = request()->post('status');
            $hours = filter_var(request()->post('payment_hours') ?? 24, FILTER_VALIDATE_INT);
            $validationErrors = validate_post([
                'customer_name' => ['ชื่อผู้ติดต่อ', 'required|string|max:120'],
                'phone' => ['เบอร์โทรศัพท์', ['required', 'string', 'regex:/^[0-9+() -]{8,25}$/']],
                'email' => ['อีเมล', 'bail|nullable|string|email:filter|max:190'],
                'line_id' => ['LINE ID', 'nullable|string|max:100'],
                'note' => ['รายละเอียด', 'nullable|string|max:2000'],
                'admin_note' => ['บันทึกภายใน', 'nullable|string|max:5000'],
                'consent' => ['การรับทราบนโยบายข้อมูล', 'required|in:1'],
            ]);
            $valid = $validationErrors === '';
            // Legacy: validation_errors('',' ') puts a space after each message.
            $error = ! $rate ? 'รอบหรือราคานี้มีการเปลี่ยนแปลง กรุณาเลือกใหม่' : (! $valid ? strip_tags(str_replace('</p>', ' ', $validationErrors)) : '');
            $error = $this->trips->booking_plan_error($rate, $count, $rooms, $quote) ?: $error;
            if (! in_array($status, ['new', 'contacted', 'confirmed'], true)) {
                $error = 'สถานะเริ่มต้นไม่ถูกต้อง';
            } elseif (! $hours || $hours < 1 || $hours > 720) {
                $error = 'กำหนดเวลาชำระ 1–720 ชั่วโมง';
            }
            $back = base_url().'admin/bookings/new?'.http_build_query(['tour' => $rate['tour_id'] ?? request()->post('tour_id'), 'departure' => is_scalar($departureId) ? $departureId : '']);
            if ($error) {
                session()->flash('error', $error);
                session()->flash('booking_old', array_intersect_key(request()->post(), array_flip(['customer_name', 'phone', 'email', 'line_id', 'note', 'admin_note', 'status', 'payment_hours'])));
                redirect_to($back);
            }
            $note = trim((string) request()->post('admin_note'));
            $fields = ['request_token' => $token, 'status' => $status === 'contacted' ? 'contacted' : 'new', 'admin_note' => $note, 'ticket_help' => ! $rate['flight_included'] && request()->post('ticket_help') === '1' ? 1 : 0];
            foreach (['customer_name', 'phone', 'email', 'line_id', 'note'] as $key) {
                $fields[$key] = trim((string) request()->post($key));
            }
            $booking = $this->trips->create_booking($rate, ['traveler_count' => $count, 'rooms' => $rooms, 'quote' => $quote, 'travel_date' => $privateStart, 'package_level' => $privateLevel], $fields);
            if (is_string($booking)) {
                session()->flash('error', $booking);
                redirect_to($back);
            }
            $this->billing->event($booking['id'], 'สร้างการจองโดยแอดมิน', session('admin_id') ?: null);
            // Confirmation reuses the follow-up flow so seats and the invoice are checked the same way.
            $confirmError = $status === 'confirmed' ? $this->trips->update_booking((int) $booking['id'], 'confirmed', $note, $hours) : null;
            session()->flash($confirmError ? 'error' : 'success', $confirmError ? 'สร้างการจองแล้ว แต่ยืนยันไม่สำเร็จ: '.$confirmError : 'สร้างการจอง '.$booking['reference'].' แล้ว');
            redirect_to(base_url().('admin/bookings/'.$booking['id']));
        }
        $tours = array_values(array_filter($this->trips->listing(), function ($t) {
            return ! empty($t['card_periods']);
        }));
        $tourId = filter_var(request()->query('tour'), FILTER_VALIDATE_INT);
        $tour = $tourId ? $this->trips->find($tourId, true) : null;
        // Named selected_tour in the view: the shared layout treats $tour as a tour page.
        $available = [];
        if ($tour) {
            $isPrivate = $tour['program_type'] === 'private';
            foreach ($tour['departures'] as $dep) {
                if ($dep['active'] && ($isPrivate ? PrivatePackage::first_date($dep) !== null : $dep['start_date'] >= date('Y-m-d')) && $dep['price_per_person'] > 0) {
                    $available[] = $dep;
                }
            }
        }

        return $this->render('admin/booking_new', ['title' => 'สร้างการจอง', 'tours' => $tours, 'selected_tour' => $tour, 'available' => $available, 'old' => session('booking_old') ?: [], 'request_token' => bin2hex(random_bytes(24))], true);
    }

    public function booking($id)
    {
        $booking = DB::table('bookings')->where('id', (int) $id)->first();
        if (! $booking) {
            show_404();
        }
        if (request_method() === 'post') {
            $status = request()->post('status');
            $note = request()->post('admin_note');
            if (! in_array($status, ['new', 'contacted', 'confirmed', 'cancelled'], true) || ! is_string($note) || mb_strlen($note) > 5000) {
                show_error('ข้อมูลไม่ถูกต้อง', 400);
            }
            $hours = filter_var(request()->post('payment_hours') ?? 24, FILTER_VALIDATE_INT);
            if (! $hours || $hours < 1 || $hours > 720) {
                show_error('กำหนดเวลาชำระ 1–720 ชั่วโมง', 400);
            }
            $error = $this->trips->update_booking((int) $id, $status, $note, $hours);
            session()->flash($error ? 'error' : 'success', $error ?: 'อัปเดตคำขอและที่นั่งว่างแล้ว');
            redirect_to(base_url().('admin/bookings/'.$id));
        }

        return $this->render('admin/booking', ['title' => $booking['reference'], 'booking' => $booking, 'departure' => $booking['departure_id'] ? $this->trips->departure($booking['departure_id']) : null], true);
    }

    public function site_access()
    {
        $error = '';
        $form = $this->settings;
        if (request_method() === 'post') {
            $enabled = request()->post('site_access_enabled') === '1' ? '1' : '0';
            $username = request()->post('site_access_username');
            $password = request()->post('site_access_password');
            $confirm = request()->post('site_access_password_confirm');
            $username = is_string($username) ? trim($username) : '';
            $form['site_access_enabled'] = $enabled;
            $form['site_access_username'] = $username;
            if (! preg_match('/^[a-zA-Z0-9@._-]{3,100}$/D', $username)) {
                $error = 'ชื่อผู้ใช้ต้องมี 3–100 ตัว ใช้อักษรอังกฤษ ตัวเลข หรือ @ . _ -';
            } elseif (! is_string($password) || ! is_string($confirm)) {
                $error = 'รหัสผ่านไม่ถูกต้อง';
            } elseif ($password !== '' && (mb_strlen($password) < 12 || strlen($password) > 72)) {
                $error = 'รหัสผ่านต้องมีอย่างน้อย 12 ตัวอักษร และไม่เกิน 72 ไบต์';
            } elseif ($password !== $confirm) {
                $error = 'รหัสผ่านและการยืนยันรหัสผ่านไม่ตรงกัน';
            } elseif ($enabled === '1' && $password === '' && empty($this->settings['site_access_password_hash'])) {
                $error = 'กรุณาตั้งรหัสผ่านสำหรับผู้เข้าชมก่อนบันทึกการเปิดจำกัดการเข้าชม';
            }
            if (! $error) {
                $values = ['site_access_enabled' => $enabled, 'site_access_username' => $username, 'site_access_revision' => bin2hex(random_bytes(16))];
                if ($password !== '') {
                    $values['site_access_password_hash'] = password_hash($password, PASSWORD_DEFAULT);
                }
                $saved = true;
                try {
                    DB::beginTransaction();
                    foreach ($values as $key => $value) {
                        DB::table('settings')->upsert([['setting_key' => $key, 'setting_value' => $value]], ['setting_key'], ['setting_key', 'setting_value']);
                    }
                    DB::commit();
                } catch (QueryException $e) {
                    DB::rollBack();
                    $saved = false;
                }
                if ($saved) {
                    session()->flash('success', $enabled === '1' ? 'เปิดจำกัดการเข้าชมแล้ว ผู้เข้าชมต้องเข้าสู่ระบบก่อนดูเว็บไซต์' : 'ปิดจำกัดการเข้าชมแล้ว ทุกคนสามารถดูเว็บไซต์ได้');
                    redirect_to(base_url().'admin/site-access', 303);
                }
                $error = 'บันทึกไม่สำเร็จ กรุณาลองใหม่';
            }
        }

        return $this->render('admin/site_access', ['title' => 'จำกัดการเข้าชม', 'error' => $error, 'gate_form' => $form], true);
    }

    public function settings()
    {
        $error = '';
        if (request_method() === 'post') {
            if (request()->post('form_complete') !== '1') {
                show_error('ข้อมูลอัปโหลดไม่ครบ กรุณาลดขนาดไฟล์แล้วลองใหม่', 400);
            }
            $values = [];
            foreach (['line_id', 'phone', 'email', 'company_name'] as $key) {
                $v = request()->post($key);
                $values[$key] = is_string($v) ? trim($v) : '';
                if (mb_strlen($values[$key]) > 190) {
                    $error = 'ข้อมูลยาวเกินกำหนด';
                }
            }
            $values['demo_mode'] = request()->post('demo_mode') ? '1' : '0';
            $mode = request()->post('tracking_mode') ?? ($this->settings['tracking_mode'] ?? 'off');
            if (! is_string($mode) || ! in_array($mode, ['off', 'gtm', 'direct'], true)) {
                $error = 'เลือกวิธีติดตั้ง Tracking ให้ถูกต้อง';
            }
            $values['tracking_mode'] = is_string($mode) ? $mode : 'off';
            foreach (tracking_fields() as $key => $field) {
                $raw = request()->post($key) ?? ($this->settings[$key] ?? '');
                $values[$key] = is_string($raw) ? trim($raw) : '';
                if (! is_string($raw) || ($values[$key] !== '' && ! preg_match($field['pattern'], $values[$key]))) {
                    $error = $field['label'].' ไม่ถูกต้อง กรุณากรอกเฉพาะ ID';
                }
            }
            if ($mode !== 'off' && ! tracking_config($values)['enabled']) {
                $error = 'กรุณากรอก GTM ID หรือ Pixel / LINE Tag ID ให้ตรงกับวิธีติดตั้งที่เลือก';
            }
            $prefix = request()->post('code_prefix');
            $values['code_prefix'] = is_string($prefix) ? strtoupper(trim($prefix)) : '';
            if (! preg_match('/^[A-Z][A-Z0-9]{0,9}$/D', $values['code_prefix'])) {
                $error = 'ตัวย่อรหัสต้องมี 1–10 ตัว ใช้ตัวอักษรอังกฤษและตัวเลข โดยขึ้นต้นด้วยตัวอักษรอังกฤษ';
            }
            if ($values['line_id'] && ! preg_match('/^@[a-zA-Z0-9._-]{1,80}$/', $values['line_id'])) {
                $error = 'LINE Official ID ต้องขึ้นต้นด้วย @ และใช้ตัวอักษรอังกฤษ/ตัวเลข';
            }
            if ($values['email'] && ! filter_var($values['email'], FILTER_VALIDATE_EMAIL)) {
                $error = 'อีเมลไม่ถูกต้อง';
            }
            foreach (['license_number', 'license_holder'] as $key) {
                $raw = request()->post($key);
                $values[$key] = is_string($raw) ? trim($raw) : '';
            }
            if (! preg_match('~^\d{1,3}/\d{3,6}$~D', $values['license_number'])) {
                $error = 'เลขที่ใบอนุญาตไม่ถูกต้อง ใช้รูปแบบเช่น 11/08038';
            } elseif ($values['license_holder'] === '' || mb_strlen($values['license_holder']) > 190) {
                $error = 'กรุณากรอกชื่อผู้ได้รับใบอนุญาต ไม่เกิน 190 ตัวอักษร';
            } elseif ($values['license_number'] !== tour_license($this->settings)['number'] && branding_path($this->settings, 'license_image') && request()->post('remove_license_image') !== '1' && ($_FILES['license_image']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                $error = 'เปลี่ยนเลขที่ใบอนุญาตแล้ว กรุณาอัปโหลดรูปใบอนุญาตใหม่ หรือเลือกลบรูปเดิม';
            }
            $uploaded = [];
            foreach (array_merge(array_keys(contact_social_fields()), ['contact_map_url', 'contact_map_embed', 'contact_hours', 'company_address']) as $key) {
                $raw = request()->post($key);
                $values[$key] = is_string($raw) ? trim($raw) : '';
                $limit = $key === 'contact_map_embed' ? 10000 : (in_array($key, ['contact_hours', 'company_address'], true) ? 1000 : 2000);
                if (mb_strlen($values[$key]) > $limit || ($raw !== null && ! is_string($raw))) {
                    $error = 'ข้อมูลช่องทางติดต่อไม่ถูกต้องหรือยาวเกินกำหนด';
                }
                if ($values[$key] && (isset(contact_social_fields()[$key]) || $key === 'contact_map_url') && ! contact_https_url($values[$key])) {
                    $error = 'ลิงก์ช่องทางติดต่อและแผนที่ต้องเป็น URL ที่ขึ้นต้นด้วย https://';
                }
            }
            if ($values['contact_map_embed']) {
                $map = contact_map_embed($values['contact_map_embed']);
                if (! $map) {
                    $error = 'กรุณาใช้โค้ดฝังแผนที่จาก Google Maps (แชร์ → ฝังแผนที่)';
                } else {
                    $values['contact_map_embed'] = $map;
                }
            }
            $names = request()->post('sales_name') ?? [];
            $phones = request()->post('sales_phone') ?? [];
            $sales = [];
            if (! is_array($names) || ! is_array($phones) || count($names) > 20 || count($names) !== count($phones)) {
                $error = 'ข้อมูลทีมขายไม่ถูกต้อง (สูงสุด 20 คน)';
            } else {
                foreach ($names as $i => $name) {
                    $phone = $phones[$i] ?? '';
                    if (! is_string($name) || ! is_string($phone)) {
                        $error = 'ข้อมูลทีมขายไม่ถูกต้อง';

                        continue;
                    }
                    $name = trim($name);
                    $phone = trim($phone);
                    if ($name === '' && $phone === '') {
                        continue;
                    }
                    $sales[] = ['name' => $name, 'phone' => $phone];
                    if ($name === '' || mb_strlen($name) > 100 || ! preg_match('/^\+?[0-9][0-9 ()-]{5,29}$/D', $phone)) {
                        $error = 'กรุณากรอกชื่อและเบอร์โทรเซลส์ให้ครบและถูกต้อง';
                    }
                }
            }
            $values['contact_sales'] = json_encode($sales, JSON_UNESCAPED_UNICODE);
            if (! $error) {
                foreach (branding_slots() as $key => $label) {
                    if (request()->post('remove_'.$key) === '1') {
                        $values[$key] = '';
                    }
                    $file = $_FILES[$key] ?? null;
                    if (! $file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                        continue;
                    }
                    if (! is_string($file['name'] ?? null) || ! is_string($file['tmp_name'] ?? null) || ($file['error'] ?? -1) !== UPLOAD_ERR_OK) {
                        $error = $label.': อัปโหลดไม่สำเร็จ';
                        break;
                    }
                    $info = @getimagesize($file['tmp_name']);
                    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                    $types = ['png' => IMAGETYPE_PNG, 'jpg' => IMAGETYPE_JPEG, 'jpeg' => IMAGETYPE_JPEG, 'webp' => IMAGETYPE_WEBP];
                    if ($key === 'favicon') {
                        $types['ico'] = IMAGETYPE_ICO;
                    }
                    if (! isset($types[$ext]) || ! $info || $info[2] !== $types[$ext] || $info[0] > 8000 || $info[1] > 8000 || $file['size'] > 2 * 1024 * 1024) {
                        $error = $label.': ใช้รูป PNG, JPG, WebP'.($key === 'favicon' ? ' หรือ ICO' : '').' ไม่เกิน 2 MB และ 8000 × 8000 px';
                        break;
                    }
                    if ($key === 'favicon' && $info[0] !== $info[1]) {
                        $error = 'Favicon ต้องเป็นภาพสี่เหลี่ยมจัตุรัส';
                        break;
                    }
                    $path = 'public/uploads/'.bin2hex(random_bytes(16)).'.'.$ext;
                    if (! @move_uploaded_file($file['tmp_name'], base_path($path))) {
                        $error = $label.': ไม่สามารถบันทึกไฟล์ได้';
                        break;
                    }
                    $uploaded[] = $path;
                    $values[$key] = $path;
                }
            }
            if (! $error) {
                DB::beginTransaction();
                $this->package_levels->lock();
                $updated = false;
                $failed = false;
                try {
                    $updated = $this->trips->sync_tour_codes($values['code_prefix']);
                    if ($updated !== false) {
                        foreach ($values as $k => $v) {
                            DB::table('settings')->upsert([['setting_key' => $k, 'setting_value' => $v]], ['setting_key'], ['setting_key', 'setting_value']);
                        }
                    }
                } catch (QueryException $e) {
                    $failed = true;
                }
                $ok = $updated !== false && ! $failed;
                if ($ok) {
                    DB::commit();
                } else {
                    DB::rollBack();
                }
                if ($ok) {
                    $oldLicense = branding_path($this->settings, 'license_image');
                    if ($oldLicense && ($values['license_image'] ?? $oldLicense) !== $oldLicense) {
                        @unlink(base_path($oldLicense));
                    }
                    session()->flash('success','บันทึกข้อมูลเว็บไซต์และโลโก้แล้ว'.($updated ? ' และอัปเดตรหัสโปรแกรม '.$updated.' รายการ' : ''));
                    redirect_to(base_url().'admin/settings');
                }
                $error = $this->trips->save_error ?: 'บันทึกข้อมูลไม่สำเร็จ กรุณาลองใหม่';
            }
            foreach ($uploaded as $path) {
                if (is_file(base_path($path))) {
                    @unlink(base_path($path));
                }
            }
            foreach ($values as $key => $value) {
                if (! array_key_exists($key,branding_slots())) {
                    $this->settings[$key] = $value;
                }
            }
        }

        return $this->render('admin/settings',['title' => 'ตั้งค่าเว็บไซต์', 'error' => $error],true);
    }
}
