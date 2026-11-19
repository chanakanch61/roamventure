<?php

namespace App\Http\Controllers;

use App\Repositories\GroupTourRepository as Groups;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/** Back office for charter groups: the request inbox and the past groups shown on /grouptour. */
class GroupTourAdminController extends Controller
{
    protected function boot(): void
    {
        $this->require_admin();
    }

    private function value(string $key): string
    {
        $v = request()->post($key);

        return is_string($v) ? trim($v) : '';
    }

    public function requests()
    {
        $filters = [];
        foreach (['q', 'status', 'page'] as $k) {
            $v = request()->query($k);
            $filters[$k] = is_string($v) ? mb_substr(trim($v), 0, 180) : '';
        }

        return $this->render('admin/group_requests', $this->group_tours->requests($filters) + [
            'title' => 'คำขอจัดกรุ๊ปเหมา',
            'filters' => $filters,
            'counts' => $this->group_tours->status_counts(),
        ], true);
    }

    public function request($id)
    {
        $row = $this->group_tours->find_request($id);
        if (! $row) {
            show_404();
        }
        if (request_method() === 'post') {
            $status = $this->value('status');
            $note = $this->value('admin_note');
            [$trip, $error] = $this->trip_dates();
            if (! isset(Groups::statuses()[$status]) || mb_strlen($note) > 5000) {
                $error = 'สถานะหรือบันทึกภายในไม่ถูกต้อง';
            }
            if ($error) {
                session()->flash('error', $error);
            } else {
                $this->group_tours->update_request($row['id'], $status, $note);
                $this->group_tours->update_request_trip($row['id'], $trip);
                session()->flash('success', 'บันทึกคำขอเรียบร้อยแล้ว');
            }
            redirect_to(base_url().'admin/group-requests/'.(int) $row['id']);
        }
        $linked = DB::table('group_trips')->select('id', 'title')->where('request_id', $row['id'])->first();

        return $this->render('admin/group_request', ['title' => 'คำขอ '.$row['reference'], 'request' => $row, 'linked_trip' => $linked, 'quotes' => $this->group_quotes->for_request((int) $row['id']), 'group_quote_page' => true], true);
    }

    /**
     * Travel dates staff confirm with the customer. A request may arrive with only a rough period;
     * once the dates are agreed they are entered here. Past dates are allowed, for records kept after the trip.
     */
    private function trip_dates(): array
    {
        $from = $this->value('date_from');
        $to = $this->value('date_to');
        $days = $this->value('days');
        $dateNote = $this->value('date_note');
        $valid = fn ($d) => $d === '' || (preg_match('/^\d{4}-\d{2}-\d{2}$/D', $d) && strtotime($d));
        if (! $valid($from) || ! $valid($to)) {
            return [[], 'วันเดินทางไม่ถูกต้อง'];
        }
        if ($to !== '' && $from === '') {
            return [[], 'กรุณาระบุวันเริ่มเดินทางคู่กับวันกลับ'];
        }
        if ($to !== '' && $to < $from) {
            return [[], 'วันกลับต้องไม่ก่อนวันเริ่มเดินทาง'];
        }
        $dayCount = $days === '' ? null : filter_var($days, FILTER_VALIDATE_INT);
        if ($days !== '' && ($dayCount === false || $dayCount < 1 || $dayCount > 60)) {
            return [[], 'จำนวนวันต้องอยู่ระหว่าง 1–60'];
        }
        // Both dates known and days left empty: count them, including the first and last day.
        if ($dayCount === null && $from !== '' && $to !== '') {
            $dayCount = min(60, (int) round((strtotime($to) - strtotime($from)) / 86400) + 1);
        }
        if (mb_strlen($dateNote) > 120) {
            return [[], 'ช่วงเวลาคร่าว ๆ ยาวเกิน 120 ตัวอักษร'];
        }

        return [['date_from' => $from ?: null, 'date_to' => $to ?: null, 'days' => $dayCount, 'date_note' => $dateNote], ''];
    }

    public function trips()
    {
        return $this->render('admin/group_trips', ['title' => 'ผลงานกรุ๊ปเหมา', 'trips' => $this->group_tours->trips(true)], true);
    }

