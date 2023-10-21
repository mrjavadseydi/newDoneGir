<?php

namespace App\Lib\Classes;

use App\Lib\Interfaces\TelegramOperator;
use App\Models\Group;

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

    ];

    public function initCheck()
    {

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
        $text =  $this->telegram->text;
        $text = str_replace('  ',' ', $text);
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
//            case '#بلاک':
//                $this->block();
//                break;
                default:
                    sendMessage([
                        'chat_id' => $this->telegram->chat_id,
                        'text' => '-'
                    ]);
            }
        }catch (\Exception $e){

        }


    }
    private function reset(){
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
            'view'=>0

        ]);
        sendMessage([
            'chat_id' => $this->telegram->chat_id,
            'text' => 'گروه ریست شد'
        ]);
    }
    private function setPrice($command){
        if (count($command) == 2) {
            Group::query()->where('chat_id', $this->telegram->chat_id)->update([
                'default_price' => $command[1]
            ]);
            sendMessage([
                'chat_id' => $this->telegram->chat_id,
                'text' => 'قیمت پیشفرض به ' . $command[1] . ' تغییر کرد'
            ]);
        }else{
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
    private function invoice(){
        $group = Group::query()->where('chat_id', $this->telegram->chat_id)->first();
        $text = "📉 صورتحساب";
        $text .= "\n\n\n";
        $text .= "💠 تعداد شات ها : " . $group->shot_count;
        $text .= "\n\n";
        $text .= "💠 تعداد کل ویو : " . $group->view;
        $text .= "\n\n";
        $text .= "💠 مجموع مبلغ : " . $group->total_amount ."ریال ";
        $text .= "\n\n";
        $text .= "💠 مجموع کسورات : " . $group->total_subtraction  ."ریال ";
        $text .= "\n\n";
        $text .= "💠 مجموع مبلغ پرداختی : " . ($group->total_amount-$group->total_subtraction)  ."ریال ";
        sendMessage([
            'chat_id'=>$this->telegram->chat_id,
            'text'=>$text
        ]);
    }
}
