<?php

use App\Support\ResponseHeaders;
use App\Support\ShownError;
use App\Support\ViewState;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Validator;

function base_url(): string
{
    return rtrim((string) config('app.url'), '/').'/';
}

/** Current path without surrounding slashes; empty on the home page. */
function uri_string(): string
{
    $path = request()->path();

    return $path === '/' ? '' : $path;
}

/** Lowercase HTTP method as actually sent, ignoring any _method override. */
function request_method(): string
{
    return strtolower(request()->getRealMethod());
}

/** Ends the request with a redirect, from any depth. Defaults to 307 after GET and 303 after anything else. */
function redirect_to(string $url, ?int $status = null): never
{
    throw new HttpResponseException(redirect()->away($url, $status ?? (request_method() === 'get' ? 307 : 303)));
}

/** Ends the request with an error page. */
function show_error(string $message, int $status = 500, string $heading = 'An Error Was Encountered'): never
{
    throw new ShownError($message, $status, $heading);
}

/** Ends the request with the 404 page. */
function show_404(): never
{
    abort(404);
}

/** Queues a response header, replacing any earlier value. Applied to whatever response ends the request. */
function set_header(string $header): void
{
    app(ResponseHeaders::class)->set($header);
}

/**
 * Renders a view with every variable passed to earlier views in this request, plus $vars.
 * Echoes unless $return is true, so partials can be called straight from a template.
 */
function load_view(string $view, array $vars = [], bool $return = false)
{
    $html = view($view, app(ViewState::class)->merge($vars))->render();
    if ($return) {
        return $html;
    }
    echo $html;
}

/**
 * Trims and validates POST fields, writing the trimmed values back to the request.
 * Returns the error messages as HTML paragraphs, or an empty string when valid.
 *
 * @param  array<string, array{0: string, 1: string|array}>  $fields  field => [label, Laravel rules]
 */
function validate_post(array $fields): string
{
    foreach (array_keys($fields) as $field) {
        $value = request()->post($field);
        if (is_string($value)) {
            request()->request->set($field, trim($value));
        }
    }
    $validator = Validator::make(request()->post(), array_map(fn ($field) => $field[1], $fields), [
        'required' => 'The :attribute field is required.',
        'string' => 'The :attribute field is not in the correct format.',
        'max' => 'The :attribute field cannot exceed :max characters in length.',
        'regex' => 'The :attribute field is not in the correct format.',
        'email' => 'The :attribute field must contain a valid email address.',
        'in' => 'The :attribute field must be one of: 1.',
    ], array_map(fn ($field) => $field[0], $fields));
    if ($validator->passes()) {
        return '';
    }

    return implode("\n", array_map(fn ($message) => '<p>'.$message.'</p>', $validator->errors()->all()))."\n";
}

