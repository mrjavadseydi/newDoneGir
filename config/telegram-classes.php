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
            \App\Lib\Classes\BlockManager::class,

            GroupManager::class,
            \App\Lib\Classes\GroupCommands::class,
        ],
        2=>[
            \App\Lib\Classes\AdminManagment\AddAdmin::class,
            \App\Lib\Classes\AdminManagment\AdminList::class,
            \App\Lib\Classes\AdminManagment\ConfirmDeleteAdmin::class,
            \App\Lib\Classes\AdminManagment\DeleteAdmin::class,
            \App\Lib\Classes\AdminManagment\StoreAdmin::class

        ],
        3=>[

        ]
    ]
];
