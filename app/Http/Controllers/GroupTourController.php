<?php

namespace App\Http\Controllers;

use App\Repositories\GroupTourRepository as Groups;
use App\Support\CustomerMail;
use App\Support\DiscordNotify;
use App\Support\Recaptcha;

/** Public charter-group page (/grouptour): service overview, past groups and the request form. */
class GroupTourController extends Controller
{
    public function index()
    {
        $trips = $this->group_tours->trips();
        $brand = $this->brand();
        $url = base_url().'grouptour';
        $meta = seo_page_meta($this->settings, 'grouptour') + ['canonical' => $url];
        $faqs = $this->faqs($brand);
        $old = session('group_old') ?: [];
        // "อยากได้ทริปแบบนี้" on a past group opens the form already pointed at that trip.
        $ref = request()->query('ref');
        $refTrip = is_string($ref) && $ref !== '' && mb_strlen($ref) <= 180 ? $this->group_tours->find_trip_slug($ref) : null;
        if ($refTrip && ! $old) {
            $old = ['destination' => $refTrip['destination'], 'group_type' => $refTrip['group_type'], 'source' => 'grouptour/'.$refTrip['slug']];
        }
        $license = tour_license($this->settings);

        return $this->render('grouptour/index', [
            'title' => $meta['title'],
            'company_page' => 'grouptour',
            'group_tour_page' => true,
            'brand' => $brand,
            'trips' => $trips,
            'stats' => $this->group_tours->stats(),
            'faqs' => $faqs,
            'old' => $old,
            'ref_trip' => $refTrip,
            'contact' => $this->contact(),
            'license' => $license,
            'recaptcha_key' => app(Recaptcha::class)->site_key(),
            'meta' => $meta,
            'structured_data' => $this->index_schema($url, $meta, $brand, $faqs, $trips, $license),
        ]);
    }

    public function show($slug)
    {
        $trip = is_string($slug) && mb_strlen($slug) <= 180 ? $this->group_tours->find_trip_slug($slug) : null;
        if (! $trip) {
            show_404();
        }
        $url = base_url().'grouptour/'.rawurlencode($trip['slug']);
        $typeLabel = Groups::group_types()[$trip['group_type']] ?? 'กรุ๊ปเหมา';
        $description = $trip['seo_description'] ?: text_excerpt($trip['summary'] ?: $trip['story'] ?: $trip['title'].' กรุ๊ปเหมา'.$trip['destination']);
        $meta = [
            'title' => ($trip['seo_title'] ?: $trip['title'].' | ผลงานกรุ๊ปเหมา').' · '.$this->brand(),
            'description' => $description,
            'image' => base_url().$trip['image'],
            'canonical' => $url,
        ];
        $others = array_values(array_filter($this->group_tours->trips(false, 4), fn ($t) => (int) $t['id'] !== (int) $trip['id']));
        $trip_schema = ['@type' => 'TouristTrip', '@id' => $url.'#trip', 'name' => $trip['title'], 'description' => $description, 'url' => $url, 'image' => base_url().$trip['image'], 'touristType' => $typeLabel, 'provider' => ['@id' => base_url().'#organization'], 'itinerary' => ['@type' => 'Place', 'name' => $trip['destination']]];
        $schema = ['@context' => 'https://schema.org', '@graph' => [
            $this->organization($this->brand()),
            $trip_schema,
            $this->breadcrumb($url, [['กรุ๊ปเหมา', base_url().'grouptour'], [$trip['title'], $url]]),
        ]];

        return $this->render('grouptour/show', [
            'title' => $meta['title'],
            'company_page' => 'grouptour',
            'group_tour_page' => true,
            'trip' => $trip,
            'type_label' => $typeLabel,
            'photos' => $this->group_tours->album_photos($trip),
            'others' => array_slice($others, 0, 3),
            'meta' => $meta,
            'structured_data' => $schema,
        ]);
    }

