<?php

namespace App\Repositories;

use App\Support\ResolvesServices;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/** Charter group tours: customer requests from /grouptour and the past groups shown as references. */
class GroupTourRepository
{
    use ResolvesServices;

    public const PER_PAGE = 20;

    public static function group_types(): array
    {
        return [
            'corporate' => 'บริษัท / องค์กร (สัมมนา, Incentive)',
            'family' => 'ครอบครัว / เครือญาติ',
            'friends' => 'กลุ่มเพื่อน',
            'school' => 'โรงเรียน / ศึกษาดูงาน',
            'association' => 'สมาคม / ชมรม / หน่วยงาน',
            'pilgrimage' => 'ทริปสายบุญ / ไหว้พระ',
            'other' => 'อื่น ๆ',
        ];
    }

    /** Short labels for cards and badges. */
    public static function group_type_short(string $key): string
    {
        return ['corporate' => 'องค์กร', 'family' => 'ครอบครัว', 'friends' => 'กลุ่มเพื่อน', 'school' => 'ศึกษาดูงาน', 'association' => 'สมาคม / หน่วยงาน', 'pilgrimage' => 'สายบุญ', 'other' => 'กรุ๊ปเหมา'][$key] ?? 'กรุ๊ปเหมา';
    }

    public static function budgets(): array
    {
        return [
            'unsure' => 'ยังไม่แน่ใจ ให้ทีมงานเสนอ',
            'under-20k' => 'ต่ำกว่า 20,000 บาท / คน',
            '20k-40k' => '20,000–40,000 บาท / คน',
            '40k-70k' => '40,000–70,000 บาท / คน',
            '70k-100k' => '70,000–100,000 บาท / คน',
            'over-100k' => 'มากกว่า 100,000 บาท / คน',
        ];
    }

    public static function hotel_levels(): array
    {
        return ['unsure' => 'ให้ทีมงานแนะนำ', '3star' => '3 ดาว', '4star' => '4 ดาว', '5star' => '5 ดาว ขึ้นไป'];
    }

    public static function services(): array
    {
        return [
            'flight' => 'ตั๋วเครื่องบิน',
            'hotel' => 'ที่พัก',
            'transport' => 'รถรับส่งตลอดทริป',
            'guide' => 'ไกด์ / หัวหน้าทัวร์',
            'meals' => 'อาหารตามโปรแกรม',
            'meeting' => 'ห้องประชุม / สัมมนา',
            'activity' => 'กิจกรรม / Team building',
            'insurance' => 'ประกันการเดินทาง',
            'visa' => 'ช่วยเตรียมเอกสารวีซ่า',
            'photographer' => 'ช่างภาพประจำกรุ๊ป',
        ];
    }

    public static function statuses(): array
    {
        return ['new' => 'คำขอใหม่', 'contacted' => 'ติดต่อแล้ว', 'quoted' => 'ส่งใบเสนอราคาแล้ว', 'confirmed' => 'ยืนยันเดินทาง', 'closed' => 'ปิดงาน / ไม่สำเร็จ'];
    }

    public static function service_labels(string $csv): array
    {
        $all = self::services();

        return array_values(array_filter(array_map(fn ($key) => $all[$key] ?? null, explode(',', $csv))));
    }

    // ---- Requests ---------------------------------------------------------

    /** Saves a request and returns the stored row, or null when the database refused it. */
    public function create_request(array $fields, string $prefix): ?array
    {
        $now = date('Y-m-d H:i:s');
        for ($attempt = 0; $attempt < 3; $attempt++) {
            $fields['reference'] = $prefix.'-G'.date('ymd').'-'.strtoupper(bin2hex(random_bytes(3)));
            try {
                $id = DB::table('group_requests')->insertGetId($fields + ['status' => 'new', 'admin_note' => '', 'created_at' => $now, 'updated_at' => $now]);

                return $this->find_request($id);
            } catch (QueryException $e) {
                // A clashing reference is retried with a fresh one; anything else is a real failure.
                if (DB::table('group_requests')->where('reference', $fields['reference'])->exists()) {
                    continue;
                }
                report($e);

                return null;
            }
        }

        return null;
    }

    public function find_request($id): ?array
    {
        return DB::table('group_requests')->where('id', (int) $id)->first();
    }

