<?php

namespace App\Support;

use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Emails the customer about their booking (MAIL_* in .env).
 * Sending runs after the response has gone to the customer, and any failure is only logged,
 * so a slow or broken mail server never blocks or breaks a booking.
 */
class CustomerMail
{
    public function booking_received(array $b, array $settings): void
    {
        $to = trim((string) ($b['email'] ?? ''));
        if ($to === '' || ! filter_var($to, FILTER_VALIDATE_EMAIL) || config('mail.default') === 'array') {
            return;
        }
        $vars = $this->booking_vars($b, $settings);
        $subject = 'ได้รับคำขอจองแล้ว · '.$b['reference'].' '.$b['tour_title'];

        app()->terminating(function () use ($to, $b, $settings, $vars, $subject) {
            try {
                Mail::send(['html' => 'emails/booking_received', 'text' => 'emails/booking_received_text'], $vars, function (Message $m) use ($to, $b, $settings, $subject) {
                    $m->to($to, $b['customer_name'])->subject($subject);
                    $reply = trim((string) ($settings['email'] ?? ''));
                    if ($reply !== '' && filter_var($reply, FILTER_VALIDATE_EMAIL)) {
                        $m->replyTo($reply, ($settings['company_name'] ?? '') ?: 'RoamVenture');
                    }
                });
            } catch (Throwable $e) {
                Log::warning('Customer email failed for '.$b['reference'].': '.$e->getMessage());
            }
        });
    }

    /** Confirms a charter-group request from /grouptour. Email is optional on that form, so this is skipped without one. */
    public function group_request_received(array $r, array $settings): void
    {
        $to = trim((string) ($r['email'] ?? ''));
        if ($to === '' || ! filter_var($to, FILTER_VALIDATE_EMAIL) || config('mail.default') === 'array') {
            return;
        }
        // Own mailbox (GROUPTOUR_MAIL_* in .env): never falls back to the booking mailer.
        $transport = (string) config('mail.mailers.grouptour.transport');
        $from = trim((string) config('mail.grouptour_from.address'));
        if ($transport === '' || ($transport === 'smtp' && ! config('mail.mailers.grouptour.host')) || ! filter_var($from, FILTER_VALIDATE_EMAIL)) {
            Log::info('Group tour email skipped for '.$r['reference'].': GROUPTOUR_MAIL_* is not configured');

            return;
        }
        $vars = $this->group_vars($r, $settings);
        $subject = 'ได้รับคำขอจัดกรุ๊ปแล้ว · '.$r['reference'].' '.$r['destination'];

        app()->terminating(function () use ($to, $r, $settings, $vars, $subject, $from) {
            try {
                Mail::mailer('grouptour')->send(['html' => 'emails/group_request_received', 'text' => 'emails/group_request_received_text'], $vars, function (Message $m) use ($to, $r, $settings, $subject, $from) {
                    $m->from($from, (string) config('mail.grouptour_from.name'))->to($to, $r['contact_name'])->subject($subject);
                    $reply = trim((string) ($settings['email'] ?? ''));
                    if ($reply !== '' && filter_var($reply, FILTER_VALIDATE_EMAIL)) {
                        $m->replyTo($reply, ($settings['company_name'] ?? '') ?: 'RoamVenture');
                    }
                });
            } catch (Throwable $e) {
                Log::warning('Customer email failed for '.$r['reference'].': '.$e->getMessage());
            }
        });
    }

    public function group_vars(array $r, array $settings): array
    {
        $groups = \App\Repositories\GroupTourRepository::class;
        $lineId = trim((string) ($settings['line_id'] ?? ''));
        $dates = $r['date_from'] ? thai_date($r['date_from']).($r['date_to'] ? ' – '.thai_date($r['date_to']) : '') : '';

        return [
            'r' => $r,
            'brand' => ($settings['company_name'] ?? '') ?: 'RoamVenture',
            'logo_png' => $this->white_logo(branding_path($settings, 'logo_navbar_desktop')),
            'group_type' => $groups::group_types()[$r['group_type']] ?? $r['group_type'],
            'travelers' => (int) $r['adults'].' ผู้ใหญ่'.((int) $r['children'] ? ' · '.(int) $r['children'].' เด็ก' : ''),
            'when' => trim($dates.($dates && $r['date_note'] ? ' · ' : '').$r['date_note']),
            'budget' => $groups::budgets()[$r['budget']] ?? '',
            'hotel' => $groups::hotel_levels()[$r['hotel_level']] ?? '',
            'services' => implode(', ', $groups::service_labels((string) $r['services'])),
            'line_id' => $lineId,
            'line_url' => $lineId ? tour_line_url($lineId, 'ขอติดตามคำขอจัดกรุ๊ปเหมา เลขที่ '.$r['reference']) : '',
            'phone' => trim((string) ($settings['phone'] ?? '')),
            'contact_email' => trim((string) ($settings['email'] ?? '')),
            'license' => tour_license($settings),
            'site_url' => base_url(),
            'past_groups_url' => base_url().'grouptour#past-groups',
        ];
    }

