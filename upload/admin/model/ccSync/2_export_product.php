<?php
include_once(DIR_SYSTEM . "/comercia/util.php");

class ModelCcSync2ExportProduct extends Model
{
    public function sync($data)
    {
        $products = $data->ccProductModel->getProducts($data->storeId, $data->syncMethod);

        $productMap = array();
        $productsChanged = array();
        $toSaveHash = [];

        \comerciaConnect\lib\Debug::writeMemory("Loaded products");
        foreach ($products as $product) {

            \comerciaConnect\lib\Debug::writeMemory("Start prepare product " . $product["product_id"]);
            $product['specialPrice'] = 0;
            $specialPrices = $data->productModel->getProductSpecials($product['product_id']);
            foreach ($specialPrices as $specialPrice) {
                if ($specialPrice['customer_group_id'] == \comercia\Util::config()->get('config_customer_group_id')) {
                    $product['specialPrice'] = $specialPrice['price'];
                }
            }

            $apiProduct = $data->ccProductModel->createApiProduct($product, $data->session, $data->categoriesMap);
            $productMap[$product["product_id"]] = $apiProduct;

            //save product to comercia connect
            if ($product["ccHash"] != $data->ccProductModel->getHashForProduct($product, $data->storeId)) {
                $productsChanged[] = $apiProduct;
                $toSaveHash[] = $product;
                $productOptionMap = array();
                $productOptions = $data->productModel->getProductOptions($product['product_id']);

                foreach ($productOptions as $productOption) {
                    $productOptionMap[$productOption['option_id']] = array_map(function ($productOptionValue) use ($data) {
                        $productOptionValue['full_value'] = $data->ccProductModel->getOptionValue($productOptionValue['option_value_id'],$data->storeId);
                        return $productOptionValue;
                    }, $productOption['product_option_value']);
                }

                if (count($productOptionMap) > 0) {
                    foreach (cc_cartesian($productOptionMap) as $child) {
                        $productsChanged[] = $data->ccProductModel->createChildProduct($data->session, $child, $productMap[$product["product_id"]]);
                    }
                }
            }

            \comerciaConnect\lib\Debug::writeMemory("Prepared product " . $product["product_id"]);


            if (count($productsChanged) > CC_BATCH_SIZE) {
                if ($data->ccProductModel->sendProductToApi($productsChanged, $data->session)) {
                    foreach ($toSaveHash as $toSaveHashProduct) {
                        $data->ccProductModel->saveHashForProduct($toSaveHashProduct, $data->storeId);
                    }
                }
                $toSaveHash = [];
                $productsChanged = [];
                \comerciaConnect\lib\Debug::writeMemory("Saved batch of products");
            }
        }

        if (count($productsChanged)) {
            if ($data->ccProductModel->sendProductToApi($productsChanged, $data->session)) {
                foreach ($toSaveHash as $toSaveHashProduct) {
                    $data->ccProductModel->saveHashForProduct($toSaveHashProduct, $data->storeId);
                }
            }
            \comerciaConnect\lib\Debug::writeMemory("Saved batch of products");
        }

        $data->productMap = $productMap;
    }

    function resultOnly($data)
    {
        $products = $data->ccProductModel->getProducts($data->storeId, $data->syncMethod);
        foreach ($products as $product) {
            $productMap[$product["product_id"]] = $data->ccProductModel->createApiProduct($product, $data->session, $data->categoriesMap);
        }
        $data->productMap = $productMap;
    }


}