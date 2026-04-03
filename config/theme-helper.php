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

    'discovery' => [
        'enabled' => false,
        'manifest_filename' => 'theme.json',
        'scan_paths' => [],
    ],

    'structured_data' => [
        'render_strategy' => 'graph',
        'allow_multiple_items' => true,
        'attach_in_language' => true,
    ],

    'seo' => [
        'sitemap' => [
            'default_changefreq' => 'weekly',
            'default_priority' => '0.8',
            'include_lastmod' => true,
            'include_images' => true,
        ],
        'robots' => [
            'default_user_agent' => '*',
            'default_rules' => [
                [
                    'user_agent' => '*',
                    'allow' => ['/'],
                    'disallow' => [],
                ],
            ],
            'sitemaps' => [],
            'host' => null,
        ],
        'images' => [
            'default_loading' => 'lazy',
            'default_decoding' => 'async',
            'default_fetchpriority' => 'auto',
            'emit_dimensions' => true,
        ],
        'schema_presets' => [
            'organization' => [
                'type' => 'organization',
                'defaults' => [],
            ],
            'website' => [
                'type' => 'website',
                'defaults' => [],
            ],
            'home_page' => [
                'type' => 'home_page',
                'defaults' => [],
            ],
            'web_page' => [
                'type' => 'web_page',
                'defaults' => [],
            ],
            'service_page' => [
                'type' => 'service_page',
                'defaults' => [],
            ],
            'product_page' => [
                'type' => 'product_page',
                'defaults' => [],
            ],
            'faq_page' => [
                'type' => 'faq_page',
                'defaults' => [],
            ],
        ],
    ],
];
