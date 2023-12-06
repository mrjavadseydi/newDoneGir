<?php

namespace App\Lib\Classes;

use App\Lib\Interfaces\TelegramOperator;
use App\Models\BlockList;
use App\Models\Shot;

class ShotManager extends TelegramOperator
{
    public $shot_id;
    public function initCheck()
    {
        if (!$this->telegram->user->admin){
            return false;
        }
        if ($this->telegram->message_type == "callback_query" ) {
            $ex = explode("_", $this->telegram->data);
            if ($ex[0] == "removeshot" || $ex[0] == "removeandblock" ) {
                $this->shot_id = $ex[1];
                return true;
            }
        }
    }

    public function handel()
    {
        $ex = explode("_", $this->telegram->data);

        $shot = Shot::query()->where('id',$this->shot_id)->first();
        if (!$shot){
            return false;
        }
        $group = \App\Models\Group::query()->where('id',$shot->group_id)->first();
        if (!$group){
            return false;
        }
        if ($ex[0]=="removeshot"){
            editMessageText([
                'chat_id'=>$this->telegram->chat_id,
                'message_id'=>$this->telegram->message_id,
                'text'=>"شات حذف شد"
            ]);

            ///subtract from group
            $group->update([
                'shot_count'=>$group->shot_count-1,
                'total_amount'=>$group->total_amount-($shot->amount*$shot->fee),
                'total_subtraction'=>$group->total_subtraction-$shot->subtraction,
                'view'=>$group->view-$shot->amount
            ]);


            $shot->delete();
        }else{
            BlockList::query()->create([
                "shaba"=>$shot->shaba_number,
                "card_number"=>$shot->card_number,
            ]);

            editMessageText([
                'chat_id'=>$this->telegram->chat_id,
                'message_id'=>$this->telegram->message_id,
                'text'=>"شات حذف شد و به لیست سیاه اضافه شد"
            ]);
            $group->update([
                'shot_count'=>$group->shot_count-1,
                'total_amount'=>$group->total_amount-($shot->amount*$shot->fee),
                'total_subtraction'=>$group->total_subtraction-$shot->subtraction,
                'view'=>$group->view-$shot->amount
            ]);


            $shot->delete();
        }
    }


}
