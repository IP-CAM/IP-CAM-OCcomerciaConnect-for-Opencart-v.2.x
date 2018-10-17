<?php

use comercia\Util;
use comerciaConnect\logic\Product;
use comerciaConnect\logic\ProductCategory;
use comerciaConnect\logic\ProductDescription;

class ModelModuleComerciaconnectProduct extends Model
{


    function saveProduct($product)
    {
        if (is_numeric($product->id)) {
            $dbProduct["product_id"] = $product->id;
        }

        $dbProduct["model"] = $product->code;
        $dbProduct["quantity"] = $product->quantity;
        $dbProduct["price"] = $product->price;
        $dbProduct["jan"] = $product->jan;
        $dbProduct["upc"] = $product->upc;
        $dbProduct["ean"] = $product->ean;
        $dbProduct["isbn"] = $product->isbn;
        $dbProduct["sku"] = $product->sku;
        $dbProduct["tax_class_id"] = $product->taxGroup;
        $dbProduct["ccCreatedBy"] = $product->createdBy;
        $dbOrderInfo["ccConnector"] = $product->connector;
        $dbProduct["subtract"] = $product->usesStock;

        $image = $this->handleImage($product->image, $product);
        if ($image) {
            $dbProduct["image"] = $image;
        }

        $productId = Util::db()->saveDataObject("product", $dbProduct);
        $dbProduct["product_id"] = $productId;
        $product->changeId($productId);

        $dbProductImage = [];
        foreach ($product->extraImages as $image) {
            $image = $this->handleImage($image->image, $product);
            if ($image) {
                $dbProductImage[] = [
                    'product_id' => $productId,
                    'image' => $image
                ];
            }
        }
        if (count($dbProductImage)) {
            Util::db()->saveDataObjectArray("product_image", $dbProductImage, ["product_id", "image"]);
        }

        $this->saveHashForProduct($dbProduct);

        if (empty($product->descriptions)) {
            $desc = new stdClass();
            $desc->language = 'en-gb';
            $desc->name = $product->name;
            $desc->description = '';

            $product->descriptions = array(
                $desc
            );
        }
        foreach ($product->descriptions as $description) {
            $language = $this->getLanguageByCode($description->language);
            $dbDescription["language_id"] = $language["language_id"] ?: 1; //if language is not found assume its english.
            $dbDescription["product_id"] = $productId;
            $dbDescription["name"] = $description->name;
            $dbDescription["description"] = $description->description;
            Util::db()->saveDataObject("product_description", $dbDescription, array("product_id", "language_id"));
        }
    }

    function updateOptionQuantity($product, $storeId)
    {
        //prepare some information
        $productModel = Util::load()->model("catalog/product");
        $optionPrefix = "option_";
        $parent = $product->parent;
        $quantity = $product->quantity;
        $expectedQuantity = -1;

        //find all used option values, and calculate an expected Quantity
        $ocOptions = $productOptions = $productModel->getProductOptions($parent->id);
        $ocUsedOptionValues = [];
        foreach ($product->originalData as $optionKey => $optionValue) {
            if (substr($optionKey, 0, strlen($optionPrefix)) == $optionPrefix) {
                $optionName = substr($optionKey, strlen($optionPrefix));
                foreach ($ocOptions as $ocOption) {
                    if ($ocOption["name"] == $optionName) {
                        foreach ($ocOption["product_option_value"] as $ocOptionValue) {
                            $optionValueInfo = $this->getOptionValue($ocOptionValue["option_value_id"], $storeId);
                            if ($optionValueInfo["name"] == $optionValue) {
                                $ocUsedOptionValues[] = $ocOptionValue;
                                if ($expectedQuantity < 0 || $ocOptionValue["quantity"] < $expectedQuantity) {
                                    $expectedQuantity = $ocOptionValue["quantity"];
                                }
                            }
                        }
                    }
                }
            }
        }

        //calculate the change made in the quantity
        $diff = $expectedQuantity - $quantity;

        foreach ($ocUsedOptionValues as $ocOptionValue) {
            Util::db()->query("UPDATE " . DB_PREFIX . "product_option_value SET quantity=quantity-" . $diff . " WHERE product_option_value_id='" . $ocOptionValue['product_option_value_id'] . "'");
        }
    }

