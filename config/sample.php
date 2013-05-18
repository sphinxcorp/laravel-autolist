<?php

return array(
    'users' => array(
        'title'             => 'Users',
        'action_controller' => 'admin.users',
        'model'             => 'User',
        'attributes'        => array(
            /*
             * attribute : string || attribute => string | array(
             *     'title' => string,
             *     'auto_escape' => boolean, // default true
             *     'linkify' => boolean || function($linked_model){ return url }, // default true
             *     'relation_config' => string, // configuration key for the related list for this field. required only if this field is a related field and the value should be linkified
             *     'decoder' => function($value) { return $decoded_value; } // optional
             *     'decoder_for_sql' => string || function($attribute){ return $sql } // optional, if a field is not relational and decoded with decoder, then to make the list sortable by this attribute this option is used. The option value (or the returnded string from the function) will be directly used in the order by clause
             *     'sortable' => boolean, // default true, have no effect for relational attributes, those van not be used for sorting
             * ),
             */
            'username' => array(
                'title' => 'User name',
            ),
            'email'    => array(
                'title'   => 'Email address',
                'linkify' => function($linked_model) {
                    return "mailto:{$linked_model->email}";
                }
            ),
            'created_at'        => array(
                'title'   => 'Registered since',
                'linkify' => false,
                'decoder' => function($value) {
                    return date('m/d/Y H:i:s');
                },
//              'decoder_for_sql' => "`created_at`", // this is equivalent to the following
                'decoder_for_sql'   => function($attribute) {
                    return "`$attribute`";
                },
            ),
        ),
//      'eager_loads' => array(), // optional, use this to eager load the related Models which are used by computed attributes
        'item_actions' => array(
            /*
             * action : string || action => string | array(
             *     'text' => string, // required
             *     'title' => string,
             *     'controller_action' => string,
             *     'permission_check' => function ($item, $id) { return boolean } // overrides global permission_check
             * ),
             */
            'edit',
            'delete' => array(
                'text'             => 'Delete',
                'permission_check' => function($item, $id) {
                    return Auth::check() && Auth::user()->id != $id;
                }
            ),
        ),
        'global_actions' => array(// These links will be global to the list
            /*
             * action : string || action => string | array(
             *     'text' => string, // required
             *     'title' => string,
             *     'controller_action' => string,
             *     'permission_check' => function ($item) { return boolean } // overrides global permission_check
             * ),
             */
            'create' => 'Create New'
        ),
//      'detail_view_action' => 'profile', // optional, default value is 'detail', used for linking
        /*
         * string || array(
         *      'action' => string, // action name; optional, default value is 'detail'
         *      'permission_check' => function($item, $id) { return boolean } // overrides global permission_check
         * )
         *
         */
//      'pager_enabled' => true, // overrides default pager_enabled of autolist::autolist.pager_enabled
//      'page_size' => 10, // // overrides default page_size of autolist::autolist.page_size
//      'default_sort' => NULL, // TBD
//      'default_sort_dir' => "ASC", // required when default_sort has non-null value
//      'permission_check' => NULL, // function($action, $item, $id=FALSE) {}, overrides default permission check of autolist::autolist.permission_check
    )
);