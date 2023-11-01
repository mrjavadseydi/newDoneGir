<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;

class ShotExport implements FromCollection
{
    public $coll;

    public function __construct($coll)
    {
        $this->coll = $coll;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $coll = $this->coll;
        $newColl = collect();
        $newColl->push([
            '#',
            'تاریخ',
            'چت آیدی کاربر',
            'تگ پیج ها',
            'قیمت تبلیغ',
            'ویو',
            'درامد',
            'هزینه کارت به کارت',
            'مبلغ قابل پرداخت',
            'شماره کارت',
            'شماره شبا',
            'نام صاحب حساب',
        ]);
        foreach ($coll as $i => $data) {
            $newColl->push([
                $i + 1,
                jdate($data->created_at)->format('Y/m/d'),
                $data->user_chat_id,
                implode("\n🆔 ", json_decode($data->pages)),
                number_format($data->fee * 10) . "ریال",
                $data->amount . "K",
                number_format($data->fee * 10 * $data->amount) . "ریال",
                number_format($data->subtraction * 10) . "ریال",
                number_format(($data->fee * 10 * $data->amount) - ($data->subtraction * 10)) . "ریال",
                $data->card_number,
                $data->shaba_number,
                $data->card_name,

            ]);
        }
//        devLog($newColl->count());
        return $newColl;
    }
}
