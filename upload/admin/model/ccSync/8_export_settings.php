<?php

use comercia\Util;
use comerciaConnect\logic\Translation;
use comerciaConnect\logic\Website;

class ModelCcSync8ExportSettings extends Model
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
        $website->orderStatus = $this->getOrderStatus();
        $website->stockStatus = $this->getStockStatus();
        $website->fieldsOrder = $this->getFieldsOrder();
        $website->customerGroups=$this->getCustomerGroups();

        $fields = $this->getFieldsProduct();
        $website->fieldsProduct = $fields["fields"];
        $website->translations = $fields["translations"];
        $adminDir = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
        $website->syncEndpoint = $adminDir . "?route=module/comerciaConnect/sync&mode=api&store_id=" . $data->storeId;
        $website->save();
    }


    function getCustomerGroups(){
        if(Util::version()->isMaximal("2.0")) {
            $cgModel = Util::load()->model("customer/customer_group");
        }else{
            $cgModel= Util::load()->model("account/customer_group");
        }
        $customerGroups=$cgModel->getCustomerGroups();
        return array_map(function($customerGroup){
            return $customerGroup["name"];
        },$customerGroups);
    }

    function getTaxRates()
    {
        $countryModel = Util::load()->model("localisation/country");
        $defaultCountry = $countryModel->getCountry(Util::config()->get("config_country_id"));

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
        $result = [];
        $query = $this->db->query("SHOW FIELDS FROM `" . DB_PREFIX . "order`");
        foreach ($query->rows as $row) {
            $result[] = $row["Field"];
        }
        return $result;
    }

    private function getFieldsProduct()
    {

        $result = [];

        $query = $this->db->query("SHOW FIELDS FROM `" . DB_PREFIX . "product`");
        $languages = $this->db->query("SELECT * FROM `" . DB_PREFIX . "language`");

        foreach ($languages->rows as $language) {
            Util::language($language["directory"])->load("catalog/product");
        }

        foreach ($query->rows as $row) {
            $result["fields"][$row["Field"]] = [
                "name" => $row["Field"],
            ];

            foreach ($languages->rows as $language) {
                $key = $row["Field"] . "_" . $language["code"];

                $text=explode("<",Util::language($language["directory"])->get("entry_" . $row["Field"]))[0];
                if ($text && $text != "entry_" . $row["Field"]) {
                    $result["translations"][$key] = new Translation($language["code"], "productField", $row["Field"], $text);
                }
            }

        }

        $optionsModel = Util::load()->model("catalog/option");
        $options = $optionsModel->getOptions();

        foreach ($options as $option) {
            $optionValues = $optionsModel->getOptionValues($option["option_id"]);

            $value = [];
            foreach ($optionValues as $optionValue) {
                $value[] = $optionValue["name"];
            }

            $optionDescriptions=$optionsModel->getOptionDescriptions($option["option_id"]);
            foreach ($languages->rows as $language) {
                $key = "option_" . $option['name'] . "_" . $language["code"];
                if($optionDescriptions[$language["language_id"]]){
                    $result["translations"][$key] = new Translation($language["code"], "productField", "option_" . $option['name'],$optionDescriptions[$language["language_id"]]);
                }
            }

            $result["fields"]["option_" . $option['name']] = [
                "name" => "option_" . $option['name'],
                "options" => $value,
            ];



        }
        $attributeGroupModel=Util::load()->model("catalog/attribute_group");
        $attributes = $attributeGroupModel->getAttributeGroups();
        foreach ($attributes as $attribute) {
            $result["fields"]["attribute_" . $attribute["name"]] = [
                "name" => "attribute_" . $attribute["name"],
            ];


            $attributeDescriptions=$attributeGroupModel->getAttributeGroupDescriptions($attribute["attribute_id"]);
            foreach ($languages->rows as $language) {
                $key = "attribute_" . $attribute["name"] . "_" . $language["code"];
                if($attributeDescriptions[$language["language_id"]]){
                    $result["translations"][$key] = new Translation($language["code"], "productField", "attribute_" . $attribute["name"],$attributeDescriptions[$language["language_id"]]);
                }
            }

        }


        $result["fields"] = array_values($result["fields"]);
        $result["translations"] = array_values($result["translations"]);

        return $result;

    }


}