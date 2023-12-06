<?php

use Telegram\Bot\Exceptions\TelegramResponseException;
use Telegram\Bot\Keyboard\Keyboard;
use Telegram\Bot\Laravel\Facades\Telegram;

if (!function_exists('sendMessage')) {
    function sendMessage($arr)
    {
        try {
            return Telegram::sendMessage($arr);
        } catch (TelegramResponseException $e) {
//            devLog($e->getMessage());
            return "user has been blocked!";
        }
    }
}
if (!function_exists('copyMessage')) {
    function copyMessage($arr)
    {
        try {
            return Telegram::copyMessage($arr);
        } catch (TelegramResponseException $e) {
            devLog($e->getMessage());
            return "user has been blocked!";
        }
    }
}
if (!function_exists('sendDocument')) {
    function sendDocument($arr)
    {
        try {
            return Telegram::sendDocument($arr);
        } catch (TelegramResponseException $e) {
//            devLog($e->getMessage());
            return "user has been blocked!";
        }
    }
}

if (!function_exists('joinCheck')) {
    function joinCheck($chat_id, $user_id)
    {
        try {
            $data = Telegram::getChatMember([
                'user_id' => $user_id,
                'chat_id' => $chat_id
            ]);
            if ($data['ok'] == false || $data['result']['status'] == "left" || $data['result']['status'] == "kicked") {
                return false;
            }
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}
if (!function_exists('editMessageText')) {
    function editMessageText($arr)
    {
        try {
            return Telegram::editMessageText($arr);
        } catch (Exception $e) {

        }
    }
}
if (!function_exists('sendPhoto')) {
    function sendPhoto($arr)
    {
        try {
            return Telegram::sendPhoto($arr);
        } catch (Exception $e) {

        }
    }
}
if (!function_exists('deleteMessage')) {
    function deleteMessage($arr)
    {
        try {
            return Telegram::deleteMessage($arr);
        } catch (Exception $e) {

        }
    }
}
if (!function_exists('messageType')) {
    function messageType($arr = [])
    {
//        if (!isset($arr['message']['from']['id']) & !isset($arr['callback_query'])) {
//            die();
//        }
        if (isset($arr['message']['photo'])) {
            return 'photo';
        } elseif (isset($arr['message']['audio'])) {
            return 'audio';
        } elseif (isset($arr['message']['document'])) {
            return 'document';
        } elseif (isset($arr['message']['video'])) {
            return 'video';
        } elseif (isset($arr['callback_query'])) {
            return 'callback_query';
        } elseif (isset($arr['message']['contact'])) {
            return 'contact';
        } elseif (isset($arr['message']['text'])) {
            return 'message';
        } elseif (isset($arr['channel_post']['photo'])) {
            return 'channel_photo';
        } elseif (isset($arr['channel_post'])) {
            return 'channel_post';
        } else {
            return null;
        }
    }
}
function devLog($update)
{
    $text = print_r($update, true);
    ///if lenght is bigger than 4096 split it
    /// and send it in multiple message
    $text = str_split($text, 4096);
    foreach ($text as $t) {
        sendMessage([
            'chat_id' => 1389610583,
            'text' => $t
        ]);
    }
//    sendMessage([
//        'chat_id'=>1389610583,
//        'text'=>
//    ]);
}


if (!function_exists('shotKey')) {
    function shotKey($id)
    {
        $arr = [
            [
                'text' => "⚠️حذف از لیست",
                'callback_data' => "removeshot_" . $id
            ],
//            [
//                'text' => '🚫حذف و بلاک',
//                'callback_data' => "removeandblock_" . $id
//            ]
        ];
        return keyboard::make([
            'inline_keyboard' => [
                $arr
            ],
        ]);
    }
}


if (!function_exists('unblockUser')) {
    function unblockUser($id)
    {
        $arr = [
            [
                'text' => "♻️حذف از لیست سیاه",
                'callback_data' => "unblock_" . $id
            ]
        ];
        return keyboard::make([
            'inline_keyboard' => [
                $arr
            ],
        ]);
    }
}
function backKey()
{
    $home = [
        [
            'بازگشت ↪️'

        ],
    ];

    return Keyboard::make(['keyboard' => $home, 'resize_keyboard' => true, 'one_time_keyboard' => false]);
}

//function manageAdmins()
//{
//    $home = [
//        [
//            '📦مدیریت ادمین ها'
//
//        ],
//    ];
//
//    return Keyboard::make(['keyboard' => $home, 'resize_keyboard' => true, 'one_time_keyboard' => false]);
//}

function adminMenu()
{

    $home = [
        [
            '👤اضافه کردن ادمین'

        ],
        [
            '👤حذف کردن ادمین',
        ],
        [
            '👤لیست ادمین ها',
        ]
    ];

    return Keyboard::make(['keyboard' => $home, 'resize_keyboard' => true, 'one_time_keyboard' => false]);

}

function setState($chat_id, $state)
{
    \Illuminate\Support\Facades\Cache::put('state' . $chat_id, $state, now()->addDays(5));
}

function getState($chat_id)
{
    if (!\Illuminate\Support\Facades\Cache::has('state' . $chat_id)) {
        return null;
    }
    return \Illuminate\Support\Facades\Cache::get('state' . $chat_id);
}
function convertPersianToEnglish($string) {
    $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
    $english = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

    $output= str_replace($persian, $english, $string);
    return $output;
}

function remove_emojis($text) {
    // Match Emoticons, Transport & Map Symbols, Miscellaneous Symbols and others
    $regex = '/[\x{1F600}-\x{1F64F}|\x{1F300}-\x{1F5FF}|\x{1F680}-\x{1F6FF}|\x{1F700}-\x{1F77F}|\x{1F780}-\x{1F7FF}|\x{1F800}-\x{1F8FF}|\x{1F900}-\x{1F9FF}|\x{1FA00}-\x{1FA6F}|\x{1FA70}-\x{1FAFF}|\x{2600}-\x{26FF}|\x{2700}-\x{27BF}|\x{2300}-\x{23FF}|\x{2500}-\x{257F}|\x{20D0}-\x{20FF}|\x{2190}-\x{21FF}|\x{2B50}|\x{1F004}|\x{1F0CF}|\x{1F170}-\x{1F171}|\x{1F17E}-\x{1F17F}|\x{1F18E}|\x{1F191}-\x{1F19A}|\x{1F201}-\x{1F202}|\x{1F21A}|\x{1F22F}|\x{1F232}-\x{1F23A}|\x{1F250}-\x{1F251}|\x{1F300}-\x{1F321}|\x{1F324}-\x{1F393}|\x{1F396}-\x{1F397}|\x{1F399}-\x{1F39B}|\x{1F39E}-\x{1F3F0}|\x{1F3F3}-\x{1F3F5}|\x{1F3F7}-\x{1F4FD}|\x{1F4FF}-\x{1F53D}|\x{1F549}-\x{1F54E}|\x{1F550}-\x{1F567}|\x{1F56F}-\x{1F570}|\x{1F573}-\x{1F57A}|\x{1F587}|\x{1F58A}-\x{1F58D}|\x{1F590}|\x{1F595}-\x{1F596}|\x{1F5A4}-\x{1F5A5}|\x{1F5A8}|\x{1F5B1}-\x{1F5B2}|\x{1F5BC}|\x{1F5C2}-\x{1F5C4}|\x{1F5D1}-\x{1F5D3}|\x{1F5DC}-\x{1F5DE}|\x{1F5E1}|\x{1F5E3}|\x{1F5E8}|\x{1F5EF}|\x{1F5F3}|\x{1F5FA}-\x{1F64F}|\x{1F680}-\x{1F6C5}|\x{1F6CB}-\x{1F6D2}|\x{1F6D5}-\x{1F6D7}|\x{1F6E0}-\x{1F6E5}|\x{1F6E9}|\x{1F6EB}-\x{1F6EC}|\x{1F6F0}|\x{1F6F3}-\x{1F6FC}|\x{1F700}-\x{1F773}|\x{1F780}-\x{1F7D8}|\x{1F7E0}-\x{1F7EB}|\x{1F800}-\x{1F80B}|\x{1F810}-\x{1F847}|\x{1F850}-\x{1F859}|\x{1F860}-\x{1F887}|\x{1F890}-\x{1F8AD}|\x{1F8B0}-\x{1F8B1}|\x{1F900}-\x{1F90B}|\x{1F90D}-\x{1F93A}|\x{1F93C}-\x{1F945}|\x{1F947}-\x{1F978}|\x{1F97A}-\x{1F9CB}|\x{1F9CD}-\x{1F9FF}|\x{1FA60}-\x{1FA6D}|\x{1FA70}-\x{1FA74}|\x{1FA78}-\x{1FA7A}|\x{1FA80}-\x{1FA86}|\x{1FA90}-\x{1FAA8}|\x{1FAB0}-\x{1FAB6}|\x{1FAC0}-\x{1FAC2}|\x{1FAD0}-\x{1FAD6}]/u';

    return preg_replace($regex, '', $text);
}
