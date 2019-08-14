<?php

namespace comercia;
class Config
{
    var $model;
    var $store_id;
    var $data = [];

    function __construct($store_id = 0)
    {
        $this->model = Util::load()->model("setting/setting");
        $this->store_id = $store_id;
        $data = Util::db()->query("SELECT * FROM " . DB_PREFIX . "setting WHERE store_id = " . $store_id . "");
        foreach ($data as $value) {
            if (!$value['serialized']) {
                $this->data[$value["key"]] = $value["value"];
            } else {
                $this->data[$value["key"]] = $this->unserialize($value["value"], true);
            }
        }
    }

    function __get($name)
    {
        return $this->get($name);
    }

    function __set($name, $value)
    {
        $code = explode("_", $name)[0];
        $this->set($code, $name, $value);
    }

    function get($key, $ignoreMainStore = false)
    {
        $result = "";
        if (isset($this->data[$key])) {
            $result = @$this->data[$key] ?: "";
        } elseif ($this->store_id && !$ignoreMainStore) {
            $result = Util::config(0)->$key;
        }

        //Fall back to normal store configuration.
        if (empty($result)) {
            $result = Util::registry()->get("config")->get($key);
        }

        return $result;
    }

    function getGroup($code)
    {
        return $this->model->getSetting($code, $this->store_id);
    }

    function set($code, $key, $value = false)
    {
        if (!is_array($key)) {
            $key = [$key => $value];
        }
        $items = Util::arrayHelper()->allPrefixed($key, $code, false);
		$this->model->editSetting($code, $items, $this->store_id);
        foreach ($items as $key => $val) {
            $this->data[$key] = $val;
        }
    }

    function serialize($str, $options = false)
    {
        if (Util::version()->isMinimal(2.1)) {
            $result = json_encode($str, $options);
        } else {
            $result = \serialize($str);
        }

        return $result;
    }

    function unserialize($str, $options = false)
    {
        if (Util::version()->isMinimal(2.1)) {
            $result = json_decode($str, $options);
        } else {
            if (!empty($options) && $options != 'allowed_classes') {
                $options = false;
            }

            $result = \unserialize($str, $options);
        }

        return $result;
    }
}

?>