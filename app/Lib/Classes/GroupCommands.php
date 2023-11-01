<?php

namespace App\Lib\Classes;

use App\Exports\ShotExport;
use App\Lib\Interfaces\TelegramOperator;
use App\Models\BlockList;
use App\Models\Group;
use App\Models\Shot;
use Illuminate\Support\Facades\Cache;
use Maatwebsite\Excel\Facades\Excel;
use Morilog\Jalali\Jalalian;
use Telegram\Bot\FileUpload\InputFile;

class GroupCommands extends TelegramOperator
{
    public $commands = [
        '#ریست',
        '#قیمت',
        '#بلاک',
        '#هزینه',
        '#توضیحات',
        '#لغو',
        '#محاسبه',
        '#ملی',
        '#پاسارگاد',
        "#ثبت",
        "#توقف",
        "#اکسل",

    ];

    public function initCheck()
    {

        if (!$this->telegram->user->admin) {
            return false;
        }
        $a = ($this->telegram->group && $this->telegram->message_type == "message");
        if (!$a) {
            return false;
        }
        foreach ($this->commands as $command) {
            if (strpos($this->telegram->text, $command) === 0) {
                return true;
            }
        }
    }

    public function handel()
    {
        $text = $this->telegram->text;
        $text = str_replace('  ', ' ', $text);
        $command = explode(' ', $text);
        try {

            switch ($command[0]) {
                case '#ریست':
                    $this->reset();
                    break;
                case '#قیمت':
                    $this->setPrice($command);
                    break;
                case '#هزینه':
                    Group::query()->where('chat_id', $this->telegram->chat_id)->update([
                        'subtraction' => $command[1]
                    ]);
                    sendMessage([
                        'chat_id' => $this->telegram->chat_id,
                        'text' => "هزینه به مقدار " . $command[1] . " تغییر کرد"
                    ]);
                    break;
                case "#محاسبه":
                    $this->invoice();
                    break;
                case "#ملی":
                    $this->meli();
                    break;
                case "#پاسارگاد":
                    $this->pasargard();
                    break;
                case "#اکسل":
                    $this->excel();
                    break;
                case "#توضیحات":
                    Group::query()->where('chat_id', $this->telegram->chat_id)->update([
                        'show_name' => 1
                    ]);
                    sendMessage([
                        'chat_id' => $this->telegram->chat_id,
                        'text' => "نمایش نام فعال شد"
                    ]);
                    break;
                case "#لغو":
                    Group::query()->where('chat_id', $this->telegram->chat_id)->update([
                        'show_name' => 0
                    ]);
                    sendMessage([
                        'chat_id' => $this->telegram->chat_id,
                        'text' => "نمایش نام غیرفعال شد"
                    ]);
                    break;

                case '#ثبت':
                    $this->insertCron($command[1]);
                    break;
                case '#توقف':
                    $this->stopCron();
                    break;
                default:
                    sendMessage([
                        'chat_id' => $this->telegram->chat_id,
                        'text' => '-'
                    ]);
            }
        } catch (\Exception $e) {

        }


    }

    private function reset()
    {
        Group::query()->updateOrCreate([
            'chat_id' => $this->telegram->chat_id
        ], [
            'chat_id' => $this->telegram->chat_id,
            'name' => $this->update['message']['chat']['title'],
            'prices' => '{}',
            'default_price' => 0,
            'subtraction' => 0,
            "show_name" => 0,
            'total_amount' => 0,
            'total_subtraction' => 0,
            'view' => 0,
            'shot_count' => 0

        ]);
        $group = Group::query()->where('chat_id', $this->telegram->chat_id)->first();
        Shot::query()->where('group_id', $group->id)->delete();
        sendMessage([
            'chat_id' => $this->telegram->chat_id,
            'text' => 'گروه ریست شد'
        ]);
    }

    private function setPrice($command)
    {
        if (count($command) == 2) {
            Group::query()->where('chat_id', $this->telegram->chat_id)->update([
                'default_price' => $command[1]
            ]);
            sendMessage([
                'chat_id' => $this->telegram->chat_id,
                'text' => 'قیمت پیشفرض به ' . $command[1] . ' تغییر کرد'
            ]);
        } else {
            $group = Group::query()->where('chat_id', $this->telegram->chat_id)->first();
            $prices = json_decode($group->prices, true);
            $prices[$command[1]] = $command[2];
            Group::query()->where('chat_id', $this->telegram->chat_id)->update([
                'prices' => json_encode($prices)
            ]);
            sendMessage([
                'chat_id' => $this->telegram->chat_id,
                'text' => 'قیمت بنر ' . $command[1] . ' به ' . $command[2] . ' تغییر کرد'
            ]);
        }
    }

    private function invoice()
    {
        $group = Group::query()->where('chat_id', $this->telegram->chat_id)->first();
        $shots = Group::query()->where('chat_id', $this->telegram->chat_id)->first()->shots;
        $view_count = Group::query()->where('chat_id', $this->telegram->chat_id)->first()->shots->sum('amount');
        $text = "📉 صورتحساب";
        $text .= "\n\n";
        $text .= "💠 تعداد شات ها : " . $group->shot_count;
        $text .= "\n\n";
        $text .= "💠 تعداد کل ویو : " . $view_count . "<b>K</b>";
//        $text .= "\n\n";
//        $text .= "💠 مجموع مبلغ : " . $group->total_amount . "ریال ";
//        $text .= "\n\n";
//        $text .= "💠 مجموع کسورات : " . $group->total_subtraction . "ریال ";
        $text .= "\n\n";
        $text .= "💠 مجموع مبلغ پرداختی : " . number_format(($view_count * $group->default_price) - $shots->count() * $group->subtraction) . "تومان ";
        $text .= "\n\n";
        $text .= "💠 نام گروه :" . $this->telegram->update["message"]["chat"]["title"];
        $text .= "\n\n";
//        $jDate = Jalalian::fromDateTime(Carbon::now());
        $text .= "🕰 زمان  : " . Jalalian::forge('today')->format('%A, %d %B');
        sendMessage([
            'chat_id' => $this->telegram->chat_id,
            'text' => $text,
            'parse_mode' => 'HTML',
        ]);
    }

