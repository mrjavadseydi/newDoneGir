<?php
namespace App\Lib\Classes\AdminManagment;
use App\Lib\Interfaces\TelegramOperator;
use App\Models\Account;
use App\Models\BotAdmin;
use App\Models\User;

class ConfirmDeleteAdmin extends TelegramOperator
{

    public function initCheck()
    {
        return (!$this->telegram->group&&$this->telegram->user->admin&&$this->telegram->message_type=="message"&&getState($this->telegram->chat_id)=="DeleteAdmin");
    }

    public function handel()
    {
        setState($this->telegram->chat_id,'');

        Account::query()->where('chat_id','like',$this->telegram->text)->update([
            'admin'=>0
        ]);
        sendMessage([
            'chat_id' => $this->telegram->chat_id,
            'text'=>'user deleted!',
            'reply_markup'=>backKey()
        ]);
    }
}
