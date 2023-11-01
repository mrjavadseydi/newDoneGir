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
            [
                'text' => '🚫حذف و بلاک',
                'callback_data' => "removeandblock_" . $id
            ]
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