function day_photos($day)
{
    if (isset($day['images_json'])) {
        return json_decode($day['images_json'], true) ?: [];
    }

    return ! empty($day['image_path']) ? [['path' => $day['image_path'], 'caption' => $day['image_caption'] ?? '']] : [];
}
// Hotels for a day in priority order: the first is the main choice, later ones are fallbacks.
function day_hotels($day)
{
    return json_decode($day['hotels_json'] ?? '', true) ?: [];
}
function destination_url($node)
{
    $prefix = ($node['kind'] ?? 'route') === 'route' ? 'tour/'.rawurlencode($node['country_slug']).'/' : 'tours/';

    return base_url().$prefix.rawurlencode($node['slug']);
}
function tour_search_terms($value)
{
    $terms = preg_split('~[\s/_-]+~u', mb_strtolower(trim($value)), -1, PREG_SPLIT_NO_EMPTY);

    return array_slice(array_unique($terms ?: []), 0, 8);
}
function code_prefix($settings)
{
    $value = $settings['code_prefix'] ?? 'ZD';
    $value = is_string($value) ? strtoupper(trim($value)) : '';

    return preg_match('/^[A-Z][A-Z0-9]{0,9}$/D', $value) ? $value : 'ZD';
}
function page_banner_defaults()
{
    return [
        'home' => ['label' => 'หน้าแรก', 'eyebrow' => 'YOUR JOURNEY. SHARED MEMORIES.', 'title' => "โลกกว้างแค่ไหน\nก็ไปได้ด้วยกัน", 'description' => 'ค้นพบทัวร์ต่างประเทศและแพ็คเกจส่วนตัว เลือกการเดินทางที่ใช่ในแบบของคุณ'],
        'flight' => ['label' => 'ทัวร์รวมตั๋ว', 'eyebrow' => 'FLIGHT INCLUDED', 'title' => 'ทัวร์รวมตั๋ว', 'description' => 'ราคาเดียวรวมตั๋วเครื่องบิน เลือกเส้นทาง เลือกรอบ แล้วเตรียมกระเป๋าได้เลย'],
        'land' => ['label' => 'ทัวร์ไม่รวมตั๋ว', 'eyebrow' => 'LAND ONLY', 'title' => 'ทัวร์ไม่รวมตั๋ว', 'description' => 'จองตั๋วเองตามสะดวก แล้วมาเจอกันที่จุดนัดพบ เราดูแลส่วนที่เหลือของทริปให้', 'image' => 'public/images/story.jpg'],
        'private' => ['label' => 'แพ็คเกจส่วนตัว', 'eyebrow' => 'YOUR PEOPLE. YOUR PRIVATE TRIP.', 'title' => 'แพ็คเกจส่วนตัว', 'description' => 'เดินทางกับคนของคุณ เลือกวันและระดับบริการ พร้อมราคาตามจำนวนผู้เดินทาง', 'image' => 'public/images/khaoyai.jpg'],
        'fixed' => ['label' => 'ทัวร์ต่างประเทศ', 'eyebrow' => 'EXPLORE THE WORLD', 'title' => 'ทัวร์ต่างประเทศ', 'description' => 'เลือกจุดหมายและรอบเดินทางที่ใช่ จองได้ตั้งแต่ 1 คน แล้วออกไปสร้างความทรงจำใหม่'],
    ];
}
// The three ways a program is sold. "fixed" (/tours) stays as the combined group-tour listing outside this list.
function program_channels()
{
    return [
        'flight' => ['label' => 'ทัวร์รวมตั๋ว', 'eyebrow' => 'FLIGHT INCLUDED', 'badge' => 'รวมตั๋ว', 'path' => 'tours-with-flight', 'list_title' => 'โปรแกรมรวมตั๋ว', 'blurb' => 'ราคาเดียวรวมตั๋วเครื่องบิน'],
        'land' => ['label' => 'ทัวร์ไม่รวมตั๋ว', 'eyebrow' => 'LAND TOUR', 'badge' => 'ไม่รวมตั๋ว', 'path' => 'land-tours', 'list_title' => 'โปรแกรมไม่รวมตั๋ว', 'blurb' => 'จองตั๋วเอง แล้วไปเจอกันที่จุดนัดพบ'],
        'private' => ['label' => 'แพ็คเกจส่วนตัว', 'eyebrow' => 'PRIVATE PACKAGE', 'badge' => 'ทริปส่วนตัว', 'path' => 'packages', 'list_title' => 'แพ็คเกจส่วนตัว', 'blurb' => 'เฉพาะกลุ่มของคุณ เลือกวันเดินทางเอง'],
    ];
}
function tour_channel($tour)
{
    if (($tour['program_type'] ?? 'fixed') === 'private') {
        return 'private';
    }

    return ! empty($tour['flight_included']) ? 'flight' : 'land';
}
function flight_note($tour)
{
    if (! empty($tour['flight_included'])) {
        return 'รวมตั๋วเครื่องบินแล้ว';
    }

    return ! empty($tour['flight_summary']['has_included']) ? 'ราคาไม่รวมตั๋วระหว่างประเทศ · รวมตั๋วภายใน' : 'ราคาไม่รวมตั๋วเครื่องบิน';
}
function flight_leg_types()
{
    return ['outbound' => 'ขาไป', 'domestic' => 'ตั๋วภายใน', 'return' => 'ขากลับ'];
}
// Logos sit on a white plate because most are drawn for light backgrounds; without one, the code stands in.
function airline_logo($path, $name, $code = '', $size = '')
{
    $class = 'airline-logo'.($size ? ' airline-logo-'.$size : '');
    if ($path) {
        return '<span class="'.$class.'"><img src="'.e(base_url().$path).'" alt="'.e($name).'" width="84" height="28" loading="lazy"></span>';
    }

    return '<span class="'.$class.' airline-logo-text" title="'.e($name).'">'.e($code ?: $name).'</span>';
}
function flight_duration($minutes)
{
    if (! $minutes) {
        return '';
    }
    $hours = (int) floor($minutes / 60);
    $rest = (int) $minutes % 60;

    return trim(($hours ? $hours.' ชม.' : '').($rest ? ' '.$rest.' น.' : ''));
}
function private_lowest_rate($periods)
{
    $lowest = null;
    foreach ($periods as $period) {
        foreach (json_decode($period['private_rates'] ?? '[]', true) ?: [] as $rate) {
            if ($lowest === null || (float) $rate['price'] < (float) $lowest['price']) {
                $lowest = $rate;
            }
        }
    }

    return $lowest;
}
function text_lines($text)
{
    $lines = [];
    foreach (preg_split('/\R/u', (string) $text) as $line) {
        $line = trim(preg_replace('/^\s*[-–•*]\s+/u', '', $line));
        if ($line !== '') {
            $lines[] = $line;
        }
    }

    return $lines;
}
function text_excerpt($text, $limit = 155)
{
    $text = trim(preg_replace('/\s+/u', ' ', (string) $text));

    return mb_strlen($text) > $limit ? rtrim(mb_substr($text, 0, $limit - 1)).'…' : $text;
}
function people_range($min, $max)
{
    return (int) $min === (int) $max ? (string) (int) $min : (int) $min.'–'.(int) $max;
}
// The detail page already prints DAY 01, so a repeated "DAY 1" or "วันที่ 1" in the title is dropped.
function day_title($title)
{
    return preg_replace('/^(?:day|วันที่)\s*\d+\s*[:.\-–—]?\s*(?=\S)/iu', '', trim((string) $title));
}
// The bundled certificate only ever shows with its own number, never beside a different licence.
function tour_license($settings)
{
    $number = trim($settings['license_number'] ?? '') ?: '11/08038';

    return ['number' => $number, 'holder' => trim($settings['license_holder'] ?? '') ?: 'บริษัท ทราเวลซี้ด จำกัด', 'image' => branding_path($settings, 'license_image') ?: ($number === '11/08038' ? 'public/images/tour-license.png' : '')];
}
function tour_availability($tour)
{
    $isPrivate = ($tour['program_type'] ?? 'fixed') === 'private';
    $available = [];
    foreach ($tour['departures'] as $dep) {
        if ($dep['active'] && ($isPrivate ? \App\Support\PrivatePackage::first_date($dep) !== null : $dep['start_date'] >= date('Y-m-d')) && $dep['price_per_person'] > 0) {
            $available[] = $dep;
        }
    }
    $bookable = array_values(array_filter($available, function ($dep) use ($isPrivate) {
        return $isPrivate || $dep['seats_available'] > 0;
    }));
    $prices = [];
    $groupSizes = [];
    foreach ($bookable as $dep) {
        if (! $isPrivate) {
            $prices[] = (float) $dep['price_per_person'];

            continue;
        }
        foreach (json_decode($dep['private_rates'], true) ?: [] as $rate) {
            if ((float) $rate['price'] > 0) {
                $prices[] = (float) $rate['price'];
                array_push($groupSizes, (int) $rate['min'], (int) $rate['max']);
            }
        }
    }

    return ['available' => $available, 'bookable' => $bookable, 'prices' => $prices, 'group_size' => $groupSizes ? people_range(min($groupSizes), max($groupSizes)) : null];
}
function page_banner($settings, $type)
{
    $banner = page_banner_defaults()[$type];
    foreach (['title', 'description'] as $field) {
        if (trim($settings['banner_'.$type.'_'.$field] ?? '') !== '') {
            $banner[$field] = $settings['banner_'.$type.'_'.$field];
        }
    }
    $banner['desktop'] = branding_path($settings, 'banner_'.$type.'_desktop') ?: ($banner['image'] ?? 'public/images/hero.jpg');
    $banner['mobile'] = branding_path($settings, 'banner_'.$type.'_mobile') ?: $banner['desktop'];

    return $banner;
}
function seo_default_description()
{
    return 'ทริปส่วนตัวสำหรับกรุ๊ปของคุณ เลือกโปรแกรม วันเดินทาง และจำนวนผู้เดินทาง พร้อมไซส์กรุ๊ปและที่นั่งว่าง พร้อมโรงแรม รถ ไกด์ และบริการถ่ายภาพตามโปรแกรม';
}
// Public pages without a database record of their own; their meta tags are edited at /admin/seo.
// Pages sharing a key with page_banner_defaults() fall back to their banner, the rest to the values here.
function seo_pages()
{
    return [
        'home' => ['label' => 'หน้าแรก', 'path' => ''],
        'fixed' => ['label' => 'ทัวร์ต่างประเทศ', 'path' => 'tours'],
        'flight' => ['label' => 'ทัวร์รวมตั๋ว', 'path' => 'tours-with-flight'],
        'land' => ['label' => 'ทัวร์ไม่รวมตั๋ว', 'path' => 'land-tours'],
        'private' => ['label' => 'แพ็คเกจส่วนตัว', 'path' => 'packages'],
        'gallery' => ['label' => 'แกลเลอรี', 'path' => 'gallery', 'title' => 'Gallery • บันทึกการเดินทาง · RoamVenture', 'description' => seo_default_description(), 'image' => 'public/images/hero.jpg'],
        'blog' => ['label' => 'บทความ', 'path' => 'blog', 'title' => 'Blog · เรื่องเล่าระหว่างทาง · RoamVenture', 'description' => 'แรงบันดาลใจ จุดหมาย และเคล็ดลับเตรียมตัวออกเดินทางจาก RoamVenture', 'image' => 'public/images/hero.jpg'],
        'about' => ['label' => 'เกี่ยวกับเรา', 'path' => 'aboutus', 'title' => 'เกี่ยวกับเรา | รู้จัก RoamVenture และรูปแบบการเดินทาง', 'description' => 'รู้จัก RoamVenture ค้นหาทัวร์และแพ็คเกจส่วนตัว เลือกวันเดินทาง จำนวนผู้เดินทาง และบริการที่เหมาะกับคุณ พร้อมขั้นตอนการจองและคำตอบก่อนออกเดินทาง', 'image' => 'public/images/story.jpg'],
        'contact' => ['label' => 'ติดต่อเรา', 'path' => 'contactus', 'title' => 'ติดต่อเรา | สอบถามทัวร์และแพ็คเกจส่วนตัว RoamVenture', 'description' => 'ติดต่อ RoamVenture เพื่อสอบถามทัวร์ แพ็คเกจส่วนตัว และคำขอจอง ดูช่องทางติดต่อ พร้อมเครื่องมือเตรียมข้อความวางแผนทริปผ่าน LINE หรืออีเมล', 'image' => 'public/images/hero.jpg'],
        'privacy' => ['label' => 'นโยบายข้อมูล', 'path' => 'privacy', 'title' => 'การใช้ข้อมูลสำหรับคำขอจอง · RoamVenture', 'description' => seo_default_description(), 'image' => 'public/images/hero.jpg'],
    ];
}
function seo_page_defaults($settings, $page)
{
    if (! isset(page_banner_defaults()[$page])) {
        return array_intersect_key(seo_pages()[$page], array_flip(['title', 'description', 'image'])) + ['keywords' => ''];
    }
    $banner = page_banner($settings, $page);
    if ($page === 'home') {
        $banner = home_hero_slides($settings, true)[0] ?? $banner;
    }

    return ['title' => str_replace(["\r", "\n"], ' ', $banner['title']).' | RoamVenture', 'description' => $banner['description'], 'image' => $banner['desktop'], 'keywords' => ''];
}
function seo_page_meta($settings, $page)
{
    $meta = seo_page_defaults($settings, $page);
    foreach (['title', 'description', 'keywords'] as $field) {
        $custom = trim($settings['seo_'.$page.'_'.$field] ?? '');
        if ($custom !== '') {
            $meta[$field] = $custom;
        }
    }
    $image = branding_path($settings, 'seo_'.$page.'_image');
    $meta['image'] = base_url().($image ?: $meta['image']);
    if ($image) {
        $meta['image_size'] = [\App\Support\ShareImage::WIDTH, \App\Support\ShareImage::HEIGHT];
    }

    return $meta;
}
function home_hero_slides($settings, $active_only = false)
{
    $slides = json_decode($settings['home_hero_slides'] ?? 'null', true);
    if (! is_array($slides)) {
        $slides = [array_merge(page_banner($settings, 'home'), ['id' => 'original', 'tablet' => '', 'enabled' => 1, 'sort' => 10])];
    }
    usort($slides, function ($a, $b) {
        return ($a['sort'] ?? 0) <=> ($b['sort'] ?? 0);
    });
    if (! $active_only) {
        return $slides;
    }
    $slides = array_values(array_filter($slides, function ($s) {
        return ! empty($s['enabled']);
    }));
    foreach ($slides as &$slide) {
        foreach (['desktop', 'tablet', 'mobile'] as $device) {
            $slide[$device] = ($slide[$device] ?? '') === 'public/images/hero.jpg' ? 'public/images/hero.jpg' : branding_path($slide, $device);
        }
        $slide['desktop'] = $slide['desktop'] ?: 'public/images/hero.jpg';
        $slide['tablet'] = $slide['tablet'] ?: $slide['desktop'];
        $slide['mobile'] = $slide['mobile'] ?: $slide['tablet'];
    }
    unset($slide);

    return $slides;
}
function branding_slots()
{
    $slots = [];
    foreach (['desktop' => 'Desktop', 'tablet' => 'Tablet', 'mobile' => 'Mobile'] as $device => $name) {
        $slots['logo_navbar_'.$device] = 'โลโก้เว็บไซต์ — '.$name;
    }
    $slots['favicon'] = 'Favicon';
    $slots['license_image'] = 'รูปใบอนุญาต';

    return $slots;
}
function branding_path($settings, $key)
{
    $path = $settings[$key] ?? '';

    return is_string($path) && preg_match('~^public/uploads/[a-f0-9]+\.(png|jpg|jpeg|webp|ico)$~D', $path) && is_file(base_path($path)) ? $path : '';
}
function brand_logo($settings, $place)
{
    $document = in_array($place, ['invoice', 'receipt'], true);
    $paths = [];
    foreach (['desktop', 'tablet', 'mobile'] as $device) {
        $paths[$device] = branding_path($settings, 'logo_navbar_'.$device);
    }
    $default = $document ? $paths['desktop'] : ($paths['desktop'] ?: ($paths['tablet'] ?: $paths['mobile']));
    if (! $default) {
        return 'Roam<span>Venture</span><b aria-hidden="true">↗</b>';
    }
    $alt = e(($settings['company_name'] ?? '') ?: 'RoamVenture');
    $html = '<picture class="brand-logo brand-logo--'.e($place).'">';
    $html .= '<source media="print" srcset="'.e(base_url().$default).'">';
    if (! $document) {
        foreach (['mobile' => 767, 'tablet' => 1023] as $device => $width) {
            $html .= '<source media="(max-width: '.$width.'px)" srcset="'.e(base_url().($paths[$device] ?: $default)).'">';
        }
    }

    return $html.'<img src="'.e(base_url().$default).'" alt="'.$alt.'"></picture>';
}
function money($value)
{
    $value = (float) $value;

    return number_format($value, abs($value - round($value)) < 0.00001 ? 0 : 2);
}
function thai_date($date)
{
    if (! $date) {
        return 'ยังไม่กำหนด';
    } $months = ['', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
    $t = strtotime($date);

    return date('j', $t).' '.$months[(int) date('n', $t)].' '.(date('Y', $t) + 543);
}
// Thai text has no word spaces, so reading time is estimated from visible characters (~900 per minute).
function reading_minutes($html)
{
    $text = preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags((string) $html), ENT_QUOTES, 'UTF-8'));

    return max(1, (int) ceil(mb_strlen(trim($text)) / 900));
}
function tour_image($path)
{
    return base_url().($path ?: 'public/images/hero.jpg');
}
function status_label($status)
{
    return ['draft' => 'ฉบับร่าง', 'published' => 'เผยแพร่', 'archived' => 'เก็บถาวร', 'new' => 'คำขอใหม่', 'contacted' => 'ติดต่อแล้ว', 'confirmed' => 'ยืนยันแล้ว', 'cancelled' => 'ยกเลิก'][$status] ?? $status;
}
function payment_status_label($status)
{
    return ['awaiting_upload' => 'รอแจ้งโอน', 'pending' => 'รอตรวจสลิป', 'approved' => 'รับชำระแล้ว', 'rejected' => 'สลิปไม่ผ่าน', 'expired' => 'หมดเวลา', 'cancelled' => 'ยกเลิกรายการชำระ'][$status] ?? $status;
}
function service_label($value)
{
    return ['included' => 'รวมในราคา', 'extra' => 'มีค่าใช้จ่ายเพิ่ม', 'none' => 'ไม่รวม'][$value] ?? 'ไม่ระบุ';
}
/**
 * Sorts a tour's price coverage and terms for the detail page. A service with no status
 * stays apart from both price lists, because it must not read as included or excluded.
 */
