<?php
namespace App\Lib\Classes;
use App\Lib\Interfaces\TelegramOperator;

class GroupManager extends TelegramOperator
{

    public function initCheck()
    {
        return ($this->telegram->group);
    }

    public function handel()
    {
        devLog($this->update);
    }
}
