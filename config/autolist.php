<?php

return array(
    'config_path' => path('app') . 'autolist/',
    'permission_checks' => array(
        'detail_view' => function($model, $id) {
            return Auth::check();
        },
        'create' => function($model) {
            return Auth::check();
        },
        'edit' => function($model, $id) {
            return Auth::check();
        },
        'delete' => function($model, $id) {
            return Auth::check();
        },
    ),
    'templates' => array(
        'list' => 'autolist::templates.list',
        'action_link' => 'autolist::templates.action_link'
    ),
    'pager_enabled' => true,
    'page_size' => 10
);