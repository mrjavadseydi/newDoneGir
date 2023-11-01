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
        if (Cache::has('cron_delete')) {
            foreach (Cache::get('cron_delete') as $data) {
                deleteMessage([
                    'chat_id' => $data['chat_id'],
                    'message_id' => $data['message_id']
                ]);
            }
        }
        $data = [];
        foreach ($list as $chat_id => $val) {
            if ($current_min % $val['min'] === 0) {
                $res = copyMessage([
                    'chat_id' => $chat_id,
                    'from_chat_id' => $chat_id,
                    'message_id' => $val['message_id']
                ]);
//                if ($res['ok']){
                $data[] = [
                    'chat_id' => $chat_id,
                    'message_id' => $res['message_id']
                ];
//                }
            } else {
                print_r("not yet \n");
            }
        }
        Cache::put('cron_delete', $data, now()->addMinutes(5));

        return Command::SUCCESS;
    }
}
