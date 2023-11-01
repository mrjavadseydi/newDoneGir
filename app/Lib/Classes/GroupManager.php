<?php

namespace App\Lib\Classes;

use App\Lib\Interfaces\TelegramOperator;
use App\Models\BlockList;
use App\Models\Shot;

class GroupManager extends TelegramOperator
{

    public function initCheck()
    {
        if (!$this->telegram->user->admin) {
            return false;
        }
        $ex = [];
        if ($this->telegram->message_type == "message") {
            $ex = explode("\n", $this->telegram->text);
        }
        if ($this->telegram->group &&
            $this->telegram->reply_to_message_id != "" && ((is_numeric($this->telegram->text) ||
                (
                    count($ex) == 2 && is_numeric($ex[0])
                    && strpos($ex[1], '#') === 0))) &&
            (!$this->cardNumExist())

        ) {


            $a = sendMessage([
                'chat_id' => $this->telegram->chat_id,
                'text' => '⚠️اطلاعات این پیام قابل مشاهده نیست لطفا روی شماره کارت ریپلای کنید'
            ]);
//            deleteMessage([
//                'chat_id' => $this->telegram->chat_id,
//                'message_id' => $a['message_id']
//
//            ]);
            return false;
        }
        return ($this->telegram->group && $this->telegram->reply_to_message_id != "" &&
            $this->telegram->message_type == "message" && ($this->cardNumExist())
            && ((is_numeric($this->telegram->text) ||
                (count($ex) == 2 && is_numeric($ex[0]) && strpos($ex[1], '#') === 0))));
    }

    public function handel()
    {
        if (isset($this->telegram->reply_to_message['text'])) {
            $this->telegram->reply_to_message['caption'] = $this->telegram->reply_to_message['text'];
        }
        $ex = explode("\n", $this->telegram->text);
        $group = \App\Models\Group::query()->where('chat_id', $this->telegram->chat_id)->first();
        if (!$group) {
            return false;
        }
        if (count($ex) == 2) {
            $this->telegram->text = $ex[0];
            $banner = str_replace("#", '', $ex[1]);
            $prices = json_decode($group->prices, true);
            if (isset($prices[$banner])) {
                $price = $prices[$banner];
            } else {
                return sendMessage([
                    'chat_id' => $this->telegram->chat_id,
                    'text' => "قیمت بنر وارد شده در لیست قیمت ها وجود ندارد"
                ]);
            }
        } else {
            $price = $group->default_price;
        }
        $caption = $this->replaceCaption($this->telegram->reply_to_message['caption']);

        $pattern = '/@[\w\d._]+/';
        $ready_caption = str_replace('http://instagram.com/', '@', strtolower($this->telegram->reply_to_message['caption']));
        $ready_caption = str_replace('https://instagram.com/', '@', strtolower($ready_caption));
        $ready_caption = str_replace('https://www.instagram.com/', '@', strtolower($ready_caption));
        $ready_caption = str_replace('http://www.instagram.com/', '@', strtolower($ready_caption));
        $ready_caption = str_replace('www.instagram.com/', '@', strtolower($ready_caption));
        $ready_caption = str_replace('instagram.com/', '@', strtolower($ready_caption));
        $e2t = explode("\n", $ready_caption);
        $ready_caption = "";
        $cardNumber = "";
        $shaba = "";
        foreach ($e2t as $t) {
            if (strlen(str_replace(' ', '', $t)) == 26) {
                $shaba = str_replace(' ', '', $t);
                if (strpos(strtoupper($shaba), 'IR') === false) {
                    $shaba = "";
                }
            } elseif (strlen(str_replace(' ', '', $t)) == 24) {
                $shaba = "IR" . str_replace(' ', '', $t);
                if (!is_numeric(str_replace(' ', '', $t))) {
                    $shaba = "";
                }
            } elseif (strlen($t) == 16) {
                $cardNumber = str_replace(' ', '', $t);
            } elseif (strlen($t) > 3) {
                $ready_caption .= '@' . $t . "\n";
            }

        }
        $ready_caption = str_replace('@@', '@', strtolower($ready_caption));
        if ($cardNumber == "") {
            $cardPattern = '/\d{16}/';
            preg_match($cardPattern, str_replace(' ', '', $this->telegram->reply_to_message['caption']), $cardMatches);
            if (isset($cardMatches[0])) {
                $cardNumber = $cardMatches[0];
            } else {
                return sendMessage([
                    'chat_id' => $this->telegram->chat_id,
                    'text' => "شماره کارت در پیام یافت نشد"
                ]);
            }
        }
        if ($shaba == "") {
            $shabaPattern = '/IR\d{24}/';
            preg_match($shabaPattern, strtoupper(str_replace(' ', '', $this->telegram->reply_to_message['caption'])), $shabaMatches);
            if (isset($shabaMatches[0])) {
                $shaba = $shabaMatches[0];

            } else {
                return sendMessage([
                    'chat_id' => $this->telegram->chat_id,
                    'text' => "شماره شبا در پیام یافت نشد"
                ]);
            }
        }
        preg_match_all($pattern, $ready_caption, $matches);
        $tags = $matches[0];
        $text = "";
        $warnings = "";
        foreach ($tags as $tag) {
            ///search for duplicates tags in database
            $dup = Shot::query()->where('group_id', $group->id)->where('pages', 'like', '%"' . $tag . '"%')->first();
            if ($dup) {
                $warnings .= "⚠️ تگ " . $tag . " قبلا در این گروه استفاده شده است \n";
            }
            $text .= "🆔" . $tag . "\n";
        }
        $text .= "\n";
        $text .= $warnings . "\n";

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
        $text .= "💶 <b>Price</b> :<code>" . number_format((($this->telegram->text * $price) - $group->subtraction)) . "</code> Toman \n";
        $text .= "💳 <b>Card Number</b> : \n <code>" . $cardNumber . "</code>\n";
        $text .= "🏦 <b>Sheba Number</b>: <code>" . $shaba . "</code>\n";

        if ($group->show_name) {
            $text .= "➖➖➖<b> Description </b>➖➖➖ \n";
            $text .= $this->extractName($caption) . "\n";
        }

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
                if (strlen($name) < 5) {
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

    private function cardNumExist()
    {
        $text = "";
        if (isset($this->telegram->reply_to_message['caption'])) {
            $text = $this->telegram->reply_to_message['caption'];
        } elseif (isset($this->telegram->reply_to_message['text'])) {
            $text = $this->telegram->reply_to_message['text'];
        } else {
            return false;
        }

        $cardPattern = '/\d{16}/';

        preg_match($cardPattern, (str_replace(' ', '', $text)), $cardMatches);
        return isset($cardMatches[0]);
    }

}