    public function submit()
    {
        $this->post_only();
        foreach (['contact_name', 'organization', 'phone', 'email', 'line_id', 'group_type', 'destination', 'date_from', 'date_to', 'date_note', 'days', 'adults', 'children', 'budget', 'hotel_level', 'message', 'consent', 'website', 'source', 'recaptcha_token'] as $field) {
            if (is_array(request()->post($field))) {
                show_error('ข้อมูลแบบฟอร์มไม่ถูกต้อง', 400);
            }
        }
        $errors = validate_post([
            'contact_name' => ['ชื่อผู้ติดต่อ', 'required|string|max:120'],
            'organization' => ['ชื่อองค์กร / กลุ่ม', 'nullable|string|max:180'],
            'phone' => ['เบอร์โทรศัพท์', ['required', 'string', 'regex:/^[0-9+() -]{8,25}$/']],
            'email' => ['อีเมล', 'bail|nullable|string|email:filter|max:190'],
            'line_id' => ['LINE ID', 'nullable|string|max:100'],
            'group_type' => ['ประเภทกรุ๊ป', ['required', 'string', 'in:'.implode(',', array_keys(Groups::group_types()))]],
            'destination' => ['จุดหมาย', 'required|string|max:200'],
            'date_from' => ['วันเริ่มเดินทาง', 'nullable|date_format:Y-m-d|after:today'],
            'date_to' => ['วันกลับ', 'nullable|date_format:Y-m-d|after_or_equal:date_from'],
            'date_note' => ['ช่วงเวลาเดินทาง', 'nullable|string|max:120'],
            'days' => ['จำนวนวัน', 'nullable|integer|min:1|max:60'],
            'adults' => ['จำนวนผู้ใหญ่', 'required|integer|min:1|max:999'],
            'children' => ['จำนวนเด็ก', 'nullable|integer|min:0|max:999'],
            'budget' => ['งบประมาณ', ['nullable', 'string', 'in:'.implode(',', array_keys(Groups::budgets()))]],
            'hotel_level' => ['ระดับที่พัก', ['nullable', 'string', 'in:'.implode(',', array_keys(Groups::hotel_levels()))]],
            'message' => ['รายละเอียดเพิ่มเติม', 'nullable|string|max:3000'],
            'consent' => ['การรับทราบนโยบายข้อมูล', 'required|in:1'],
        ]);
        $services = request()->post('services');
        $services = is_array($services) ? array_values(array_intersect(array_keys(Groups::services()), $services)) : [];
        $error = $errors === '' ? '' : strip_tags(str_replace('</p>', ' ', $errors));
        if ($error === '' && request()->post('date_to') && ! request()->post('date_from')) {
            $error = 'กรุณาระบุวันเริ่มเดินทางคู่กับวันกลับ';
        }
        if (request()->post('website')) {
            $error = 'ไม่สามารถส่งคำขอได้';
        }
        if (session('last_group_request_at') > time() - 60) {
            $error = 'กรุณารอ 1 นาทีก่อนส่งคำขอใหม่';
        }
        // Checked last so Google is only asked about requests that would otherwise go through.
        if ($error === '' && ! app(Recaptcha::class)->passes(request()->post('recaptcha_token'), 'grouptour')) {
            $error = 'ระบบตรวจสอบความปลอดภัยไม่ผ่าน กรุณาลองส่งอีกครั้ง หรือติดต่อทีมงานทาง LINE';
        }
        $source = (string) request()->post('source');
        $source = preg_match('~^grouptour(?:/[\p{L}\p{M}\p{N}-]{1,180})?$~uD', $source) ? $source : 'grouptour';
        if ($error !== '') {
            $keep = ['contact_name', 'organization', 'phone', 'email', 'line_id', 'group_type', 'destination', 'date_from', 'date_to', 'date_note', 'days', 'adults', 'children', 'budget', 'hotel_level', 'message'];
            session()->flash('error', $error);
            session()->flash('group_old', array_intersect_key(request()->post(), array_flip($keep)) + ['services' => $services, 'source' => $source]);
            redirect_to(base_url().'grouptour#request');
        }
        $text = fn ($key) => trim((string) request()->post($key));
        $fields = [
            'contact_name' => $text('contact_name'),
            'organization' => $text('organization'),
            'phone' => $text('phone'),
            'email' => $text('email'),
            'line_id' => $text('line_id'),
            'group_type' => $text('group_type'),
            'destination' => $text('destination'),
            'date_from' => $text('date_from') ?: null,
            'date_to' => $text('date_to') ?: null,
            'date_note' => $text('date_note'),
            'days' => $text('days') !== '' ? (int) $text('days') : null,
            'adults' => (int) $text('adults'),
            'children' => (int) $text('children'),
            'budget' => $text('budget'),
            'hotel_level' => $text('hotel_level'),
            'services' => implode(',', $services),
            'message' => $text('message'),
            'source' => $source,
        ];
        $request = $this->group_tours->create_request($fields, code_prefix($this->settings));
        if (! $request) {
            session()->flash('error', 'บันทึกคำขอไม่สำเร็จ กรุณาลองใหม่ หรือติดต่อทีมงานทาง LINE');
            session()->flash('group_old', $fields + ['services' => $services]);
            redirect_to(base_url().'grouptour#request');
        }
        app(DiscordNotify::class)->new_group_request($request);
        app(CustomerMail::class)->group_request_received($request, $this->settings);
        // No free text goes to ad platforms: only the fixed group type and head count.
        session()->flash('tracking_lead', ['event_id' => bin2hex(random_bytes(16)), 'currency' => 'THB', 'value' => 0, 'items' => [['item_id' => 'grouptour', 'item_name' => 'กรุ๊ปเหมา', 'item_category' => $request['group_type'], 'quantity' => (int) $request['adults'] + (int) $request['children']]]]);
        session()->put(['last_group_request' => $request['reference'], 'last_group_request_at' => time()]);
        redirect_to(base_url().'grouptour/success');
    }

