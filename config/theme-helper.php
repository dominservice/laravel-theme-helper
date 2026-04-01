<?php

return [
    'contexts' => [
        'admin' => [
            'default_theme' => 'admin',
        ],
        'public' => [
            'default_theme' => 'public',
        ],
    ],

    'themes' => [
        'admin' => [
            'label' => 'Admin',
            'context' => 'admin',
            'driver' => 'static',
            'layout' => null,
            'view_path' => null,
            'build_config_path' => null,
            'manifest_path' => null,
            'hot_file_path' => null,
            'dev_server_url' => null,
            'public_path' => null,
            'asset_url' => null,
            'deploy_target' => null,
            'entries' => [
                'css' => [],
                'js' => [],
            ],
            'meta' => [],
        ],
        'public' => [
            'label' => 'Public',
            'context' => 'public',
            'driver' => 'static',
            'layout' => null,
            'view_path' => null,
            'build_config_path' => null,
            'manifest_path' => null,
            'hot_file_path' => null,
            'dev_server_url' => null,
            'public_path' => null,
            'asset_url' => null,
            'deploy_target' => null,
            'entries' => [
                'css' => [],
                'js' => [],
            ],
            'meta' => [],
        ],
    ],
];
