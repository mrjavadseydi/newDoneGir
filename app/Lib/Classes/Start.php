<?php

namespace App\Lib\Classes;

use App\Lib\Interfaces\TelegramOperator;

class Start extends TelegramOperator
{

    public function initCheck()
    {
        return ($this->telegram->message_type == "message" && ($this->telegram->text == "/start" || $this->telegram->text == 'بازگشت ↪️') && !$this->telegram->group);
    }

    public function handel()
    {
        setState($this->telegram->chat_id, '');
        if ($this->telegram->user->admin) {
            sendMessage([
                'chat_id' => $this->telegram->chat_id,
                'text' => "سلام به ربات خوش امدید",
                'reply_markup' => adminMenu()
            ]);
        } else {
            sendMessage([
                'chat_id' => $this->telegram->chat_id,
                'text' => "شما به ربات دسترسی ندارید"
            ]);
        }

    }
}
