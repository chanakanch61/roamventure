<?php

namespace App\Repositories;

use App\Support\ResolvesServices;
use Illuminate\Support\Facades\DB;

class PassportRepository
{
    use ResolvesServices;

    public function eligible($b, $s = null)
    {
        $s = $s ?? $this->billing->state($b);
        if ($b['status'] !== 'confirmed' || ! $s['invoice'] || $s['paid'] <= 0) {
            return false;
        }
        $i = $s['invoice'];

        return $s['remaining'] === 0 || (! empty($i['deposit_enabled']) && $this->billing->cents($i['deposit_amount']) > 0 && $s['paid'] >= $this->billing->cents($i['deposit_amount']));
    }

    public function documents($id)
    {
        $rows = DB::table('booking_passports')->where('booking_id', $id)->orderBy('traveler_number')->get()->all();

        return array_column($rows, null, 'traveler_number');
    }

    public function path($name)
    {
        return is_string($name) && preg_match('/^[a-f0-9]{32}\.(jpg|jpeg|png)$/D', $name) ? base_path('storage/passports/'.$name) : null;
    }

    public function save($id, $number, $name)
    {
        $this->billing->begin();
        $b = DB::select('SELECT * FROM bookings WHERE id=? FOR UPDATE', [$id])[0] ?? null;
        if (! $b || ! $this->eligible($b) || $number < 1 || $number > (int) $b['traveler_count'] || ! $this->path($name)) {
            DB::rollBack();

            return 'การจองนี้ยังไม่เปิดรับพาสปอร์ตหรือไม่พบผู้เดินทาง';
        }
        $old = DB::table('booking_passports')->where('booking_id', $id)->where('traveler_number', $number)->first();
        $data = ['booking_id' => $id, 'traveler_number' => $number, 'file_name' => $name, 'uploaded_at' => date('Y-m-d H:i:s')];
        if ($old) {
            $this->billing->soft(fn () => DB::table('booking_passports')->where('id', $old['id'])->update($data));
        } else {
            $this->billing->soft(fn () => DB::table('booking_passports')->insert($data));
        }
        $this->billing->event($id, 'ได้รับพาสปอร์ตผู้เดินทางคนที่ '.$number);
        if (! $this->billing->trans_ok) {
            DB::rollBack();

            return 'บันทึกไม่สำเร็จ กรุณาลองใหม่';
        }
        DB::commit();
        if ($old && $old['file_name'] !== $name && ($path = $this->path($old['file_name'])) && is_file($path)) {
            unlink($path);
        }

        return null;
    }
}