    public function success()
    {
        $ref = session('last_group_request');
        if (! is_string($ref) || $ref === '') {
            redirect_to(base_url().'grouptour');
        }
        set_header('Cache-Control: no-store, private');
        set_header('X-Robots-Tag: noindex, nofollow');
        $contact = $this->contact();

        return $this->render('grouptour/success', [
            'title' => 'ได้รับคำขอจัดกรุ๊ปแล้ว',
            'company_page' => 'grouptour',
            'group_tour_page' => true,
            'reference' => $ref,
            'contact' => $contact,
            'line_message_url' => $contact['line'] ? tour_line_url($contact['line'], 'สวัสดีครับ/ค่ะ ขอติดตามคำขอจัดกรุ๊ปเหมา เลขที่ '.$ref) : '',
            'tracking_lead' => session('tracking_lead'),
            'meta' => ['title' => 'ได้รับคำขอจัดกรุ๊ปแล้ว · '.$this->brand(), 'description' => 'ได้รับคำขอจัดกรุ๊ปเหมาแล้ว', 'canonical' => base_url().'grouptour'],
        ]);
    }

    private function brand(): string
    {
        return trim($this->settings['company_name'] ?? '') ?: 'RoamVenture';
    }

    private function contact(): array
    {
        $line = trim($this->settings['line_id'] ?? '');
        $phone = trim($this->settings['phone'] ?? '');
        $email = trim($this->settings['email'] ?? '');
        $line = preg_match('/^@?[a-zA-Z0-9._-]{1,80}$/D', $line) ? $line : '';

        return [
            'line' => $line,
            'line_url' => $line ? 'https://line.me/R/ti/p/'.rawurlencode($line) : '',
            'phone' => $phone,
            'tel' => preg_replace('/[^+0-9]/', '', $phone),
            'email' => filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : '',
        ];
    }

