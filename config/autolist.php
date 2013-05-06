<?php

return array(
    'permission_check' => function($action, $model, $id=FALSE) {
        return Auth::check();
    },
    'views' => array(
        'list' => 'autolist::list',
        'header_item' => 'autolist::header_item',
        'action_link' => 'autolist::action_link',
        'detail_link' => 'autolist::detail_link',
    ),
    'pager_enabled' => true,
    'page_size' => 10,
    'detail_view_action_default' => 'detail'
);