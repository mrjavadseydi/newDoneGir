<?php

namespace App\Lib\Classes\AdminManagment;

use App\Lib\Interfaces\TelegramOperator;
use App\Models\Account;
use App\Models\BotAdmin;
use App\Models\User;

class StoreAdmin extends TelegramOperator
{

    public function initCheck()
    {
        return (!$this->telegram->group && $this->telegram->user->admin && $this->telegram->message_type == "message" && getState($this->telegram->chat_id) == "AddAdmin");
    }

    public function handel()
    {
        setState($this->telegram->chat_id, '');
        try {
            if ($user = Account::query()->where('chat_id', 'like', $this->telegram->text)->first()) {
                $user->update([
                    'admin' => 1
                ]);

                sendMessage([
                    'chat_id' => $this->telegram->chat_id,
                    'text' => "user added and set as admin!",
                    'reply_markup' => backKey()
                ]);
            }else{
                sendMessage([
                    'chat_id' => $this->telegram->chat_id,
                    'text' => "کاربر یافت نشد",
                    'reply_markup' => backKey()
                ]);
            }

        } catch (\Exception $e) {
            sendMessage([
                'chat_id' => $this->telegram->chat_id,
                'text' => "خطا در ذخیره سازی",
                'reply_markup' => backKey()
            ]);
        }

    }
}
