<?php

namespace App\Http\Controllers;

use App\Lib\Interfaces\TelegramVariables;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class TelegramController extends Controller
{
    public function init(Request $request){
        $update = $request->toArray();
        $update = json_encode($update);
        $update = json_decode(convertPersianToEnglish($update),true);
        $telegram = new  TelegramVariables($update);
        for ($i=1;$i<4;$i++){
            foreach(config('telegram-classes.classes.'.$i) as $class){
                $object = new $class($update,$telegram);
                if ($object->class_status){
                    break;
                }
            }
        }

    }
}
