<?php
namespace App\Lib\Classes\AdminManagment;
use App\Lib\Interfaces\TelegramOperator;
use App\Models\Account;
use App\Models\User;

class AdminList extends TelegramOperator
{

    public function initCheck()
    {
        return (!$this->telegram->group&&$this->telegram->user->admin&&$this->telegram->message_type=="message"&&$this->telegram->text== '👤لیست ادمین ها');
    }

    public function handel()
    {
        $users = Account::where('admin',1)->get();
        $text = "👤List of admins:\n";
        foreach ($users as $user){
            $text .= "👤 `".$user->chat_id."`\n";
        }
        sendMessage([
            'chat_id' => $this->telegram->chat_id,
            'text'=>$text,
            'reply_markup'=>backKey(),
            'parse_mode'=>'markdown',
        ]);
    }
}
