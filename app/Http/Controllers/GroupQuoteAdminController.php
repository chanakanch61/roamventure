<?php

namespace App\Http\Controllers;

use App\Repositories\GroupQuoteRepository as Quotes;
use App\Repositories\GroupTourRepository as Groups;

/** Quotations for charter groups: made here, printed / saved as PDF, and sent to the customer by staff. */
class GroupQuoteAdminController extends Controller
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

    public function index()
    {
        $filters = [];
        foreach (['q', 'status', 'page'] as $k) {
            $v = request()->query($k);
            $filters[$k] = is_string($v) ? mb_substr(trim($v), 0, 180) : '';
        }

        return $this->render('admin/group_quotes', $this->group_quotes->listing($filters) + ['title' => 'ใบเสนอราคากรุ๊ป', 'filters' => $filters], true);
    }

    public function edit($id = 0)
    {
        $id = (int) $id;
        $quote = $id ? $this->group_quotes->find($id) : $this->blank();
        if (! $quote) {
            show_404();
        }
        $errors = [];
        if (request_method() === 'post') {
            [$quote, $errors] = $this->read($quote);
            if (! $errors) {
                $data = $quote;
                unset($data['id'], $data['number']);
                $data['items'] = json_encode($quote['items'], JSON_UNESCAPED_UNICODE);
                $data['updated_at'] = date('Y-m-d H:i:s');
                if ($id) {
                    $this->group_quotes->update($id, $data);
                } else {
                    $data['created_at'] = $data['updated_at'];
                    $id = $this->group_quotes->insert($data, code_prefix($this->settings));
                }
                if (! $id) {
                    $errors[] = 'บันทึกไม่สำเร็จ กรุณาลองใหม่';
                } else {
                    $this->group_quotes->sync_request($quote['request_id'], $quote['status']);
                    session()->flash('success', 'บันทึกใบเสนอราคาเรียบร้อยแล้ว');
                    redirect_to(base_url().'admin/group-quotes/'.$id);
                }
            }
        }

        return $this->render('admin/group_quote_form', [
            'title' => $quote['id'] ? 'ใบเสนอราคา '.$quote['number'] : 'สร้างใบเสนอราคา',
            'quote' => $quote,
            'errors' => $errors,
            'request_row' => $quote['request_id'] ? $this->group_tours->find_request($quote['request_id']) : null,
            'invoices' => $quote['id'] ? $this->group_invoices->for_quote((int) $quote['id']) : [],
            'group_quote_page' => true,
        ], true);
    }

    /** A4 page for printing or "Save as PDF"; stands alone, without the admin layout. */
    public function print($id)
    {
        $quote = $this->group_quotes->find($id);
        if (! $quote) {
            show_404();
        }
        $data = ['quote' => $quote, 'settings' => $this->settings, 'license' => tour_license($this->settings), 'company' => trim($this->settings['company_name'] ?? '') ?: 'RoamVenture'];

        return response(load_view('admin/group_quote_print', $data, true));
    }

    /** New draft with the same content and a new number, e.g. a revised offer. */
    public function copy($id)
    {
        $this->post_only();
        $quote = $this->group_quotes->find($id);
        if (! $quote) {
            show_404();
        }
        $data = $quote;
        unset($data['id'], $data['number']);
        $now = date('Y-m-d H:i:s');
        $data = array_merge($data, ['items' => json_encode($quote['items'], JSON_UNESCAPED_UNICODE), 'status' => 'draft', 'issue_date' => date('Y-m-d'), 'valid_until' => date('Y-m-d', strtotime('+14 days')), 'prepared_by' => (string) session('admin_name'), 'created_at' => $now, 'updated_at' => $now]);
        $newId = $this->group_quotes->insert($data, code_prefix($this->settings));
        if (! $newId) {
            show_error('ทำสำเนาไม่สำเร็จ กรุณาลองใหม่', 500);
        }
        session()->flash('success', 'สร้างสำเนาจาก '.$quote['number'].' แล้ว แก้ไขรายละเอียดแล้วกดบันทึก');
        redirect_to(base_url().'admin/group-quotes/'.$newId);
    }

    public function delete($id)
    {
        $this->post_only();
        $quote = $this->group_quotes->find($id);
        if (! $quote) {
            show_404();
        }
        $this->group_quotes->delete((int) $quote['id']);
        session()->flash('success', 'ลบใบเสนอราคา '.$quote['number'].' แล้ว');
        redirect_to(base_url().($quote['request_id'] ? 'admin/group-requests/'.(int) $quote['request_id'] : 'admin/group-quotes'));
    }

    /** Empty quotation, filled from the group request when opened with ?request=ID. */
    private function blank(): array
    {
        $quote = ['id' => 0, 'number' => '', 'request_id' => null, 'status' => 'draft', 'issue_date' => date('Y-m-d'), 'valid_until' => date('Y-m-d', strtotime('+14 days')), 'customer_name' => '', 'customer_company' => '', 'customer_address' => '', 'customer_tax_id' => '', 'customer_phone' => '', 'customer_email' => '', 'title' => '', 'travel_dates' => '', 'travelers' => null, 'items' => [['description' => 'ค่าทัวร์', 'qty' => 1, 'unit' => 'ท่าน', 'price' => 0]], 'discount' => 0, 'vat_mode' => 'none', 'vat_rate' => 7, 'wht_enabled' => 0, 'wht_rate' => 3, 'wht_gross_up' => 1, 'includes' => '', 'excludes' => '', 'payment_terms' => '', 'notes' => '', 'prepared_by' => (string) session('admin_name')];
        $requestId = (int) request()->query('request');
        $r = $requestId ? $this->group_tours->find_request($requestId) : null;
        if ($r) {
            $dates = $r['date_from'] ? thai_date($r['date_from']).($r['date_to'] ? ' – '.thai_date($r['date_to']) : '') : '';
            $items = [['description' => 'ค่าทัวร์ผู้ใหญ่ '.$r['destination'], 'qty' => (int) $r['adults'], 'unit' => 'ท่าน', 'price' => 0]];
            if ((int) $r['children']) {
                $items[] = ['description' => 'ค่าทัวร์เด็ก '.$r['destination'], 'qty' => (int) $r['children'], 'unit' => 'ท่าน', 'price' => 0];
            }
            $quote = array_merge($quote, [
                'request_id' => (int) $r['id'],
                'customer_name' => $r['contact_name'],
                'customer_company' => $r['organization'],
                'customer_phone' => $r['phone'],
                'customer_email' => $r['email'],
                'title' => 'กรุ๊ปเหมา '.$r['destination'].($r['days'] ? ' '.(int) $r['days'].' วัน' : ''),
                'travel_dates' => trim($dates.($dates && $r['date_note'] ? ' · ' : '').$r['date_note']),
                'travelers' => (int) $r['adults'] + (int) $r['children'],
                'items' => $items,
                'includes' => implode("\n", Groups::service_labels((string) $r['services'])),
            ]);
        }

        return $quote;
    }

    /** Reads and checks the posted form; totals always come from the server, never from the browser. */
    private function read(array $quote): array
    {
        $errors = [];
        foreach (['customer_name' => 180, 'customer_company' => 200, 'customer_address' => 1000, 'customer_tax_id' => 30, 'customer_phone' => 40, 'customer_email' => 190, 'title' => 250, 'travel_dates' => 150, 'includes' => 5000, 'excludes' => 5000, 'payment_terms' => 5000, 'notes' => 5000, 'prepared_by' => 120] as $key => $max) {
            $quote[$key] = $this->value($key);
            if (mb_strlen($quote[$key]) > $max) {
                $errors[] = 'ข้อความยาวเกินกำหนด: '.$key;
            }
        }
        if ($quote['customer_name'] === '') {
            $errors[] = 'กรุณากรอกชื่อลูกค้า';
        }
        if ($quote['title'] === '') {
            $errors[] = 'กรุณากรอกชื่อรายการ / ทริป';
        }
        if ($quote['customer_email'] !== '' && ! filter_var($quote['customer_email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'อีเมลลูกค้าไม่ถูกต้อง';
        }
        $quote['status'] = $this->value('status');
        if (! isset(Quotes::statuses()[$quote['status']])) {
            $errors[] = 'สถานะไม่ถูกต้อง';
        }
        $quote['vat_mode'] = $this->value('vat_mode');
        if (! isset(Quotes::vat_modes()[$quote['vat_mode']])) {
            $errors[] = 'รูปแบบ VAT ไม่ถูกต้อง';
        }
        foreach (['issue_date' => 'วันที่ออกเอกสาร', 'valid_until' => 'ยืนราคาถึง'] as $key => $label) {
            $date = $this->value($key);
            $ok = preg_match('/^\d{4}-\d{2}-\d{2}$/D', $date) && strtotime($date);
            if ($key === 'issue_date' && ! $ok) {
                $errors[] = 'กรุณาระบุ'.$label;
            } elseif ($date !== '' && ! $ok) {
                $errors[] = $label.'ไม่ถูกต้อง';
            }
            $quote[$key] = $date !== '' ? $date : null;
        }
        if ($quote['valid_until'] && $quote['issue_date'] && $quote['valid_until'] < $quote['issue_date']) {
            $errors[] = 'วันยืนราคาต้องไม่ก่อนวันที่ออกเอกสาร';
        }
        $travelers = $this->value('travelers');
        $quote['travelers'] = $travelers === '' ? null : filter_var($travelers, FILTER_VALIDATE_INT);
        if ($travelers !== '' && ($quote['travelers'] === false || $quote['travelers'] < 1 || $quote['travelers'] > 9999)) {
            $errors[] = 'จำนวนผู้เดินทางต้องอยู่ระหว่าง 1–9999';
        }
        $money = function (string $raw, string $label, float $max) use (&$errors): float {
            $raw = str_replace([',', ' '], '', $raw);
            if ($raw === '') {
                return 0.0;
            }
            if (! is_numeric($raw) || (float) $raw < 0 || (float) $raw > $max) {
                $errors[] = $label.'ไม่ถูกต้อง';

                return 0.0;
            }

            return round((float) $raw, 2);
        };
        $quote['vat_rate'] = $money($this->value('vat_rate'), 'อัตรา VAT ', 30) ?: 7.0;
        $quote['discount'] = $money($this->value('discount'), 'ส่วนลด', 99999999);
        $quote['wht_enabled'] = $this->value('wht_enabled') === '1' ? 1 : 0;
        $quote['wht_rate'] = $money($this->value('wht_rate'), 'อัตราหัก ณ ที่จ่าย ', 15) ?: 3.0;
        $quote['wht_gross_up'] = $this->value('wht_gross_up') === '1' ? 1 : 0;

        $rows = request()->post('items');
        $items = [];
        foreach (is_array($rows) ? $rows : [] as $row) {
            if (! is_array($row)) {
                continue;
            }
            $text = fn ($k) => is_string($row[$k] ?? null) ? trim($row[$k]) : '';
            if ($text('description') === '' && $text('price') === '' && $text('qty') === '') {
                continue; // Blank row left in the table.
            }
            $item = ['description' => $text('description'), 'qty' => $money($text('qty'), 'จำนวนในรายการ ', 99999), 'unit' => $text('unit'), 'price' => $money($text('price'), 'ราคาต่อหน่วย ', 99999999)];
            if ($item['description'] === '' || mb_strlen($item['description']) > 300 || mb_strlen($item['unit']) > 30) {
                $errors[] = 'กรุณากรอกชื่อรายการให้ครบ (ไม่เกิน 300 ตัวอักษร) และหน่วยไม่เกิน 30 ตัวอักษร';
            }
            if ($item['qty'] <= 0) {
                $errors[] = 'จำนวนของ “'.mb_substr($item['description'], 0, 40).'” ต้องมากกว่า 0';
            }
            $items[] = $item;
        }
        if (! $items) {
            $errors[] = 'เพิ่มรายการค่าใช้จ่ายอย่างน้อย 1 รายการ';
        }
        if (count($items) > Quotes::MAX_ITEMS) {
            $errors[] = 'รายการต้องไม่เกิน '.Quotes::MAX_ITEMS.' รายการ';
        }
        $quote['items'] = $items ?: $quote['items'];
        $totals = Quotes::compute($quote['items'], (float) $quote['discount'], $quote['vat_mode'], (float) $quote['vat_rate'], (bool) $quote['wht_enabled'], (float) $quote['wht_rate'], (bool) $quote['wht_gross_up']);
        if ((float) $quote['discount'] > $totals['items_total']) {
            $errors[] = 'ส่วนลดต้องไม่เกินยอดรวมรายการ';
        }

        return [array_merge($quote, $totals), array_values(array_unique($errors))];
    }
}
