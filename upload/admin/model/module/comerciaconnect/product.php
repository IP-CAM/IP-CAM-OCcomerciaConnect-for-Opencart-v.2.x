<?php
use comercia\Util;
use comerciaConnect\logic\Product;
use comerciaConnect\logic\ProductCategory;
use comerciaConnect\logic\ProductDescription;

class ModelModuleComerciaconnectProduct extends Model
{
    function saveProduct($product)
    {
        $dbProduct["product_id"] = $product->id;
  	    $dbProduct["model"] = $product->code;
        $dbProduct["quantity"] = $product->quantity;
        $dbProduct["price"] = $product->price;
        $dbProduct["ean"] = $product->ean;
        $dbProduct["isbn"] = $product->isbn;
        $dbProduct["sku"] = $product->sku;
        $dbProduct["tax_class_id"] = $product->taxGroup;
        $productId = Util::db()->saveDataObject("product",$dbProduct);
        $product->changeId($product->id, $productId);

        if(empty($product->descriptions)) {
            $desc = new stdClass();
            $desc->language = 'en-gb';
            $desc->name = $product->name;
            $desc->description = '';

            $product->descriptions = array(
                $desc
            );
        }
        foreach($product->descriptions as $description) {
            $language = $this->getLanguageByCode($description->language);
            $dbDescription["language_id"] = $language["language_id"]?:1; //if language is not found assume its english.
            $dbDescription["product_id"] = $productId;
            $dbDescription["name"] = $description->name;
            $dbDescription["description"] = $description->description;
            Util::db()->saveDataObject("product_description", $dbDescription, array("product_id", "language_id"));
        }
    }

    //fix for 1.5
    private function getLanguageByCode($code) {
        if(Util::version()->isMaximal("1.6")){
            $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "language` WHERE code = '" . $this->db->escape($code) . "'");
            return $query->row;
        }else{
          return  $this->model_localisation_language->getLanguageByCode($code);
        }
    }

    function sendCategoryToApi($category, $session)
    {
        $apiCategory = new ProductCategory($session);
        $apiCategory->name = $category["name"];
        $apiCategory->id = $category["category_id"];
        $apiCategory->save();

        return $apiCategory;
    }

    function sendProductToApi($product, $session, $categoriesMap)
    {
        $this->load->model('localisation/tax_class');
        $this->load->model('localisation/tax_rate');
        $this->load->model('localisation/geo_zone');
        $this->load->model('localisation/language');

        $languages = $this->model_localisation_language->getLanguages();
        $productDescriptions = $this->model_catalog_product->getProductDescriptions($product["product_id"]);
        $descriptions = array();

        foreach ($languages as $language) {
            if(isset($productDescriptions[$language['language_id']])) {
                $descriptions[] = new ProductDescription($language["code"], $productDescriptions[$language["language_id"]]["name"], $productDescriptions[$language["language_id"]]["description"]);
            }
        }

        //decide categories
        $productCategories = $this->model_catalog_product->getProductCategories($product["product_id"]);
        $categories = array();

        foreach ($productCategories as $category) {
            $categories[] = $categoriesMap[$category];
        }

        //create new api product
        $apiProduct = new Product($session);

        //product basic information
        $apiProduct->id = $product["product_id"];
        $apiProduct->name = $product["name"];
        $apiProduct->quantity = $product["quantity"];
        $apiProduct->price = $product["price"];
        $apiProduct->url = HTTP_CATALOG;
        $apiProduct->ean = $product["ean"];
        $apiProduct->isbn = $product["isbn"];
        $apiProduct->sku = $product["sku"];
        $apiProduct->taxGroup = $product['tax_class_id'];

        //add arrays
        $apiProduct->categories = $categories;
        $apiProduct->descriptions = $descriptions;

        //save product to comercia connect
        if($product['date_modified'] > $this->config->get('comerciaConnect_last_sync')) {
            $apiProduct->save();
        }

        return $apiProduct;
    }
}

?>