function tour_coverage($tour)
{
    $services = ['included' => [], 'excluded' => [], 'unspecified' => []];
    foreach (['hotel' => 'โรงแรม / ที่พัก', 'transport' => 'รถและการเดินทาง', 'guide' => 'ไกด์', 'photographer' => 'ช่างภาพ'] as $key => $label) {
        $status = $tour[$key.'_status'];
        $details = trim((string) $tour[$key.'_details']);
        if ($status === null && $details === '') {
            continue;
        }
        $group = $status === null ? 'unspecified' : ($status === 'included' ? 'included' : 'excluded');
        $services[$group][] = ['label' => $label, 'status' => $status, 'details' => $details];
    }
    $policies = array_filter(['การจองและการชำระเงิน' => text_lines($tour['payment_terms'] ?? ''), 'การยกเลิกและการคืนเงิน' => text_lines($tour['cancellation_terms'] ?? '')]);
    $terms = text_lines($tour['terms'] ?? '');

    return [
        'included_lines' => text_lines($tour['inclusions'] ?? ''),
        'included_services' => $services['included'],
        'excluded_lines' => text_lines($tour['exclusions'] ?? ''),
        'excluded_services' => $services['excluded'],
        'unspecified_services' => $services['unspecified'],
        'policies' => $policies,
        'terms' => $terms,
        'has_terms' => $policies || $terms,
    ];
}
function room_label($value)
{
    return ['twin' => 'พักคู่ · 2 เตียงเดี่ยว', 'double' => 'พักคู่ · 1 เตียงใหญ่', 'triple' => 'ห้องพัก 3 คน', 'single' => 'พักเดี่ยว · 1 คน/ห้อง', 'none' => 'ไม่มีที่พักในโปรแกรม'][$value] ?? 'ไม่ได้ระบุ (คำขอเดิม)';
}
function booking_rooms_label($booking)
{
    if (! isset($booking['twin_rooms'])) {
        return room_label($booking['room_type']);
    }
    $parts = [];
    foreach (['twin' => 'ห้องคู่ 2 เตียงเดี่ยว', 'double' => 'ห้องคู่ 1 เตียงใหญ่', 'triple' => 'ห้องพัก 3 คน', 'single' => 'ห้องเดี่ยว'] as $type => $label) {
        if (($booking[$type.'_rooms'] ?? 0) > 0) {
            $parts[] = $label.' '.(int) $booking[$type.'_rooms'].' ห้อง';
        }
    }

    return $parts ? implode(' / ', $parts) : room_label('none');
}

