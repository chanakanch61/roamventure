<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use PDO;
use Throwable;

/** Six sample programs. All dates, prices and itineraries are demonstration data. */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $pdo = DB::getPdo();
        $samples = [
            ['chiang-mai-mountain', 'เชียงใหม่', 'เชียงใหม่ พักใจในโอบกอดของขุนเขา', 'chiangmai.jpg', 3, 8900, 'เดินเล่นเมืองเก่า จิบกาแฟบนดอย และใช้เวลาช้า ๆ กับคนที่คุณอยากพามาด้วย', 'เมืองเก่าและวัดพระสิงห์|ม่อนแจ่มและสวนดอกไม้|คาเฟ่และงานคราฟต์ท้องถิ่น'],
            ['krabi-island', 'กระบี่', 'กระบี่ ทะเลสีฟ้าและวันดี ๆ ของเรา', 'krabi.jpg', 3, 11900, 'ปล่อยใจไปกับน้ำทะเลใส ล่องเรือชมเกาะ และเก็บภาพสวยของกรุ๊ปคุณ', 'อ่าวนางและพระอาทิตย์ตก|ทริปเกาะและชายหาด|เดินเล่นชุมชนและคาเฟ่'],
            ['nan-slow-life', 'น่าน', 'น่าน เติมความสุขระหว่างทาง', 'nan.jpg', 3, 7900, 'ถนนสายเล็ก ทุ่งนา และขุนเขา ชวนกันไปพักจากความวุ่นวายในทริปส่วนตัว', 'วัดภูมินทร์และเมืองเก่าน่าน|ปัวและวิวทุ่งนา|บ่อเกลือและชุมชนท้องถิ่น'],
            ['khao-yai-weekend', 'เขาใหญ่', 'เขาใหญ่ วันหยุดที่อยากให้ยาวขึ้น', 'khaoyai.jpg', 2, 5900, 'ทริปใกล้กรุงสำหรับแก๊งคนโปรด พักท่ามกลางธรรมชาติและแวะมุมถ่ายรูป', 'คาเฟ่และสวนดอกไม้|ธรรมชาติและจุดชมวิว'],
            ['phang-nga-seaside', 'พังงา', 'พังงา เก็บแสงเช้าและเรื่องราวริมทะเล', 'phangnga.jpg', 3, 10900, 'ชวนกันดูแสงเช้าเหนืออ่าว เดินชายหาด และเก็บความทรงจำในจังหวะสบาย ๆ', 'เขาหลักและชายหาด|จุดชมวิวอ่าวพังงา|ชุมชนและร้านกาแฟท้องถิ่น'],
            ['chiang-mai-photo', 'เชียงใหม่', 'เชียงใหม่ ทริปของเราในภาพความทรงจำ', 'story.jpg', 4, 12900, 'ออกไปค้นพบมุมใหม่ของเชียงใหม่ พร้อมไกด์และช่างภาพที่ดูแลกรุ๊ปคุณ', 'เมืองเก่าเชียงใหม่|ดอยและสวนดอกไม้|ชุมชนงานคราฟต์|คาเฟ่และตลาดท้องถิ่น'],
        ];
        $pdo->beginTransaction();
        try {
            foreach ($samples as $i => $s) {
                [$slug,$destination,$title,$image,$days,$price,$summary,$highlights] = $s;
                $now = date('Y-m-d H:i:s');
                $id = $this->insertRow($pdo, 'tours', ['code' => 'ZD-'.str_pad($i + 1, 3, '0', STR_PAD_LEFT), 'slug' => $slug, 'title' => $title, 'destination' => $destination, 'days' => $days, 'nights' => $days - 1, 'summary' => $summary, 'highlights' => str_replace('|', "\n", $highlights), 'cover' => 'public/images/'.$image, 'hotel_status' => 'included', 'hotel_details' => 'ที่พักระดับ 3–4 ดาว ห้องพักคู่ หรือเทียบเท่า ตามรายละเอียดที่ทีมงานยืนยัน', 'transport_status' => 'included', 'transport_details' => 'รถส่วนตัวพร้อมคนขับตามขนาดกรุ๊ป รับส่งตามจุดนัดหมายในโปรแกรม', 'guide_status' => 'included', 'guide_details' => 'ไกด์ดูแลตลอดโปรแกรม สำหรับสมาชิกในกรุ๊ปของคุณ', 'photographer_status' => $i === 3 ? 'extra' : 'included', 'photographer_details' => $i === 3 ? 'สามารถขอเพิ่มช่างภาพ โดยทีมงานเสนอราคาแยกก่อนยืนยัน' : 'ช่างภาพร่วมเดินทาง เก็บภาพบรรยากาศและส่งไฟล์ภาพที่คัดแล้วตามที่ตกลง', 'exclusions' => "ตั๋วเครื่องบินและค่าเดินทางก่อนถึงจุดนัดพบ\nมื้ออาหารที่ไม่ได้ระบุว่ารวม\nค่าใช้จ่ายส่วนตัว และบริการเพิ่มเติมนอกโปรแกรม", 'terms' => "ข้อมูลตัวอย่างสำหรับทดสอบเว็บไซต์ ยังไม่ใช่ข้อเสนอขายจริง\nราคาต่อคน จองได้ตั้งแต่ 1 คนจนถึงที่นั่งว่างของรอบ\nการส่งคำขอไม่ใช่การยืนยันการจอง ต้องรอทีมงานตรวจสอบและแจ้งเงื่อนไขการชำระเงิน\nเงื่อนไขการเปลี่ยนวันและยกเลิกต้องได้รับการยืนยันจากทีมงานก่อนชำระเงิน", 'status' => 'published', 'featured' => 1, 'sort_order' => $i, 'seo_description' => $summary, 'created_at' => $now, 'updated_at' => $now]);
                $activities = explode('|', $highlights);
                for ($day = 1; $day <= $days; $day++) {
                    $this->insertRow($pdo, 'tour_days', ['tour_id' => $id, 'day_number' => $day, 'title' => $activities[$day - 1], 'description' => 'โปรแกรมตัวอย่าง: '.$activities[$day - 1]."\nเดินทางด้วยรถส่วนตัว แวะเที่ยวและถ่ายภาพตามโปรแกรม พร้อมเวลาพักระหว่างทาง\nจุดนัดพบ เวลาเดินทาง และรายละเอียดสถานที่จะยืนยันกับทีมงานก่อนจอง", 'hotel' => $day < $days ? 'โรงแรมตามโปรแกรม หรือเทียบเท่า · ห้องพักคู่' : '', 'breakfast' => $day > 1 ? 1 : 0, 'lunch' => 0, 'dinner' => 0]);
                }
                foreach ([$image, 'hero.jpg'] as $j => $img) {
                    $this->insertRow($pdo, 'tour_images', ['tour_id' => $id, 'path' => 'public/images/'.$img, 'caption' => 'ภาพบรรยากาศประกอบ ไม่ใช่ภาพรับรองสถานที่พักในโปรแกรม', 'sort_order' => $j]);
                }
                foreach ([25 + $i * 3, 55 + $i * 3, 85 + $i * 3] as $offset) {
                    $start = date('Y-m-d', strtotime('+'.$offset.' days'));
                    $end = date('Y-m-d', strtotime($start.' +'.($days - 1).' days'));
                    $this->insertRow($pdo, 'departures', ['tour_id' => $id, 'start_date' => $start, 'end_date' => $end, 'active' => 1, 'capacity' => 12, 'price_per_person' => $price]);
                }
            }
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    private function insertRow(PDO $pdo, string $table, array $row)
    {
        $cols = array_keys($row);
        $sql = 'INSERT INTO '.$table.' (`'.implode('`,`', $cols).'`) VALUES ('.implode(',', array_fill(0, count($cols), '?')).')';
        $pdo->prepare($sql)->execute(array_values($row));

        return $pdo->lastInsertId();
    }
}
