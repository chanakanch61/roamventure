<?php

namespace App\Repositories;

use App\Support\ResolvesServices;
use Illuminate\Support\Facades\DB;

class HomeDestinationRepository
{
    use ResolvesServices;

    const KEY = 'home_destinations';

    public function config($settings)
    {
        $defaults = ['enabled' => 1, 'mode' => 'auto', 'eyebrow' => '02 / FIND YOUR SOMEWHERE', 'title' => 'จุดหมายต่อไป… ไปไหนดี', 'description' => 'เริ่มต้นจากสถานที่ที่คุณอยากไป แล้วชวนคนรู้ใจออกเดินทาง', 'cards' => []];
        $saved = json_decode($settings[self::KEY] ?? '', true);

        return is_array($saved) ? array_merge($defaults, $saved) : $defaults;
    }

    public function choices()
    {
        return DB::select("SELECT n.id,n.kind,n.name,n.slug,p.name parent_name,p.slug country_slug,COUNT(DISTINCT t.id) total,COALESCE(NULLIF(n.cover_path,''),MIN(t.cover)) cover FROM destination_nodes n LEFT JOIN destination_nodes p ON p.id=n.parent_id LEFT JOIN (".$this->places->links_sql().") tr ON tr.route_id=n.id LEFT JOIN tours t ON t.id=tr.tour_id AND t.status='published' WHERE n.kind='route' GROUP BY n.id,n.kind,n.name,n.slug,n.cover_path,p.name,p.slug ORDER BY n.name");
    }

    public function cards($config)
    {
        if (empty($config['enabled'])) {
            return [];
        }
        $choices = array_column($this->choices(), null, 'id');
        $result = [];
        if ($config['mode'] !== 'auto') {
            foreach (array_slice(is_array($config['cards']) ? $config['cards'] : [], 0, 4) as $card) {
                $node = $choices[$card['node_id'] ?? null] ?? null;
                if (! $node || empty($card['enabled']) || ! $node['total']) {
                    continue;
                }
                if (! empty($card['image'])) {
                    $node['cover'] = $card['image'];
                }
                $result[] = $node;
            }
        }
        // Cards saved before the section became route-only may point at countries, so an empty manual pick falls through.
        if ($result) {
            return $result;
        }
        $routes = array_values(array_filter($choices, function ($n) {
            return $n['total'] > 0;
        }));
        // Routes with the most programs lead; usort is stable, so ties keep the name order.
        usort($routes, function ($a, $b) {
            return $b['total'] <=> $a['total'];
        });

        return $routes;
    }
}
