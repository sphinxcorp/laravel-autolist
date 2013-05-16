<?php

class AutoList {

    var $config;
    var $model;
    var $model_key;
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
        $sortable    = true;
        $linkify     = false;
        $auto_escape = true;
        $decoder     = null;
        $relational  = false;
        if (is_int($attribute) && is_string($options)) {
            $attribute = $options;
            $title     = ucwords(str_replace('_', ' ', $attribute));
        } else if (is_array($options)) {
            $sortable    = isset($options['sortable']) ? $options['sortable'] : $sortable;
            $linkify     = isset($options['linkify']) ? $options['linkify'] : $linkify;
            $auto_escape = isset($options['auto_escape']) ? $options['auto_escape'] : $auto_escape;
            $title       = isset($options['title']) ? $options['title'] : ucwords(str_replace('_', ' ', $attribute));
            $decoder     = isset($options['decoder']) && is_callable($options['decoder']) ? $options['decoder'] : NULL;
        } else if (is_string($options)) {
            $title = $options;
        }

        return compact('attribute', 'title', 'sortable', 'linkify', 'auto_escape', 'decoder', 'relational');
    }

    private function _get_action_details($action, $options) {
        if (is_string($options)) {
            if (is_int($action)) {
                $action = $options;
                $text   = $title  = ucwords(str_replace('_', ' ', $action));
            } else {
                $text  = $title = ucwords($options);
            }
            $controller_action = $this->config['action_controller'] . "@$action";
            $permission_check  = NULL;
        } else if (is_string($action) && is_array($options)) {
            $text              = $options['text']? : ($options['title']? : ucwords(str_replace('_', ' ', $action)));
            $title             = $options['title']? : $text;
            $controller_action = isset($options['controller_action']) ? $options['controller_action'] : $this->config['action_controller'] . "@$action";
            $permission_check  = isset($options['permission_check']) ? $options['permission_check'] : NULL;
        } else {
            throw new Exception('Invalid configuration provided for action');
        }

        return compact('action', 'text', 'title', 'controller_action', 'permission_check');
    }

    private function _get_detail_view_action_details() {
        $action           = Config::get('autolist::autolist.detail_view_action_default', 'detail');
        $permission_check = NULL;

        if (isset($this->config['detail_view_action'])) {
            if (is_string($this->config['detail_view_action'])) {
                $action = $this->config['detail_view_action'];
            } else if (is_array($this->config['detail_view_action'])) {
                if (!empty($this->config['detail_view_action']['action'])) {
                    $action = $this->config['detail_view_action']['action'];
                }

                if (!empty($this->config['detail_view_action']['permission_check']) && is_callable($this->config['detail_view_action']['permission_check'])) {
                    $permission_check = $this->config['detail_view_action']['permission_check'];
                }
            }
        }
        


        return array($action, $permission_check);
    }

    private function _get_query() {
        $model_class                = $this->config['model'];
        $this->model                = new $model_class;
        $this->model_key            = $model_class::$key;
        $attributes                 = $this->config['attributes'];
        $eager_loads                = is_array($this->config['eager_loads']) ? $this->config['eager_loads'] : array();
        $this->config['attributes'] = array();
        foreach ($attributes as $attribute => $options) {
            $attribute_details = $this->_get_attribute_details($attribute, $options);
            $parts             = explode('.', $attribute_details['attribute']);
            if (count($parts) > 1) {
                array_pop($parts);
                $eager_loads[] = implode('.', $parts);
                $attribute_details['relational'] = true;
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
        
 
        /*if (is_callable($this->query_modifier)) {
            $query = $this->model->where($this->query_modifier);
        }*/
        
        return $query;
    }

    private function _get_attribute_value($item, $attribute_details, $detail_view_action) {
        $value_store     = $item;
        $raw_value       = NULL;
        $attribute_parts = explode('.', $attribute_details['attribute']);
        $is_relational   = count($attribute_parts) > 1;
        foreach ($attribute_parts as $part) {
            $raw_value = $value_store->$part;
            if (!is_object($raw_value)) {
                break;
            }
            $value_store = $raw_value;
        }

        if ($attribute_details['decoder']) {
            $raw_value = $attribute_details['decoder']($raw_value);
        }

        $value = $attribute_details['auto_escape'] ? e($raw_value) : $raw_value;
        if (!$is_relational && $attribute_details['linkify'] && !empty($raw_value)) {
            $controller_action = $this->config['action_controller'] . "@$detail_view_action";
            $value             = render(Config::get('autolist::autolist.views.detail_link'), array(
                'id'                => $item->{$this->model_key},
                'attribute'         => $attribute_details['attribute'],
                'action'            => $detail_view_action,
                'raw_value'         => $raw_value,
                'value'             => $value,
                'controller_action' => $controller_action
            ));
        }
        return $value;
    }

    public function set_query_modifier($query_modifier) {
        $this->query_modifier = $query_modifier;
    }

    public function render() {
        $query = $this->_get_query();
        $sort_by = Input::query('sort_by', $this->config['default_sort']);
        if (!empty($sort_by) && $this->config['attributes'][$sort_by]['sortable'] 
                && is_null($this->config['attributes'][$sort_by]['decoder']) // decoded attributes won't be sortable
                && !$this->config['attributes'][$sort_by]['relational']) { // relational attributes won't be sortable
            if (!method_exists($this->model, "get_{$sort_by}")) { // ignoring the computed attributes with sortable flag set
                
                $sort_dir = Input::query('sort_dir');
                if (empty($sort_dir)) {
                    $sort_dir = ($sort_by == $this->config['default_sort'] && !empty($this->config['default_sort_dir'])) ? $this->config['default_sort_dir'] : 'ASC';
                }

                $query = $query->order_by($sort_by, strtolower($sort_dir));
            }
        }
        $search=Input::query('search');
        if($search){
            foreach ($this->config['attributes'] as $key=>$value){
                if (!method_exists($this->model, "get_{$key}")
                && !$this->config['attributes'][$key]['relational']) { 
                    $query=$query->or_where($key,'LIKE','%'.$search.'%');
                }
            }
        }
        

        $paginate = isset($this->config['pager_enabled']) ? $this->config['pager_enabled'] : Config::get('autolist::autolist.pager_enabled', true);
        $per_page = isset($this->config['page_size']) ? $this->config['page_size'] : Config::get('autolist::autolist.page_size', 10);

        $page_links = FALSE;
        if ($paginate) {
            $pager = $query->paginate($per_page);
            if (!empty($sort_by)) {
                $pager->appends(compact('sort_by', 'sort_dir'));
            }
            $page_links = $pager->links();
            $items      = $pager->results;
        } else {
            $items = $query->get();
        }

        $permission_check = isset($this->config['permission_check']) && is_callable($this->config['permission_check']) ? $this->config['permission_check'] : Config::get('autolist::autolist.permission_check');

        list($detail_view_action, $detail_view_permission_check) = $this->_get_detail_view_action_details();

        $permitted_items  = array();
        $has_item_actions = false;

        foreach ($items as $item) {
            if (!is_null($detail_view_permission_check) && !($detail_view_permission_check($item, $item->{$this->model_key}))) {
                continue;
            } else if ($permission_check && is_callable($permission_check) && !$permission_check($detail_view_action, $item, $item->{$this->model_key})) {
                continue;
            }

            $action_links = array();
            foreach ($this->config['item_actions'] as $action => $action_options) {
                $action_details   = $this->_get_action_details($action, $action_options);
                $action_permitted = true;
                if (is_callable($action_details['permission_check'])) {
                    $action_permission_check = $action_details['permission_check'];
                    $action_permitted        = $action_permission_check($item, $item->{$this->model_key});
                } else if (is_callable($permission_check)) {
                    $action_permitted = $permission_check($action_details['action'], $item, $item->{$this->model_key});
                }

                if ($action_permitted) {
                    $action_details['id']                    = $item->{$this->model_key};
                    $action_links[$action_details['action']] = render(Config::get('autolist::autolist.views.action_link'), $action_details);
                    $has_item_actions                        = true;
                }
            }

            $item_data = array();
            foreach ($this->config['attributes'] as $attribute => $attribute_details) {
                $item_data[$attribute] = $this->_get_attribute_value($item, $attribute_details, $detail_view_action);
            }

            $item_data['action_links'] = $action_links;

            $permitted_items[] = $item_data;
        }

        $global_action_links = array();
        foreach ($this->config['global_actions'] as $action => $action_options) {
            $action_details = $this->_get_action_details($action, $action_options);

            $action_permitted = true;
            if (is_callable($action_details['permission_check'])) {
                $action_permission_check = $action_details['permission_check'];
                $action_permitted        = $action_permission_check($item);
            } else if (is_callable($permission_check)) {
                $action_permitted = $permission_check($action_details['action'], $item);
            }
            if ($action_permitted) {
                $action_details['id']                           = NULL;
                $global_action_links[$action_details['action']] = render(Config::get('autolist::autolist.views.action_link'), $action_details);
            }
        }

        $header_columns = array();
        foreach ($this->config['attributes'] as $attribute => $attribute_details) {
            if ($attribute_details['sortable'] && is_null($attribute_details['decoder']) 
                    && !$attribute_details['relational'] 
                    && !method_exists($this->model, "get_{$attribute_details['attribute']}")) {
                $cur_url = URL::to(URI::current());
                if (Input::get('page')!=NULL) {
                    $cur_url.= '?page='.Input::get('page');
                }
                $link ='<a href="'.$cur_url;
                if (strpos($cur_url,"?")==false) {
                    $link.='?';
                } else {
                    $link.= '&';
                }
                $link.='sort_by='.$attribute_details['attribute'].'&sort_dir=';
                $link_asc = $link.'ASC">'.'<i class="icon-chevron-up"></i>'.'</a>';
                $link_desc = $link.'DESC">'.'<i class="icon-chevron-down"></i>'.'</a>';
                $title = '<div class="row-fluid">';
                $title .= '<div class="span7" align="left">'.$attribute_details['title'].'</div>';
                $title .= '<div class="span5" align="right">' . $link_asc . $link_desc . '</div>';
                $title .= '</div>';
                $attribute_details['title']=$title;
            }
            $header_columns[$attribute] = render(Config::get('autolist::autolist.views.header_item'), $attribute_details);
        }


        $list_data = array(
            'title'               => $this->config['title'],
            'header_columns'      => $header_columns,
            'has_item_actions'    => $has_item_actions,
            'items'               => $permitted_items,
            'global_action_links' => $global_action_links,
            'page_links'          => $page_links,
        );
        return render(Config::get('autolist::autolist.views.list'), $list_data);
    } 

    public function __toString() {
        return $this->render();
    }

}