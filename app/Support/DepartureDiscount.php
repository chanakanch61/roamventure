<?php

namespace App\Support;

// Percentage discounts on departures. A group departure has one discount_percent; a private package
// period gives each rate row (service level × group size) its own `discount` in private_rates, and the
// period's discount_until ends them all. The database keeps regular prices; apply() turns a departure
// read for the public site into its selling prices and remembers the regular ones beside them.
class DepartureDiscount
{
    public const MAX_PERCENT = 90;

    // Whether the period's discounts still run today (no end date, or not past it).
    private static function running($dep)
    {
        $until = $dep['discount_until'] ?? null;

        return ! $until || $until >= date('Y-m-d');
    }

    private static function clamp($percent)
    {
        return max(0, min((int) $percent, self::MAX_PERCENT));
    }

    // The group departure's percent in force today. Private periods discount per rate instead.
    public static function percent($dep)
    {
        if (! empty($dep['private_rates']) || ! self::running($dep)) {
            return 0;
        }

        return self::clamp($dep['discount_percent'] ?? 0);
    }

    // Discounted prices round to whole baht; an undiscounted price is left exactly as entered.
    public static function price($regular, $percent)
    {
        if ($percent < 1) {
            return (float) $regular;
        }

        return round(round((float) $regular * (100 - $percent), 2) / 100);
    }

    // Same rule as price() for group departures in SQL, so listings can pick the cheapest by selling price.
    // Private tours recompute their "from" price in PHP from the discounted rates.
    public static function price_sql($alias, $today)
    {
        $active = "$alias.private_rates IS NULL AND $alias.discount_percent>0 AND ($alias.discount_until IS NULL OR $alias.discount_until>=$today)";

        return "CASE WHEN $active THEN ROUND($alias.price_per_person*(100-LEAST($alias.discount_percent,".self::MAX_PERCENT."))/100) ELSE $alias.price_per_person END";
    }

    // Adds `discount` (the largest percent in force) and `regular_price`, and swaps price_per_person and every
    // private rate's price for the selling price. Each rate keeps `regular` and its in-force `discount`.
    // A private period's price_per_person becomes its cheapest selling rate, regular_price that rate's regular price.
    public static function apply($dep)
    {
        if (array_key_exists('regular_price', $dep)) {
            return $dep;
        }
        if (empty($dep['private_rates'])) {
            $percent = self::percent($dep);
            $dep['discount'] = $percent;
            $dep['regular_price'] = (float) $dep['price_per_person'];
            $dep['price_per_person'] = self::price($dep['price_per_person'], $percent);

            return $dep;
        }
        $running = self::running($dep);
        $rates = json_decode($dep['private_rates'], true) ?: [];
        $cheapest = null;
        $best = 0;
        foreach ($rates as &$rate) {
            $percent = $running ? self::clamp($rate['discount'] ?? 0) : 0;
            $rate['discount'] = $percent;
            $rate['regular'] = (float) $rate['price'];
            $rate['price'] = self::price($rate['price'], $percent);
            $best = max($best, $percent);
            if ($cheapest === null || $rate['price'] < $cheapest['price']) {
                $cheapest = $rate;
            }
        }
        unset($rate);
        $dep['private_rates'] = json_encode($rates, JSON_UNESCAPED_UNICODE);
        $dep['discount'] = $best;
        $dep['regular_price'] = $cheapest ? $cheapest['regular'] : (float) $dep['price_per_person'];
        if ($cheapest) {
            $dep['price_per_person'] = $cheapest['price'];
        }

        return $dep;
    }

    // Discounts on every price a visitor can still book: each private rate, each group departure with seats.
    private static function open($deps)
    {
        $open = [];
        foreach ($deps as $dep) {
            if (! empty($dep['private_rates'])) {
                foreach (json_decode($dep['private_rates'], true) ?: [] as $rate) {
                    $open[] = (int) ($rate['discount'] ?? 0);
                }
            } elseif ((int) ($dep['seats_available'] ?? 0) > 0) {
                $open[] = (int) ($dep['discount'] ?? 0);
            }
        }

        return $open;
    }

    public static function best($deps)
    {
        return max([0, ...self::open($deps)]);
    }

    // Badge wording: "ลด" when every open price has the same discount, otherwise "ลดสูงสุด".
    public static function label($deps)
    {
        return count(array_unique(self::open($deps))) > 1 ? 'ลดสูงสุด' : 'ลด';
    }

    // Whether some open prices carry no discount at all (the detail page then says "in some departures").
    public static function partial($deps)
    {
        return in_array(0, self::open($deps), true);
    }
}
