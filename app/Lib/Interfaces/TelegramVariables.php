<?php

namespace App\Lib\Interfaces;

use App\Models\Account;
use Illuminate\Support\Facades\Cache;

class TelegramVariables
{
    public $message_type, $data, $text, $chat_id, $from_id, $message_id, $reply_to_message,$update,$group,$reply_to_message_id;
    public $user = null;

    public function __construct($update)
    {
        $this->update = $update;
        $this->message_type = messageType($update);
        $this->group = (isset($this->update['message']['chat']['type']) && ($this->update['message']['chat']['type'] == "supergroup" || $this->update['message']['chat']['type'] == "group"));
        $name = " -";
        $username = "-";
        if ($this->message_type == "callback_query") {
            $this->data = $update["callback_query"]['data'];
            $this->chat_id = $update["callback_query"]['message']['chat']['id'];
            $this->from_id = $update["callback_query"]['from']['id'];
            $this->message_id = $update["callback_query"]["message"]['message_id'];
            $this->text = $update["callback_query"]['message']['text'] ?? "";
//            $username = $update["callback_query"]['from']['username'];
//            $name = $update["callback_query"]['from']['first_name'];
        } elseif ($this->message_type == "channel_post" || $this->message_type == "channel_photo") {
            $this->text = $update['channel_post']['text'] ?? "//**";
            $this->chat_id = $update['channel_post']['chat']['id'] ?? "";
            $this->from_id = $update['channel_post']['from']['id'] ?? "";
            $this->message_id = $update['channel_post']['message_id'] ?? "";
            $this->reply_to_message = $update['channel_post']['reply_to_message']['message_id'] ?? "";
            $username = $update['channel_post']['from']['username'] ?? "";
            $name = $update['channel_post']['from']['first_name'] ?? "";
        } else {
            $this->text = $update['message']['text'] ?? "//**";
            $this->chat_id = $update['message']['chat']['id'] ?? "";
            $this->from_id = $update['message']['from']['id'] ?? "";
            $this->message_id = $update['message']['message_id'] ?? "";
            $this->reply_to_message = $update['message']['reply_to_message'] ?? "";
            $this->reply_to_message_id = $update['message']['reply_to_message']['message_id'] ?? "";
            $username = $update['message']['from']['username'] ?? "";
            $name = $update['message']['from']['first_name'] ?? "";
        }
        if (isset($this->text) && !empty($this->text) && $this->text != null) {
            $this->text = convertPersianToEnglish($this->text);
        }

        $chat_id = $this->chat_id;
        $from_id = $this->from_id;
//        devLog($from_id);
//        if (!$this->group ) {

            $user = Cache::remember('useraccount' . $this->from_id, now()->addSeconds(20), function () use ($from_id, $name, $username) {
                return Account::query()->firstOrCreate(['chat_id' => $from_id], [
                    'name' => " - ",
                    'admin' => 0,
                ]);
            });
            if (isset($username) && $username != "-") {
                $user->username = $username;
                $user->name = $name;
                $user->save();
            }
            $this->user = $user;
//        }

    }

}
