<?php

namespace App\Repositories;

use App\Support\ResolvesServices;
use Illuminate\Support\Facades\DB;

class TourSearchRepository
{
    use ResolvesServices;

    public function catalog($scope = null)
    {
        // Share one lightweight index across every search form on the page.
        // Keep the full catalog so a new keyword can discover another destination.
        $tours = DB::select("SELECT t.id,t.title,t.code,t.slug,t.program_type,t.flight_included,t.cover,t.days,t.featured,t.sort_order,COALESCE(b.total,0) popularity FROM tours t LEFT JOIN (SELECT tour_id,COUNT(*) total FROM bookings WHERE status='confirmed' GROUP BY tour_id) b ON b.tour_id=t.id WHERE t.status='published'");
        $tours = array_column($tours, null, 'id');
        $nodes = DB::select('SELECT n.id,n.kind,n.parent_id,n.name,n.slug,p.slug country_slug FROM destination_nodes n LEFT JOIN destination_nodes p ON p.id=n.parent_id');
        $nodes = array_column($nodes, null, 'id');
        $links = DB::select('SELECT tr.tour_id,tr.route_id FROM ('.$this->places->links_sql().") tr JOIN tours t ON t.id=tr.tour_id WHERE t.status='published'");
        $places = [];
        $stats = [];
        $search = [];
        foreach ($links as $link) {
            $id = $link['tour_id'];
            $node = $nodes[$link['route_id']] ?? null;
            if (! $node || ! isset($tours[$id])) {
                continue;
            }
            $visited = [];
            while ($node && ! isset($visited[$node['id']])) {
                $visited[$node['id']] = true;
                $places[$id][$node['id']] = $node;
                $stats[$node['id']][$id] = true;
                $search[$id][] = $node['name'].' '.$node['slug'];
                $node = $nodes[$node['parent_id']] ?? null;
            }
        }
        $items = [];
        $scopedTours = [];
        foreach ($tours as $id => $tour) {
            $inScope = ! $scope || isset($places[$id][$scope['id']]);
            if ($inScope) {
                $scopedTours[$id] = true;
            }
            $countries = [];
            foreach ($places[$id] ?? [] as $place) {
                if ($place['kind'] === 'country') {
                    $countries[] = $place['name'];
                }
            }
            $items[] = ['id' => 'program-'.$id, 'group' => $tour['program_type'] === 'private' ? 'private' : 'tour', 'key' => $tour['code'], 'title' => $tour['title'], 'subtitle' => $tour['code'].' · '.implode(' / ', $countries).' · '.(int) $tour['days'].' วัน · '.program_channels()[tour_channel($tour)]['badge'], 'url' => base_url().'tours/'.rawurlencode($tour['code']), 'image' => tour_image($tour['cover']), 'search' => $tour['title'].' '.$tour['code'].' '.$tour['slug'].' '.implode(' ', $search[$id] ?? []), 'in_scope' => $inScope, 'popularity' => (int) $tour['popularity'], 'featured' => (int) $tour['featured'], 'order' => (int) $tour['sort_order']];
        }
        foreach ($stats as $id => $members) {
            $node = $nodes[$id];
            $popularity = 0;
            foreach ($members as $tourId => $unused) {
                $popularity += (int) $tours[$tourId]['popularity'];
            }
            $parent = $nodes[$node['parent_id']] ?? null;
            $keywords = $node['name'].' '.$node['slug'];
            $ancestor = $parent;
            $visited = [$id => true];
            while ($ancestor && ! isset($visited[$ancestor['id']])) {
                $visited[$ancestor['id']] = true;
                $keywords .= ' '.$ancestor['name'].' '.$ancestor['slug'];
                $ancestor = $nodes[$ancestor['parent_id']] ?? null;
            }
            $items[] = ['id' => 'destination-'.$id, 'group' => 'destination', 'kind' => $node['kind'], 'key' => $node['slug'], 'title' => $node['name'], 'subtitle' => ($node['kind'] === 'route' ? ($parent['name'] ?? 'เส้นทาง') : ($node['kind'] === 'country' ? 'ประเทศ' : 'โซน')).' · '.count($members).' โปรแกรม', 'url' => destination_url($node), 'search' => $keywords, 'in_scope' => (bool) array_intersect_key($members, $scopedTours), 'popularity' => $popularity, 'featured' => 0, 'order' => 0, 'total' => count($members)];
        }
        usort($items, function ($a, $b) {
            return ($b['popularity'] <=> $a['popularity']) ?: ($b['featured'] <=> $a['featured']) ?: (($b['total'] ?? 0) <=> ($a['total'] ?? 0)) ?: ($a['order'] <=> $b['order']) ?: strcmp($a['id'], $b['id']);
        });

        return $items;
    }
}
