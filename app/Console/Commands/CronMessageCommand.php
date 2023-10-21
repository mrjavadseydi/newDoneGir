<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class CronMessageCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'message:cron';

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
        $list = Cache::get('cron_list');
//        devLog($list);
        $current_min = date('i');
        foreach ($list as $chat_id => $val){
            if ($current_min%$val['min']===0){
                copyMessage([
                    'chat_id'=>$chat_id,
                    'from_chat_id'=>$chat_id,
                    'message_id'=>$val['message_id']
                ]);
            }else{
                print_r("not yet \n");
            }
        }

        return Command::SUCCESS;
    }
}
