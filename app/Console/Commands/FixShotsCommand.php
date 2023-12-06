<?php

namespace App\Console\Commands;

use App\Models\Group;
use App\Models\Shot;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class FixShotsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:shot';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'send cron messages ';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
//       $start_id = ;
        $shots = Shot::query()->where('response_message_id',"!=",null)->orderBy('id','desc')->get();
        foreach ($shots as $shot){
            $id = $shot->response_message_id -1;
//            $chat_id = Group::query()
            sendMessage([
                'chat_id'=>1389610583,
                'text'=>"message_id:".$id."@from_chat_id:".$shot->group->chat_id."@reply_to_message_id:".$shot->message_id
            ]);
            die();
        }
        return Command::SUCCESS;
    }
}
