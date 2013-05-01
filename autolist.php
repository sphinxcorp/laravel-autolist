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
            $title = ucwords($attribute);
        } else if (is_array($options)) {
            $sortable = isset($options['sortable'])? : $sortable;
            $linkify = isset($options['linkify'])? : $linkify;
            $title = isset($options['title']) ? $options['title'] : ucwords($attribute);
        }
        else if(is_string($options)){
            $title = $options;
        }

        return compact('attribute', 'title', 'sortable', 'linkify');
    }

    private function _get_action_details($action, $options) {
        if (is_int($action) && is_string($options)) {
            $action = $options;
            $text = $title = ucwords($action);
            $route = $this->config['action_controller'] . ".$action";
        } else if (is_array($options)) {
            $text = $options['text'];
            $title = $options['title']? : $options['text'];
            $route = isset($options['route']) ? $options['route'] : $this->config['action_controller'] . ".$action";
        }
        return compact('action', 'text', 'title', 'route');
    }

    private function _get_entities() {
        $model = $this->config['model'];
        $this->model = new $model;
        $attributes = array_keys($this->config['attributes']);
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

        if (is_callable($this->query_modifier)) {
            $this->model = call_user_func($this->query_modifier, $this->model);
        }

        if (!is_object($this->model) || !is_a($this->model, $this->config['model'])) {
            return array();
        }

        return $this->model->get();
    }

    public function set_query_modifier($query_modifier) {
        $this->query_modifier = $query_modifier;
    }

    public function render() {
        $items = $this->_get_entities();
        $permission_check = isset($this->config['permission_check']) && is_callable($this->config['permission_check']) ? $this->config['permission_check'] : Config::get('autolist::autolist.permission_check');

        $permitted_items = array();
        $has_item_actions = false;
        foreach ($items as $item) {
            if ($permission_check && is_callable($permission_check) && !$permission_check('view', $item, $item->id)) {
                continue;
            }

            $item->action_links = array();
            foreach ($this->config['item_actions'] as $action => $action_options) {
                $action_details = $this->_get_action_details($action, $action_options);
                if ($permission_check && is_callable($permission_check) && $permission_check($action_details['action'], $item, $item->id)) {
                    $item->action_links[$action_details['action']] = View::make(Config::get('autolist::autolist.views.action_link'), $action_details);
                    $has_item_actions = true;
                }
            }

            $permitted_items[] = $item;
        }

        $global_action_links = array();
        foreach ($this->config['global_actions'] as $action => $action_options) {
            $action_details = $this->_get_action_details($action, $action_options);
            if ($permission_check && is_callable($permission_check) && $permission_check($action_details['action'], $this->model, NULL)) {
                $global_action_links[$action_details['action']] = View::make(Config::get('autolist::autolist.views.action_link'), $action_details);
            }
        }
        
        $list_data = array(
            'attributes' => $this->config['attributes'],
            'has_item_actions'=>$has_item_actions,
            'items' => $permitted_items,
            'global_action_links' => $global_action_links
        );
        
        return View::make(Config::get('autolist::autolist.views.list'), $list_data);
    }

    public function __toString() {
        return $this->render();
    }

}