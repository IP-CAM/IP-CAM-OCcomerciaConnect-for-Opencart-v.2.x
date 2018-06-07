<?php

namespace comercia;
class Config
{
    var $model;
    var $store_id;
    var $data=[];

    function __construct($store_id = 0)
    {
        $this->model = Util::load()->model("setting/setting");
        $this->store_id=$store_id;
        $data=Util::db()->query("SELECT * FROM " . DB_PREFIX . "setting WHERE store_id = ".$store_id."");
        foreach($data as $value){
            $this->data[$value["key"]]=$value["value"];
        }
    }

    function __get($name)
    {
        return $this->get($name);
    }

    function get($key,$ignoreMainStore=false)
    {
        if(isset($this->data[$key])){
            return @$this->data[$key]?:"";
        }elseif($this->store_id && !$ignoreMainStore) {
            return Util::config(0)->$key;
        }
        return "";
    }

    function getGroup($code)
    {
        return $this->model->getSetting($code,$this->store_id);
    }

    function set($code, $key, $value = false)
    {
        if (is_array($key)) {
            $this->model->editSetting($code, $key,$this->store_id);
            $items=Util::arrayHelper()->allPrefixed($key,$code,false);
            foreach($items as $key=>$val){
                $this->data[$key]=$val;
            }
        } else {
            $this->model->editSettingValue($code, $key, $value,$this->store_id);
            $this->data[$key]=$value;
        }
    }
}

?>