    private function meli()
    {
        $a = sendMessage([
            'chat_id' => $this->telegram->chat_id,
            'text' => "در حال ایجاد فایل"
        ]);
        $group = Group::query()->where('chat_id', $this->telegram->chat_id)->first();
//        devLog($group->id);
        $file = "";
//        $rand = random_int(10000, 50000);

        foreach ($group->shots as $i => $shot) {
            if (BlockList::query()->where('card_number', $shot->card_number)->orWhere('shaba', $shot->shaba_number)->first()) {
                continue;
            }
            $amount = (($shot->fee * $shot->amount) - $shot->subtraction) * 10;

            $b = $i + 1;
            $shaba = strtoupper($shot->shaba_number);
            $file .= "$amount,$shaba,$b,{$shot->card_name}" . PHP_EOL;
        }
        file_put_contents(public_path('meli.txt'), $file);
        sendDocument([
            'chat_id' => $this->telegram->chat_id,
            'document' => InputFile::create(public_path('meli.txt')),
            'caption' => "💵فایل ارسال گروهی بانک ملی
نام گروه :" . $this->telegram->update["message"]["chat"]["title"] . "\n" .
                "🕰 زمان  : " . Jalalian::forge('today')->format('%A, %d %B')

        ]);
        deleteMessage([
            'chat_id' => $this->telegram->chat_id,
            'message_id' => $a['message_id']
        ]);
//        devLog($a);
    }

    private function pasargard()
    {
        $a = sendMessage([
            'chat_id' => $this->telegram->chat_id,
            'text' => "در حال ایجاد فایل"
        ]);
        $group = Group::query()->where('chat_id', $this->telegram->chat_id)->first();
        $file = "";
        $rand = random_int(10000, 500000);
        foreach ($group->shots as $i => $shot) {
            if (BlockList::query()->where('card_number', $shot->card_number)->orWhere('shaba', $shot->shaba_number)->first()) {
                continue;
            }
            $amount = (($shot->fee * $shot->amount) - $shot->subtraction) * 10;
            $b = $i + 1;
            $shaba = str_replace('IR', '', strtoupper($shot->shaba_number));
            $file .= "$shaba,$amount,$rand,$b,{$shot->card_name}," . PHP_EOL;
        }
        file_put_contents(public_path('pasargad.txt'), $file);
        sendDocument([
            'chat_id' => $this->telegram->chat_id,
            'document' => InputFile::create(public_path('pasargad.txt')),
            'caption' => "💵فایل ارسال گروهی بانک پاسارگاد
نام گروه :" . $this->telegram->update["message"]["chat"]["title"] . "\n" .
                "🕰 زمان  : " . Jalalian::forge('today')->format('%A, %d %B')
        ]);
        deleteMessage([
            'chat_id' => $this->telegram->chat_id,
            'message_id' => $a['message_id']
        ]);
//        devLog($a);
    }

    private function excel()
    {
        $a = sendMessage([
            'chat_id' => $this->telegram->chat_id,
            'text' => "در حال ایجاد فایل"
        ]);
        $group = Group::query()->where('chat_id', $this->telegram->chat_id)->first();
         Excel::store(new ShotExport($group->shots),('invoices.xlsx'));
        sendDocument([
            'chat_id' => $this->telegram->chat_id,
            'document' => InputFile::create(storage_path('app/invoices.xlsx')),
            'caption' => "💵گزارش اکسل
نام گروه :" . $this->telegram->update["message"]["chat"]["title"] . "\n" .
                "🕰 زمان  : " . Jalalian::forge('today')->format('%A, %d %B')
        ]);
        deleteMessage([
            'chat_id' => $this->telegram->chat_id,
            'message_id' => $a['message_id']
        ]);
//        devLog($a);
    }

    private function insertCron($min)
    {
        if ($this->telegram->reply_to_message_id == "") {
            return sendMessage([
                'chat_id' => $this->telegram->chat_id,
                'text' => "لطفا روی پیامی ریپلای کنید"
            ]);
        }
        $copy_id = $this->telegram->reply_to_message_id;
        if (!is_numeric($min)) {
            return sendMessage([
                'chat_id' => $this->telegram->chat_id,
                'text' => "مقدار دقیقه باید با بصورت عددی باشد."
            ]);
        }
        $list = [];
        if (Cache::has('cron_list')) {
            $list = Cache::get('cron_list');
        }
        $list[$this->telegram->chat_id] = [
            'chat_id' => $this->telegram->chat_id,
            'message_id' => $copy_id,
            'min' => $min
        ];
        Cache::put('cron_list', $list, now()->addDays(5));

        sendMessage([
            'chat_id' => $this->telegram->chat_id,
            'text' => "ثبت شد"
        ]);

    }

    private function stopCron()
    {
        $list = [];
        if (Cache::has('cron_list')) {
            $list = Cache::get('cron_list');
        }
        unset($list[$this->telegram->chat_id]);
        Cache::put('cron_list', $list, now()->addDays(5));

        sendMessage([
            'chat_id' => $this->telegram->chat_id,
            'text' => "ارسال لغو شد"
        ]);

    }
}