    /** Everything the template needs, worked out up front so the view stays simple. */
    public function booking_vars(array $b, array $settings): array
    {
        $package = json_decode($b['package_snapshot'] ?? 'null', true);
        $single = (int) ($b['single_rooms'] ?? 0);
        $lineId = trim((string) ($settings['line_id'] ?? ''));

        return [
            'b' => $b,
            'brand' => ($settings['company_name'] ?? '') ?: 'RoamVenture',
            'logo_png' => $this->white_logo(branding_path($settings, 'logo_navbar_desktop')),
            'detail_url' => base_url().'booking/detail/'.$b['access_token'],
            'package' => is_array($package) ? $package : null,
            'rooms' => booking_rooms_label($b),
            'tour_total' => (float) $b['price_per_person'] * (int) $b['traveler_count'],
            'single_rooms' => $single,
            'line_id' => $lineId,
            'line_url' => $lineId ? tour_line_url($lineId, 'ติดตามคำขอ '.$b['reference'].' โปรแกรม '.$b['tour_title']) : '',
            'phone' => trim((string) ($settings['phone'] ?? '')),
            'contact_email' => trim((string) ($settings['email'] ?? '')),
            'license' => tour_license($settings),
            'site_url' => base_url(),
        ];
    }

    /**
     * The site logo turned white for the dark email header, as PNG bytes; '' falls back to the text wordmark.
     * The site does this with a CSS filter, which email clients ignore, so the pixels are recoloured here:
     * transparent margins trimmed, every visible pixel white with its own transparency, 144px tall (2x of the 72px shown).
     */
    private function white_logo(string $path): string
    {
        if ($path === '' || ! function_exists('imagecreatefromstring')) {
            return '';
        }
        try {
            $src = @imagecreatefromstring((string) file_get_contents(base_path($path)));
            if (! $src) {
                return '';
            }
            imagepalettetotruecolor($src);
            imagealphablending($src, false);
            imagesavealpha($src, true);
            $src = $this->trim_transparent($src);
            $h = 144;
            $w = max(1, (int) round(imagesx($src) * $h / imagesy($src)));
            $out = imagecreatetruecolor($w, $h);
            imagealphablending($out, false);
            imagesavealpha($out, true);
            imagefill($out, 0, 0, imagecolorallocatealpha($out, 255, 255, 255, 127));
            imagecopyresampled($out, $src, 0, 0, 0, 0, $w, $h, imagesx($src), imagesy($src));
            for ($x = 0; $x < $w; $x++) {
                for ($y = 0; $y < $h; $y++) {
                    $alpha = (imagecolorat($out, $x, $y) >> 24) & 0x7F;
                    imagesetpixel($out, $x, $y, imagecolorallocatealpha($out, 255, 255, 255, $alpha));
                }
            }
            ob_start();
            imagepng($out, null, 9);

            return (string) ob_get_clean();
        } catch (Throwable $e) {
            Log::warning('Email logo failed: '.$e->getMessage());

            return '';
        }
    }

    /** Crops to the clearly visible pixels; faint near-transparent specks in the margins are ignored. */
    private function trim_transparent($img)
    {
        $w = imagesx($img);
        $h = imagesy($img);
        [$left, $top, $right, $bottom] = [$w, $h, -1, -1];
        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                if ((imagecolorat($img, $x, $y) >> 24 & 0x7F) < 96) {
                    $left = min($left, $x);
                    $right = max($right, $x);
                    $top = min($top, $y);
                    $bottom = max($bottom, $y);
                }
            }
        }
        if ($right < 0) {
            return $img;
        }

        return imagecrop($img, ['x' => $left, 'y' => $top, 'width' => $right - $left + 1, 'height' => $bottom - $top + 1]) ?: $img;
    }
}
