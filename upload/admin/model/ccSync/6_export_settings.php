<?php
use comercia\Util;
use comerciaConnect\logic\Website;

class ModelCcSync6ExportSettings extends Model
{
    public function sync($data)
    {
        //todo: optimize in future to skip this if not needed. This one extra call shouldn't cause problems for now.
        $website = Website::getWebsite($data->session);
        $website->address = Util::config()->get("config_address");
        $website->storeName = Util::config()->get("config_title");
        $website->email = Util::config()->get("config_email");
        $website->phone = Util::config()->get("config_telephone");

        $website->homepageUrl = Util::url()->catalog("");
        $website->checkoutConditionsUrl = Util::url()->catalog("information/information",
            "information_id=" . Util::config()->get("config_checkout_id"));
        $website->returnConditionsUrl = Util::url()->catalog("information/information",
            "information_id=" . Util::config()->get("config_return_id"));
        $website->userConditionsUrl = Util::url()->catalog("information/information",
            "information_id=" . Util::config()->get("config_account_id"));

        $website->languages = $this->getLanguages();
        $website->currencies = $this->getCurrencies();
        $website->weightUnits = $this->getWeightUnits();
        $website->lengthUnits = $this->getLengthUnits();
        $website->taxRates = $this->getTaxRates();
        $website->orderStatus=$this->getOrderStatus();
        $website->stockStatus=$this->getStockStatus();
        $website->fieldsOrder=$this->getFieldsOrder();
        $website->fieldsProduct=$this->getFieldsProduct();
        $website->save();
    }



    function getTaxRates()
    {
        $countryModel = Util::load()->model("localisation/country");
        $defaultCountry=$countryModel->getCountry(Util::config()->get("config_country_id"));

        $query = $this->db->query("SELECT r.tax_class_id AS class, tr.rate AS rate, c.iso_code_2 AS country FROM " . DB_PREFIX . "tax_rule AS r 
        LEFT JOIN " . DB_PREFIX . "tax_rate AS tr ON tr.tax_rate_id=r.tax_rate_id 
        LEFT JOIN " . DB_PREFIX . "geo_zone AS gz ON gz.geo_zone_id=tr.geo_zone_id 
        LEFT JOIN `" . DB_PREFIX . "zone_to_geo_zone` AS ztgz ON gz.geo_zone_id=ztgz.geo_zone_id 
        LEFT JOIN `" . DB_PREFIX . "country` c ON c.country_id = ztgz.country_id
        ");

        $result = [];
        foreach ($query->rows as $row) {
            $result[$row["country"]][$row["class"]] = $row["rate"];

            if ($defaultCountry["iso_code_2"] == $row["country"]) {
                $result["default"][$row["class"]] = $row["rate"];
            }
        }
        return $result;
    }

    function getLengthUnits()
    {
        $lengthClassModel = Util::load()->model("localisation/length_class");
        $classes = $lengthClassModel->getLengthClasses();
        $result = [];
        foreach ($classes as $class) {
            $result[] = $class["unit"];
        }
        return $result;
    }

    function getWeightUnits()
    {
        $weightClassModel = Util::load()->model("localisation/weight_class");
        $classes = $weightClassModel->getWeightClasses();
        $result = [];
        foreach ($classes as $class) {
            $result[] = $class["unit"];
        }
        return $result;
    }

    function getLanguages()
    {
        $languageModel = Util::load()->model("localisation/language");
        $languages = $languageModel->getLanguages();
        $result = [];
        foreach ($languages as $language) {
            $result[] = $language["code"];
        }
        return $result;
    }

    function getCurrencies()
    {
        $currencyModel = Util::load()->model("localisation/currency");
        $currencies = $currencyModel->getCurrencies();
        $result = [];
        foreach ($currencies as $currency) {
            $result[] = $currency["code"];
        }
        return $result;
    }

    function getOrderStatus()
    {
        $stockStatusModel = Util::load()->model("localisation/order_status");
        $statuses = $stockStatusModel->getOrderStatuses();
        $result = [];
        foreach ($statuses as $status) {
            $result[] = $status["name"];
        }
        return $result;
    }

    function getStockStatus()
    {
        $stockStatusModel = Util::load()->model("localisation/stock_status");
        $statuses = $stockStatusModel->getStockStatuses();
        $result = [];
        foreach ($statuses as $status) {
            $result[] = $status["name"];
        }
        return $result;
    }

    private function getFieldsOrder()
    {
        $result=[];
        $query=$this->db->query("SHOW FIELDS FROM `".DB_PREFIX."order`");
        foreach($query->rows as $row){
            $result[]=$row["Field"];
        }
        return $result;
    }

    private function getFieldsProduct()
    {
        $result=[];
        $query=$this->db->query("SHOW FIELDS FROM `".DB_PREFIX."product`");
        foreach($query->rows as $row){
            $result[]=$row["Field"];
        }

        $options=Util::load()->model("catalog/option")->getOptions();
        foreach($options as $option){
            $result[]="option_".$option['name'];
        }

        $attributes=Util::load()->model("catalog/attribute_group")->getAttributeGroups();
        foreach($attributes as $attribute){
            $result[]="attribute_".$attribute["name"];
        }

        return $result;

    }

}