    private function getLanguageByCode($code)
    {
        $code = is_array($code) ? $code['language'] : $code;
        if (Util::version()->isMaximal("1.6")) {
            $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "language` WHERE code = '" . $this->db->escape($code) . "'");
            return $query->row;
        } else {
            $model = Util::load()->model('localisation/language');
            return $model->getLanguageByCode($code);
        }
    }

    function createApiCategory($category, $session)
    {
        $apiCategory = new ProductCategory($session);
        $apiCategory->name = $category["name"];
        $apiCategory->id = $category["category_id"];
        return $apiCategory;
    }

    function sendCategoryToApi($apiCategory, $session = false)
    {
        if (is_object($apiCategory)) {
            $apiCategory->save();
        } elseif (is_array($apiCategory) && $session) {
            return ProductCategory::saveBatch($session, $apiCategory)["success"];
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


    function createApiProduct($product, $session, $categoriesMap, $conditions)
    {
        $this->load->model('localisation/tax_class');
        $this->load->model('localisation/stock_status');
        $this->load->model('localisation/tax_rate');
        $this->load->model('localisation/geo_zone');
        $this->load->model('localisation/language');
        $this->load->model('catalog/manufacturer');
        $this->load->model('catalog/attribute');
        $this->load->model('catalog/attribute_group');
        $this->load->model("tool/image");

        $languages = $this->model_localisation_language->getLanguages();
        $productDescriptions = $this->model_catalog_product->getProductDescriptions($product["product_id"]);
        $descriptions = array();

        foreach ($languages as $language) {
            if (isset($productDescriptions[$language['language_id']])) {
                $descriptions[] = new ProductDescription($language["code"], $productDescriptions[$language["language_id"]]["name"], $productDescriptions[$language["language_id"]]["description"], $productDescriptions[$language["language_id"]]);
            }
        }

        //decide categories
        $productCategories = $this->model_catalog_product->getProductCategories($product["product_id"]);
        $categories = array();

        foreach ($productCategories as $category) {
            $categories[] = $categoriesMap[$category];
        }

        $productImages = $this->model_catalog_product->getProductImages($product["product_id"]);
        $extraImages = array();


        if (@$conditions["img_min_width"]) {
            $imageWidth = $conditions["img_min_width"];
        } else {
            $imageWidth = 800;
        }
        if (@$conditions["img_min_height"]) {
            $imageHeight = $conditions["img_min_height"];
        } else {
            $imageHeight = 600;
        }

        foreach ($productImages as $image) {
            $extraImages[] = ['image' => $this->model_tool_image->resize($image['image'], $imageWidth, $imageHeight)];
        }

        //create new api product
        $apiProduct = new Product($session);

        //product basic information
        $apiProduct->id = $product["product_id"];
        $apiProduct->name = $product["name"];
        $apiProduct->code = $product["model"];
        $apiProduct->quantity = $product["quantity"];
        $apiProduct->price = $product["price"];
        $apiProduct->specialPrice = $product["specialPrice"];
        $apiProduct->url = Util::url()->getCatalogUrl() . "?route=product/product&product_id=" . $product["product_id"];
        $brand = $this->model_catalog_manufacturer->getManufacturer($product["manufacturer_id"]);
        $apiProduct->brand = (!empty($brand["name"]) ? $brand["name"] : "");
        $apiProduct->ean = $product["ean"];
        $apiProduct->isbn = $product["isbn"];
        $apiProduct->sku = $product["sku"];
        $apiProduct->taxGroup = $product['tax_class_id'];
        $apiProduct->active = $product['status'];
        //todo: in future make this configurable
        $apiProduct->image = $this->model_tool_image->resize($product['image'], $imageWidth, $imageHeight);


        //build original data
        $originalData = $product;


        static $attrGroupNames;
        if (!$attrGroupNames) {
            $attributes = Util::load()->model("catalog/attribute_group")->getAttributeGroups();
            foreach ($attributes as $attribute) {
                $attrGroupNames[$attribute["attribute_group_id"]] = "attribute_" . $attribute["name"];
            }
        }

        $attributes = $this->model_catalog_product->getProductAttributes($product["product_id"]);
        foreach ($attributes as $productAttribute) {
            $attributeInfo = $this->model_catalog_attribute->getAttribute($productAttribute['attribute_id']);
            $originalData[$attrGroupNames[$attributeInfo["attribute_group_id"]]] = $attributeInfo["name"];
        }

        $apiProduct->originalData = $originalData;

        $apiProduct->inStockStatus = "inStock";
        $stockStatus = $this->model_localisation_stock_status->getStockStatus($product["stock_status_id"]);
        if ($stockStatus) {
            $apiProduct->noStockStatus = $stockStatus["name"];
        }

        //add arrays
        $apiProduct->categories = $categories;
        $apiProduct->descriptions = $descriptions;
        $apiProduct->extraImages = $extraImages;

        $apiProduct->weight = $product['weight'];
        $apiProduct->height = $product['height'];
        $apiProduct->length = $product['length'];
        $apiProduct->width = $product['width'];

        $apiProduct->usesStock = $product['subtract'];
        $apiProduct->jan = @$product['jan'];
        $apiProduct->upc = @$product['upc'];

        return $apiProduct;
    }

    function createChildProduct($session, $child, $parent)
    {
        $id = $parent->id . '_';
        $name = $parent->name . ' - ';
        $price = $parent->price;
        $specialPrice = $parent->specialPrice;
        $quantity = $parent->quantity;
        $originalData = $parent->originalData;


        foreach ($child as $key => $value) {
            if ($value['quantity'] < $quantity) {
                $quantity = $value['quantity'];
            }
            $option = Util::load()->model("catalog/option")->getOption($value["full_value"]["option_id"]);
            $price = ($value['price_prefix'] == '-') ? $price - (float)$value['price'] : $price + (float)$value['price'];
            if ($specialPrice) {
                $specialPrice = ($value['price_prefix'] == '-') ? $specialPrice - (float)$value['price'] : $specialPrice + (float)$value['price'];
            }

            $name .= $value['full_value']['name'] . ' ';
            $id .= $value['option_value_id'] . '_';
            $originalData["option_" . $option['name']] = $value['full_value']['name'];
        }

        $id = rtrim($id, '_');


        $product = new Product($session, [
            'id' => $id,
            'name' => rtrim($name),
            'code' => $parent->code . '_' . $id,
            'quantity' => $quantity,
            'price' => $price,
            'descriptions' => $parent->descriptions,
            'categories' => $parent->categories,
            'taxGroup' => $parent->taxGroup,
            'specialPrice' => $specialPrice,
            'type' => PRODUCT_TYPE_PRODUCT,
            'image' => $parent->image,
            'brand' => $parent->brand,
            'active' => $parent->active,
            'parent' => $parent,
            'originalData' => $originalData
        ]);

        return $product;
    }


    function deactivateChildrenFor($apiProduct, $session = false)
    {
        if (is_object($apiProduct)) {
            $apiProduct->deactivateChildren();
        } elseif (is_array($apiProduct) && $session) {
            return Product::deactivateChildrenBatch($session, $apiProduct)["success"];
        }
    }


    function sendProductToApi($apiProduct, $session = false)
    {
        if (is_object($apiProduct)) {
            $apiProduct->save();
        } elseif (is_array($apiProduct) && $session) {
            return Product::saveBatch($session, $apiProduct)["success"];
        }
        return $apiProduct;
    }

    function touchBatch($session, $products)
    {
        Product::touchBatch($session, $products);
    }

    function getHashForProduct($product, $storeId = 0)
    {
        $originalHash = $product["ccHash"];
        $length = CC_HASH_LENGTH;
        $currentProductHash = substr(md5(@$product['date_modified'] . '_' . $product["quantity"] . '_' . ControllerModuleComerciaConnect::$subHash . "_" . $product["price"] . "_" . $product["status"]), 0, $length);
        $stores = Util::info()->stores();
        $newHash = "";
        foreach ($stores as $key => $store) {
            if ($store["store_id"] == $storeId) {
                $newHash .= $currentProductHash;
            } else {
                if (strlen($originalHash) >= $length * $key + $length) {
                    $newHash .= substr($originalHash, $key * $length, $length);
                } else {
                    $newHash .= str_repeat("_", $length);
                }
            }
        }

        return $newHash;
    }

    function saveHashForProduct($product, $storeId = 0)
    {
        $this->db->query("UPDATE `" . DB_PREFIX . "product` SET `ccHash` = '" . $this->getHashForProduct($product, $storeId) . "' WHERE `product_id` = '" . $product['product_id'] . "'");
    }

    function getHashForCategory($category, $storeId = 0)
    {
        $originalHash = $category["ccHash"];
        $length = CC_HASH_LENGTH;
        $currentCategoryHash = substr(md5(@$category['date_modified'] . '_' . ControllerModuleComerciaConnect::$subHash), 0, $length);
        $stores = Util::info()->stores();

        $newHash = "";

        foreach ($stores as $key => $store) {
            if ($store["store_id"] == $storeId) {
                $newHash .= $currentCategoryHash;
            } else {
                if (strlen($originalHash) >= $length * $key + $length) {
                    $newHash .= substr($originalHash, $key * $length, $length);
                } else {
                    $newHash .= str_repeat("_", $length);
                }
            }
        }

        return $newHash;
    }

    function saveHashForCategory($category, $storeId = 0)
    {
        $this->db->query("UPDATE `" . DB_PREFIX . "category` SET `ccHash` = '" . $this->getHashForCategory($category, $storeId) . "' WHERE `category_id` = '" . $category['category_id'] . "'");
    }


    function isHashed($productId)
    {
        $result = $this->db->query("select ccHash from `" . DB_PREFIX . "product` where product_id='" . $productId . "'")->row;
        return $result["ccHash"] ? true : false;
    }

    // Start OC Version <= 1.5.2.1 specific functions
    public function getOptionValue($option_value_id, $storeId)
    {
        return $this->db->query("SELECT * FROM " . DB_PREFIX . "option_value ov LEFT JOIN " . DB_PREFIX . "option_value_description ovd ON (ov.option_value_id = ovd.option_value_id) WHERE ov.option_value_id = '" . (int)$option_value_id . "' AND ovd.language_id = '" . (int)Util::load()->model("module/comerciaconnect/general")->getLanguageIdForStore($storeId) . "'")->row;
    }

    public function getCategory($category_id, $storeId)
    {
        $languageId = Util::load()->model("module/comerciaconnect/general")->getLanguageIdForStore($storeId);
        return $this->db->query("
              SELECT DISTINCT *
              FROM " . DB_PREFIX . "category c 
              JOIN " . DB_PREFIX . "category_description cd ON (cd.category_id = c.category_id) 
              WHERE c.category_id = '" . (int)$category_id . "' 
              AND cd.language_id = '" . $languageId . "'
              ")->row;
    }

    public function getProducts($store = 0, $syncMethod = 0)
    {
        $sql = "SELECT * FROM " . DB_PREFIX . "product as p LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id)";

        if ($syncMethod) {
            $sql .= " LEFT JOIN " . DB_PREFIX . "product_to_store AS ps ON ps.product_id=p.product_id";
        }

        $language = Util::load()->model("module/comerciaconnect/general")->getLanguageIdForStore($store);

        $sql .= " WHERE pd.language_id = '" . $language . "'";
        if ($syncMethod) {
            $sql .= " AND ps.store_id='" . $store . "'";
        }

        $query = $this->db->query($sql);

        return $query->rows;
    }

    public function getCategories($store = 0, $syncMethod = 0)
    {
        $sql = "SELECT 
                c.category_id AS category_id, c.parent_id AS parent_id
                FROM " . DB_PREFIX . "category AS c
            ";

        if ($syncMethod) {
            $sql .= "LEFT JOIN " . DB_PREFIX . "category_to_store AS cs ON cs.category_id=c.category_id WHERE cs.store_id='" . $store . "'";
        }


        $query = $this->db->query($sql);

        return $query->rows;
    }

    private function handleImage($image, $product)
    {

        if (strpos($image, $_SERVER['HTTP_HOST']) !== false) {
            $image = str_replace([@HTTP_CATALOG . "image", @HTTPS_CATALOG . "image", "/cache"], "", $image);
            $image = substr($image, 1);

            $exp = explode("-", $image);
            array_pop($exp);

            $image = implode("-", $exp);
            $image = Util::filesystem()->search(DIR_IMAGE, $image);
            $image = $image[0];

            return str_replace(DIR_IMAGE, '', $image);
        }

        $pathConnect = DIR_IMAGE . 'connect/';
        $dirConnect = 'connect/';
        if (!is_dir($pathConnect)) {
            mkdir($pathConnect);
        }

        $exp = explode("/", $image);
        $filename = $exp[count($exp) - 1];

        $content = $product->getImageData($image);
        if ($content) {
            $handle = fopen($pathConnect . $filename, "w+");
            fwrite($handle, $content);
            fclose($handle);
            return $dirConnect . $filename;
        }

        return false;
    }
    // End OC Version <= 1.5.2.1 specific functions

}

?>