    /** Question-and-answer pairs shown on the page and repeated in FAQPage markup, so both always match. */
    private function faqs(string $brand): array
    {
        return [
            ['กรุ๊ปเหมาคืออะไร ต่างจากทัวร์จอยกรุ๊ปอย่างไร?', 'กรุ๊ปเหมาคือทริปที่จัดเฉพาะสมาชิกในกลุ่มของคุณ ไม่มีลูกทัวร์อื่นร่วมเดินทาง คุณเลือกวันเดินทาง จุดหมาย และปรับโปรแกรมได้เอง ส่วนทัวร์จอยกรุ๊ปเดินทางตามรอบและโปรแกรมที่บริษัทกำหนด และเดินทางร่วมกับผู้ที่จองรอบเดียวกัน'],
            [$brand.' รับจัดกรุ๊ปเหมาแบบไหนบ้าง?', 'รับจัดกรุ๊ปบริษัทและองค์กร เช่น สัมมนา ทริป Incentive และ outing รวมถึงกรุ๊ปครอบครัว กลุ่มเพื่อน โรงเรียนหรือคณะศึกษาดูงาน สมาคม ชมรม หน่วยงาน และทริปสายบุญ โดยออกแบบโปรแกรมตามจุดหมาย งบประมาณ และจำนวนผู้เดินทางของแต่ละกลุ่ม'],
            ['ราคากรุ๊ปเหมาคิดจากอะไร?', 'ราคาขึ้นกับจุดหมาย จำนวนวัน จำนวนผู้เดินทาง ช่วงเวลาเดินทาง ระดับที่พัก ตั๋วเครื่องบิน รถ ไกด์ มื้ออาหาร และกิจกรรมที่เลือก ทีมงานจะคำนวณจากรายละเอียดที่คุณส่งมาและเสนอราคาต่อคนพร้อมรายการที่รวมและไม่รวมในราคา'],
            ['ควรติดต่อจัดกรุ๊ปล่วงหน้านานเท่าไร?', 'ยิ่งติดต่อเร็วยิ่งมีตัวเลือกตั๋วเครื่องบินและที่พักมากขึ้น ทริปต่างประเทศควรเริ่มคุยอย่างน้อย 2–3 เดือนก่อนเดินทาง และควรเร็วกว่านั้นสำหรับช่วงเทศกาล วันหยุดยาว กรุ๊ปขนาดใหญ่ หรือจุดหมายที่ต้องขอวีซ่า'],
            ['ส่งคำขอจัดกรุ๊ปแล้วเกิดอะไรขึ้นต่อ?', 'ระบบออกเลขที่คำขอให้ทันที ทีมงานจะตรวจรายละเอียดแล้วติดต่อกลับทางโทรศัพท์ LINE หรืออีเมลที่คุณให้ไว้ เพื่อสอบถามข้อมูลเพิ่มเติมและส่งร่างโปรแกรมพร้อมใบเสนอราคา การส่งคำขอยังไม่ใช่การจองและยังไม่มีค่าใช้จ่าย'],
            ['ปรับโปรแกรมกรุ๊ปเหมาได้แค่ไหน?', 'ปรับได้ทั้งจุดเที่ยว จำนวนวัน ระดับที่พัก ร้านอาหาร และกิจกรรม เช่น ห้องประชุมสัมมนา งานเลี้ยง หรือ Team building ภายใต้เงื่อนไขของผู้ให้บริการแต่ละรายและเวลาที่ใช้เดินทางจริง ทีมงานจะแจ้งหากส่วนใดปรับไม่ได้หรือมีค่าใช้จ่ายเพิ่ม'],
            ['ต้องเตรียมข้อมูลอะไรก่อนขอใบเสนอราคา?', 'เตรียมจุดหมายที่สนใจ ช่วงวันเดินทาง จำนวนผู้ใหญ่และเด็ก งบประมาณต่อคนโดยประมาณ ระดับที่พัก และบริการที่ต้องการ เช่น ตั๋วเครื่องบิน รถ ไกด์ หรือห้องประชุม ถ้ายังไม่แน่ใจบางข้อ ส่งเท่าที่มีได้ ทีมงานจะช่วยแนะนำ'],
            [$brand.' มีใบอนุญาตประกอบธุรกิจนำเที่ยวหรือไม่?', 'มี เลขที่ใบอนุญาตประกอบธุรกิจนำเที่ยวแสดงอยู่ท้ายทุกหน้าของเว็บไซต์ และในหัวข้อความน่าเชื่อถือของหน้านี้ คุณตรวจสอบเลขใบอนุญาตกับกรมการท่องเที่ยวได้ก่อนตัดสินใจ'],
        ];
    }

