<?php

class AutoList {
    var $config_id;
    var $filters;
    var $model;
    
    function __construct($config_id, $data_filters=array()) {
        $this->config_id = $config_id;
        if(!Config::has($config_id) || !Config::has("$config_id.model")){
            throw new Exception('Model must be defined in autolist config');
        }
        $model = Config::get("$config_id.model");
        $this->model = new $model;
        $this->filters = $data_filters;
    }
    
    public function render() {
        
    }

    public function __toString() {
        return $this->render();
    }
}