<?php

use App\Lib\Classes\GroupManager;
use App\Lib\Classes\ShotManager;
use App\Lib\Classes\UnblockUser;

return[
    'classes'=>[
        1=>[
            \App\Lib\Classes\Start::class,
            ShotManager::class,
            UnblockUser::class,
            GroupManager::class,
            \App\Lib\Classes\GroupCommands::class,
        ],
        2=>[

        ],
        3=>[

        ]
    ]
];
