<?php
namespace App\Lib\Classes\AdminManagment;
use App\Lib\Interfaces\TelegramOperator;

class DeleteAdmin extends TelegramOperator
{

    public function initCheck()
    {
        return (!$this->telegram->group&&$this->telegram->user->admin&&$this->telegram->message_type=="message"&&$this->telegram->text== '👤حذف کردن ادمین');
    }

    public function handel()
    {
        setState($this->telegram->chat_id,'DeleteAdmin');
        sendMessage([
            'chat_id' => $this->telegram->chat_id,
            'text'=>"🈳 please send chat id of admin to delete:",
            'reply_markup'=>backKey()
        ]);
    }
}
