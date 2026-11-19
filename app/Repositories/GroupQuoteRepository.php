<?php

namespace App\Repositories;

use App\Support\ResolvesServices;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/** Quotations for charter groups: back office only, printed or saved as PDF by staff. */
class GroupQuoteRepository
{
    use ResolvesServices;

    public const PER_PAGE = 20;

    public const MAX_ITEMS = 50;

    public static function statuses(): array
    {
        return ['draft' => 'ฉบับร่าง', 'sent' => 'ส่งลูกค้าแล้ว', 'accepted' => 'ลูกค้าตอบรับ', 'rejected' => 'ไม่ผ่าน / ยกเลิก'];
    }

    public static function vat_modes(): array
    {
        return ['none' => 'ไม่มี VAT', 'included' => 'ราคารวม VAT แล้ว', 'excluded' => 'บวก VAT เพิ่ม'];
    }

    /**
     * Totals from line items. Money is rounded to satang at each step, as it is printed.
     * included: the grand total already holds VAT, which is backed out of it.
     * excluded: VAT is added on top of the amount after discount.
     * Withholding tax is taken from the amount before VAT and only lowers what the customer transfers;
     * the grand total on the quotation stays the full price.
     */
    /**
     * Unit price printed on the quotation. With gross-up on, the withholding rate is added straight onto the
     * price staff typed (10,000 at 3% → 10,300.00), as the company quotes it; kept to 2 decimals, not rounded up.
     */
    public static function unit_price(float $price, bool $wht, bool $grossUp, float $whtRate): float
    {
        return $wht && $grossUp ? round($price * (1 + $whtRate / 100), 2) : round($price, 2);
    }

    public static function compute(array $items, float $discount, string $mode, float $rate, bool $wht = false, float $whtRate = 3, bool $grossUp = false): array
    {
        $sum = 0.0;
        foreach ($items as $item) {
            $sum += round((float) $item['qty'] * self::unit_price((float) $item['price'], $wht, $grossUp, $whtRate), 2);
        }
        $sum = round($sum, 2);
        $discount = round(min(max(0, $discount), $sum), 2);
        $after = round($sum - $discount, 2);
        if ($mode === 'excluded') {
            $vat = round($after * $rate / 100, 2);
            $subtotal = $after;
            $total = round($after + $vat, 2);
        } elseif ($mode === 'included') {
            $total = $after;
            $vat = round($after * $rate / (100 + $rate), 2);
            $subtotal = round($total - $vat, 2);
        } else {
            $vat = 0.0;
            $subtotal = $after;
            $total = $after;
        }

        $whtAmount = $wht ? round($subtotal * $whtRate / 100, 2) : 0.0;

        return ['items_total' => $sum, 'discount' => $discount, 'subtotal' => $subtotal, 'vat_amount' => $vat, 'total' => $total, 'wht_amount' => $whtAmount, 'net_payable' => round($total - $whtAmount, 2)];
    }

    public function find($id): ?array
    {
        $quote = DB::table('group_quotations')->where('id', (int) $id)->first();
        if ($quote) {
            $quote['items'] = json_decode((string) $quote['items'], true) ?: [];
        }

        return $quote;
    }

    public function for_request(int $requestId): array
    {
        return DB::table('group_quotations')->select('id', 'number', 'status', 'issue_date', 'total', 'title')->where('request_id', $requestId)->orderBy('id', 'DESC')->get()->all();
    }

    public function listing(array $filters): array
    {
        $query = DB::table('group_quotations');
        if (isset(self::statuses()[$filters['status'] ?? ''])) {
            $query->where('status', $filters['status']);
        }
        if (($filters['q'] ?? '') !== '') {
            $like = '%'.addcslashes($filters['q'], '%_\\').'%';
            $query->where(function ($q) use ($like) {
                foreach (['number', 'customer_name', 'customer_company', 'customer_phone', 'title'] as $column) {
                    $q->orWhere($column, 'like', $like);
                }
            });
        }
        $total = $query->count();
        $pages = max(1, (int) ceil($total / self::PER_PAGE));
        $page = min($pages, max(1, (int) ($filters['page'] ?? 1)));
        $rows = $query->orderBy('id', 'DESC')->limit(self::PER_PAGE)->offset(($page - 1) * self::PER_PAGE)->get()->all();

        return ['quotes' => $rows, 'total' => $total, 'page' => $page, 'pages' => $pages];
    }

    /** Next free number for this month, e.g. RVT-QT2611-001. */
    public function next_number(string $prefix): string
    {
        $base = $prefix.'-QT'.date('ym').'-';
        $last = DB::table('group_quotations')->where('number', 'like', addcslashes($base, '%_\\').'%')->orderBy('number', 'DESC')->value('number');
        $seq = $last ? (int) substr($last, strlen($base)) + 1 : 1;

        return $base.str_pad((string) $seq, 3, '0', STR_PAD_LEFT);
    }

    /** Inserts with a fresh number, retrying if two staff save at the same moment. Returns the new id or null. */
    public function insert(array $data, string $prefix): ?int
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $data['number'] = $this->next_number($prefix);
            try {
                return (int) DB::table('group_quotations')->insertGetId($data);
            } catch (QueryException $e) {
                if (! DB::table('group_quotations')->where('number', $data['number'])->exists()) {
                    report($e);

                    return null;
                }
            }
        }

        return null;
    }

    public function update(int $id, array $data): void
    {
        DB::table('group_quotations')->where('id', $id)->update($data);
    }

    public function delete(int $id): void
    {
        DB::table('group_quotations')->where('id', $id)->delete();
    }

    /** Moves the linked request forward when a quotation is sent or accepted; never moves it backwards. */
    public function sync_request(?int $requestId, string $quoteStatus): void
    {
        if (! $requestId) {
            return;
        }
        $request = $this->group_tours->find_request($requestId);
        if (! $request) {
            return;
        }
        $order = ['new' => 0, 'contacted' => 1, 'quoted' => 2, 'confirmed' => 3];
        $target = ['sent' => 'quoted', 'accepted' => 'confirmed'][$quoteStatus] ?? null;
        if ($target && isset($order[$request['status']]) && $order[$request['status']] < $order[$target]) {
            $this->group_tours->update_request($requestId, $target, (string) $request['admin_note']);
        }
    }
}
