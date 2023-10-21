<?php
namespace App\Lib\Classes\AdminManagment;
use App\Lib\Interfaces\TelegramOperator;

class AddAdmin extends TelegramOperator
{

    public function initCheck()
    {
        return (!$this->telegram->group&&$this->telegram->user->admin&&$this->telegram->message_type=="message"&&$this->telegram->text=='👤اضافه کردن ادمین');
    }

    public function handel()
    {
        setState($this->telegram->chat_id,'AddAdmin');
        sendMessage([
            'chat_id' => $this->telegram->chat_id,
            'text'=>'please send user chat id 🛃',
            'reply_markup'=>backKey()
        ]);
    }
}
