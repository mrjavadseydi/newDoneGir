<?php

namespace App\Lib\Classes;

use App\Lib\Interfaces\TelegramOperator;
use App\Models\BlockList;
use App\Models\Shot;

class UnblockUser extends TelegramOperator
{
    public $block_id;
    public function initCheck()
    {
        if (!$this->telegram->user->admin){
            return false;
        }
        if ($this->telegram->message_type == "callback_query" ) {
            $ex = explode("_", $this->telegram->data);
            if ($ex[0] == "unblock"  ) {
                $this->block_id = $ex[1];
                return true;
            }
        }
    }

    public function handel()
    {
        BlockList::query()->find($this->block_id)->delete();
        editMessageText([
            'chat_id'=>$this->telegram->chat_id,
            'message_id'=>$this->telegram->message_id,
            'text'=>"کاربر از لیست سیاه حذف شد"
        ]);
    }


}
