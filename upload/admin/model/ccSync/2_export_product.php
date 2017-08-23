<?php

class ModelCcSync2ExportProduct extends Model
{
    public function sync($data)
    {
        $products = $data->productModel->getProducts();
        $productMap = array();
        $productsChanged = array();
        foreach ($products as $product) {
            $apiProduct = $data->ccProductModel->createApiProduct($product, $data->session, $data->categoriesMap);
            $productMap[$product["product_id"]] = $apiProduct;

            //save product to comercia connect
            if ($product["ccHash"] != $data->ccProductModel->getHashForProduct($product)) {
                $productsChanged[] = $apiProduct;
                $data->ccProductModel->saveHashForProduct($product);

                $productOptionMap = array();
                $productOptions = $data->productModel->getProductOptions($product['product_id']);

                foreach ($productOptions as $productOption) {
                    $productOptionMap[$productOption['option_id']] = array_map(function ($productOptionValue) use ($data) {
                        $productOptionValue['full_value'] = $data->optionModel->getOptionValue($productOptionValue['option_value_id']);
                        return $productOptionValue;
                    }, $productOption['product_option_value']);
                }

                if (count($productOptionMap) > 0) {
                    foreach (cc_cartesian($productOptionMap) as $child) {
                        $productsChanged[] = $data->ccProductModel->createChildProduct($data->session, $child, $productMap[$product["product_id"]]);
                    }
                }
            }
        }

        if (count($productsChanged)) {
            $data->ccProductModel->sendProductToApi($productsChanged, $data->session);
        }

        $data->productMap = $productMap;
    }
}