<?php

namespace App\Repositories;

use App\Support\ResolvesServices;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Invoices and the receipts issued when they are paid. Two kinds:
 * installment: a share of a group quotation; charges: its own line items (visa fees, sundries),
 * optionally tied to a quotation or a booking but never counted against the quotation's total.
 */
class GroupInvoiceRepository
{
    use ResolvesServices;

    public const PER_PAGE = 20;

    public const MAX_ITEMS = 50;

    public static function kinds(): array
    {
        return ['installment' => 'งวดตามใบเสนอราคา', 'charges' => 'ค่าใช้จ่ายอื่น'];
    }

    public static function statuses(): array
    {
        return ['unpaid' => 'รอชำระ', 'paid' => 'ชำระแล้ว', 'cancelled' => 'ยกเลิก'];
    }

    public static function share_types(): array
    {
        return ['percent' => 'เปอร์เซ็นต์ของยอดรวม', 'amount' => 'ระบุจำนวนเงิน', 'remaining' => 'ส่วนที่เหลือทั้งหมด'];
    }

    public static function payment_methods(): array
    {
        return ['transfer' => 'โอนเงินเข้าบัญชี', 'promptpay' => 'PromptPay / QR', 'cheque' => 'เช็ค', 'cash' => 'เงินสด', 'other' => 'อื่น ๆ'];
    }

    public function find($id): ?array
    {
        $invoice = DB::table('group_invoices')->where('id', (int) $id)->first();
        if ($invoice) {
            $invoice['items'] = json_decode((string) $invoice['items'], true) ?: [];
        }

        return $invoice;
    }

    /** Saved line items offered in the charges invoice form, in the order staff arranged them. */
    public function templates(): array
    {
        return DB::table('invoice_item_templates')->orderBy('sort_order')->orderBy('id')->get()->all();
    }

    /** Replaces the whole template list with the rows as posted (their order becomes the sort order). */
    public function save_templates(array $rows): void
    {
        $now = date('Y-m-d H:i:s');
        DB::transaction(function () use ($rows, $now) {
            DB::table('invoice_item_templates')->delete();
            foreach (array_values($rows) as $i => $row) {
                DB::table('invoice_item_templates')->insert($row + ['sort_order' => $i, 'updated_at' => $now]);
            }
        });
    }

    public function for_booking(int $bookingId): array
    {
        return DB::table('group_invoices')->where('booking_id', $bookingId)->orderBy('id')->get()->all();
    }

    public function for_quote(int $quoteId): array
    {
        return DB::table('group_invoices')->where('quotation_id', $quoteId)->orderBy('id')->get()->all();
    }

    /** Totals already billed on a quotation by installments that still count (not cancelled), optionally leaving one out. */
    public function billed(int $quoteId, int $exceptId = 0): array
    {
        $row = DB::table('group_invoices')->selectRaw('COALESCE(SUM(total),0) total, COALESCE(SUM(subtotal),0) subtotal')
            ->where('quotation_id', $quoteId)->where('kind', 'installment')->where('status', '!=', 'cancelled')->where('id', '!=', $exceptId)->first();

        return ['total' => round((float) $row['total'], 2), 'subtotal' => round((float) $row['subtotal'], 2)];
    }

    /**
     * Amounts for one installment of a quotation. VAT and withholding follow the quotation's settings,
     * in proportion; "remaining" bills exactly what is left, so the installments always add up to the quotation.
     * Returns [amounts, error].
     */
    public static function share(array $quote, string $type, float $value, array $billed): array
    {
        $qTotal = round((float) $quote['total'], 2);
        $qSub = round((float) $quote['subtotal'], 2);
        $left = round($qTotal - $billed['total'], 2);
        if ($qTotal <= 0) {
            return [[], 'ใบเสนอราคานี้ยอดรวมเป็น 0'];
        }
        if ($type === 'percent') {
            if ($value <= 0 || $value > 100) {
                return [[], 'เปอร์เซ็นต์ต้องมากกว่า 0 และไม่เกิน 100'];
            }
            $total = round($qTotal * $value / 100, 2);
            $subtotal = round($qSub * $value / 100, 2);
        } elseif ($type === 'amount') {
            if ($value <= 0) {
                return [[], 'จำนวนเงินต้องมากกว่า 0'];
            }
            $total = round($value, 2);
            $subtotal = round($qSub * $total / $qTotal, 2);
        } else {
            $total = $left;
            $subtotal = round($qSub - $billed['subtotal'], 2);
        }
        if ($total <= 0) {
            return [[], 'ใบเสนอราคานี้ออกใบแจ้งหนี้ครบยอดแล้ว'];
        }
        if ($total > $left + 0.001) {
            return [[], 'ยอดงวดนี้ ฿'.number_format($total, 2).' เกินยอดที่เหลือ ฿'.number_format($left, 2)];
        }
        // The last cents go to the final installment, so the parts never drift from the quotation.
        if (abs($total - $left) < 0.005) {
            $total = $left;
            $subtotal = round($qSub - $billed['subtotal'], 2);
        }
        if ($quote['vat_mode'] === 'none') {
            $subtotal = $total;
        }
        $wht = ! empty($quote['wht_enabled']) ? round($subtotal * (float) $quote['wht_rate'] / 100, 2) : 0.0;

        return [['subtotal' => $subtotal, 'vat_amount' => round($total - $subtotal, 2), 'total' => $total, 'wht_amount' => $wht, 'net_payable' => round($total - $wht, 2)], ''];
    }

