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
        $sortable = true;
        $linkify = true;
        $auto_escape = true;
        $decoder = null;
        $decoder_for_sql = null;
        $relational = false;
        $relation_config = null;
        $expose_filter = false;
        $filter_type = 'string';
        if (is_int($attribute) && is_string($options)) {
            $attribute = $options;
            $title = ucwords(str_replace('_', ' ', $attribute));
        } else if (is_array($options)) {
            $sortable = isset($options['sortable']) ? $options['sortable'] : $sortable;
            $linkify = isset($options['linkify']) ? $options['linkify'] : $linkify;
            $auto_escape = isset($options['auto_escape']) ? $options['auto_escape'] : $auto_escape;
            $title = isset($options['title']) ? $options['title'] : ucwords(str_replace('_', ' ', $attribute));
            $decoder = isset($options['decoder']) && is_callable($options['decoder']) ? $options['decoder'] : NULL;
            $decoder_for_sql = isset($options['decoder_for_sql']) ? $options['decoder_for_sql'] : NULL;
            $relation_config = isset($options['relation_config']) ? Config::get($options['relation_config'], NULL) : NULL;
            $expose_filter = isset($options['expose_filter']) ? $options['expose_filter'] : $expose_filter;
            $filter_type = isset($options['filter_type']) ? $options['filter_type'] : $filter_type;
        } else if (is_string($options)) {
            $title = $options;
        }

        /**
         * Compute whether the attributes is really sortable
         */
        $sortable = $sortable // must not be disabled through configuration
                && (is_null($decoder) || !is_null($decoder_for_sql)) // decoded attributes won't be sortable unless a decoder_for_sql is provided
                && !method_exists($this->model, "get_{$attribute}"); // computed fields are not sortabe

        return compact('attribute', 'title', 'sortable', 'linkify', 'auto_escape', 'decoder', 'decoder_for_sql', 'relational', 'relation_config', 'expose_filter', 'filter_type');
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

    private function _get_detail_view_action_details($config) {
        $action = Config::get('autolist::autolist.detail_view_action_default', 'detail');
        $permission_check = NULL;

        if (isset($config['detail_view_action'])) {
            if (is_string($config['detail_view_action'])) {
                $action = $config['detail_view_action'];
            } else if (is_array($config['detail_view_action'])) {
                if (!empty($config['detail_view_action']['action'])) {
                    $action = $config['detail_view_action']['action'];
                }

                if (!empty($config['detail_view_action']['permission_check']) && is_callable($config['detail_view_action']['permission_check'])) {
                    $permission_check = $config['detail_view_action']['permission_check'];
                }
            }
        }

        return array($action, $permission_check);
    }

    private function _get_query() {
        $model_class = $this->config['model'];
        $this->model = new $model_class;
        $this->model_key = $model_class::$key;
        $attributes = $this->config['attributes'];
        $eager_loads = is_array($this->config['eager_loads']) ? $this->config['eager_loads'] : array();
        $this->config['attributes'] = array();
        foreach ($attributes as $attribute => $options) {
            $attribute_details = $this->_get_attribute_details($attribute, $options);
            $parts = explode('.', $attribute);
            if (count($parts) > 1) {
                array_pop($parts);
                $attribute_details['relational'] = true;
                $eager_loads[] = $attribute_details['relation'] = implode('.', $parts);
                $attribute_details['sortable'] = false; // disable sorting on relational attributes
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


        /* if (is_callable($this->query_modifier)) {
          $query = $this->model->where($this->query_modifier);
          } */

        return $query;
    }

    private function _get_attribute_value($item, $attribute_details, $detail_view_action) {
        $value_store = $item;
        $raw_value = NULL;
        $attribute_parts = explode('.', $attribute_details['attribute']);
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
        if ($attribute_details['linkify'] && !empty($raw_value)) {
            $linked_model = $value_store;
            $linked_model_class = get_class($linked_model);
            $link_url = false;
            if (is_callable($attribute_details['linkify'])) {
                $link_url = call_user_func($attribute_details['linkify'], $linked_model);
            } else if (!empty($attribute_details['relation_config'])) {
                $permission_check = isset($attribute_details['relation_config']['permission_check']) && is_callable($attribute_details['relation_config']['permission_check']) ? $attribute_details['relation_config']['permission_check'] : Config::get('autolist::autolist.permission_check');
                list($related_detail_view_action, $related_detail_view_permission_check) = $this->_get_detail_view_action_details($attribute_details['relation_config']);

                $link_url = URL::to_action($attribute_details['relation_config']['action_controller'] . "@$related_detail_view_action", array($linked_model->{$linked_model_class::$key}));

                if ((!is_null($related_detail_view_permission_check) && !($related_detail_view_permission_check($linked_model, $linked_model->{$linked_model_class::$key}))) ||
                        ($permission_check && is_callable($permission_check) && !$permission_check($related_detail_view_action, $linked_model, $linked_model->{$linked_model_class::$key}))) {

                    $link_url = FALSE;
                }
            } else {
                $link_url = URL::to_action($this->config['action_controller'] . "@$detail_view_action", array($item->{$this->model_key}));
            }
            if (!empty($link_url)) {
                $value = render(Config::get('autolist::autolist.views.detail_link'), array(
                    'id' => $item->{$this->model_key},
                    'attribute' => $attribute_details['attribute'],
                    'action' => $detail_view_action,
                    'raw_value' => $raw_value,
                    'value' => $value,
                    'url' => $link_url
                ));
            }
        }
        return $value;
    }

    private function _find_filter_operator($filter, $operator) {
        
        $operators = array(
            'eq' => '=',
            'neq' => '!=',
            'sw' => 'LIKE',
            'cont' => 'LIKE',
            'ew' => 'LIKE',
            'lt' => '<',
            'gt' => '>',
            'btn' => 'BETWEEN'
        );
        $filter_operators = array(
            'string' => array('eq','neq','sw', 'cont', 'ew'),
            'number' => array('eq', 'neq', 'lt', 'gt', 'btn'),
            'integer' => array('eq', 'neq', 'lt', 'gt', 'btn'),
            'date' => array('eq','neq','lt', 'gt', 'btn'),
            'enum' => array('eq','neq')
        );
        if (in_array($operator, $filter_operators[$filter])) {
            return $operators[$operator];
        } else {
            
            return NULL;   
        }
    }

    private function _validate_filter_string($filter, $operator, $filter_string) {
        
        $rules = array(
//            'ibtn' => array(
//                'filter_string' => '/^[1-9][0-9]*,[1-9][0-9]*$/'
//            ),
//            'nbtn' => array(
//                'filter_string' => '/^([0-9]*.[0.9]+),([0-9]*.[0-9]+)$/'
//            ),
            'string' => array(
                'filter_string' => '/^[0-9a-zA-Z]*(,[0-9a-zA-Z]+)*$/'
            ),
            'numeric' => array(
                'filter_string' => '/^([0-9]*.[0.9]+)+(,[0-9]*.[0.9]+)*$/'
            ),
            'integer' => array(
                'filter_string' => '/^[1-9][0-9]*(,[1-9][0-9]*)*$/'
            ),
            'date' => array(
                'filter_string' => '/^[0-9]+-[0-9]+-[0-9]\s[0-9]+:[0-9]+:[0-9]+(,[0-9]+-[0-9]+-[0-9]\s[0-9]+:[0-9]+:[0-9]+)*$/'
            )
        );

        $rules['string_eq'] = $rules['string_neq'] = $rules['string_sw'] = $rules['string_ew'] = $rules['string_cont'] = $rules['string'];
        $rules['number_eq'] = $rules['number_neq'] = $rules['number_gt'] = $rules['number_lt'] = $rules['number_btn'] = $rules['numeric'];
        $rules['integer_eq'] = $rules['integer_neq'] = $rules['integer_gt'] = $rules['integer_lt'] = $rules['integer_btn'] = $rules['integer'];
        $rules['enum_eq'] = $rules['enum_neq'] = $rules['string'];
        //$rules['in_eq'] = $rules['in_neq'] = $rules['string'];
        $rules['date_eq'] = $rules['date_neq'] = $rules['date_gt'] = $rules['date_lt'] = $rules['date_btn'] = $rules['date']; 
        
        
        


        $operator_rules = $rules[$filter . '_' . $operator]['filter_string'];
        
        if (preg_match($operator_rules, $filter_string)) {
            return true;
        } else {
            //echo "Not found";
            return false;
        }
    }
    
    private function _filter_enabled($filter_by) {
        if ($this->config['attributes'][$filter_by]['sortable'] == true 
                && $this->config['attributes'][$filter_by]['expose_filter'] == true
                && isset($this->config['attributes'][$filter_by]['filter_type'])) return true;
        return false;
    }

    public function set_query_modifier($query_modifier) {
        $this->query_modifier = $query_modifier;
    }

    public function render() {
        $query_params = Input::query();
        $query = $this->_get_query();
        $active_sort_by = isset($query_params['sort_by']) ? $query_params['sort_by'] : $this->config['default_sort'];
        if (!empty($active_sort_by) && $this->config['attributes'][$active_sort_by]['sortable']) {
            $active_sort_dir = Input::query('sort_dir');
            if (empty($active_sort_dir)) {
                $active_sort_dir = ($active_sort_by == $this->config['default_sort'] && !empty($this->config['default_sort_dir'])) ? $this->config['default_sort_dir'] : 'ASC';
            }
            if (!is_null($this->config['attributes'][$active_sort_by]['decoder_for_sql'])) {
                $decoder_for_sql = $this->config['attributes'][$active_sort_by]['decoder_for_sql'];
                $sort_column = is_callable($decoder_for_sql) ? $decoder_for_sql($active_sort_by) : $decoder_for_sql;
                $sort_column = DB::raw($sort_column);
            } else {
                $sort_column = $active_sort_by;
            }
            $query = $query->order_by($sort_column, strtolower($active_sort_dir));
        } else {
            $active_sort_by = false;
            $active_sort_dir = false;
        }
        
        /* ----------- Filtering start */
        
        
        $filter_fields = array();
        foreach($this->config['attributes'] as $config) {
            if ($this->_filter_enabled($config['attribute']) == true) {
                $filter_fields[$config['attribute']] = $config; 
            }
        }
        
        $filter_optitles = array(
            'eq'	=> 'Equals',
            'neq'	=> 'Not equals',
            'lt'	=> 'Less than',
            'gt'	=> 'Greater than',
            'cont'	=> 'Contains',
            'sw'	=> 'Starts with',
            'ew'	=> 'Ends with',
            'btn'	=> 'Between'
        );
        
        $filter_opmap = array(
            'string'    => array(
                'eq'    => 'st',
                'neq'   => 'st',
                'sw'    => 'st',
                'ew'    => 'st',
                'cont'  => 'st'
            ),
            'integer'   => array(
                'eq'    => 'st',
                'neq'   => 'st',
                'lt'    => 'st',
                'gt'    => 'st',
                'btn'   => 'dt'
            ),
            'number'    => array(
                'eq'    => 'st',
                'neq'   => 'st',
                'lt'    => 'st',
                'gt'    => 'st',
                'btn'   => 'dt'
            ),
            'date'      => array(
                'eq'    => 'st',
                'neq'   => 'st',
                'lt'    => 'st',
                'gt'    => 'st',
                'btn'   => 'dt'
            ),
            'enum'      => array(
                'eq'    => 'select',
                'neq'   => 'select'
            )
        );
        
        
        
        $filter_by = ( isset($query_params['filter_by']) ? $query_params['filter_by'] : NULL ); // Check if filter is asked for
        $filter_operator = ( isset($query_params['filter_op']) ? $query_params['filter_op'] : NULL ); // Check if any operator is set
        $filter_string = ( isset($query_params['filter_str']) ? $query_params['filter_str'] : NULL );
        
        
        if ( !is_null($filter_by) && !is_null($filter_operator) && $this->_filter_enabled($filter_by) ) { // Filter has been asked for
            $configs = $this->config['attributes']; // Fetched the configs to shorten lines
            
            $active_filter_by = NULL;
            if ( isset($configs[$filter_by]['decoder_for_sql']) ) {
                $active_filter_by = ( is_callable($configs[$filter_by]['decoder_for_sql']) ? $configs[$filter_by]['decoder_for_sql']($filter_by) : $configs[$filter_by]['decoder_for_sql'] );
                $active_filter_by = DB::raw($active_filter_by);
                
            }
            
            $active_filter_type = NULL; // Default filter field is NULL
            if ( $configs[$filter_by]['expose_filter'] == true ) { // Check if filtering on that column is allowed
                $active_filter_type = $configs[$filter_by]['filter_type']; // Fetched the configured filter_type
                if ( is_array($active_filter_type) ) { // Decisive, array() = enumerated list, non_array = defined type
                    $active_filter_type = 'enum'; // Set the filter type to enum for further compatibility
                }
            }
            
            
            $active_filter_operator = NULL; // Default filter operator is NULL
            if ( !is_null($active_filter_type) ) { // Filter is valid
                $active_filter_operator = $this->_find_filter_operator($active_filter_type, $filter_operator); // Operator that is going to be used in querries
            }
            
            
            if ( !is_null($active_filter_by) && !is_null($active_filter_type) && !is_null($active_filter_operator) ) { // Filter is applicable if valid query is found
                $is_valid = false; // Default
                $is_valid = $this->_validate_filter_string($active_filter_type, $filter_operator, $filter_string); //validate input string
                if ($active_filter_type == 'string' || $active_filter_type == 'date') { // For string and date data types, exceptions are applied
                    $is_valid = true;
                }

                if ($is_valid) {
                    
                    
                    if ($active_filter_type != 'enum') { // 'string','integer','date','number'
                        $active_filter_list = explode(',',$filter_string);
                    } else { // 'enum'(array())
                        $active_filter_list = array();
                        $enum_filter_list = array();
                        foreach($configs[$filter_by]['filter_type'] as $value => $label) {
                            $enum_filter_list[] = "$value";
                        }
                        foreach(explode(',',$filter_string) as $string) {
                            if (in_array($string,$enum_filter_list)) {
                                $active_filter_list[] = $string;
                            }
                        }
                    }
                    
                    if (is_null($filter_string) || empty($filter_string)) { //
                        if ($active_filter_type == 'date') {
                            $filter_string = '0000-00-00 00:00:00';
                        }
                        $query = $query->where_null($active_filter_by)
                                       ->or_where($active_filter_by,$active_filter_operator,$filter_string);
                    } else if (count($active_filter_list)>0) {
                        if ($active_filter_operator == '=') {
                            $query = $query->where_in($active_filter_by,$active_filter_list);
                        } else if ($active_filter_operator == '!=') {
                            $query = $query->where_not_in($active_filter_by, $active_filter_list);
                        } else if ($active_filter_operator != 'BETWEEN') {
                            foreach($active_filter_list as $active_filter_string) {
                                if ($filter_operator == 'sw') $active_filter_string = $active_filter_string . '%';
                                else if ($filter_operator == 'ew') $active_filter_string = '%' . $active_filter_string;
                                else if ($filter_operator == 'cont') $active_filter_string = '%' . $active_filter_string . '%';
                                $query = $query->or_where($active_filter_by,$active_filter_operator,$active_filter_string);
                            }
                        } else {
                            $query = $query->where_between($active_filter_by,$active_filter_list[0],$active_filter_list[1]);
                        }
                    }
                }
            }
        }
        /* ----------- Filtering end */
        

        $paginate = isset($this->config['pager_enabled']) ? $this->config['pager_enabled'] : Config::get('autolist::autolist.pager_enabled', true);
        $per_page = isset($this->config['page_size']) ? $this->config['page_size'] : Config::get('autolist::autolist.page_size', 10);

        $page_links = FALSE;
        if ($paginate) {
            $pager = $query->paginate($per_page);
            $extra_query_params = $query_params;
            unset($extra_query_params['page']);
            $pager->appends($extra_query_params);
            $page_links = $pager->links();
            $items = $pager->results;
        } else {
            $items = $query->get();
        }

        $permission_check = isset($this->config['permission_check']) && is_callable($this->config['permission_check']) ? $this->config['permission_check'] : Config::get('autolist::autolist.permission_check');

        list($detail_view_action, $detail_view_permission_check) = $this->_get_detail_view_action_details($this->config);

        $permitted_items = array();
        $has_item_actions = false;



        foreach ($items as $item) {
            if (!is_null($detail_view_permission_check) && !($detail_view_permission_check($item, $item->{$this->model_key}))) {
                continue;
            } else if ($permission_check && is_callable($permission_check) && !$permission_check($detail_view_action, $item, $item->{$this->model_key})) {
                continue;
            }

            $action_links = array();
            foreach ($this->config['item_actions'] as $action => $action_options) {
                $action_details = $this->_get_action_details($action, $action_options);
                $action_permitted = true;
                if (is_callable($action_details['permission_check'])) {
                    $action_permission_check = $action_details['permission_check'];
                    $action_permitted = $action_permission_check($item, $item->{$this->model_key});
                } else if (is_callable($permission_check)) {
                    $action_permitted = $permission_check($action_details['action'], $item, $item->{$this->model_key});
                }

                if ($action_permitted) {
                    $action_details['id'] = $item->{$this->model_key};
                    $action_links[$action_details['action']] = render(Config::get('autolist::autolist.views.action_link'), $action_details);
                    $has_item_actions = true;
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
                $action_permitted = $action_permission_check($item);
            } else if (is_callable($permission_check)) {
                $action_permitted = $permission_check($action_details['action'], $item);
            }
            if ($action_permitted) {
                $action_details['id'] = NULL;
                $global_action_links[$action_details['action']] = render(Config::get('autolist::autolist.views.action_link'), $action_details);
            }
        }

        $header_columns = array();

        foreach ($this->config['attributes'] as $attribute => $attribute_details) {
            if ($attribute_details['sortable']) {

                $attribute_details['active_sort_by'] = $active_sort_by;
                $attribute_details['active_sort_dir'] = $active_sort_dir;

                $current_link_params = $query_params;

                $current_link_params['sort_by'] = $attribute;
                $current_link_params['sort_dir'] = 'ASC';

                $attribute_details['sort_url_asc'] = URL::to(URI::current() . "?" . http_build_query($current_link_params), Request::secure());

                $current_link_params['sort_dir'] = 'DESC';
                $attribute_details['sort_url_desc'] = URL::to(URI::current() . "?" . http_build_query($current_link_params), Request::secure());
            }

            $header_columns[$attribute] = render(Config::get('autolist::autolist.views.header_item'), $attribute_details);
        }


        $list_data = array(
            'title' => $this->config['title'],
            'header_columns' => $header_columns,
            'has_item_actions' => $has_item_actions,
            'items' => $permitted_items,
            'global_action_links' => $global_action_links,
            'page_links' => $page_links,
            'filter_fields' => $filter_fields,
            'filter_opmap' => $filter_opmap,
            'filter_optitles' => $filter_optitles
        );
        return render(Config::get('autolist::autolist.views.list'), $list_data);
    }

    public function __toString() {
        return $this->render();
    }

}