    public function requests(array $filters): array
    {
        $query = DB::table('group_requests');
        if (isset(self::statuses()[$filters['status'] ?? ''])) {
            $query->where('status', $filters['status']);
        }
        if (($filters['q'] ?? '') !== '') {
            $like = '%'.addcslashes($filters['q'], '%_\\').'%';
            $query->where(function ($q) use ($like) {
                foreach (['reference', 'contact_name', 'organization', 'phone', 'email', 'line_id', 'destination'] as $column) {
                    $q->orWhere($column, 'like', $like);
                }
            });
        }
        $total = $query->count();
        $pages = max(1, (int) ceil($total / self::PER_PAGE));
        $page = min($pages, max(1, (int) ($filters['page'] ?? 1)));
        $rows = $query->orderBy('id', 'DESC')->limit(self::PER_PAGE)->offset(($page - 1) * self::PER_PAGE)->get()->all();

        return ['requests' => $rows, 'total' => $total, 'page' => $page, 'pages' => $pages];
    }

    public function status_counts(): array
    {
        $counts = array_fill_keys(array_keys(self::statuses()), 0);
        foreach (DB::table('group_requests')->selectRaw('status, COUNT(*) AS total')->groupBy('status')->get()->all() as $row) {
            $counts[$row['status']] = (int) $row['total'];
        }

        return $counts;
    }

    public function update_request($id, string $status, string $note): void
    {
        DB::table('group_requests')->where('id', (int) $id)->update(['status' => $status, 'admin_note' => $note, 'updated_at' => date('Y-m-d H:i:s')]);
    }

    /** Travel dates confirmed by staff (date_from, date_to, days, date_note). */
    public function update_request_trip($id, array $trip): void
    {
        $allowed = array_intersect_key($trip, array_flip(['date_from', 'date_to', 'days', 'date_note']));
        if ($allowed) {
            DB::table('group_requests')->where('id', (int) $id)->update($allowed + ['updated_at' => date('Y-m-d H:i:s')]);
        }
    }

    // ---- Past groups ------------------------------------------------------

    public function trips(bool $admin = false, ?int $limit = null): array
    {
        $query = DB::table('group_trips');
        if (! $admin) {
            $query->where('status', 'published');
        }
        $query->orderBy('sort_order')->orderByRaw('travel_date IS NULL')->orderBy('travel_date', 'DESC')->orderBy('id', 'DESC');
        if ($limit) {
            $query->limit($limit);
        }
        $trips = $query->get()->all();
        foreach ($trips as &$trip) {
            $trip['image'] = $this->trip_image($trip);
        }
        unset($trip);

        return $trips;
    }

    public function find_trip($id): ?array
    {
        return DB::table('group_trips')->where('id', (int) $id)->first();
    }

    public function find_trip_slug(string $slug): ?array
    {
        $trip = DB::table('group_trips')->where('slug', $slug)->where('status', 'published')->first();
        if ($trip) {
            $trip['image'] = $this->trip_image($trip);
        }

        return $trip;
    }

    public function slug_taken(string $slug, int $id): bool
    {
        return DB::table('group_trips')->where('slug', $slug)->where('id', '!=', $id)->exists();
    }

    /** Totals across published past groups, for the figures at the top of the page. */
    public function stats(): array
    {
        $published = DB::table('group_trips')->where('status', 'published');

        return [
            'groups' => (clone $published)->count(),
            'travelers' => (int) (clone $published)->sum('travelers'),
            'destinations' => (clone $published)->distinct()->count('destination'),
        ];
    }

    /** The trip's own cover, else the cover of its linked Gallery album, else a stock photo. */
    public function trip_image(array $trip): string
    {
        if ($trip['cover'] !== '') {
            return $trip['cover'];
        }
        if ($trip['album_id']) {
            $album = $this->gallery->find($trip['album_id']);
            if ($album && $album['status'] === 'published') {
                return $this->gallery->previews($trip['album_id'], 1)[0] ?? 'public/images/story.jpg';
            }
        }

        return 'public/images/story.jpg';
    }

    /** Photos of the linked album, only while that album is published. */
    public function album_photos(array $trip): array
    {
        if (! $trip['album_id']) {
            return [];
        }
        $album = $this->gallery->find($trip['album_id']);

        return $album && $album['status'] === 'published' ? $this->gallery->photos($trip['album_id']) : [];
    }

    public function albums(): array
    {
        return DB::table('gallery_albums')->select('id', 'title', 'status')->orderBy('sort_order')->orderBy('id', 'DESC')->get()->all();
    }

    public function remove_cover(string $path): void
    {
        if (preg_match('~^public/uploads/group-trips/[a-f0-9]+\.(jpg|jpeg|png|webp)$~D', $path) && is_file(base_path($path))) {
            unlink(base_path($path));
        }
    }
}
