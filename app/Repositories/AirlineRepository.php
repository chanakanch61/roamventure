<?php

namespace App\Repositories;

use App\Support\ResolvesServices;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class AirlineRepository
{
    use ResolvesServices;

    public $error = '';

    public function all()
    {
        return DB::table('airlines')->orderBy('sort_order')->orderBy('name')->get()->all();
    }

    public function find($id)
    {
        return DB::table('airlines')->where('id', (int) $id)->first();
    }

    // Active airlines, plus any inactive one this program already flies so its rows stay valid.
    public function options($tourId = 0)
    {
        $used = $tourId ? DB::table('tour_flights as f')->join('tour_flight_sets as s', 's.id', '=', 'f.flight_set_id')->where('s.tour_id', (int) $tourId)->pluck('f.airline_id')->all() : [];

        return array_values(array_filter($this->all(), function ($airline) use ($used) {
            return $airline['is_active'] || in_array($airline['id'], $used);
        }));
    }

    public function usages($id)
    {
        return DB::table('tour_flights as f')->join('tour_flight_sets as s', 's.id', '=', 'f.flight_set_id')->where('f.airline_id', (int) $id)->distinct()->count('s.tour_id');
    }

    public function save($id, $data)
    {
        $this->error = '';
        if (DB::table('airlines')->where('name', $data['name'])->where('id', '!=', (int) $id)->exists()) {
            $this->error = 'ชื่อสายการบินนี้มีอยู่แล้ว';

            return false;
        }
        $data['updated_at'] = date('Y-m-d H:i:s');
        try {
            if ($id) {
                DB::table('airlines')->where('id', $id)->update($data);
            } else {
                $data['created_at'] = $data['updated_at'];
                $id = DB::table('airlines')->insertGetId($data);
            }
        } catch (QueryException $e) {
            $this->error = 'บันทึกไม่สำเร็จ กรุณาลองใหม่';

            return false;
        }

        return $id;
    }

    public function delete($id)
    {
        $this->error = '';
        $airline = $this->find($id);
        if (! $airline) {
            $this->error = 'ไม่พบสายการบิน';

            return false;
        }
        if ($this->usages($id)) {
            $this->error = 'สายการบินนี้ใช้อยู่ในโปรแกรม ให้ปิดการใช้งานแทนการลบ';

            return false;
        }
        try {
            DB::table('airlines')->where('id', $id)->delete();
        } catch (QueryException $e) {
            $this->error = 'สายการบินนี้ใช้อยู่ในโปรแกรม ให้ปิดการใช้งานแทนการลบ';

            return false;
        }
        $this->remove_logo_file($airline['logo_path']);

        return true;
    }

    public function remove_logo_file($path)
    {
        if (preg_match('~^public/uploads/airlines/[a-f0-9]+\.(jpg|jpeg|png|webp)$~D', $path) && is_file(base_path($path))) {
            unlink(base_path($path));
        }
    }

    // Adds each program's flight sets and the short summary its cards and detail page show.
    public function attach($tours)
    {
        if (! $tours) {
            return [];
        }
        $sets = $this->sets_for_tours(array_column($tours, 'id'));
        foreach ($tours as &$tour) {
            $tour['flight_sets'] = $sets[$tour['id']] ?? [];
            $tour['flight_summary'] = $this->summary($tour);
        }
        unset($tour);

        return $tours;
    }

    private function sets_for_tours($tourIds)
    {
        $sets = DB::table('tour_flight_sets')->whereIn('tour_id', $tourIds)->orderBy('sort_order')->orderBy('id')->get()->all();
        if (! $sets) {
            return [];
        }
        $flights = DB::table('tour_flights as f')->selectRaw('f.*,a.name airline_name,a.code airline_code,a.logo_path airline_logo')->join('airlines as a', 'a.id', '=', 'f.airline_id')->whereIn('f.flight_set_id', array_column($sets, 'id'))->orderBy('f.sort_order')->orderBy('f.id')->get()->all();
        $bySet = [];
        foreach ($flights as $flight) {
            $bySet[$flight['flight_set_id']][] = $flight;
        }
        $result = [];
        foreach ($sets as $set) {
            $set['flights'] = $bySet[$set['id']] ?? [];
            $result[$set['tour_id']][] = $set;
        }

        return $result;
    }

    private function summary($tour)
    {
        if (($tour['program_type'] ?? 'fixed') === 'private' || empty($tour['flight_sets'])) {
            return null;
        }
        $byId = array_column($tour['flight_sets'], null, 'id');
        // Open rounds are what a customer can buy, so they decide which flights the card speaks for.
        $open = [];
        foreach ($tour['card_periods'] ?? $tour['departures'] ?? [] as $period) {
            if (! empty($period['flight_set_id']) && isset($byId[$period['flight_set_id']])) {
                $open[$period['flight_set_id']] = $byId[$period['flight_set_id']];
            }
        }
        $sets = array_values($open ?: $byId);
        $legs = array_merge(...array_column($sets, 'flights'));
        if (! $legs) {
            return null;
        }
        $outbound = array_values(array_filter($sets[0]['flights'], function ($leg) {
            return $leg['leg_type'] === 'outbound';
        }));
        $suggested = array_values(array_filter($legs, function ($leg) {
            return ! $leg['included'];
        }));

        return [
            'lead' => $outbound[0] ?? $sets[0]['flights'][0] ?? $legs[0],
            'same_flights' => count($sets) === 1,
            'airlines' => array_values(array_unique(array_column($legs, 'airline_name'))),
            'suggested_airline' => $suggested[0]['airline_name'] ?? '',
            'has_included' => count($suggested) < count($legs),
        ];
    }

    public function snapshot($setId)
    {
        if (! $setId) {
            return null;
        }
        $flights = DB::table('tour_flights as f')->selectRaw('f.leg_type,f.day_number,f.flight_number,f.from_airport,f.to_airport,f.depart_time,f.arrive_time,f.arrive_next_day,f.duration_minutes,f.included,a.name airline_name')->join('airlines as a', 'a.id', '=', 'f.airline_id')->where('f.flight_set_id', (int) $setId)->orderBy('f.sort_order')->orderBy('f.id')->get()->all();

        return $flights ? json_encode($flights, JSON_UNESCAPED_UNICODE) : null;
    }
}