    /**
     * Totals of a charges invoice. Each line is qty × price, rounded to satang as printed.
     * Lines marked "disbursement" (paid on the customer's behalf, e.g. the embassy's visa fee) carry no VAT
     * and no withholding; they are added after VAT as exempt_amount.
     * included: the other lines' prices already hold VAT, which is backed out; excluded: VAT is added on top.
     */
    public static function charges(array $items, string $mode, float $rate, bool $wht, float $whtRate): array
    {
        $taxable = 0.0;
        $exempt = 0.0;
        foreach ($items as $item) {
            $amount = round((float) $item['qty'] * round((float) $item['price'], 2), 2);
            if (! empty($item['disbursement'])) {
                $exempt += $amount;
            } else {
                $taxable += $amount;
            }
        }
        $taxable = round($taxable, 2);
        $exempt = round($exempt, 2);
        if ($mode === 'excluded') {
            $vat = round($taxable * $rate / 100, 2);
            $subtotal = $taxable;
        } elseif ($mode === 'included') {
            $vat = round($taxable * $rate / (100 + $rate), 2);
            $subtotal = round($taxable - $vat, 2);
        } else {
            $vat = 0.0;
            $subtotal = $taxable;
        }
        $total = round($subtotal + $vat + $exempt, 2);
        $whtAmount = $wht ? round($subtotal * $whtRate / 100, 2) : 0.0;

        return ['subtotal' => $subtotal, 'vat_amount' => $vat, 'exempt_amount' => $exempt, 'total' => $total, 'wht_amount' => $whtAmount, 'net_payable' => round($total - $whtAmount, 2)];
    }

    public function listing(array $filters): array
    {
        $query = DB::table('group_invoices');
        if (isset(self::statuses()[$filters['status'] ?? ''])) {
            $query->where('status', $filters['status']);
        }
        if (isset(self::kinds()[$filters['kind'] ?? ''])) {
            $query->where('kind', $filters['kind']);
        }
        if (($filters['q'] ?? '') !== '') {
            $like = '%'.addcslashes($filters['q'], '%_\\').'%';
            $query->where(function ($q) use ($like) {
                foreach (['number', 'receipt_number', 'quote_number', 'booking_reference', 'customer_name', 'customer_company', 'title', 'label'] as $column) {
                    $q->orWhere($column, 'like', $like);
                }
            });
        }
        $total = $query->count();
        $pages = max(1, (int) ceil($total / self::PER_PAGE));
        $page = min($pages, max(1, (int) ($filters['page'] ?? 1)));
        $rows = $query->orderBy('id', 'DESC')->limit(self::PER_PAGE)->offset(($page - 1) * self::PER_PAGE)->get()->all();

        return ['invoices' => $rows, 'total' => $total, 'page' => $page, 'pages' => $pages];
    }

    /** Next free number in a monthly series, e.g. RVT-IV2611-001 or RVT-RC2611-001. */
    private function next_number(string $column, string $base): string
    {
        $last = DB::table('group_invoices')->where($column, 'like', addcslashes($base, '%_\\').'%')->orderBy($column, 'DESC')->value($column);
        $seq = $last ? (int) substr($last, strlen($base)) + 1 : 1;

        return $base.str_pad((string) $seq, 3, '0', STR_PAD_LEFT);
    }

    public function insert(array $data, string $prefix): ?int
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $data['number'] = $this->next_number('number', $prefix.'-IV'.date('ym').'-');
            try {
                return (int) DB::table('group_invoices')->insertGetId($data);
            } catch (QueryException $e) {
                if (! DB::table('group_invoices')->where('number', $data['number'])->exists()) {
                    report($e);

                    return null;
                }
            }
        }

        return null;
    }

    public function update(int $id, array $data): void
    {
        DB::table('group_invoices')->where('id', $id)->update($data + ['updated_at' => date('Y-m-d H:i:s')]);
    }

    /** Records the payment and gives the receipt the next number of the month it was paid in. Returns the receipt number. */
    public function mark_paid(int $id, string $prefix, array $payment): ?string
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $number = $this->next_number('receipt_number', $prefix.'-RC'.date('ym', strtotime($payment['paid_at'])).'-');
            try {
                $changed = DB::table('group_invoices')->where('id', $id)->where('status', 'unpaid')
                    ->update($payment + ['status' => 'paid', 'receipt_number' => $number, 'updated_at' => date('Y-m-d H:i:s')]);

                return $changed ? $number : null;
            } catch (QueryException $e) {
                if (! DB::table('group_invoices')->where('receipt_number', $number)->exists()) {
                    report($e);

                    return null;
                }
            }
        }

        return null;
    }
}
