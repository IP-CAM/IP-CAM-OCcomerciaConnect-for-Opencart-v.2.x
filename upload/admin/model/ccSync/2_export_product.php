<?php
include_once(DIR_SYSTEM . "/comercia/util.php");
use comercia\Util;

class ModelCcSync2ExportProduct extends Model
{
    public function sync($data)
    {
        $products = $data->productModel->getProducts();
        $productMap = array();
        $productsChanged = array();
        $toSaveHash=[];
        foreach ($products as $product) {
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
            if ($product["ccHash"] != $data->ccProductModel->getHashForProduct($product)) {
                $productsChanged[] = $apiProduct;
                $toSaveHash[]=$product;
                $productOptionMap = array();
                $productOptions = $data->productModel->getProductOptions($product['product_id']);

                foreach ($productOptions as $productOption) {
                    $productOptionMap[$productOption['option_id']] = array_map(function ($productOptionValue) use ($data) {
                        if (Util::version()->isMaximal("1.5.2.1")) {
                            $productOptionValue['full_value'] = $data->ccProductModel->getOptionValue($productOptionValue['option_value_id']);
                        } else {
                            $productOptionValue['full_value'] = $data->optionModel->getOptionValue($productOptionValue['option_value_id']);
                        }
                        return $productOptionValue;
                    }, $productOption['product_option_value']);
                }

                if (count($productOptionMap) > 0) {
                    foreach (cc_cartesian($productOptionMap) as $child) {
                        $productsChanged[] = $data->ccProductModel->createChildProduct($data->session, $child, $productMap[$product["product_id"]]);
                    }
                }
            }
            if (count($productsChanged)>100) {
                if($data->ccProductModel->sendProductToApi($productsChanged, $data->session)) {
                    foreach ($toSaveHash as $toSaveHashProd) {
                        $data->ccProductModel->saveHashForProduct($toSaveHashProd);
                    }
                }
                $toSaveHash=[];
                $productsChanged=[];
            }
        }

        if (count($productsChanged)) {
            if($data->ccProductModel->sendProductToApi($productsChanged, $data->session)) {
                foreach ($toSaveHash as $toSaveHashProd) {
                    $data->ccProductModel->saveHashForProduct($toSaveHashProd);
                }
            }
        }

        $data->productMap = $productMap;
    }
}