function contact_social_fields()
{
    return ['line_url' => 'LINE', 'facebook_url' => 'Facebook', 'messenger_url' => 'Messenger', 'instagram_url' => 'Instagram', 'tiktok_url' => 'TikTok'];
}
// Official LINE IDs (@...) accept a prefilled message; personal IDs can only open the profile.
function tour_line_url($lineId, $message)
{
    return strpos($lineId, '@') === 0
        ? 'https://line.me/R/oaMessage/'.rawurlencode($lineId).'/?'.rawurlencode($message)
        : 'https://line.me/R/ti/p/'.rawurlencode($lineId);
}
function contact_https_url($value)
{
    if (! is_string($value) || ! filter_var($value, FILTER_VALIDATE_URL)) {
        return '';
    }
    $p = parse_url($value);

    return ($p['scheme'] ?? '') === 'https' && empty($p['user']) && empty($p['pass']) ? $value : '';
}
function contact_map_embed($value)
{
    if (! is_string($value)) {
        return '';
    }
    $value = trim($value);
    if (preg_match('~<iframe\b[^>]*\bsrc\s*=\s*([\x22\x27])(.*?)\1~is', $value, $m)) {
        $value = html_entity_decode($m[2], ENT_QUOTES, 'UTF-8');
    }
    if (! contact_https_url($value)) {
        return '';
    }
    $p = parse_url($value);

    return in_array(strtolower($p['host']), ['www.google.com', 'maps.google.com', 'www.google.co.th'], true) && preg_match('~^/maps/embed(?:/|$)~', $p['path'] ?? '') && empty($p['port']) ? $value : '';
}
function contact_sales($value)
{
    $rows = json_decode(is_string($value) ? $value : '', true);
    if (! is_array($rows)) {
        return [];
    }

    return array_values(array_filter($rows, function ($row) {
        return is_array($row) && is_string($row['name'] ?? null) && is_string($row['phone'] ?? null) && trim($row['name']) !== '' && preg_match('/^\+?[0-9][0-9 ()-]{5,29}$/D', $row['phone']);
    }));
}

