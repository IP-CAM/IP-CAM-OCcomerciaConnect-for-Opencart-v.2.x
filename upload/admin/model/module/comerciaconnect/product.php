<?php
use comercia\Util;
use comerciaConnect\logic\Product;
use comerciaConnect\logic\ProductCategory;
use comerciaConnect\logic\ProductDescription;

class ModelModuleComerciaconnectProduct extends Model
{
    function saveProduct($product)
    {

        if(is_numeric($product->id)) {
            $dbProduct["product_id"] = $product->id;
        }

  	    $dbProduct["model"] = $product->code;
        $dbProduct["quantity"] = $product->quantity;
        $dbProduct["price"] = $product->price;
        $dbProduct["ean"] = $product->ean;
        $dbProduct["isbn"] = $product->isbn;
        $dbProduct["sku"] = $product->sku;
        $dbProduct["tax_class_id"] = $product->taxGroup;
        $productId = Util::db()->saveDataObject("product",$dbProduct);
        $dbProduct["product_id"]=$productId;
        $product->changeId($productId);

        $this->saveHashForProduct($dbProduct);

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


    function createApiCategory($category, $session){

        $apiCategory = new ProductCategory($session);
        $apiCategory->name = $category["name"];
        $apiCategory->id = $category["category_id"];
        return $apiCategory;
    }
    function sendCategoryToApi($apiCategory,$session=false)
    {
        if(is_object($apiCategory)) {
            $apiCategory->save();
        }elseif(is_array($apiCategory) && $session){
            ProductCategory::saveBatch($session,$apiCategory);
        }
        return $apiCategory;
    }



    function updateCategoryStructure($session, $categories)
    {
        $maps = [];
        foreach ($categories as $category) {
            $maps[$category["category_id"]] = $category["parent_id"];
        }

        ProductCategory::updateStructure($session, $maps);
    }


    function createApiProduct($product, $session, $categoriesMap){
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
        $apiProduct->code = $product["model"];
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

        return $apiProduct;
    }

    function createChildProduct($session, $child, $parent)
    {
        $id = $parent->id . '_';
        $name = $parent->name . ' - ';
        $code = $parent->code;
        $price = $parent->price;
        $quantity = $parent->quantity;
        foreach ($child as $key => $value) {
            if ($value['quantity'] < $quantity) {
                $quantity = $value['quantity'];
            }
            $price = ($value['price_prefix'] == '-') ? $price - (float)$value['price'] : $price + (float)$value['price'];
            $name .= $value['full_value']['name'] . ' ';
            $id .= $value['option_value_id'] . '_';
        }
        $product = new Product($session, [
            'id' => rtrim($id, '_'),
            'name' => rtrim($name),
            'code' => rtrim($code),
            'quantity' => $quantity,
            'price' => $price,
            'descriptions' => $parent->descriptions,
            'categories' => $parent->categories,
            'taxGroup' => $parent->taxGroup,
            'type' => PRODUCT_TYPE_PRODUCT,
            'code' => $parent->code . '_' . $id,
            'image' => $parent->image,
            'brand' => $parent->brand,
            'parent' => $parent
        ]);

        return $product;
    }

    function sendProductToApi($apiProduct,$session=false)
    {
        if(is_object($apiProduct)) {
            $apiProduct->save();
        }elseif(is_array($apiProduct) && $session){
            Product::saveBatch($session,$apiProduct);
        }
        return $apiProduct;
    }

    function touchBatch($session,$products){
        Product::touchBatch($session,$products);
    }

    function getHashForProduct($product){
        return md5($product['date_modified']."_".$product["quantity"]);
    }

    function saveHashForProduct($product){
        $this->db->query("update ".DB_PREFIX."product set ccHash='".$this->getHashForProduct($product)."' where product_id='".$product['product_id']."'");
    }

    function getHashForCategory($category){
        return md5($category['date_modified']);
    }

    function saveHashForCategory($category){
        $this->db->query("update ".DB_PREFIX."category set ccHash='".$this->getHashForCategory($category)."' where category_id='".$category['category_id']."'");
    }

}

?>