    private function organization(string $brand): array
    {
        $org = ['@type' => 'Organization', '@id' => base_url().'#organization', 'name' => $brand, 'url' => base_url()];
        $contact = $this->contact();
        if ($contact['tel']) {
            $org['telephone'] = $contact['tel'];
        }
        if ($contact['email']) {
            $org['email'] = $contact['email'];
        }
        $logo = branding_path($this->settings, 'logo_navbar_desktop');
        if ($logo) {
            $org['logo'] = base_url().$logo;
        }

        return $org;
    }

    private function breadcrumb(string $url, array $items): array
    {
        $list = [['@type' => 'ListItem', 'position' => 1, 'name' => 'หน้าแรก', 'item' => base_url()]];
        foreach ($items as $i => $item) {
            $list[] = ['@type' => 'ListItem', 'position' => $i + 2, 'name' => $item[0], 'item' => $item[1]];
        }

        return ['@type' => 'BreadcrumbList', '@id' => $url.'#breadcrumb', 'itemListElement' => $list];
    }

    private function index_schema(string $url, array $meta, string $brand, array $faqs, array $trips, array $license): array
    {
        $org = $this->organization($brand);
        if ($license['number'] ?? '') {
            $org['hasCredential'] = ['@type' => 'EducationalOccupationalCredential', 'credentialCategory' => 'ใบอนุญาตประกอบธุรกิจนำเที่ยว', 'name' => 'ใบอนุญาตประกอบธุรกิจนำเที่ยว เลขที่ '.$license['number']];
        }
        $service = [
            '@type' => 'Service', '@id' => $url.'#service',
            'name' => 'รับจัดกรุ๊ปเหมา ทัวร์ส่วนตัวสำหรับองค์กรและหมู่คณะ',
            'serviceType' => 'Private group tour / กรุ๊ปเหมา',
            'description' => $meta['description'],
            'url' => $url,
            'provider' => ['@id' => base_url().'#organization'],
            'audience' => array_map(fn ($label) => ['@type' => 'Audience', 'audienceType' => $label], array_values(array_diff_key(Groups::group_types(), ['other' => true]))),
            'hasOfferCatalog' => ['@type' => 'OfferCatalog', 'name' => 'บริการที่จัดให้กรุ๊ปเหมาได้', 'itemListElement' => array_map(fn ($label) => ['@type' => 'Offer', 'itemOffered' => ['@type' => 'Service', 'name' => $label]], array_values(Groups::services()))],
        ];
        $graph = [
            $org,
            ['@type' => 'WebPage', '@id' => $url.'#webpage', 'url' => $url, 'name' => $meta['title'], 'description' => $meta['description'], 'inLanguage' => 'th', 'about' => ['@id' => $url.'#service'], 'mainEntity' => ['@id' => $url.'#service'], 'publisher' => ['@id' => base_url().'#organization'], 'breadcrumb' => ['@id' => $url.'#breadcrumb']],
            $service,
            $this->breadcrumb($url, [['รับจัดกรุ๊ปเหมา', $url]]),
            ['@type' => 'FAQPage', '@id' => $url.'#faq', 'isPartOf' => ['@id' => $url.'#webpage'], 'mainEntity' => array_map(fn ($faq) => ['@type' => 'Question', 'name' => $faq[0], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $faq[1]]], $faqs)],
        ];
        if ($trips) {
            $graph[] = ['@type' => 'ItemList', '@id' => $url.'#past-groups', 'name' => 'ผลงานกรุ๊ปเหมาที่ผ่านมา', 'numberOfItems' => count($trips), 'itemListElement' => array_map(fn ($trip, $i) => ['@type' => 'ListItem', 'position' => $i + 1, 'url' => base_url().'grouptour/'.rawurlencode($trip['slug']), 'name' => $trip['title']], $trips, array_keys($trips))];
        }

        return ['@context' => 'https://schema.org', '@graph' => $graph];
    }
}
