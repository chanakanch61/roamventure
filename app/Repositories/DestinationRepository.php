<?php

namespace App\Repositories;

use App\Support\ResolvesServices;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class DestinationRepository
{
    use ResolvesServices;

    public $error = '';

    public function image_suggestions($items)
    {
        if ($items === null) {
            return [];
        }
        if (! is_array($items) || array_values($items) !== $items || count($items) > 12) {
            throw new InvalidArgumentException('รายการภาพอ้างอิงต้องเป็นรายการไม่เกิน 12 ภาพ');
        }
        $clean = [];
        foreach ($items as $item) {
            if (! is_array($item)) {
                throw new InvalidArgumentException('รูปแบบรายการภาพอ้างอิงไม่ถูกต้อง');
            }
            foreach (['section' => 160, 'keywords' => 200, 'alt' => 200] as $key => $max) {
                $value = $item[$key] ?? '';
                if (! is_string($value) || mb_strlen($value) > $max) {
                    throw new InvalidArgumentException('ข้อมูลภาพอ้างอิง '.$key.' ไม่ถูกต้องหรือยาวเกินกำหนด');
                }
                $item[$key] = trim($value);
            }
            if ($item['section'] === '' || ! preg_match("/^[A-Za-z0-9][A-Za-z0-9 ,.'()&-]*$/D", $item['keywords'])) {
                throw new InvalidArgumentException('กรุณาระบุหัวข้อและคำค้นภาพภาษาอังกฤษ');
            }
            $slug = trim(strtolower(preg_replace('/[^A-Za-z0-9]+/', '-', $item['keywords'])), '-');
            $clean[] = ['section' => $item['section'], 'keywords' => $item['keywords'], 'alt' => $item['alt'], 'shutterstock_url' => 'https://www.shutterstock.com/search/'.$slug];
        }

        return $clean;
    }

    public function clean_content($html)
    {
        return $this->destination_html->clean_html($html, 'destinations');
    }

    public function remove_cover_file($path)
    {
        if (preg_match('~^public/uploads/destinations/[a-f0-9]+\.(jpg|jpeg|png|webp)$~D', $path) && is_file(base_path($path))) {
            unlink(base_path($path));
        }
    }

    public function kinds()
    {
        return ['zone' => 'Zone / โซน', 'country' => 'Country / ประเทศ', 'route' => 'Route / เส้นทาง'];
    }

    private function node_query()
    {
        return DB::table('destination_nodes as n')->select('n.*', 'c.slug as country_slug')->leftJoin('destination_nodes as c', function ($join) {
            $join->on('c.id', '=', 'n.parent_id')->where('c.kind', '=', 'country');
        });
    }

    public function all($kind = null)
    {
        $query = $this->node_query();
        if ($kind) {
            $query->where('n.kind', $kind);
        }

return $query->orderBy('n.sort_order')->orderBy('n.name')->get()->all();
    }

    public function children($id)
    {
        return $this->node_query()->where('n.parent_id', (int) $id)->orderBy('n.sort_order')->orderBy('n.name')->get()->all();
    }

    // Legacy fallback supports old imports; normal admin saves always populate tour_routes.
    public function links_sql()
    {
        return 'SELECT tour_id,route_id,sort_order FROM tour_routes UNION ALL SELECT t.id tour_id,t.route_id,0 sort_order FROM tours t WHERE t.route_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM tour_routes tr WHERE tr.tour_id=t.id)';
    }

    public function attach($tours)
    {
        if (! $tours) {
            return [];
        }
        $ids = implode(',', array_map('intval', array_column($tours, 'id')));
        $rows = DB::select('SELECT tr.tour_id,tr.sort_order,r.id route_id,r.name,r.slug,r.cover_path route_cover,r.cover_alt route_cover_alt,c.id country_id,c.name country_name,c.slug country_slug,c.flag_path country_flag,z.id zone_id,z.name zone_name,z.slug zone_slug FROM ('.$this->links_sql().") tr JOIN destination_nodes r ON r.id=tr.route_id JOIN destination_nodes c ON c.id=r.parent_id JOIN destination_nodes z ON z.id=c.parent_id WHERE tr.tour_id IN ($ids) ORDER BY tr.tour_id,tr.sort_order,r.id");
        $groups = [];
        foreach ($rows as $r) {
            $groups[$r['tour_id']][] = $r;
        }
        foreach ($tours as &$t) {
            $t['destinations'] = $groups[$t['id']] ?? [];
            $countries = [];
            foreach ($t['destinations'] as $r) {
                if (! isset($countries[$r['country_id']])) {
                    $countries[$r['country_id']] = ['id' => $r['country_id'], 'name' => $r['country_name'], 'slug' => $r['country_slug'], 'flag' => $r['country_flag']];
                }
            } $t['countries'] = array_values($countries);
        }

        return $tours;
    }

    public function find($id)
    {
        return $this->node_query()->where('n.id', (int) $id)->first();
    }

    public function route($id)
    {
        return DB::select("SELECT r.*,c.id country_id,c.name country_name,c.slug country_slug,z.id zone_id,z.name zone_name,z.slug zone_slug FROM destination_nodes r JOIN destination_nodes c ON c.id=r.parent_id AND c.kind='country' JOIN destination_nodes z ON z.id=c.parent_id AND z.kind='zone' WHERE r.id=? AND r.kind='route'", [(int) $id])[0] ?? null;
    }

    public function public_node($kind, $slug, $country_slug = null)
    {
        $query = $this->node_query()->where('n.kind', $kind)->where('n.slug', $slug);
        if ($country_slug !== null) {
            $query->where('c.slug', $country_slug);
        }

return $query->first();
    }

    public function listing_node($slug)
    {
        return $this->node_query()->whereIn('n.kind', ['zone', 'country'])->where('n.slug', $slug)->first();
    }

    public function listing_slug_taken($slug, $id = 0)
    {
        return DB::table('destination_nodes')->whereIn('kind', ['zone', 'country'])->where('slug', $slug)->where('id', '!=', (int) $id)->count() > 0;
    }

    public function trail($node)
    {
        $trail = [$node];
        while ($node['parent_id']) {
            $node = $this->find($node['parent_id']);
            if (! $node) {
                break;
            } array_unshift($trail, $node);
        }

return $trail;
    }

    public function save($kind, $id, $data)
    {
        $this->error = '';
        DB::beginTransaction();
        if ($id) {
            $old = DB::select('SELECT * FROM destination_nodes WHERE id=? FOR UPDATE', [$id])[0] ?? null;
            if (! $old || $old['kind'] !== $kind) {
                return $this->fail('ไม่พบรายการ');
            }
        }
        $expected = ['zone' => null, 'country' => 'zone', 'route' => 'country'][$kind] ?? null;
        if ($expected) {
            $parent = DB::select('SELECT * FROM destination_nodes WHERE id=? FOR UPDATE', [$data['parent_id'] ?? null])[0] ?? null;
            if (! $parent || $parent['kind'] !== $expected) {
                return $this->fail('กรุณาเลือกรายการแม่ให้ถูกต้อง');
            }
        } else {
            $data['parent_id'] = null;
        }
        if ($kind === 'route') {
            if (DB::table('destination_nodes')->where('kind', $kind)->where('slug', $data['slug'])->where('id', '!=', $id)->count()) {
                return $this->fail('URL นี้มีอยู่แล้วในประเภทเดียวกัน');
            }
        } else {
            if ($this->listing_slug_taken($data['slug'], $id)) {
                return $this->fail('URL นี้ถูกใช้โดย Zone หรือ Country อื่นแล้ว');
            }
            if (DB::table('tours')->where(function ($q) use ($data) {
                $q->where('code', $data['slug'])->orWhere('slug', $data['slug']);
            })->count() || DB::table('tour_code_aliases')->where('code', $data['slug'])->count()) {
                return $this->fail('URL นี้ถูกใช้โดยโปรแกรมทัวร์หรือลิงก์โปรแกรมเดิมแล้ว');
            }
        }
        $data['kind'] = $kind;
        $data['updated_at'] = date('Y-m-d H:i:s');
        // A duplicate slug raced with another editor must be a form error, not a SQL page.
        $ok = true;
        try {
            if ($id) {
                DB::table('destination_nodes')->where('id', $id)->update($data);
            } else {
                $data['created_at'] = $data['updated_at'];
                $id = DB::table('destination_nodes')->insertGetId($data);
            }
        } catch (QueryException $e) {
            $ok = false;
        }
        if (! $ok) {
            return $this->fail('บันทึกไม่สำเร็จ กรุณาตรวจสอบ URL ซ้ำหรือโหลดข้อมูลใหม่');
        }
        if ($kind === 'route') {
            try {
                DB::table('tours')->where('route_id', $id)->update(['destination' => $data['name']]);
            } catch (QueryException $e) {
                return $this->fail('บันทึกไม่สำเร็จ');
            }
        }
        DB::commit();
        if (isset($data['flag_path']) && ! empty($old['flag_path']) && $data['flag_path'] !== $old['flag_path']) {
            $this->remove_flag_file($old['flag_path']);
        }
        if (isset($data['source_pdf']) && ! empty($old['source_pdf']) && $data['source_pdf'] !== $old['source_pdf']) {
            $this->remove_source_pdf($old['source_pdf']);
        }
        if (isset($data['cover_path']) && ! empty($old['cover_path']) && $data['cover_path'] !== $old['cover_path']) {
            $this->remove_cover_file($old['cover_path']);
        }

        return $id;
    }

    public function delete($kind, $id)
    {
        DB::beginTransaction();
        $node = DB::select('SELECT * FROM destination_nodes WHERE id=? FOR UPDATE', [$id])[0] ?? null;
        if (! $node || $node['kind'] !== $kind) {
            return $this->fail('ไม่พบรายการ');
        }
        if (DB::table('destination_nodes')->where('parent_id', $id)->count() || DB::table('tours')->where('route_id', $id)->count() || DB::table('tour_routes')->where('route_id', $id)->count()) {
            return $this->fail('ลบไม่ได้: มีรายการย่อยหรือโปรแกรมทัวร์ใช้งานอยู่ กรุณาย้ายข้อมูลที่เกี่ยวข้องก่อน');
        }
        $ok = true;
        try {
            DB::table('destination_nodes')->where('id', $id)->delete();
        } catch (QueryException $e) {
            $ok = false;
        }
        if (! $ok) {
            return $this->fail('รายการนี้มีข้อมูลอ้างอิงอยู่ กรุณาโหลดใหม่');
        } DB::commit();
        $this->remove_flag_file($node['flag_path'] ?? '');
        $this->remove_source_pdf($node['source_pdf'] ?? '');
        $this->remove_cover_file($node['cover_path'] ?? '');

        return true;
    }

    public function remove_source_pdf($path)
    {
        if (preg_match('~^storage/destination-pdfs/[a-f0-9]+\.pdf$~D', $path) && is_file(base_path($path))) {
            unlink(base_path($path));
        }
    }

    public function remove_flag_file($path)
    {
        if (preg_match('~^public/uploads/flags/[a-f0-9]+\.(jpg|jpeg|png|webp)$~D', $path) && is_file(base_path($path))) {
            unlink(base_path($path));
        }
    }

    private function fail($message)
    {
        $this->error = $message;
        DB::rollBack();

        return false;
    }
}
