<?php

namespace App\Repositories;

use App\Support\DepartureDiscount;
use App\Support\PrivatePackage;
use App\Support\ResolvesServices;
use App\Support\VehicleOptions;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class TripRepository
{
    use ResolvesServices;

    private const UPLOAD_COLUMNS = [
        'tours' => ['cover', 'source_pdf', 'vehicle_options'],
        'vehicle_templates' => ['vehicle_rows'],
        'hotel_library' => ['images_json'],
        'tour_images' => ['path'],
        'tour_days' => ['image_path', 'images_json', 'hotels_json'],
        'blog_posts' => ['cover', 'content'],
        'destination_nodes' => ['flag_path', 'cover_path', 'onpage_content'],
        'gallery_photos' => ['path'],
        'settings' => ['setting_value'],
        'invoices' => ['snapshot'],
        'payments' => ['account_snapshot', 'receipt_snapshot'],
    ];

    public $save_error = '';

    private function seat_sql()
    {
        return "SELECT d.*, CASE WHEN d.private_rates IS NOT NULL THEN 0 ELSE COALESCE(b.used,0) END booked_seats, CASE WHEN d.private_rates IS NOT NULL THEN d.capacity ELSE GREATEST(0,d.capacity-COALESCE(b.used,0)) END seats_available FROM departures d LEFT JOIN (SELECT departure_id,SUM(traveler_count) used FROM bookings WHERE status='confirmed' GROUP BY departure_id) b ON b.departure_id=d.id";
    }

    public function quote($dep)
    {
        // An anytime period's dates move every day, so its quote is pinned to its horizon instead.
        $anytime = PrivatePackage::anytime($dep);

        return hash_hmac('sha256', implode('|', [$dep['id'], $anytime ? 'anytime' : $dep['start_date'], $anytime ? (int) $dep['advance_months'] : $dep['end_date'],number_format((float) $dep['price_per_person'], 2, '.', ''), $dep['single_supplement'] === null ? 'unset' : number_format((float) $dep['single_supplement'], 2, '.', ''), (int) $dep['room_available'], $dep['private_rates'] ?? '', $dep['lead_days'] ?? 0, $dep['blackout_dates'] ?? '', $dep['days'] ?? 0, $dep['deposit_per_person'] ?? 0, $dep['deposit_enabled'] ?? 0, $dep['deposit_cutoff_days'] ?? 30, $dep['balance_due_days'] ?? 14]), config('app.key'));
    }

    public function departure($id, $public = false)
    {
        $sql = "SELECT s.*,t.title,t.slug,t.code,t.status tour_status,t.program_type,t.flight_included,t.days,t.deposit_per_person,(t.nights>0 AND t.hotel_status<>'none') room_available FROM (".$this->seat_sql().') s JOIN tours t ON t.id=s.tour_id WHERE s.id=?';
        if ($public) {
            $sql .= " AND s.active=1 AND ((t.program_type='fixed' AND s.start_date>=?) OR (t.program_type='private' AND s.end_date>=?)) AND t.status='published' AND s.price_per_person>0";
        }
        $dep = DB::select($sql, $public ? [(int) $id, date('Y-m-d'), date('Y-m-d')] : [(int) $id])[0] ?? null;
        if ($dep) {
            $dep = $this->package_levels->hydrate($dep);
            // Public reads (and every booking) use selling prices; admin reads keep the regular ones.
            if ($public) {
                $dep = DepartureDiscount::apply($dep);
            }
            if (PrivatePackage::anytime($dep)) {
                $dated = DB::select('SELECT start_date,end_date FROM departures WHERE tour_id=? AND id<>? AND active=1 AND anytime=0 AND private_rates IS NOT NULL', [$dep['tour_id'], $dep['id']]);
                $dep = PrivatePackage::resolve($dep, array_map(fn ($d) => [$d['start_date'], $d['end_date']], $dated));
            }
            $dep['quote'] = $this->quote($dep);
        }

        return $dep;
    }

    // Gives a tour's anytime period today's dates, leaving the dates its active dated periods cover to them.
    private function resolve_periods($deps)
    {
        $covered = [];
        foreach ($deps as $dep) {
            if (! empty($dep['private_rates']) && ! PrivatePackage::anytime($dep) && $dep['active']) {
                $covered[] = [$dep['start_date'], $dep['end_date']];
            }
        }
        foreach ($deps as &$dep) {
            $dep = PrivatePackage::resolve($dep, $covered);
        }
        unset($dep);

        return $deps;
    }

    public function settings()
    {
        return array_column(DB::table('settings')->get()->all(), 'setting_value', 'setting_key');
    }

    // Caller owns the transaction and locks package levels before changing tour identities.
    public function sync_tour_codes($prefix)
    {
        $this->save_error = '';
        if (! is_string($prefix) || ! preg_match('/^[A-Z][A-Z0-9]{0,9}$/D', $prefix)) {
            $this->save_error = 'ตัวย่อรหัสไม่ถูกต้อง';

            return false;
        }
        $tours = DB::select('SELECT id,code,slug FROM tours ORDER BY id FOR UPDATE');
        $changes = [];
        $targets = [];
        foreach ($tours as $tour) {
            $parts = explode('-', $tour['code'], 2);
            $code = $prefix.'-'.($parts[1] ?? $tour['id']);
            if (strlen($code) > 30 || isset($targets[strtolower($code)])) {
                $this->save_error = 'เปลี่ยนตัวย่อไม่ได้ เนื่องจากรหัสโปรแกรมซ้ำหรือยาวเกินกำหนด';

                return false;
            }
            $targets[strtolower($code)] = true;
            if ($code === $tour['code']) {
                continue;
            }
            $conflict = DB::table('tours')->where('id', '!=', $tour['id'])->where(function ($q) use ($code) {
                $q->where('code', $code)->orWhere('slug', $code);
            })->count();
            $aliasConflict = DB::table('tour_code_aliases')->where('tour_id', '!=', $tour['id'])->whereIn('code', [$code, $tour['code']])->count();
            if ($conflict || $aliasConflict || $this->places->listing_slug_taken($code)) {
                $this->save_error = 'เปลี่ยนตัวย่อไม่ได้ เนื่องจากรหัสหรือลิงก์เดิมซ้ำกับโปรแกรมอื่นหรือจุดหมาย';

                return false;
            }
            $changes[] = ['tour' => $tour, 'code' => $code];
        }
        foreach ($changes as $change) {
            $tour = $change['tour'];
            $exists = DB::table('tour_code_aliases')->where('code', $tour['code'])->count();
            if (! $exists) {
                try {
                    DB::table('tour_code_aliases')->insert(['code' => $tour['code'], 'tour_id' => $tour['id']]);
                } catch (QueryException $e) {
                    $this->save_error = 'บันทึกลิงก์โปรแกรมเดิมไม่สำเร็จ';

                    return false;
                }
            }
            try {
                DB::table('tours')->where('id', $tour['id'])->update(['code' => $change['code']]);
            } catch (QueryException $e) {
                $this->save_error = 'อัปเดตรหัสโปรแกรมไม่สำเร็จ';

                return false;
            }
        }

        return count($changes);
    }

    public function normalize_travel_dates($filters)
    {
        $valid = function ($value) {
            return is_string($value) && preg_match('/^([1-9][0-9]{3})-([0-9]{2})-([0-9]{2})$/D', $value, $parts) && checkdate((int) $parts[2], (int) $parts[3], (int) $parts[1]);
        };
        $from = $filters['date_from'] ?? '';
        $to = $filters['date_to'] ?? '';
        if ($from === '' && $to === '' && is_string($filters['month'] ?? null) && preg_match('/^[1-9][0-9]{3}-(0[1-9]|1[0-2])$/D', $filters['month'])) {
            $from = $filters['month'].'-01';
            $to = date('Y-m-t', strtotime($from));
        }
        if ($valid($from) && $valid($to)) {
            $filters['date_from'] = min($from, $to);
            $filters['date_to'] = max($from, $to);
        } else {
            $filters['date_from'] = '';
            $filters['date_to'] = '';
        }
        unset($filters['month']);

        return $filters;
    }

    public function listing($filters = [], $limit = null)
    {
        $filters = $this->normalize_travel_dates($filters);
        $pdo = DB::getPdo();
        $today = $pdo->quote(date('Y-m-d'));
        $query = DB::table('tours as t')->selectRaw('t.*,'.DepartureDiscount::price_sql('r', $today).' min_price, r.price_per_person min_regular_price, r.start_date next_date, r.capacity, r.seats_available');
        $membership = 'SELECT 1 FROM ('.$this->places->links_sql().') tr JOIN destination_nodes dr ON dr.id=tr.route_id JOIN destination_nodes dc ON dc.id=dr.parent_id JOIN destination_nodes dz ON dz.id=dc.parent_id WHERE tr.tour_id=t.id';
        foreach (['route_id' => 'dr.id', 'country_id' => 'dc.id', 'zone_id' => 'dz.id'] as $key => $column) {
            if (! empty($filters[$key])) {
                $query->whereRaw('EXISTS ('.$membership.' AND '.$column.'='.(int) $filters[$key].')');
            }
        }
        $window = '';
        $rangeValid = $filters['date_from'] !== '' && $filters['date_to'] !== '';
        if ($rangeValid) {
            $window = ' AND (d.private_rates IS NOT NULL OR (d.start_date >= '.$pdo->quote($filters['date_from']).' AND d.start_date <= '.$pdo->quote($filters['date_to']).'))';
        }
        $seatSql = $this->seat_sql();
        $salePrice = DepartureDiscount::price_sql('d', $today);
        $query->leftJoin(DB::raw("($seatSql) r"), function ($join) use ($seatSql, $today, $window, $salePrice) {
            $join->whereRaw("r.id=(SELECT d.id FROM ($seatSql) d WHERE d.tour_id=t.id AND d.active=1 AND ((d.private_rates IS NULL AND d.start_date >= $today) OR (d.private_rates IS NOT NULL AND d.end_date >= $today)) AND d.price_per_person>0 $window ORDER BY (d.seats_available>0) DESC,$salePrice ASC,d.start_date ASC,d.id ASC LIMIT 1)");
        });
        $query->where('t.status', 'published');
        if (in_array($filters['program_type'] ?? '', ['fixed', 'private'], true)) {
            $query->where('t.program_type', $filters['program_type']);
        } elseif (in_array($filters['program_type'] ?? '', ['flight', 'land'], true)) {
            $query->where('t.program_type', 'fixed')->where('t.flight_included', $filters['program_type'] === 'flight' ? 1 : 0);
        }
        if (! empty($filters['featured'])) {
            $query->where('t.featured', 1);
        }
        $q = $filters['q'] ?? '';
        foreach (tour_search_terms(is_array($q) ? '' : $q) as $term) {
            // Legacy passed the term through escape_like_str and then escape, so quotes and backslashes are escaped twice in this pattern only.
            $like = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], substr($pdo->quote($term), 1, -1)).'%';
            $search = addcslashes($term, '%_\\');
            $query->where(function ($group) use ($search, $membership, $like) {
                $group->where('t.title', 'like', '%'.$search.'%')->orWhere('t.code', 'like', '%'.$search.'%')->orWhere('t.slug', 'like', '%'.$search.'%')->orWhereRaw('EXISTS ('.$membership." AND (dr.name LIKE ? ESCAPE '!' OR dr.slug LIKE ? ESCAPE '!' OR dc.name LIKE ? ESCAPE '!' OR dc.slug LIKE ? ESCAPE '!' OR dz.name LIKE ? ESCAPE '!' OR dz.slug LIKE ? ESCAPE '!'))", [$like, $like, $like, $like, $like, $like]);
            });
        }
        if (! empty($filters['destination'])) {
            $query->whereRaw('EXISTS ('.$membership.' AND dr.name=?)', [$filters['destination']]);
        }
        if ($rangeValid) {
            $query->whereNotNull('r.tour_id');
        }
        $query->orderBy('t.sort_order', 'ASC')->orderBy('t.id', 'DESC');
        // Apply the limit after validating private travel windows.
        $tours = $query->get()->all();
        if (! $tours) {
            return [];
        }
        // Cards and their prices use the same inclusive departure-date range.
        $ids = implode(',', array_map('intval', array_column($tours, 'id')));
        $periods = DB::select("SELECT d.* FROM ($seatSql) d WHERE d.tour_id IN ($ids) AND d.active=1 AND ((d.private_rates IS NULL AND d.start_date >= $today) OR (d.private_rates IS NOT NULL AND d.end_date >= $today)) AND d.price_per_person>0 $window ORDER BY d.start_date,d.id");
        $byTour = [];
        $levels = $this->package_levels->all();
        foreach ($periods as $period) {
            $byTour[$period['tour_id']][] = DepartureDiscount::apply($this->package_levels->hydrate($period, $levels));
        }
        foreach ($tours as &$tour) {
            $tour['card_periods'] = $this->resolve_periods($byTour[$tour['id']] ?? []);
            if ($tour['program_type'] === 'private') {
                $tour['card_periods'] = array_values(array_filter($tour['card_periods'], function ($d) use ($tour, $filters) {
                    return PrivatePackage::first_date($d + ['days' => $tour['days']], $filters['date_from'], $filters['date_to']) !== null;
                }));
                $cheapest = null;
                foreach ($tour['card_periods'] as $period) {
                    if ($cheapest === null || (float) $period['price_per_person'] < (float) $cheapest['price_per_person']) {
                        $cheapest = $period;
                    }
                }
                $tour['min_price'] = $cheapest['price_per_person'] ?? null;
                $tour['min_regular_price'] = $cheapest['regular_price'] ?? null;
            }
            $tour['max_discount'] = DepartureDiscount::best($tour['card_periods']);
        }
        unset($tour);
        if ($rangeValid) {
            $tours = array_values(array_filter($tours, function ($t) {
                return ! empty($t['card_periods']);
            }));
        }
        if ($limit) {
            $tours = array_slice($tours, 0, $limit);
        }

        return $this->airlines->attach($this->places->attach($tours));
    }

    // First bookable day of a listing period: a private window's first open date, or a group departure that still has seats.
    private function period_start($tour, $period)
    {
        if ($tour['program_type'] === 'private') {
            return PrivatePackage::first_date($period + ['days' => $tour['days']]);
        }

        return (int) $period['seats_available'] > 0 ? $period['start_date'] : null;
    }

    // Re-orders listing() rows for the visitor's sort choice. Tours missing the sorted value go last,
    // and usort is stable, so ties keep the admin's sort_order.
    public function sort_listing($tours, $sort)
    {
        $key = match ($sort) {
            'price_asc', 'price_desc' => fn ($t) => $t['min_price'] !== null && (float) $t['min_price'] > 0 ? (float) $t['min_price'] : null,
            'departure' => function ($t) {
                $dates = array_filter(array_map(fn ($p) => $this->period_start($t, $p), $t['card_periods']));

                return $dates ? min($dates) : null;
            },
            'duration' => fn ($t) => (int) $t['days'] > 0 ? (int) $t['days'] : null,
            'popular' => fn ($t) => (int) ($t['view_count'] ?? 0),
            default => null,
        };
        if (! $key) {
            return $tours;
        }
        $desc = in_array($sort, ['price_desc', 'popular'], true);
        $values = array_map($key, $tours);
        $order = array_keys($tours);
        usort($order, function ($a, $b) use ($values, $desc) {
            if ($values[$a] === null || $values[$b] === null) {
                return ($values[$a] === null) <=> ($values[$b] === null);
            }

            return $desc ? $values[$b] <=> $values[$a] : $values[$a] <=> $values[$b];
        });

        return array_map(fn ($i) => $tours[$i], $order);
    }

    // One card per route, built from listing() rows so prices and dates respect seats and private travel windows.
    public function route_cards($tours, $type)
    {
        $cards = [];
        foreach ($tours as $tour) {
            $next = null;
            $last = null;
            $people = [];
            foreach ($tour['card_periods'] as $period) {
                $start = $this->period_start($tour, $period);
                if ($start === null) {
                    continue;
                }
                $next = $next === null ? $start : min($next, $start);
                $last = max($last, $period['end_date']);
                foreach (json_decode($period['private_rates'] ?? '[]', true) ?: [] as $rate) {
                    array_push($people, (int) $rate['min'], (int) $rate['max']);
                }
            }
            foreach ($tour['destinations'] as $place) {
                $id = $place['route_id'];
                $card = $cards[$id] ?? ['route_id' => $id, 'name' => $place['name'], 'country_name' => $place['country_name'], 'country_flag' => $place['country_flag'], 'url' => destination_url($place).($type === 'fixed' ? '' : '?program_type='.$type), 'cover' => $place['route_cover'] ?: $tour['cover'], 'cover_alt' => $place['route_cover_alt'] ?: $place['name'], 'listing_type' => $type, 'total' => 0, 'min_price' => null, 'lowest_rate' => null, 'next_date' => null, 'last_date' => null, 'people' => [], 'airlines' => [], 'flight_states' => []];
                $card['total']++;
                if ($tour['min_price'] && ($card['min_price'] === null || (float) $tour['min_price'] < (float) $card['min_price'])) {
                    $card['min_price'] = $tour['min_price'];
                    $card['lowest_rate'] = private_lowest_rate($tour['card_periods']);
                }
                if ($next !== null) {
                    $card['next_date'] = $card['next_date'] === null ? $next : min($card['next_date'], $next);
                    $card['last_date'] = max($card['last_date'], $last);
                }
                $card['people'] = array_merge($card['people'], $people);
                $card['flight_states'][(int) $tour['flight_included']] = true;
                $card['airlines'] = array_values(array_unique(array_merge($card['airlines'], $tour['flight_summary']['airlines'] ?? ($tour['airline'] !== '' ? [$tour['airline']] : []))));
                $cards[$id] = $card;
            }
        }
        // Routes with the most programs lead; usort is stable, so ties keep the admin's program order.
        usort($cards, function ($a, $b) {
            return $b['total'] <=> $a['total'];
        });

        return $cards;
    }

    // Fills the side column beside the main card until a page has enough routes of its own.
    public function route_extras($type, $cards)
    {
        $slots = 3 - count($cards);
        if (! $cards || $slots < 1) {
            return [];
        }
        $taken = array_column($cards, 'route_id');
        $candidates = [];
        foreach (array_keys(program_channels()) as $other) {
            if ($other === $type || ($type === 'fixed' && $other !== 'private')) {
                continue;
            }
            $candidates[] = array_values(array_filter($this->route_cards($this->listing(['program_type' => $other]), $other), function ($card) {
                return $card['next_date'] !== null;
            }));
        }
        // Take one route from each other type in turn so the column shows the range on offer.
        $extras = [];
        for ($turn = 0; count($extras) < $slots && array_filter($candidates); $turn = ($turn + 1) % max(count($candidates), 1)) {
            $card = array_shift($candidates[$turn]);
            if ($card && ! in_array($card['route_id'], $taken, true)) {
                $taken[] = $card['route_id'];
                $extras[] = $card;
            }
        }

        return $extras;
    }

    // Each home tab shows its featured programs, or the whole channel until one is marked.
    public function home_channels($limit = 8)
    {
        $grouped = [];
        foreach ($this->listing() as $tour) {
            $grouped[tour_channel($tour)][] = $tour;
        }
        $channels = [];
        foreach (['land', 'flight', 'private'] as $key) {
            if (empty($grouped[$key])) {
                continue;
            }
            $featured = array_filter($grouped[$key], function ($tour) {
                return ! empty($tour['featured']);
            });
            $channels[$key] = program_channels()[$key] + ['total' => count($grouped[$key]), 'tours' => array_slice($featured ?: $grouped[$key], 0, $limit)];
        }

        return $channels;
    }

    // The home departure board lists the soonest open group departures across all published tours.
    public function upcoming_departures($limit = 6)
    {
        $rows = [];
        foreach ($this->listing(['program_type' => 'fixed']) as $tour) {
            foreach ($tour['card_periods'] as $period) {
                if ((int) $period['seats_available'] > 0) {
                    $rows[] = ['tour' => $tour, 'period' => $period];
                }
            }
        }
        usort($rows, function ($a, $b) {
            return strcmp($a['period']['start_date'], $b['period']['start_date']) ?: ((int) $a['period']['id'] <=> (int) $b['period']['id']);
        });

        return array_slice($rows, 0, $limit);
    }

    public function related($tour, $limit = 8)
    {
        if ($limit < 1) {
            return [];
        }
        $routes = array_column($tour['destinations'] ?? [], 'route_id');
        $countries = array_column($tour['countries'] ?? [], 'id');
        $ranked = [];
        foreach ($this->listing(['program_type' => $tour['program_type'] ?? 'fixed']) as $position => $candidate) {
            if ((int) $candidate['id'] === (int) $tour['id']) {
                continue;
            }
            $rank = array_intersect($routes, array_column($candidate['destinations'], 'route_id')) ? 2 : (array_intersect($countries, array_column($candidate['countries'], 'id')) ? 1 : 0);
            $ranked[] = ['tour' => $candidate, 'rank' => $rank, 'position' => $position];
        }
        usort($ranked, function ($a, $b) {
            return ($b['rank'] <=> $a['rank']) ?: ($a['position'] <=> $b['position']);
        });

        return array_column(array_slice($ranked, 0, min(8, (int) $limit)), 'tour');
    }

    public function destinations()
    {
        return DB::select('SELECT dr.name destination,dr.slug route_slug,COUNT(DISTINCT t.id) total,MIN(t.cover) cover FROM tours t JOIN ('.$this->places->links_sql().") tr ON tr.tour_id=t.id JOIN destination_nodes dr ON dr.id=tr.route_id WHERE t.status='published' GROUP BY dr.id,dr.name,dr.slug ORDER BY total DESC,dr.name");
    }

    // $priced (default: same as $public) gives departures their selling prices, see DepartureDiscount::apply().
    // The admin editor reads regular prices so saving the form never discounts twice.
    public function find($idOrSlug, $public = false, $priced = null)
    {
        $priced = $priced ?? $public;
        $query = DB::table('tours');
        if (is_int($idOrSlug)) {
            $query->where('id', $idOrSlug);
        } else {
            $query->where(function ($q) use ($idOrSlug) {
                $q->where('code', $idOrSlug)->orWhere('slug', $idOrSlug);
            });
            $query->orderByRaw('CASE WHEN code=? THEN 0 ELSE 1 END ASC', [$idOrSlug]);
        }
        if ($public) {
            $query->where('status', 'published');
        }
        $tour = $query->first();
        if (! $tour && ! is_int($idOrSlug)) {
            $query = DB::table('tour_code_aliases as a')->select('t.*')->join('tours as t', 't.id', '=', 'a.tour_id')->where('a.code', $idOrSlug);
            if ($public) {
                $query->where('t.status', 'published');
            }
            $tour = $query->first();
        }
        if (! $tour) {
            return null;
        }
        $tour = $this->places->attach([$tour])[0];
        $tour['itinerary'] = DB::table('tour_days')->where('tour_id', $tour['id'])->orderBy('day_number')->get()->all();
        $tour['images'] = DB::table('tour_images')->where('tour_id', $tour['id'])->orderBy('sort_order')->get()->all();
        $sql = 'SELECT s.*, (SELECT COUNT(*) FROM bookings b WHERE b.departure_id=s.id) booking_count FROM ('.$this->seat_sql().') s WHERE s.tour_id=?';
        $binds = [$tour['id']];
        if ($public) {
            $sql .= ' AND s.active=1 AND '.($tour['program_type'] === 'private' ? 's.end_date' : 's.start_date').'>=?';
            $binds[] = date('Y-m-d');
        }
        $tour['departures'] = DB::select($sql.' ORDER BY s.start_date,s.id', $binds);
        foreach ($tour['departures'] as &$departure) {
            $departure = $this->package_levels->hydrate($departure);
            if ($priced) {
                $departure = DepartureDiscount::apply($departure);
            }
            $departure['days'] = $tour['days'];
            $departure['program_type'] = $tour['program_type'];
            $departure['deposit_per_person'] = $tour['deposit_per_person'];
            $departure['room_available'] = $tour['nights'] > 0 && $tour['hotel_status'] !== 'none' ? 1 : 0;
            $departure['quote'] = $this->quote($departure);
        }
        unset($departure);
        $tour['departures'] = $this->resolve_periods($tour['departures']);

        return $this->airlines->attach([$tour])[0];
    }

    public function save($id, $tour, $days, $departures, $images, $hierarchy = null, $flightSets = [])
    {
        $this->save_error = '';
        $this->billing->begin();
        $tour['updated_at'] = date('Y-m-d H:i:s');
        $levels = $this->package_levels->lock();
        foreach ($departures as &$row) {
            if (! empty($row['private_rates'])) {
                foreach (json_decode($row['private_rates'], true) ?: [] as $rate) {
                    if (! $this->package_levels->resolve($rate, $levels)) {
                        return $this->save_failure('ระดับบริการถูกลบแล้ว กรุณาเลือกระดับใหม่');
                    }
                }
                $row = $this->package_levels->hydrate($row, $levels);
            }
        }
        unset($row);
        $routeRows = $hierarchy;
        if ($routeRows === null) {
            $existingLinks = $id ? $this->places->attach([['id' => $id]])[0]['destinations'] : [];
            $routeRows = $existingLinks ?: (! empty($tour['route_id']) ? [['route_id' => $tour['route_id']]] : []);
        }
        if (! $routeRows || count($routeRows) > 60) {
            return $this->save_failure('กรุณาเลือกจุดหมาย 1–60 รายการ');
        }
        $routeIds = array_map('intval', array_column($routeRows, 'route_id'));
        if (count(array_unique($routeIds)) !== count($routeIds)) {
            return $this->save_failure('ไม่สามารถเลือก Route ซ้ำในทัวร์เดียวกัน');
        }
        $sortedIds = $routeIds;
        sort($sortedIds, SORT_NUMERIC);
        $this->billing->soft(fn () => DB::select('SELECT id FROM destination_nodes WHERE id IN ('.implode(',', $sortedIds).') ORDER BY id FOR UPDATE'));
        foreach ($routeRows as $pos => $row) {
            $route = $this->places->route((int) $row['route_id']);
            if (! $route || (isset($row['zone_id']) && ((int) $route['zone_id'] !== (int) $row['zone_id'] || (int) $route['country_id'] !== (int) $row['country_id']))) {
                return $this->save_failure('ลำดับจุดหมายเปลี่ยนแปลง กรุณาเลือก Zone / Country / Route ใหม่');
            }
            if ($pos === 0) {
                $tour['route_id'] = $route['id'];
                $tour['destination'] = $route['name'];
            }
        }
        // Every writer locks tour -> departure -> booking, including confirmation.
        if ($id) {
            $identity = DB::select('SELECT id,code,slug,program_type FROM tours WHERE id=? FOR UPDATE', [$id])[0] ?? null;
            if (! $identity) {
                return $this->save_failure('ไม่พบโปรแกรม');
            } if ($identity['program_type'] !== $tour['program_type'] && DB::table('bookings')->where('tour_id', $id)->count()) {
                return $this->save_failure('โปรแกรมที่มีการจองแล้วเปลี่ยนประเภทไม่ได้ กรุณาสร้างโปรแกรมใหม่');
            } $tour['code'] = $identity['code'];
            $tour['slug'] = $identity['slug'];
        }
        $old = $id ? DB::table('departures')->where('tour_id', $id)->get()->all() : [];
        $oldById = array_column($old, null, 'id');
        $retained = [];
        foreach ($departures as $dep) {
            $depId = (int) ($dep['id'] ?? 0);
            if (! $depId) {
                continue;
            }
            if (! isset($oldById[$depId]) || isset($retained[$depId])) {
                return $this->save_failure('ข้อมูลรอบเดินทางเปลี่ยนไป กรุณาโหลดหน้าใหม่');
            }
            $retained[$depId] = true;
            $previous = $oldById[$depId];
            $current = $this->departure($depId);
            if ($tour['program_type'] === 'fixed' && $dep['capacity'] < $current['booked_seats']) {
                return $this->save_failure('ไซส์กรุ๊ปต้องไม่น้อยกว่าจำนวนที่ยืนยันแล้ว ('.$current['booked_seats'].' คน)');
            }
            $hasBookings = DB::table('bookings')->where('departure_id', $depId)->count();
            if ($hasBookings && ($dep['start_date'] !== $previous['start_date'] || $dep['end_date'] !== $previous['end_date'])) {
                return $this->save_failure('รอบที่มีคำขอจองแล้วเปลี่ยนวันที่ไม่ได้ กรุณาปิดรอบเดิมและเพิ่มรอบใหม่');
            }
        }
        foreach ($old as $dep) {
            if (! isset($retained[$dep['id']]) && DB::table('bookings')->where('departure_id', $dep['id'])->count()) {
                return $this->save_failure('ลบรอบที่มีคำขอจองแล้วไม่ได้ ให้ปิดรับคำขอแทน');
            }
        }
        if ($id) {
            $this->billing->soft(fn () => DB::table('tours')->where('id', $id)->update($tour));
        } else {
            $tour['created_at'] = $tour['updated_at'];
            $tour['code'] = 'PENDING-'.bin2hex(random_bytes(10));
            $tour['slug'] = $tour['code'];
            $id = $this->billing->soft(fn () => DB::table('tours')->insertGetId($tour));
            $ok = $id !== false;
            if ($ok) {
                $code = code_prefix($this->settings()).'-'.$id;
                $reserved = DB::table('tour_code_aliases')->where('code', $code)->count() || DB::table('tours')->where('id', '!=', $id)->where('slug', $code)->count() || $this->places->listing_slug_taken($code);
                $ok = ! $reserved && $this->billing->soft(fn () => DB::table('tours')->where('id', $id)->update(['code' => $code, 'slug' => $code])) !== false;
            }
            if (! $ok) {
                return $this->save_failure('สร้างรหัสโปรแกรมอัตโนมัติไม่สำเร็จ อาจมีรหัสนี้อยู่แล้ว กรุณาลองบันทึกอีกครั้ง');
            }
        }
        $this->billing->soft(fn () => DB::table('tour_routes')->where('tour_id', $id)->delete());
        foreach ($routeIds as $pos => $routeId) {
            $this->billing->soft(fn () => DB::table('tour_routes')->insert(['tour_id' => $id, 'route_id' => $routeId, 'sort_order' => $pos]));
        }
        foreach (['tour_days', 'tour_images'] as $table) {
            $this->billing->soft(fn () => DB::table($table)->where('tour_id', $id)->delete());
        }
        foreach ($old as $dep) {
            if (! isset($retained[$dep['id']])) {
                $this->billing->soft(fn () => DB::table('departures')->where('id', $dep['id'])->delete());
            }
        }
        foreach ($days as $i => $day) {
            $day['tour_id'] = $id;
            $day['day_number'] = $i + 1;
            $this->billing->soft(fn () => DB::table('tour_days')->insert($day));
        }
        // Sets are rewritten like the itinerary; bookings hold their own copy, so no history depends on these ids.
        $this->billing->soft(fn () => DB::table('tour_flight_sets')->where('tour_id', $id)->delete());
        $flightSetIds = [];
        foreach ($flightSets as $setOrder => $set) {
            $setId = $this->billing->soft(fn () => DB::table('tour_flight_sets')->insertGetId(['tour_id' => $id, 'name' => $set['name'], 'sort_order' => $setOrder]));
            $flightSetIds[$set['key']] = $setId;
            foreach ($set['flights'] as $flightOrder => $flight) {
                $this->billing->soft(fn () => DB::table('tour_flights')->insert(['flight_set_id' => $setId, 'sort_order' => $flightOrder] + $flight));
            }
        }
        foreach ($departures as $departure) {
            $depId = (int) ($departure['id'] ?? 0);
            unset($departure['id']);
            $departure['tour_id'] = $id;
            $departure['flight_set_id'] = $flightSetIds[$departure['flight_set_key'] ?? ''] ?? null;
            unset($departure['flight_set_key']);
            if ($depId) {
                $this->billing->soft(fn () => DB::table('departures')->where('id', $depId)->where('tour_id', $id)->update($departure));
            } else {
                $this->billing->soft(fn () => DB::table('departures')->insert($departure));
            }
        }
        foreach ($images as $i => $img) {
            $this->billing->soft(fn () => DB::table('tour_images')->insert(['tour_id' => $id, 'path' => $img['path'], 'caption' => $img['caption'], 'sort_order' => $i]));
        }
        if (! $this->billing->trans_ok) {
            return $this->save_failure('บันทึกไม่สำเร็จ กรุณาลองใหม่');
        }
        DB::commit();

        return $id;
    }

    private function save_failure($message)
    {
        $this->save_error = $message;
        DB::rollBack();

        return false;
    }

    // Bookings keep a foreign key to the tour, so a tour with booking history cannot be deleted.
    public function delete($id)
    {
        $this->save_error = '';
        $tour = DB::table('tours')->where('id', $id)->first();
        if (! $tour) {
            $this->save_error = 'ไม่พบโปรแกรม';

            return false;
        }
        if (DB::table('bookings')->where('tour_id', $id)->exists()) {
            $this->save_error = 'โปรแกรมนี้มีการจองแล้ว ให้เปลี่ยนสถานะเป็นเก็บถาวรแทนการลบ';

            return false;
        }
        $paths = [$tour['cover'], $tour['source_pdf'], ...array_column(DB::table('tour_images')->where('tour_id', $id)->get()->all(), 'path'), ...VehicleOptions::images(VehicleOptions::decode($tour['vehicle_options'] ?? null))];
        foreach (DB::table('tour_days')->where('tour_id', $id)->get()->all() as $day) {
            $paths = array_merge($paths, array_column(day_photos($day), 'path'));
            foreach (day_hotels($day) as $hotel) {
                $paths = array_merge($paths, $hotel['images']);
            }
        }
        try {
            DB::table('tours')->where('id', $id)->delete();
        } catch (QueryException $e) {
            $this->save_error = 'ลบโปรแกรมไม่สำเร็จ อาจมีการจองเข้ามาระหว่างนี้ กรุณาโหลดหน้าใหม่';

            return false;
        }
        $this->remove_replaced_files(array_filter($paths));

        return true;
    }

    // A new tour has no id until save() commits, so its uploads start in a temporary folder.
    // If this fails the files stay there and the stored paths remain valid.
    public function move_upload_folder($id, $folder)
    {
        $moved = [];
        foreach (['public/uploads/tours/', 'storage/tours/'] as $root) {
            if (! is_dir(base_path($root.$folder))) {
                continue;
            }
            if (! @rename(base_path($root.$folder), base_path($root.$id))) {
                return $this->restore_upload_folder($moved, $id, $folder);
            }
            $moved[] = $root;
        }
        if (! $moved) {
            return true;
        }
        $swap = [$folder, (string) $id];
        try {
            DB::transaction(function () use ($id, $swap) {
                DB::update('UPDATE tours SET cover=REPLACE(cover,?,?), source_pdf=REPLACE(source_pdf,?,?), vehicle_options=REPLACE(vehicle_options,?,?) WHERE id=?', [...$swap, ...$swap, ...$swap, $id]);
                DB::update('UPDATE tour_images SET path=REPLACE(path,?,?) WHERE tour_id=?', [...$swap, $id]);
                DB::update('UPDATE tour_days SET images_json=REPLACE(images_json,?,?), hotels_json=REPLACE(hotels_json,?,?) WHERE tour_id=?', [...$swap, ...$swap, $id]);
            });
        } catch (QueryException $e) {
            return $this->restore_upload_folder($moved, $id, $folder);
        }

        return true;
    }

    private function restore_upload_folder($moved, $id, $folder)
    {
        foreach ($moved as $root) {
            @rename(base_path($root.$id), base_path($root.$folder));
        }

        return false;
    }

    // Call after save() commits, so a failed save never loses the current files.
    public function remove_replaced_files($paths)
    {
        foreach (array_unique($paths) as $path) {
            if (preg_match('~^(public/uploads/(tours/[a-z0-9-]+/images/|vehicle-templates/|hotel-library/)?[a-f0-9]+\.(jpg|jpeg|png|webp)|storage/(tour-pdfs|tours/[a-z0-9-]+/doc)/[a-f0-9]+\.pdf)$~D', $path) && ! $this->upload_in_use($path) && is_file(base_path($path))) {
                @unlink(base_path($path));
            }
        }
    }

    // public/uploads is shared with other features and its URLs can be pasted into content.
    // Matches the file name to also catch full URLs and JSON-escaped paths. If a check fails the file is kept.
    private function upload_in_use($path)
    {
        try {
            foreach (self::UPLOAD_COLUMNS as $table => $columns) {
                foreach ($columns as $column) {
                    if (DB::table($table)->where($column, 'like', '%'.basename($path).'%')->exists()) {
                        return true;
                    }
                }
            }
        } catch (QueryException $e) {
            return true;
        }

        return false;
    }

    // Room counts from a booking form; invalid entries become false.
    public function booking_rooms($post)
    {
        $rooms = [];
        foreach (['twin', 'double', 'triple', 'single'] as $type) {
            $raw = $post[$type.'_rooms'] ?? null;
            $n = filter_var($raw === null ? 0 : $raw, FILTER_VALIDATE_INT);
            $rooms[$type] = $n === false || $n < 0 || $n > 500 ? false : $n;
        }

        return $rooms;
    }

    // Quote, room plan and seat checks shared by the public and admin booking forms.
    public function booking_plan_error($rate, $count, $rooms, $quote)
    {
        if ($rate && (! is_string($quote) || ! hash_equals($rate['quote'], $quote))) {
            return 'ข้อมูลรอบหรือราคามีการเปลี่ยนแปลง กรุณาเลือกใหม่';
        }
        $validRooms = ! in_array(false, $rooms, true);
        if ($rate && $rate['room_available']) {
            if (! $validRooms || 2 * ($rooms['twin'] + $rooms['double']) + 3 * $rooms['triple'] + $rooms['single'] !== $count) {
                return 'กรุณาระบุจำนวนห้องเป็นจำนวนเต็ม และจัดห้องให้ครบตามจำนวนผู้เดินทาง';
            }
            if ($rooms['single'] > 0 && $rate['single_supplement'] === null) {
                return 'รอบนี้ยังไม่กำหนดค่าพักเดี่ยว กรุณาสอบถามทีมงาน';
            }
        } elseif ($rate && (! $validRooms || array_filter($rooms))) {
            return 'รอบนี้ไม่มีที่พักในโปรแกรม กรุณาเลือกข้อมูลใหม่';
        }
        if (! $count || $count < 1 || $count > 500) {
            return 'กรุณาระบุจำนวนผู้เดินทางเป็นจำนวนเต็มตั้งแต่ 1 ท่าน';
        }
        if ($rate && $count > $rate['seats_available']) {
            return 'จำนวนผู้เดินทางเกินที่นั่งว่าง ขณะนี้เหลือ '.$rate['seats_available'].' ที่นั่ง';
        }

        return '';
    }

    // Revalidate and lock the selected published departure while recording the request.
    // Returns the inserted booking row, or an error message.
    public function create_booking($rate, $plan, $fields)
    {
        $count = $plan['traveler_count'];
        $rooms = $plan['rooms'];
        $this->billing->begin();
        $this->package_levels->lock();
        $this->billing->soft(fn () => DB::select('SELECT id FROM tours WHERE id=? FOR UPDATE', [$rate['tour_id']]));
        $this->billing->soft(fn () => DB::select('SELECT id FROM departures WHERE id=? FOR UPDATE', [$rate['id']]));
        $rate = $this->departure($rate['id'], true);
        if ($rate && $rate['program_type'] === 'private') {
            $rate = PrivatePackage::offer($rate, $plan['travel_date'], $count, $plan['package_level']);
        }
        if (! $rate || $count > $rate['seats_available'] || ! is_string($plan['quote']) || ! hash_equals($rate['quote'], $plan['quote'])) {
            DB::rollBack();

            return 'ที่นั่งว่างหรือข้อมูลรอบมีการเปลี่ยนแปลง กรุณาเลือกใหม่';
        }
        $chosen = array_keys(array_filter($rooms));
        $now = date('Y-m-d H:i:s');
        $ref = code_prefix($this->settings()).'-'.date('ymd').'-'.strtoupper(bin2hex(random_bytes(4)));
        $data = ['reference' => $ref, 'tour_id' => $rate['tour_id'], 'tour_title' => $rate['title'], 'departure_id' => $rate['id'], 'start_date' => $rate['start_date'], 'end_date' => $rate['end_date'], 'group_size' => $rate['capacity'], 'traveler_count' => $count, 'price_per_person' => $rate['price_per_person'], 'total_price' => round($rate['price_per_person'] * $count, 2), 'status' => 'new', 'admin_note' => '', 'consent_at' => $now, 'created_at' => $now, 'updated_at' => $now];
        $data['package_snapshot'] = $rate['package_snapshot'] ?? null;
        $data['flight_included'] = (int) $rate['flight_included'];
        $data['flight_snapshot'] = $rate['program_type'] === 'private' ? null : $this->airlines->snapshot($rate['flight_set_id']);
        $data['room_type'] = count($chosen) > 1 ? 'mixed' : ($chosen[0] ?? 'none');
        $data['access_token'] = bin2hex(random_bytes(32));
        foreach ($rooms as $type => $number) {
            $data[$type.'_rooms'] = $number;
        }
        $data['single_supplement'] = $rooms['single'] > 0 ? $rate['single_supplement'] : 0;
        $data['supplement_total'] = round($data['single_supplement'] * $rooms['single'], 2);
        $data['total_price'] = round($data['total_price'] + $data['supplement_total'], 2);
        $data = array_replace($data, $fields);
        $data['id'] = $this->billing->soft(fn () => DB::table('bookings')->insertGetId($data));
        if (! $this->billing->trans_ok) {
            DB::rollBack();

            return 'บันทึกไม่สำเร็จ กรุณาลองใหม่';
        }
        DB::commit();

        return $data;
    }

    public function update_booking($id, $status, $note, $hours = 24)
    {
        $initial = DB::table('bookings')->where('id', $id)->first();
        if (! $initial) {
            return 'ไม่พบคำขอจอง';
        }
        $this->billing->begin();
        $this->billing->soft(fn () => DB::select('SELECT id FROM tours WHERE id=? FOR UPDATE', [$initial['tour_id']]));
        if ($initial['departure_id']) {
            $this->billing->soft(fn () => DB::select('SELECT id FROM departures WHERE id=? FOR UPDATE', [$initial['departure_id']]));
        }
        $booking = DB::select('SELECT * FROM bookings WHERE id=? FOR UPDATE', [$id])[0] ?? null;
        if ($status !== 'confirmed' && DB::table('payments')->where('booking_id', $id)->where('status', 'pending')->count()) {
            DB::rollBack();

            return 'มีสลิปรอตรวจ กรุณาตรวจสอบก่อนเปลี่ยนสถานะการจอง';
        }
        if (in_array($status, ['new', 'contacted'], true) && $this->billing->paid($id) > 0) {
            DB::rollBack();

            return 'รับชำระแล้ว ไม่สามารถย้อนกลับเป็นคำขอใหม่หรือติดต่อแล้ว';
        }
        $dep = ($booking['departure_id'] ?? null) ? $this->departure($booking['departure_id']) : null;
        if ($status === 'confirmed' && ($booking['status'] ?? null) !== 'confirmed') {
            $error = '';
            if (! $dep || ! $dep['active'] || $dep['tour_status'] !== 'published' || $booking['start_date'] < date('Y-m-d')) {
                $error = 'รอบนี้ไม่เปิดรับการยืนยัน กรุณาตรวจสอบโปรแกรมและวันเดินทาง';
            } elseif (empty($booking['package_snapshot']) && $dep['seats_available'] < $booking['traveler_count']) {
                $error = 'ที่นั่งไม่พอ เหลือ '.$dep['seats_available'].' ที่นั่ง แต่คำขอนี้จอง '.$booking['traveler_count'].' คน';
            }
            if ($error) {
                DB::rollBack();

                return $error;
            }
        }
        $this->billing->soft(fn () => DB::table('bookings')->where('id', $id)->update(['status' => $status, 'admin_note' => $note, 'updated_at' => date('Y-m-d H:i:s')]));
        if ($status === 'confirmed') {
            $this->billing->issue($booking,$hours);
        }
        if ($status !== ($booking['status'] ?? null)) {
            $this->billing->event($id,'สถานะการจอง: '.status_label($status),session('admin_id') ?: null);
            if ($status !== 'confirmed') {
                $this->billing->soft(fn () => DB::table('payments')->where('booking_id',$id)->where('status','awaiting_upload')->update(['status' => 'cancelled']));
            }
        }
        if (! $this->billing->trans_ok) {
            DB::rollBack();

            return 'บันทึกไม่สำเร็จ กรุณาลองใหม่';
        }
        DB::commit();

        return null;
    }
}
