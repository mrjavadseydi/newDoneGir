<?php

namespace App\Lib\Classes;

use App\Lib\Interfaces\TelegramOperator;
use App\Models\BlockList;
use App\Models\Shot;
use App\Models\TagsBlockList;

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
        $ex1 = [];
        if ($this->telegram->message_type == "message") {
            $ex1 = explode(" ", $this->telegram->text);
        }
        if (
            $this->telegram->group &&
            $this->telegram->reply_to_message_id != "" &&
            (
            (
                is_numeric($this->telegram->text) ||
                (
                    count($ex) == 2 && is_numeric($ex[0])
                    && strpos($ex[1], '#') === 0
                )
                ||
                (
                    count($ex1) == 2 && is_numeric($ex1[0])
                    && str_contains($ex1[1], '%')
                )


            )
            )

            && (!$this->cardNumExist())

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
            &&
            ((is_numeric($this->telegram->text) ||
            (count($ex) == 2 && is_numeric($ex[0]) && strpos($ex[1], '#') === 0)
                || (
                (
                    count($ex1) == 2 && is_numeric($ex1[0])
                    && str_contains($ex1[1], '%')
                ))

            )));
    }

    public function handel()
    {
        $reply_text = $this->telegram->text;
//        if ($this->telegram->from_id!="1389610583"){
//            return sendMessage([
//                'chat_id'=>$this->telegram->chat_id,
//                'text'=>"در حال بروزرسانی لطفا حسابرسی انجام ندهید"
//            ]);
//        }
        if (isset($this->telegram->reply_to_message['text'])) {
            $this->telegram->reply_to_message['caption'] = $this->telegram->reply_to_message['text'];
        }

        $ex = explode("\n", $this->telegram->text);
        $group = \App\Models\Group::query()->where('chat_id', $this->telegram->chat_id)->first();
        if (!$group) {
            return false;
        }
        $name_append = "";
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
        $sub = 0;

        if (str_contains($this->telegram->text, '%') && str_contains($this->telegram->text, ' ')) {
            $ex1 = explode(' ', $this->telegram->text);
//            devLog($ex1);
            $sub = str_replace('%', '', $ex1[1]);
            if (!is_numeric($sub)) {
                $sub = 0;
            } else {
                $this->telegram->text = $ex1[0];
            }
        }
        ///remove anything and accept numbers only
//        $this->telegram->text = preg_replace('/[^0-9]/', '', $this->telegram->text);

        $caption = remove_emojis($this->replaceCaption($this->telegram->reply_to_message['caption']));
//        $caption = preg_replace('/[\x00-\x1F\x80-\x9F]/u', '', $caption);
//        $caption = preg_replace('/[\x{200B}-\x{200D}\x{FEFF}]/u', '', $caption);

        $ready_caption = str_replace('http://instagram.com/', '@', strtolower($caption));
        $ready_caption = str_replace('https://instagram.com/', '@', strtolower($ready_caption));
        $ready_caption = str_replace('https://www.instagram.com/', '@', strtolower($ready_caption));
        $ready_caption = str_replace('http://www.instagram.com/', '@', strtolower($ready_caption));
        $ready_caption = str_replace('www.instagram.com/', '@', strtolower($ready_caption));
        $ready_caption = str_replace('instagram.com/', '@', strtolower($ready_caption));

        $tagstring = "";
        $tags_step_1 = $this->findTags($caption);
        foreach ($tags_step_1 as $tag) {
            $tagstring .= $tag . "\n";
            $ready_caption = str_replace($tag, '', $ready_caption);
        }

        $e2t = explode("\n", $ready_caption);
        $ready_caption = $tagstring;
        $cardNumber  = "";
        $shaba = "";
        $unwanted_chars = [
            ' ', '  ', ',', '-', '_', ':', '.', 'IR', 'Ir', 'ir', 'iR', ' ', "\n","‌"
        ];
        foreach ($e2t as $t) {
            $has_space = false;
            if (str_contains($t, ' ')) {
                $has_space = true;
            }
            $replaced_text = convertPersianToEnglish(trim(str_replace($unwanted_chars, '', $t)));
            if (strlen($replaced_text) == 24 && is_numeric($replaced_text)) {
                $shaba = "IR" . $replaced_text;
            } else if (strlen($replaced_text) == 16 && is_numeric($replaced_text)) {
                $cardNumber = $replaced_text;
            } else if (!$has_space) {
                $t = preg_replace('/[\x{0600}-\x{06FF}\x{0750}-\x{077F}\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFF}\x{10E60}-\x{10E7F}]/u', '', $t);
                $t = preg_replace('/[@._\-\/\\\[\]=]/', '', $t);
                if (strlen($t) > 2) {
                    $ready_caption .=  $t . "\n";
                }
            }
        }
        $text = "";
        $warnings = "";

        $ready_caption = str_replace('@@', '@', strtolower($ready_caption));
        if ($cardNumber == "") {
            $cardPattern = '/\d{16}/';
            preg_match($cardPattern, str_replace(' ', '', $this->telegram->reply_to_message['caption']), $cardMatches);
            if (isset($cardMatches[0])) {
                $cardNumber = $cardMatches[0];
            } else {
                $warnings.="\n شماره کارت در پیام یافت نشد";
            }
        }

        if ($shaba == "") {
            $shabaPattern = '/IR\d{24}/';
            preg_match($shabaPattern, strtoupper(str_replace(' ', '', $this->telegram->reply_to_message['caption'])), $shabaMatches);
            if (isset($shabaMatches[0])) {
                $shaba = $shabaMatches[0];

            } else {
                $warnings .="\n شماره شبا در پیام یافت نشد";
            }
        }
        $pattern = '/@[\w\d._]+/';

        preg_match_all($pattern, $ready_caption, $matches);
        $tags = $matches[0];
        ///remove duplicate tags
        $tags = array_unique($tags);
        foreach ($tags as $tag) {
            ///search for duplicates tags in database
            $dup = Shot::query()->where('group_id', $group->id)->where('pages', 'like', '%"' . $tag . '"%')->first();
            if ($dup) {
                $warnings .= "⚠️ تگ " . $tag . " قبلا در این گروه استفاده شده است \n";
            }
//            $blocked = TagsBlockList::query()->where('tag','like', $tag)->first();
//            if ($blocked) {
//                $warnings .= "❌ تگ " . $tag . " در لیست سیاه ما موجود است \n";
//            }
            $text .= "🆔" . $tag . "\n";
        }
        $text .= "\n";
        $text .= $warnings . "\n";

        $block_list = BlockList::query()->where('card_number', $cardNumber)->orWhere('shaba', $shaba)->first();
        if ($block_list) {
//            return sendMessage([
//                'chat_id' => $this->telegram->chat_id,
//                'text' => "این کارت یا شبا در لیست سیاه ما موجود است",
//                'reply_to_message_id' => $this->telegram->message_id,
//                'reply_markup' => unblockUser($block_list->id)
//            ]);
            $name_append = "بلاک شده ";

        }
        $total_subtraction = $group->subtraction;
        try {
            $total = ($this->telegram->text * $price);

        }catch (\Exception $e){
            return 1;
        }
        $sub_amount = 0;
//        devLog($sub>0?" - ❗️ $sub% ($sub_amount) -":" " );
        if ($sub > 0) {
            $new_total = round(((100 - $sub) * $total) / 100);
            $sub_amount = ($total - $new_total);
            $total_subtraction = $sub_amount + $total_subtraction;
            $total = $new_total;
        }
        $total = $total - $group->subtraction;

        $text .= "📄 <b>Value</b> :<code> ( " . $this->telegram->text . " * " . $price . " ) - " . ($sub > 0 ? "❗️ $sub% ($sub_amount) -" : " ") . $group->subtraction . "</code>\n";
        $text .= "💶 <b>Price</b> :<code>" . number_format($total) . "</code> Toman \n";
        $text .= "💳 <b>Card Number</b> : \n <code>" . $cardNumber . "</code>\n";
        $text .= "🏦 <b>Sheba Number</b>: <code>" . $shaba . "</code>\n";
        $new_caption = $caption;
        foreach ($tags_step_1 as $tag) {
            $new_caption = str_replace(strtoupper($tag), '', strtoupper($new_caption));
        }

        $new_caption = str_replace([0,1,2,3,4,5,6,7,8,9], '', $new_caption);
        $new_caption = str_replace(['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'], '', $new_caption);
        $new_caption = str_replace($cardNumber, '', $new_caption);
        $new_caption = str_replace(strtoupper($shaba), '', strtoupper($new_caption));
        ##remove empty lines from $new_caption
        $new_caption = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $new_caption);
        $name = $name_append.trim($this->extractName(trim($new_caption)));
        if ($group->show_name) {
            $text .= "➖➖➖<b> Description </b>➖➖➖ \n";
            $text .= $name . "\n";
        }
        //check there is a old shot with this message_id ?
        $ex_shot = Shot::query()->where('group_id', $group->id)->where('message_id', $this->telegram->reply_to_message['message_id'])->first();
        if($ex_shot){
            //subtract from group
            $group->update([
                'shot_count'=>$group->shot_count-1,
                'total_amount'=>$group->total_amount-($ex_shot->amount*$ex_shot->fee),
                'total_subtraction'=>$group->total_subtraction-$ex_shot->subtraction,
                'view'=>$group->view-$ex_shot->amount
            ]);
            deleteMessage([
                'chat_id' => $this->telegram->chat_id,
                'message_id' => $ex_shot->response_message_id
            ]);
            $ex_shot->delete();
        }
        try {
            $shot = Shot::query()->create([
                'group_id' => $group->id,
                'user_chat_id' => $this->telegram->reply_to_message['from']['id'],
                'pages' => json_encode($tags),
                'card_number' => $cardNumber,
                'shaba_number' => $shaba,
                'card_name' =>$name,
                'amount' => $this->telegram->text,
                'subtraction' => $total_subtraction,
                'fee' => $price,
                'caption'=>$this->telegram->reply_to_message['caption'],
                'total'=>$total,
                'message_id' => $this->telegram->reply_to_message['message_id'],
                'reply'=>$reply_text
            ]);
        }catch (\Exception $e){
            return 1;
        }

        $a = sendMessage([
            'chat_id' => $this->telegram->chat_id,
            'text' => $text,
            'parse_mode' => 'HTML',
            'reply_to_message_id' => $this->telegram->message_id,
            'reply_markup' => shotKey($shot->id)
        ]);
        $shot->update([
            'response_message_id' => $a['message_id']
        ]);
        $group->shot_count++;
        $group->total_amount += ($total);
        $group->view = $group->view + $this->telegram->text;
        $group->total_subtraction += $total_subtraction;
        $group->save();

    }

    public function extractName($text)
    {
        $text = preg_replace("/[a-zA-Z]+/", '', $text);
        // Normalize the encoding and remove extra spaces
        $text = trim(preg_replace('/\s+/', ' ', $text));

        // Split the text into lines
        $lines = preg_split('/\r\n|\r|\n/', $text);
//        devLog($text);

        // Characters to remove from the name
        $remove_chars = [',', '.', '@', '_', '-', '/' ,":",'"',"'",'◾',"\n",'•',
            '=','?','؟','!','!','،','؛','«','»','٬','٫','[',']','+','#','-','  ','   ','  ',
            '️ ️',' ',' ‌','‌‌','‌ ‌','‌ ','—','﷽','‏','°',')','(','1','2','3','4','5','6','7','8','9','0','*','&','^','%','$',
            '@','!','~','`','|','\\','{','}','<','>','؟','؛','؛','؛','؛','؛','؛','؛','؛','؛','؛','؛','؛','؛','؛','؛','؛','؛',
            '۱','۲','۳','۴','۵','۶','۷','۸','۹','۰'
        ];

        // Go through each line to find the name
        foreach ($lines as $line) {
            // Remove unwanted characters
            $name = str_replace($remove_chars, '', $line);

            // Trim whitespace from the name
            $name = trim($name);

            // Check if the name contains Arabic characters and ensure it is longer than 1 character
            if (mb_strlen($name) > 1 && preg_match('/[\x{0600}-\x{06FF}\x{0750}-\x{077F}\x{08A0}-\x{08FF}\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFF}\s]+/u', $name)) {
                // Return the name without any unwanted characters
                $ex = explode(' ',$name);
                if (count($ex)>3){
                    return trim($ex[0]." ".$ex[1]." ".$ex[2]);
                }
                return trim($name);
            }
        }

        // If no name is found, return an empty string or null
        return 'توضیحات';

    }

    public function replaceCaption($text)
    {
        $text = str_replace('شماره شبا:', '', $text);
        $text = str_replace('شماره کارت :', '', $text);
        $text = str_replace('شماره شبا', '', $text);
        $text = str_replace('حساب', '', $text);
        $text = str_replace('بانک سامان', '', $text);
        $text = str_replace('قرض الحسنه', '', $text);
        $text = str_replace('مهر ایران', '', $text);
        $text = str_replace('تجارت', '', $text);
        $text = str_replace('ملی', '', $text);
        $text = str_replace('بانک', '', $text);
        $text = str_replace('شماره کارت', '', $text);
        $text = str_replace('کارت', '', $text);
        $text = str_replace('شبا', '', $text);
        $text = str_replace('به نام', '', $text);
        $text = str_replace('بنام', '', $text);
        $text = str_replace('به اسم', '', $text);
        $text = str_replace('حساب', '', $text);
        $text = str_replace('﷽', '', $text);
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
    public function findTags($str){
        $pattern = '/@[\w\d._]+/';

        preg_match_all($pattern, $str, $matches);
        if (isset($matches[0])){
            return $matches[0];
        }
        return [];
    }

}