    public function trip($id = 0)
    {
        $id = (int) $id;
        $blank = ['id' => 0, 'slug' => '', 'title' => '', 'group_type' => 'corporate', 'organization' => '', 'destination' => '', 'travel_date' => null, 'days' => null, 'travelers' => null, 'summary' => '', 'story' => '', 'highlights' => '', 'testimonial' => '', 'testimonial_by' => '', 'cover' => '', 'album_id' => null, 'seo_title' => '', 'seo_description' => '', 'status' => 'draft', 'sort_order' => 0, 'request_id' => null];
        $trip = $id ? $this->group_tours->find_trip($id) : $blank;
        if (! $trip) {
            show_404();
        }
        // "สร้างผลงานจากคำขอนี้" starts a draft from a confirmed request.
        $from = (int) request()->query('from_request');
        if (! $id && $from && request_method() !== 'post' && ($req = $this->group_tours->find_request($from))) {
            $trip = array_merge($trip, ['group_type' => $req['group_type'], 'organization' => $req['organization'], 'destination' => $req['destination'], 'travel_date' => $req['date_from'], 'days' => $req['days'], 'travelers' => (int) $req['adults'] + (int) $req['children'], 'request_id' => $req['id']]);
        }
        $errors = [];
        if (request_method() === 'post') {
            $original = $trip;
            foreach (['title' => 200, 'slug' => 180, 'organization' => 180, 'destination' => 200, 'summary' => 400, 'story' => 20000, 'highlights' => 5000, 'testimonial' => 3000, 'testimonial_by' => 180, 'seo_title' => 200, 'seo_description' => 300] as $key => $max) {
                $trip[$key] = $this->value($key);
                if (mb_strlen($trip[$key]) > $max) {
                    $errors[] = 'ข้อความยาวเกินกำหนด: '.$key;
                }
            }
            if ($trip['title'] === '') {
                $errors[] = 'กรุณากรอกชื่อผลงาน';
            }
            if ($trip['destination'] === '') {
                $errors[] = 'กรุณากรอกจุดหมาย';
            }
            $trip['group_type'] = $this->value('group_type');
            if (! isset(Groups::group_types()[$trip['group_type']])) {
                $errors[] = 'ประเภทกรุ๊ปไม่ถูกต้อง';
            }
            if ($trip['slug'] === '') {
                $trip['slug'] = trim(mb_strtolower((string) preg_replace('/[^\p{L}\p{M}\p{N}]+/u', '-', $trip['title'])), '-');
            }
            if (! preg_match('/^[\p{L}\p{M}\p{N}]+(?:-[\p{L}\p{M}\p{N}]+)*$/uD', $trip['slug']) || mb_strlen($trip['slug']) > 180) {
                $errors[] = 'URL ใช้ได้เฉพาะตัวอักษร ตัวเลข และขีด (-)';
            } elseif (in_array($trip['slug'], ['request', 'success'], true)) {
                $errors[] = 'URL นี้สงวนไว้ใช้กับระบบ กรุณาเปลี่ยน';
            } elseif ($this->group_tours->slug_taken($trip['slug'], $id)) {
                $errors[] = 'URL นี้ถูกใช้แล้ว กรุณาเปลี่ยน';
            }
            $date = $this->value('travel_date');
            $trip['travel_date'] = $date !== '' ? $date : null;
            if ($date !== '' && (! preg_match('/^\d{4}-\d{2}-\d{2}$/D', $date) || ! strtotime($date))) {
                $errors[] = 'วันที่เดินทางไม่ถูกต้อง';
            }
            foreach (['days' => [1, 60, 'จำนวนวัน'], 'travelers' => [1, 9999, 'จำนวนผู้เดินทาง']] as $key => [$min, $max, $label]) {
                $raw = $this->value($key);
                $num = filter_var($raw, FILTER_VALIDATE_INT);
                $trip[$key] = $raw === '' ? null : $num;
                if ($raw !== '' && ($num === false || $num < $min || $num > $max)) {
                    $errors[] = $label.'ต้องอยู่ระหว่าง '.$min.'–'.$max;
                }
            }
            $album = $this->value('album_id');
            $trip['album_id'] = $album === '' ? null : (int) $album;
            if ($trip['album_id'] && ! DB::table('gallery_albums')->where('id', $trip['album_id'])->exists()) {
                $errors[] = 'ไม่พบอัลบั้ม Gallery ที่เลือก';
            }
            $trip['status'] = $this->value('status');
            if (! in_array($trip['status'], ['draft', 'published'], true)) {
                $errors[] = 'สถานะไม่ถูกต้อง';
            }
            $sort = filter_var($this->value('sort_order'), FILTER_VALIDATE_INT);
            if ($sort === false || $sort < 0 || $sort > 9999) {
                $errors[] = 'ลำดับต้องอยู่ระหว่าง 0–9999';
            } else {
                $trip['sort_order'] = $sort;
            }
            $requestId = (int) $this->value('request_id');
            $trip['request_id'] = $requestId && $this->group_tours->find_request($requestId) ? $requestId : null;

            $oldCover = $original['cover'];
            $newCover = '';
            if ($this->value('remove_cover') === '1') {
                $trip['cover'] = '';
            }
            $file = $_FILES['cover_file'] ?? null;
            if (! $errors && $file && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                $dir = base_path('public/uploads/group-trips/');
                if (! is_dir($dir)) {
                    @mkdir($dir, 0755, true);
                }
                $this->upload->initialize(['upload_path' => $dir, 'allowed_types' => 'jpg|jpeg|png|webp', 'max_size' => 5120, 'max_width' => 6000, 'max_height' => 6000, 'encrypt_name' => true, 'file_ext_tolower' => true]);
                if (! $this->upload->do_upload('cover_file')) {
                    $errors[] = 'อัปโหลดรูปปกไม่สำเร็จ: '.strip_tags($this->upload->display_errors());
                } else {
                    $f = $this->upload->data();
                    $newCover = 'public/uploads/group-trips/'.$f['file_name'];
                    if (! @getimagesize($f['full_path'])) {
                        $errors[] = 'ไฟล์ปกต้องเป็นรูปภาพเท่านั้น';
                    } else {
                        $trip['cover'] = $newCover;
                    }
                }
            }
            if ($trip['status'] === 'published') {
                if ($trip['summary'] === '') {
                    $errors[] = 'กรอกสรุปผลงานก่อนเผยแพร่ (ใช้เป็นคำอธิบายบนการ์ดและผลค้นหา)';
                }
                if ($trip['cover'] === '' && ! $trip['album_id']) {
                    $errors[] = 'เพิ่มรูปปก หรือเลือกอัลบั้ม Gallery ก่อนเผยแพร่';
                }
            }
            if (! $errors) {
                $data = $trip;
                unset($data['id'], $data['image']);
                $data['updated_at'] = date('Y-m-d H:i:s');
                try {
                    if ($id) {
                        DB::table('group_trips')->where('id', $id)->update($data);
                    } else {
                        $data['created_at'] = $data['updated_at'];
                        $id = (int) DB::table('group_trips')->insertGetId($data);
                    }
                } catch (QueryException $e) {
                    report($e);
                    $errors[] = 'บันทึกไม่สำเร็จ กรุณาลองใหม่';
                }
            }
            if (! $errors) {
                if ($oldCover !== '' && $oldCover !== $trip['cover']) {
                    $this->group_tours->remove_cover($oldCover);
                }
                session()->flash('success', 'บันทึกผลงานเรียบร้อยแล้ว');
                redirect_to(base_url().'admin/group-trips/'.$id);
            }
            if ($newCover !== '') {
                $this->group_tours->remove_cover($newCover);
            }
            $trip['cover'] = $oldCover;
        }

        return $this->render('admin/group_trip_form', [
            'title' => $trip['id'] ? 'แก้ไขผลงานกรุ๊ป' : 'เพิ่มผลงานกรุ๊ป',
            'trip' => $trip,
            'errors' => $errors,
            'albums' => $this->group_tours->albums(),
        ], true);
    }

    public function delete_trip($id)
    {
        $this->post_only();
        $trip = $this->group_tours->find_trip($id);
        if (! $trip) {
            show_404();
        }
        DB::table('group_trips')->where('id', (int) $trip['id'])->delete();
        $this->group_tours->remove_cover($trip['cover']);
        session()->flash('success', 'ลบผลงานเรียบร้อยแล้ว');
        redirect_to(base_url().'admin/group-trips');
    }
}
