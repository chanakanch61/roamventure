<?php

namespace App\Support;

use App\Repositories\PackageLevelRepository;
use DateTime;

class PrivatePackage
{
    // Placeholder dates stored on an "anytime" period, so date filters in SQL keep it listed.
    public const ANYTIME_START = '1000-01-01';

    public const ANYTIME_END = '9999-12-31';

    public const ADVANCE_MONTHS = [6, 12, 18, 24];

    public static function anytime($dep)
    {
        return ! empty($dep['anytime']);
    }

    // First and last calendar day a period covers. An anytime period runs from today to its booking horizon.
    public static function window($dep)
    {
        if (! self::anytime($dep)) {
            return [$dep['start_date'], $dep['end_date']];
        }
        $today = date('Y-m-d');
        $months = (int) ($dep['advance_months'] ?? 0) ?: 12;

        return [$today, date('Y-m-d', strtotime($today.' +'.$months.' months'))];
    }

    // Gives an anytime period today's real dates. $covered lists [start, end] of the tour's active dated
    // periods: a trip that fits inside one of them is booked there, at that period's price, instead.
    public static function resolve($dep, $covered = [])
    {
        if (! self::anytime($dep)) {
            return $dep;
        }
        [$dep['start_date'], $dep['end_date']] = self::window($dep);
        $dep['covered'] = array_values($covered);

        return $dep;
    }

    public static function date($value)
    {
        if (! is_string($value) || ! preg_match('/^[1-9][0-9]{3}-[0-9]{2}-[0-9]{2}$/D', $value)) {
            return false;
        }
        $d = DateTime::createFromFormat('!Y-m-d', $value);

        return $d && $d->format('Y-m-d') === $value;
    }

    public static function rates($rows, &$errors)
    {
        $result = [];
        $packageLevels = app(PackageLevelRepository::class);
        $levels = $packageLevels->all();
        if (! is_array($rows) || ! $rows || count($rows) > 30) {
            $errors[] = 'แต่ละพีเรียดต้องมีราคา 1–30 แถว';

            return [];
        }
        foreach ($rows as $row) {
            if (! is_array($row)) {
                $errors[] = 'ข้อมูลราคาไม่ถูกต้อง';

                continue;
            }
            $catalog = $packageLevels->resolve($row, $levels);
            $level = $catalog['name'] ?? '';
            $min = filter_var($row['min'] ?? null, FILTER_VALIDATE_INT);
            $max = filter_var($row['max'] ?? null, FILTER_VALIDATE_INT);
            $price = $row['price'] ?? '';
            $details = $row['details'] ?? '';
            if (! $catalog || ! $min || ! $max || $min < 1 || $max < $min || $max > 500 || ! is_scalar($price) || ! preg_match('/^\d{1,8}(\.\d{1,2})?$/D', (string) $price) || (float) $price <= 0 || ! is_string($details) || mb_strlen($details) > 1000) {
                $errors[] = 'ตรวจระดับบริการ จำนวนขั้นต่ำ–สูงสุด (1–500) ราคา และรายละเอียดไม่เกิน 1,000 ตัวอักษร';

                continue;
            }
            foreach ($result as $other) {
                if ($other['level'] === $level && $min <= $other['max'] && $max >= $other['min']) {
                    $errors[] = 'ช่วงจำนวนคนในระดับ '.$level.' ซ้อนกัน';
                }
            }
            $result[] = ['level_id' => (int) $catalog['id'], 'level' => $level, 'min' => $min, 'max' => $max, 'price' => number_format((float) $price, 2, '.', ''), 'details' => trim($details)];
        }

        return $result;
    }

    // Booking cutoff (Bangkok time): Mon–Fri before 15:00 → tomorrow, from 15:00 → the day after;
    // Saturday or Sunday → next Tuesday at the earliest.
    public static function earliest_start($now = null)
    {
        $now = $now ?? time();
        if ((int) date('N', $now) >= 6) {
            return date('Y-m-d', strtotime('next tuesday', $now));
        }

        return date('Y-m-d', strtotime(date('Y-m-d', $now).' +'.((int) date('G', $now) < 15 ? 1 : 2).' days'));
    }

    public static function dates_allowed($dep, $start)
    {
        if (! self::date($start)) {
            return false;
        }
        $end = date('Y-m-d', strtotime($start.' +'.((int) $dep['days'] - 1).' days'));
        [$from, $to] = self::window($dep);
        if ($start < $from || $end > $to || $start < self::earliest_start()) {
            return false;
        }
        if (self::anytime($dep)) {
            foreach ($dep['covered'] ?? [] as [$coveredStart, $coveredEnd]) {
                if ($start >= $coveredStart && $end <= $coveredEnd) {
                    return false;
                }
            }
        }
        foreach (json_decode($dep['blackout_dates'] ?? '[]', true) ?: [] as $blocked) {
            if ($blocked >= $start && $blocked <= $end) {
                return false;
            }
        }

        return $end;
    }

    public static function first_date($dep, $from = null, $to = null)
    {
        [$periodStart, $periodEnd] = self::window($dep);
        $start = max($periodStart, self::earliest_start(), $from ?: '0000-00-00');
        $last = min(date('Y-m-d', strtotime($periodEnd.' -'.((int) $dep['days'] - 1).' days')), $to ?: '9999-12-31');
        for ($date = $start; $date <= $last; $date = date('Y-m-d', strtotime($date.' +1 day'))) {
            if (self::dates_allowed($dep, $date)) {
                return $date;
            }
        }

        return null;
    }

    public static function offer($dep, $start, $count, $level)
    {
        $end = self::dates_allowed($dep, $start);
        if (! $end || ! is_int($count) || $count < 1) {
            return null;
        }
        foreach (json_decode($dep['private_rates'] ?? '[]', true) ?: [] as $rate) {
            if ($rate['level'] === $level && $count >= $rate['min'] && $count <= $rate['max']) {
                [$periodStart, $periodEnd] = self::window($dep);
                $snapshot = $rate + ['period_start' => $periodStart, 'period_end' => $periodEnd, 'anytime' => self::anytime($dep) ? 1 : 0,'deposit_per_person' => $dep['deposit_per_person'], 'deposit_enabled' => $dep['deposit_enabled'], 'deposit_cutoff_days' => $dep['deposit_cutoff_days'], 'balance_due_days' => $dep['balance_due_days']];

                return array_replace($dep, ['start_date' => $start, 'end_date' => $end, 'price_per_person' => $rate['price'], 'capacity' => $rate['max'], 'seats_available' => $rate['max'], 'package_snapshot' => json_encode($snapshot,JSON_UNESCAPED_UNICODE)]);
            }
        }

        return null;
    }
}
