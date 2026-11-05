<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CompanyController extends Controller
{
    public function about()
    {
        return $this->page('about');
    }

    public function contact()
    {
        return $this->page('contact');
    }

    public function legacy_about()
    {
        redirect_to(base_url().'aboutus', 301);
    }

    public function legacy_contact()
    {
        redirect_to(base_url().'contactus', 301);
    }

    private function page($kind)
    {
        $brand = trim($this->settings['company_name'] ?? '') ?: 'RoamVenture';
        $contact = [
            'line' => trim($this->settings['line_id'] ?? ''),
            'phone' => trim($this->settings['phone'] ?? ''),
            'email' => trim($this->settings['email'] ?? ''),
            'address' => trim($this->settings['company_address'] ?? ''),
        ];
        $contact['tel'] = preg_replace('/[^+0-9]/', '', $contact['phone']);
        if (! filter_var($contact['email'], FILTER_VALIDATE_EMAIL)) {
            $contact['email'] = '';
        }
        if (! preg_match('/^@?[a-zA-Z0-9._-]{1,80}$/D', $contact['line'])) {
            $contact['line'] = '';
        }
        $contact['line_url'] = $contact['line'] ? 'https://line.me/R/ti/p/'.rawurlencode($contact['line']) : '';
        $contact['socials'] = [];
        foreach (contact_social_fields() as $key => $label) {
            $link = contact_https_url(trim($this->settings[$key] ?? ''));
            if ($key === 'line_url') {
                $link = $link ?: $contact['line_url'];
                $contact['line_url'] = $link;
            }
            if ($link) {
                $contact['socials'][] = ['key' => $key, 'label' => $label, 'url' => $link];
            }
        }
        $contact['sales'] = contact_sales($this->settings['contact_sales'] ?? '');
        $contact['hours'] = trim($this->settings['contact_hours'] ?? '');
        $contact['map_embed'] = contact_map_embed($this->settings['contact_map_embed'] ?? '');
        $contact['map_url'] = contact_https_url(trim($this->settings['contact_map_url'] ?? '')) ?: ($contact['address'] ? 'https://www.google.com/maps/search/?api=1&query='.rawurlencode($contact['address']) : '');
        $channels = [];
        if ($contact['line']) {
            $channels[] = 'LINE '.$contact['line'];
        }
        if ($contact['tel']) {
            $channels[] = 'โทรศัพท์ '.$contact['phone'];
        }
        if ($contact['email']) {
            $channels[] = 'อีเมล '.$contact['email'];
        }
        $faqs = $kind === 'about' ? [
            ['RoamVenture คืออะไร?', 'RoamVenture เป็นเว็บไซต์สำหรับค้นหาโปรแกรมทัวร์และแพ็คเกจส่วนตัว เปรียบเทียบรายละเอียด วันเดินทาง และราคาที่แสดงในแต่ละโปรแกรม พร้อมส่งคำขอจองหรือสอบถามทีมงานก่อนตัดสินใจ'],
            ['ทัวร์กับแพ็คเกจส่วนตัวต่างกันอย่างไร?', 'ทัวร์มีรอบวันเดินทางตามโปรแกรม ส่วนแพ็คเกจส่วนตัวให้เลือกวันเดินทาง จำนวนผู้เดินทาง และระดับบริการตามตัวเลือกที่เปิดให้จอง ราคาและบริการที่รวมจะแสดงในหน้าโปรแกรม'],
            ['ราคาแพ็คเกจรวมอะไรบ้าง?', 'บริการที่รวมขึ้นอยู่กับแต่ละโปรแกรม เช่น ที่พัก การเดินทาง ไกด์ หรือบริการถ่ายภาพ ควรตรวจสอบหัวข้อบริการที่รวม รายการไม่รวม และเงื่อนไขก่อนส่งคำขอจอง'],
            ['ส่งคำขอจองแล้วถือว่ายืนยันการเดินทางหรือไม่?', 'การส่งคำขอจองเป็นขั้นตอนเริ่มต้น ทีมงานต้องตรวจสอบรายละเอียดและที่ว่างก่อนยืนยัน โปรดตรวจสอบสถานะคำขอและเงื่อนไขการชำระเงินของรายการนั้น'],
        ] : [
            ['ติดต่อ RoamVenture ได้ทางไหน?', ($channels ? 'ติดต่อได้ทาง '.implode(' หรือ ', $channels).' ' : '').'หรือเลือกโปรแกรมที่สนใจแล้วส่งคำขอจองผ่านหน้าโปรแกรมเพื่อให้ทีมงานติดต่อกลับ'],
            ['ควรเตรียมข้อมูลอะไรเมื่อสอบถามทริป?', 'เตรียมจุดหมายหรือรหัสโปรแกรม ช่วงวันเดินทาง จำนวนผู้เดินทาง และบริการที่ต้องการ หากมีงบประมาณหรือข้อจำกัดการเดินทาง สามารถแจ้งทีมงานเพิ่มเติมได้'],
            ['แบบฟอร์มวางแผนทริปส่งข้อมูลอย่างไร?', 'เครื่องมือนี้ช่วยร่างข้อความบนอุปกรณ์ของคุณ เมื่อเปิด LINE หรืออีเมล คุณต้องกดส่งในแอปนั้นอีกครั้ง ข้อความจะยังไม่ถูกบันทึกเป็นคำขอจองในเว็บไซต์'],
            ['มีคำขอจองอยู่แล้ว ต้องแจ้งอะไรกับทีมงาน?', 'แจ้งหมายเลขคำขอจองและประเด็นที่ต้องการสอบถามผ่านช่องทางติดต่อของเว็บไซต์ เก็บลิงก์รายละเอียดคำขอจองไว้เป็นส่วนตัว และไม่ส่งข้อมูลบัตรชำระเงินในข้อความสอบถาม'],
        ];
        $url = base_url().($kind === 'about' ? 'aboutus' : 'contactus');
        $meta = seo_page_meta($this->settings, $kind) + ['canonical' => $url];
        $title = $meta['title'];
        $description = $meta['description'];
        $org = ['@type' => 'Organization', '@id' => base_url().'#organization', 'name' => $brand, 'url' => base_url(), 'description' => 'เว็บไซต์ค้นหาโปรแกรมทัวร์และแพ็คเกจส่วนตัว'];
        if ($contact['tel']) {
            $org['telephone'] = $contact['tel'];
        }
        if ($contact['email']) {
            $org['email'] = $contact['email'];
        }
        if ($contact['address']) {
            $org['address'] = ['@type' => 'PostalAddress', 'streetAddress' => $contact['address']];
        }
        if ($contact['line_url']) {
            $org['sameAs'] = [$contact['line_url']];
        }
        $logo = branding_path($this->settings, 'logo_navbar_desktop');
        if ($logo) {
            $org['logo'] = base_url().$logo;
        }
        $schema = ['@context' => 'https://schema.org', '@graph' => [
            $org,
            ['@type' => $kind === 'about' ? 'AboutPage' : 'ContactPage', '@id' => $url.'#webpage', 'url' => $url, 'name' => $title, 'description' => $description, 'inLanguage' => 'th', 'about' => ['@id' => base_url().'#organization'], 'publisher' => ['@id' => base_url().'#organization'], 'breadcrumb' => ['@id' => $url.'#breadcrumb']],
            ['@type' => 'BreadcrumbList', '@id' => $url.'#breadcrumb', 'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'หน้าแรก', 'item' => base_url()],
                ['@type' => 'ListItem', 'position' => 2, 'name' => $kind === 'about' ? 'เกี่ยวกับเรา' : 'ติดต่อเรา', 'item' => $url],
            ]],
            ['@type' => 'FAQPage', '@id' => $url.'#faq', 'isPartOf' => ['@id' => $url.'#webpage'], 'mainEntity' => array_map(function ($faq) {
                return ['@type' => 'Question', 'name' => $faq[0], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $faq[1]]];
            }, $faqs)],
        ]];

        return $this->render('company/'.$kind, ['title' => $title, 'company_page' => $kind, 'brand' => $brand, 'contact' => $contact, 'faqs' => $faqs, 'structured_data' => $schema, 'meta' => $meta]);
    }

    public function sitemap()
    {
        $urls = [base_url(), base_url().'tours', base_url().'tours-with-flight', base_url().'land-tours', base_url().'packages', base_url().'aboutus', base_url().'contactus', base_url().'privacy'];
        foreach (DB::table('tours')->select('code')->where('status', 'published')->get()->all() as $tour) {
            $urls[] = base_url().'tours/'.rawurlencode($tour['code']);
        }
        foreach ($this->places->all() as $node) {
            $urls[] = destination_url($node);
        }
        $urls[] = base_url().'gallery';
        if (Schema::hasTable('gallery_albums')) {
            foreach (DB::table('gallery_albums')->select('id')->where('status', 'published')->get()->all() as $album) {
                $urls[] = base_url().'gallery/'.$album['id'];
            }
        }
        $urls[] = base_url().'blog';
        if (Schema::hasTable('blog_posts')) {
            foreach (DB::table('blog_posts')->select('slug')->where('status', 'published')->where('published_at', '<=', date('Y-m-d H:i:s'))->get()->all() as $post) {
                $urls[] = base_url().'blog/'.rawurlencode($post['slug']);
            }
        }
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n".'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        foreach (array_unique($urls) as $url) {
            $xml .= '<url><loc>'.htmlspecialchars($url, ENT_XML1 | ENT_QUOTES, 'UTF-8').'</loc></url>';
        }

        return response($xml.'</urlset>', 200, ['Content-Type' => 'application/xml; charset=utf-8']);
    }

    public function robots()
    {
        return response("User-agent: *\nAllow: /\nDisallow: /admin\nDisallow: /booking\nDisallow: /billing_admin\nDisallow: /passports\nSitemap: ".base_url()."sitemap.xml\n", 200, ['Content-Type' => 'text/plain; charset=utf-8']);
    }
}
