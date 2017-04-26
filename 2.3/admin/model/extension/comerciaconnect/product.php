<?php

use comerciaConnect\logic\Product;
use comerciaConnect\logic\ProductCategory;

class ModelExtensionComerciaconnectOrder extends Model
{

    function saveProduct($product)
    {
        $dbProduct["product_id"] = $product->id;
        $dbProduct["name"] = $product->name;
  	$dbProduct["model"] = $product->code;
        $dbProduct["quantity"] = $product->quantity;
        $dbProduct["price"] = $product->price;
        $dbProduct["ean"] = $product->ean;
        $dbProduct["isbn"] = $product->isbn;
        $dbProduct["sku"] = $product->sku;
        $productId=\comercia\Util::db()->saveDataObject("product",$dbProduct);


        foreach($product->descriptions as $description){
            $language=$this->model_localisation_language->getLanguageByCode($description->language);
            $dbDescription["language_id"]=$language["language_id"];
            $dbDescription["product_id"]=$productId;
            $dbDescription["name"]=$description->name;
            $dbDescription["description"]=$description;
            \comercia\Util::db()->saveDataObject("product_description",$dbProduct,array("product_id","language_id"));
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
        $languages = $this->model_localisation_language->getLanguages();
        $productDescriptions = $this->model_catalog_product->getProductDescriptions($product["product_id"]);
        $descriptions = array();
        foreach ($languages as $language) {
            $descriptions[] = new ProductDescription($language["code"], $productDescriptions[$language["language_id"]]["name"], $productDescriptions[$language["language_id"]]["description"]);
        }

        //decide categories
        $productCategories = $this->model_catalog_product->getProductCategories($product["product_id"]);
        $categories = array();
        foreach ($productCategories as $category) {
            $categories[] = $categoriesMap[$category["category_id"]];
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


        //add arrays
        $apiProduct->categories = $categories;
        $apiProduct->descriptions = $descriptions;

        //save product to comercia connect
        $apiProduct->save();
        return $apiProduct;

    }
}

?>
