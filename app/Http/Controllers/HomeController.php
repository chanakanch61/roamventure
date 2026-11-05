<?php

namespace App\Http\Controllers;

use App\Support\PrivatePackage;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    public function index()
    {
        return $this->render('home', ['title' => 'เที่ยวในแบบของเรา ไปกับ RoamVenture', 'home_channels' => $this->trips->home_channels(), 'departures' => $this->trips->upcoming_departures(6), 'destinations' => $this->trips->destinations(), 'home_posts' => $this->blog->latest_public(), 'home_gallery' => $this->gallery->home_highlights()]);
    }

    public function packages()
    {
        return $this->tours('private');
    }

    public function flight_tours()
    {
        return $this->tours('flight');
    }

    public function land_tours()
    {
        return $this->tours('land');
    }

    public function tours($type = 'fixed')
    {
        $channel = program_channels()[$type] ?? null;
        $type = $channel ? $type : 'fixed';
        $filters = $this->search_filters();
        $filters['program_type'] = $type;
        $tours = $this->trips->listing($filters);
        // Searching narrows the page to matching programs, so the route cards step aside.
        $searching = $filters['q'] !== '' || $filters['destination'] !== '' || $filters['date_from'] !== '';
        $routeCards = $searching ? [] : $this->trips->route_cards($tours, $type);

        return $this->render('tours', ['title' => $channel['label'] ?? 'ทัวร์ต่างประเทศ', 'listing_type' => $type, 'listing_path' => $channel['path'] ?? 'tours', 'tours' => $tours, 'filters' => $filters, 'destinations' => $this->trips->destinations(), 'listing_searching' => $searching, 'route_cards' => $routeCards, 'route_extras' => $this->trips->route_extras($type, $routeCards)]);
    }

    public function detail($slug)
    {
        $tour = $this->trips->find($slug, true);
        if (! $tour) {
            show_404();
        }
        if ($slug !== $tour['code']) {
            $query = request()->query();
            redirect_to(base_url().('tours/'.rawurlencode($tour['code']).($query ? '?'.http_build_query($query) : '')), 301);
        }
        if (! session('request_token')) {
            session()->put('request_token', bin2hex(random_bytes(24)));
        }

        return $this->render('detail', ['title' => $tour['title'], 'tour' => $tour, 'preview' => false, 'related_tours' => $this->trips->related($tour), 'structured_data' => $this->tour_structured_data($tour)]);
    }

    private function tour_structured_data($tour)
    {
        $url = base_url().'tours/'.rawurlencode($tour['code']);
        $photos = array_filter(array_merge([$tour['cover']], array_column($tour['images'], 'path')));
        $trip = ['@type' => 'TouristTrip', '@id' => $url.'#trip', 'name' => $tour['title'], 'description' => $tour['seo_description'] ?: $tour['summary'], 'url' => $url, 'provider' => ['@type' => 'Organization', 'name' => trim($this->settings['company_name'] ?? '') ?: 'RoamVenture', 'url' => base_url()]];
        if ($photos) {
            $trip['image'] = array_values(array_map('tour_image', $photos));
        }
        if ($tour['itinerary']) {
            $trip['itinerary'] = ['@type' => 'ItemList', 'itemListElement' => array_map(function ($day, $index) {
                return ['@type' => 'ListItem', 'position' => $index + 1, 'name' => day_title($day['title'])];
            }, $tour['itinerary'], array_keys($tour['itinerary']))];
        }
        $prices = tour_availability($tour)['prices'];
        if ($prices) {
            $trip['offers'] = ['@type' => 'AggregateOffer', 'priceCurrency' => 'THB', 'lowPrice' => min($prices), 'highPrice' => max($prices), 'offerCount' => count($prices), 'availability' => 'https://schema.org/InStock', 'url' => $url];
        }
        $channel = program_channels()[tour_channel($tour)];
        $crumbs = [['name' => $channel['label'], 'item' => base_url().$channel['path']]];
        $route = ! empty($tour['route_id']) ? $this->places->route($tour['route_id']) : null;
        if ($route && count($tour['destinations']) === 1) {
            foreach ($this->places->trail($route) as $place) {
                $crumbs[] = ['name' => $place['name'], 'item' => destination_url($place)];
            }
        }
        $crumbs[] = ['name' => $tour['title'], 'item' => $url];
        foreach ($crumbs as $index => $crumb) {
            $crumbs[$index] = ['@type' => 'ListItem', 'position' => $index + 1] + $crumb;
        }

        return ['@context' => 'https://schema.org', '@graph' => [$trip, ['@type' => 'BreadcrumbList', 'itemListElement' => $crumbs]]];
    }

    public function tour_slug($slug)
    {
        $node = $this->places->listing_node($slug);
        if ($node) {
            return $this->render_destination($node);
        }

        return $this->detail($slug);
    }

    public function route_destination($country_slug, $slug)
    {
        $node = $this->places->public_node('route', $slug, $country_slug);
        if (! $node) {
            show_404();
        }

        return $this->render_destination($node);
    }

    public function destination($kind, $slug)
    {
        if (! array_key_exists($kind, $this->places->kinds())) {
            show_404();
        }
        $node = $this->places->public_node($kind, $slug);
        if (! $node) {
            show_404();
        }
        $query = request()->query();
        redirect_to(destination_url($node).($query ? '?'.http_build_query($query) : ''), 301);
    }

    private function render_destination($node)
    {
        $url = destination_url($node);
        if (uri_string() !== substr($url, strlen(base_url()))) {
            $query = request()->query();
            redirect_to($url.($query ? '?'.http_build_query($query) : ''), 301);
        }
        $kind = $node['kind'];
        $type = request()->query('program_type');
        $type = in_array($type, ['fixed', 'flight', 'land', 'private'], true) ? $type : 'all';
        $filters = $this->search_filters();
        $filters[$kind.'_id'] = $node['id'];
        if ($type !== 'all') {
            $filters['program_type'] = $type;
        }
        $tours = $this->trips->listing($filters);
        $node['onpage_content'] = $this->places->clean_content($node['onpage_content'] ?? '');

        return $this->render('destination', ['destination_page' => true, 'title' => 'ทัวร์'.$node['name'], 'node' => $node, 'search_scope' => $node['name'], 'listing_type' => $type, 'listing_path' => substr($url, strlen(base_url())), 'filters' => $filters, 'trail' => $this->places->trail($node), 'children' => $this->places->children($node['id']), 'tours' => $tours, 'listing_searching' => $filters['q'] !== '' || $filters['date_from'] !== '', 'meta' => ['canonical' => $url, 'title' => $node['meta_title'] ?: 'ทัวร์'.$node['name'].' | RoamVenture', 'description' => $node['meta_description'] ?: mb_substr($node['description'] ?: 'ค้นหาโปรแกรมทัวร์'.$node['name'].' พร้อมวันเดินทาง ราคาและที่นั่งว่าง กับ RoamVenture', 0, 300), 'keywords' => $node['meta_keywords'], 'image' => tour_image($node['cover_path'] ?: ($tours[0]['cover'] ?? ''))]]);
    }

    private function search_filters()
    {
        $filters = [];
        foreach (['q', 'destination', 'month', 'date_from', 'date_to'] as $key) {
            $filters[$key] = is_string(request()->query($key)) ? mb_substr(trim(request()->query($key)), 0, 100) : '';
        }

        return $this->trips->normalize_travel_dates($filters);
    }

    public function book()
    {
        $this->post_only();
        $departureId = request()->post('departure_id');
        foreach (['customer_name', 'phone', 'email', 'line_id', 'note', 'consent', 'website', 'room_type', 'ticket_help'] as $field) {
            if (is_array(request()->post($field))) {
                show_error('ข้อมูลแบบฟอร์มไม่ถูกต้อง', 400);
            }
        }
        $rate = is_scalar($departureId) ? $this->trips->departure((int) $departureId, true) : null;
        $count = filter_var(request()->post('traveler_count'), FILTER_VALIDATE_INT);
        $quote = request()->post('quote');
        $privateStart = request()->post('travel_date');
        $privateLevel = request()->post('package_level');
        if ($rate && $rate['program_type'] === 'private') {
            $rate = PrivatePackage::offer($rate, $privateStart, $count, $privateLevel);
        }
        $rooms = $this->trips->booking_rooms(request()->post());
        $slug = $rate ? rawurlencode($rate['code']) : null;
        $token = request()->post('request_token');
        if (! is_string($token) || strlen($token) !== 48) {
            show_error('คำขอไม่ถูกต้อง กรุณาเปิดหน้าโปรแกรมใหม่', 400);
        }
        $existing = DB::table('bookings')->where('request_token', $token)->first();
        if ($existing && session('last_booking') === $existing['reference']) {
            redirect_to(base_url().('booking/success'));
        }
        if (! session('request_token') || ! hash_equals(session('request_token'), $token)) {
            show_error('กรุณาเปิดหน้าโปรแกรมใหม่แล้วลองอีกครั้ง', 400);
        }
        // bail on email: CI reported only the first failing rule per field.
        $validationErrors = validate_post([
            'customer_name' => ['ชื่อผู้ติดต่อ', 'required|string|max:120'],
            'phone' => ['เบอร์โทรศัพท์', ['required', 'string', 'regex:/^[0-9+() -]{8,25}$/']],
            'email' => ['อีเมล', 'bail|nullable|string|email:filter|max:190'],
            'line_id' => ['LINE ID', 'nullable|string|max:100'],
            'note' => ['รายละเอียด', 'nullable|string|max:2000'],
            'consent' => ['การรับทราบนโยบายข้อมูล', 'required|in:1'],
        ]);
        $valid = $validationErrors === '';
        // validation_errors('',' ') closed each message with a space instead of </p>.
        $error = ! $rate ? 'รอบหรือราคานี้มีการเปลี่ยนแปลง กรุณาเลือกใหม่' : (! $valid ? strip_tags(str_replace('</p>', ' ', $validationErrors)) : '');
        $error = $this->trips->booking_plan_error($rate, $count, $rooms, $quote) ?: $error;
        if (request()->post('website')) {
            $error = 'ไม่สามารถส่งคำขอได้';
        }
        if (session('last_request_at') > time() - 30) {
            $error = 'กรุณารอ 30 วินาทีก่อนส่งคำขอใหม่';
        }
        if ($error) {
            session()->flash('error', $error);
            session()->flash('booking_old', array_intersect_key(request()->post(), array_flip(['customer_name', 'phone', 'email', 'line_id', 'note', 'ticket_help'])));
            redirect_to(base_url().($slug ? 'tours/'.$slug.'#booking' : 'tours'));
        }
        $fields = ['request_token' => $token, 'ticket_help' => ! $rate['flight_included'] && request()->post('ticket_help') === '1' ? 1 : 0];
        foreach (['customer_name', 'phone', 'email', 'line_id', 'note'] as $key) {
            $fields[$key] = trim((string) request()->post($key));
        }
        $data = $this->trips->create_booking($rate, ['traveler_count' => $count, 'rooms' => $rooms, 'quote' => $quote, 'travel_date' => $privateStart, 'package_level' => $privateLevel], $fields);
        if (is_string($data)) {
            session()->flash('error', $data);
            redirect_to(base_url().('tours/'.$slug.'#booking'));
        }
        $ref = $data['reference'];
        session()->flash('tracking_lead', ['event_id' => bin2hex(random_bytes(16)), 'currency' => 'THB', 'value' => (float) $data['total_price'], 'items' => [['item_id' => $rate['code'], 'item_name' => $rate['title'], 'quantity' => $count]]]);
        session()->put(['last_booking' => $ref, 'last_request_at' => time(), 'request_token' => bin2hex(random_bytes(24))]);
        redirect_to(base_url().('booking/success'));
    }

    public function success()
    {
        $ref = session('last_booking');
        if (! $ref) {
            redirect_to(base_url().('tours'));
        }
        $booking = DB::table('bookings')->where('reference', $ref)->first();
        if (! $booking) {
            show_404();
        }
        set_header('Cache-Control: no-store, private');
        set_header('Referrer-Policy: no-referrer');
        set_header('X-Robots-Tag: noindex, nofollow');
        $lead = session('tracking_lead');
        if (request()->query('continue') !== '1' && $lead && tracking_config($this->settings)['enabled']) {
            return $this->render('tracking_success', ['title' => 'ได้รับคำขอจองแล้ว', 'tracking_lead' => $lead]);
        }
        redirect_to(base_url().('booking/detail/'.$booking['access_token']));
    }

    public function privacy()
    {
        return $this->render('privacy', ['title' => 'การใช้ข้อมูลสำหรับคำขอจอง', 'meta' => seo_page_meta($this->settings, 'privacy')]);
    }
}
