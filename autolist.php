<?php

class AutoList {

    var $config;
    var $model;
    var $query_modifier = NULL;

    /**
     * 
     * @param string $config_id A valid configuration id to autolist configuration
     * @param function $query_modifier An optional function to modify the model query just before result retrieval
     * @throws Exception
     */
    function __construct($config_id, $query_modifier = NULL) {
        $this->config_id = $config_id;
        if (!Config::has($config_id) || !Config::has("$config_id.model")) {
            throw new Exception('Model must be defined in autolist config');
        }
        $this->config = Config::get($config_id);
        $this->set_query_modifier($query_modifier);
    }

    private function _get_attribute_details($attribute, $options) {
        $sortable = true;
        $linkify = true;
        if (is_int($attribute) && is_string($options)) {
            $attribute = $options;
            $title = ucwords(str_replace('_', ' ', $attribute));
        } else if (is_array($options)) {
            $sortable = isset($options['sortable'])? : $sortable;
            $linkify = isset($options['linkify'])? : $linkify;
            $title = isset($options['title']) ? $options['title'] : ucwords(str_replace('_', ' ', $attribute));
        } else if (is_string($options)) {
            $title = $options;
        }

        return compact('attribute', 'title', 'sortable', 'linkify');
    }

    private function _get_action_details($action, $options) {
        if (is_string($options)) {
            if (is_int($action)) {
                $action = $options;
                $text = $title = ucwords(str_replace('_', ' ', $action));
            } else {
                $text = $title = ucwords($options);
            }
            $controller_action = $this->config['action_controller'] . "@$action";
            $permission_check = NULL;
        } else if (is_string($action) && is_array($options)) {
            $text = $options['text']? : ($options['title']? : ucwords(str_replace('_', ' ', $action)));
            $title = $options['title']? : $text;
            $controller_action = isset($options['controller_action']) ? $options['controller_action'] : $this->config['action_controller'] . "@$action";
            $permission_check = isset($options['permission_check']) ? $options['permission_check'] : NULL;
        } else {
            throw new Exception('Invalid configuration provided for action');
        }
        return compact('action', 'text', 'title', 'controller_action', 'permission_check');
    }

    private function _get_query() {
        $model = $this->config['model'];
        $this->model = new $model;
        $attributes = $this->config['attributes'];
        $eager_loads = array();
        $this->config['attributes'] = array();
        foreach ($attributes as $attribute => $options) {
            $attribute_details = $this->_get_attribute_details($attribute, $options);
            $parts = explode('.', $attribute_details['attribute']);
            if (count($parts) > 1) {
                array_pop($parts);
                $eager_loads[] = implode('.', $parts);
            }
            $this->config['attributes'][$attribute_details['attribute']] = $attribute_details;
        }

        if (!empty($eager_loads)) {
            $this->model->with($eager_loads);
        }

        $query = $this->model;
        if (is_callable($this->query_modifier)) {
            $query = $this->model->where($this->query_modifier);
        }
        
        return $query;
    }

    public function set_query_modifier($query_modifier) {
        $this->query_modifier = $query_modifier;
    }

    public function render() {
        $query = $this->_get_query();
        
        $paginate = isset($this->config['pager_enabled'])?$this->config['pager_enabled']:Config::get('autolist::autolist.pager_enabled',true);
        $per_page = isset($this->config['page_size'])?$this->config['page_size']:Config::get('autolist::autolist.page_size',10);
        
        $page_links = FALSE;
        if($paginate){
            $pager = $query->paginate($per_page);
            $page_links = $pager->links();
            $items = $pager->results;
        }
        else {
            $items = $query->get();
        }
        
        $permission_check = isset($this->config['permission_check']) && is_callable($this->config['permission_check']) ? $this->config['permission_check'] : Config::get('autolist::autolist.permission_check');

        $permitted_items = array();
        $has_item_actions = false;
        foreach ($items as $item) {
            if ($permission_check && is_callable($permission_check) && !$permission_check('view', $item, $item->id)) {
                continue;
            }

            $action_links = array();
            foreach ($this->config['item_actions'] as $action => $action_options) {
                $action_details = $this->_get_action_details($action, $action_options);
                $action_permitted = true;
                if (is_callable($action_details['permission_check'])) {
                    $action_permission_check = $action_details['permission_check'];
                    $action_permitted = $action_permission_check($item, $item->id);
                } else if (is_callable($permission_check)) {
                    $action_permitted = $permission_check($action_details['action'], $item, $item->id);
                }

                if ($action_permitted) {
                    $action_details['id'] = $item->id;
                    $action_links[$action_details['action']] = View::make(Config::get('autolist::autolist.views.action_link'), $action_details)->render();
                    $has_item_actions = true;
                }
            }

            $item->action_links = $action_links;

            $permitted_items[] = $item;
        }

        $global_action_links = array();
        foreach ($this->config['global_actions'] as $action => $action_options) {
            $action_details = $this->_get_action_details($action, $action_options);

            $action_permitted = true;
            if (is_callable($action_details['permission_check'])) {
                $action_permission_check = $action_details['permission_check'];
                $action_permitted = $action_permission_check($item);
            } else if (is_callable($permission_check)) {
                $action_permitted = $permission_check($action_details['action'], $item);
            }
            if ($action_permitted) {
                $action_details['id'] = NULL;
                $global_action_links[$action_details['action']] = View::make(Config::get('autolist::autolist.views.action_link'), $action_details);
            }
        }

        $list_data = array(
            'title' => $this->config['title_plural'],
            'attributes' => $this->config['attributes'],
            'has_item_actions' => $has_item_actions,
            'items' => $permitted_items,
            'global_action_links' => $global_action_links,
            'page_links' => $page_links
        );

        return View::make(Config::get('autolist::autolist.views.list'), $list_data)->render();
    }

    public function __toString() {
        return $this->render();
    }

}