function tracking_fields()
{
    return [
        'tracking_gtm_id' => ['label' => 'Google Tag Manager Container ID', 'pattern' => '/^GTM-[A-Z0-9]{4,20}$/D', 'placeholder' => 'GTM-XXXXXXX'],
        'tracking_meta_id' => ['label' => 'Meta / Facebook Pixel ID', 'pattern' => '/^[0-9]{5,25}$/D', 'placeholder' => 'ตัวเลข Pixel ID'],
        'tracking_line_id' => ['label' => 'LINE Ads Tag ID', 'pattern' => '/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/iD', 'placeholder' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx'],
    ];
}

function tracking_config($settings)
{
    $mode = $settings['tracking_mode'] ?? 'off';
    if (! in_array($mode, ['off', 'gtm', 'direct'], true)) {
        $mode = 'off';
    }
    $ids = [];
    foreach (tracking_fields() as $key => $field) {
        $value = $settings[$key] ?? '';
        $ids[$key] = is_string($value) && preg_match($field['pattern'], $value) ? $value : '';
    }

    return ['mode' => $mode, 'enabled' => ($mode === 'gtm' && $ids['tracking_gtm_id'] !== '') || ($mode === 'direct' && ($ids['tracking_meta_id'] !== '' || $ids['tracking_line_id'] !== '')),
        'gtm_id' => $mode === 'gtm' ? $ids['tracking_gtm_id'] : '',
        'meta_id' => $mode === 'direct' ? $ids['tracking_meta_id'] : '',
        'line_id' => $mode === 'direct' ? $ids['tracking_line_id'] : ''];
}

function tracking_context($settings, $view, $data, $controller)
{
    $config = tracking_config($settings);
    $controller = strtolower($controller);
    $private = ! empty($data['admin_layout']) || ! empty($data['preview']) || ! empty($data['blog_preview'])
     || in_array($controller, ['admin', 'booking', 'passports'], true) || substr($controller, -6) === '_admin'
     || strpos($view, 'booking/') === 0;
    // The success screen contains no booking token, customer fields or reference.
    if ($view === 'tracking_success' && $controller === 'home') {
        $private = false;
    }
    if (! $config['enabled'] || $private) {
        return null;
    }
    $context = ['page_type' => $view === 'detail' ? 'tour' : (in_array($view, ['home', 'tours', 'destination', 'tracking_success'], true) ? $view : 'content')];
    if ($view === 'detail' && ! empty($data['tour'])) {
        $tour = $data['tour'];
        $context['items'] = [['item_id' => (string) $tour['code'], 'item_name' => $tour['title'], 'item_category' => $tour['program_type'] ?? 'fixed']];
    }
    $config['context'] = $context;
    if ($view === 'tracking_success' && ! empty($data['tracking_lead'])) {
        $config['lead'] = $data['tracking_lead'];
    }

    return $config;
}
