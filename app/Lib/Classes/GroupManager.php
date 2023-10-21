<?php

namespace App\Lib\Classes;

use App\Lib\Interfaces\TelegramOperator;
use App\Models\BlockList;
use App\Models\Shot;

class GroupManager extends TelegramOperator
{

    public function initCheck()
    {
        $ex = [];
        if ($this->telegram->message_type == "message") {
            $ex = explode("\n", $this->telegram->text);
        }
        return ($this->telegram->group && $this->telegram->reply_to_message_id != "" &&
            $this->telegram->message_type == "message" && isset($this->telegram->reply_to_message['caption'])
            && ((is_numeric($this->telegram->text) ||
                (count($ex) == 2 && is_numeric($ex[0]) && strpos($ex[1],'#')===0)) ));
    }

    public function handel()
    {
        $ex = explode("\n", $this->telegram->text);
        $group = \App\Models\Group::query()->where('chat_id', $this->telegram->chat_id)->first();

        if (count($ex) == 2) {
            $this->telegram->text = $ex[0];
            $banner = str_replace("#",'',$ex[1]);
            $prices = json_decode($group->prices,true);
            if (isset($prices[$banner])){
                $price = $prices[$banner];
            }else{
                return sendMessage([
                    'chat_id'=>$this->telegram->chat_id,
                    'text'=>"قیمت بنر وارد شده در لیست قیمت ها وجود ندارد"
                ]);
            }
        }else{
            $price = $group->default_price;
        }



        $pattern = '/@[\w\d._]+/';
        preg_match_all($pattern, $this->telegram->reply_to_message['caption'], $matches);

        $tags = $matches[0];
        $text = "";
        if (count($tags) == 0) {
            return sendMessage([
                'chat_id' => $this->telegram->chat_id,
                'text' => "در توضیحات پست شما تگی یافت نشد"
            ]);
        }
        foreach ($tags as $tag) {
            $text .= "🆔" . $tag . "\n";
        }
        $text .= "\n";
        $shabaPattern = '/IR\d{24}/';
        $cardPattern = '/\d{16}/';

        preg_match($shabaPattern, $this->telegram->reply_to_message['caption'], $shabaMatches);
        preg_match($cardPattern, $this->telegram->reply_to_message['caption'], $cardMatches);
        if (!isset($shabaMatches[0])) {
            return sendMessage([
                'chat_id' => $this->telegram->chat_id,
                'text' => "شبا در توضیحات پست شما یافت نشد"
            ]);
        }
        if (!isset($cardMatches[0])) {
            return sendMessage([
                'chat_id' => $this->telegram->chat_id,
                'text' => "شماره کارت در توضیحات پست شما یافت نشد"
            ]);
        }

        $shaba = $shabaMatches[0];
        $cardNumber = $cardMatches[0];
        $block_list = BlockList::query()->where('card_number', $cardNumber)->orWhere('shaba', $shaba)->first();
        if ($block_list) {
            return sendMessage([
                'chat_id' => $this->telegram->chat_id,
                'text' => "این کارت یا شبا در لیست سیاه ما موجود است",
                'reply_to_message_id' => $this->telegram->message_id,
                'reply_markup' => unblockUser($block_list->id)
            ]);
        }
        $text .= "📄 <b>Value</b> :<code> ( " . $this->telegram->text . " * " . $price . " ) - " . $group->subtraction . "</code>\n";
        $text .= "💶 <b>Price</b> :<code>" . ($this->telegram->text * $price) - $group->subtraction . "</code> Rial \n";
        $text .= "💳 <b>Card Number</b> : \n <code>" . $cardNumber . "</code>\n";
        $text .= "🏦 <b>Sheba Number</b>: <code>" . $shaba . "</code>\n";
        $text .= "➖➖➖<b> Description </b>➖➖➖ \n";
        $caption = $this->replaceCaption($this->telegram->reply_to_message['caption']);
        $text .= $this->extractName($caption) . "\n";
        $shot = Shot::query()->create([
            'group_id' => $group->id,
            'user_chat_id' => $this->telegram->reply_to_message['from']['id'],
            'pages' => json_encode($tags),
            'card_number' => $cardNumber,
            'shaba_number' => $shaba,
            'card_name' => $this->extractName($caption),
            'amount' => $this->telegram->text,
            'subtraction' => $group->subtraction,
            'fee' => $price,
        ]);
        sendMessage([
            'chat_id' => $this->telegram->chat_id,
            'text' => $text,
            'parse_mode' => 'HTML',
            'reply_to_message_id' => $this->telegram->message_id,
            'reply_markup' => shotKey($shot->id)
        ]);

        $group->shot_count++;
        $group->total_amount += ($this->telegram->text * $group->default_price);
        $group->view = $group->view + $this->telegram->text;
        $group->total_subtraction += $group->subtraction;
        $group->save();

    }

    public function extractName($text)
    {
        $lines = preg_split('/\r\n|\r|\n/', $text);

        foreach ($lines as $line) {
            $name = trim($line);

            // Check if the line contains a name
            if (preg_match('/^[\p{L}\p{M}\']+[\p{Zs}\p{P}]+[\p{L}\p{M}\']+$/u', $name)) {
                if (strlen($name) < 10) {
                    continue;
                }
                return $name;
            }
        }
        /// return persian part of text
        $text = preg_replace('/[^\x{0600}-\x{06FF}]/u', '', $text);
        return $text;
    }

    public function replaceCaption($text)
    {
        $text = str_replace('شماره شبا', '', $text);
        $text = str_replace('شماره کارت', '', $text);
        $text = str_replace('کارت', '', $text);
        $text = str_replace('شبا', '', $text);
        $text = str_replace('به نام', '', $text);
        $text = str_replace('حساب', '', $text);
        return $text;
    }

}
