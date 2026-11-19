<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Posts booking events to a Discord channel webhook (DISCORD_WEBHOOK_URL in .env).
 * Sending runs after the response has gone to the customer, and any failure is only logged,
 * so a slow or broken Discord never blocks or breaks a booking or a slip upload.
 */
class DiscordNotify
{
    public function new_booking(array $b): void
    {
        $this->later(fn () => $this->send([
            'title' => '🧳 คำขอจองใหม่ '.$b['reference'],
            'color' => 0x2D9CDB,
            'url' => $this->admin_url($b['id']),
            'fields' => [
                $this->field('โปรแกรม', $b['tour_title'], false),
                $this->field('วันเดินทาง', thai_date($b['start_date'])),
                $this->field('ผู้เดินทาง', (int) $b['traveler_count'].' คน'),
                $this->field('ยอดรวม', '฿'.money($b['total_price'])),
                $this->field('ผู้ติดต่อ', $b['customer_name']),
                $this->field('โทร', $b['phone']),
                $this->field('LINE', $b['line_id'] ?? ''),
                $this->field('หมายเหตุ', $b['note'] ?? '', false),
            ],
        ]));
    }

    public function new_group_request(array $r): void
    {
        $dates = $r['date_from'] ? thai_date($r['date_from']).($r['date_to'] ? ' – '.thai_date($r['date_to']) : '') : '';
        $this->later(fn () => $this->send([
            'title' => '👥 คำขอจัดกรุ๊ปเหมาใหม่ '.$r['reference'],
            'color' => 0x9B51E0,
            'url' => base_url().'admin/group-requests/'.(int) $r['id'],
            'fields' => [
                $this->field('จุดหมาย', $r['destination'], false),
                $this->field('ประเภทกรุ๊ป', \App\Repositories\GroupTourRepository::group_types()[$r['group_type']] ?? $r['group_type']),
                $this->field('ผู้เดินทาง', (int) $r['adults'].' ผู้ใหญ่'.((int) $r['children'] ? ' · '.(int) $r['children'].' เด็ก' : '')),
                $this->field('วันเดินทาง', trim($dates.' '.$r['date_note'])),
                $this->field('จำนวนวัน', $r['days'] ? (int) $r['days'].' วัน' : ''),
                $this->field('งบต่อคน', \App\Repositories\GroupTourRepository::budgets()[$r['budget']] ?? ''),
                $this->field('ผู้ติดต่อ', trim($r['contact_name'].' '.($r['organization'] ? '('.$r['organization'].')' : ''))),
                $this->field('โทร', $r['phone']),
                $this->field('LINE', $r['line_id']),
                $this->field('รายละเอียด', $r['message'] ?? '', false),
            ],
        ]));
    }

    public function slip(int $bookingId, int $paymentId): void
    {
        $this->later(function () use ($bookingId, $paymentId) {
            $b = DB::table('bookings')->where('id', $bookingId)->first();
            $p = DB::table('payments')->where('booking_id', $bookingId)->where('id', $paymentId)->first();
            if (! $b || ! $p) {
                return;
            }
            $file = $p['slip_path'] ? base_path($p['slip_path']) : null;
            $file = $file && is_file($file) ? $file : null;
            $embed = [
                'title' => '🧾 แนบสลิปใหม่ '.$b['reference'],
                'color' => 0xF2994A,
                'url' => $this->admin_url($b['id']),
                'description' => 'รอตรวจสลิปและยืนยันรับเงิน',
                'fields' => [
                    $this->field('โปรแกรม', $b['tour_title'], false),
                    $this->field('ผู้ติดต่อ', $b['customer_name']),
                    $this->field('โทร', $b['phone']),
                    $this->field('รายการชำระ', $p['reference']),
                    $this->field('ยอดเรียกเก็บ', '฿'.money($p['expected_amount'])),
                    $this->field('ยอดโอนที่แจ้ง', '฿'.money($p['reported_amount'])),
                    $this->field('ยอดรวมการจอง', '฿'.money($b['total_price'])),
                ],
            ];
            if ($file) {
                $embed['image'] = ['url' => 'attachment://slip.'.pathinfo($file, PATHINFO_EXTENSION)];
            }
            $this->send($embed, $file);
        });
    }

    private function later(callable $task): void
    {
        if (! config('services.discord.webhook_url')) {
            return;
        }
        // Runs once the response has been sent (PHP-FPM finishes the request first).
        app()->terminating(function () use ($task) {
            try {
                $task();
            } catch (Throwable $e) {
                // Guzzle errors quote the request URL, and the webhook token in it is a secret.
                Log::warning('Discord notify failed: '.preg_replace('#/webhooks/\S+#', '/webhooks/***', $e->getMessage()));
            }
        });
    }

    private function send(array $embed, ?string $file = null): void
    {
        $embed['fields'] = array_values(array_filter($embed['fields'] ?? []));
        $embed['timestamp'] = date('c');
        // No pings: customer-typed text must never trigger @everyone or role mentions.
        $payload = ['username' => 'RoamVenture', 'embeds' => [$embed], 'allowed_mentions' => ['parse' => []]];
        $request = Http::timeout(8)->connectTimeout(3);
        if ($file) {
            $request = $request->attach('files[0]', file_get_contents($file), 'slip.'.pathinfo($file, PATHINFO_EXTENSION));
            $response = $request->post(config('services.discord.webhook_url'), ['payload_json' => json_encode($payload, JSON_UNESCAPED_UNICODE)]);
        } else {
            $response = $request->post(config('services.discord.webhook_url'), $payload);
        }
        if ($response->failed()) {
            Log::warning('Discord notify failed: HTTP '.$response->status().' '.mb_substr($response->body(), 0, 300));
        }
    }

    private function field(string $name, $value, bool $inline = true): ?array
    {
        $value = trim((string) $value);

        return $value === '' ? null : ['name' => $name, 'value' => mb_substr($value, 0, 1000), 'inline' => $inline];
    }

    private function admin_url($id): string
    {
        return base_url().'admin/bookings/'.(int) $id;
    }
}
