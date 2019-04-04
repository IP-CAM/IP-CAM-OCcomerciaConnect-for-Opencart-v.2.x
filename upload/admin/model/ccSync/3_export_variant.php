<?php
include_once(DIR_SYSTEM . "/comercia/util.php");

class ModelCcSync3ExportVariant extends Model
{
    public function sync($data)
    {
        $productsChanged = [];
        $productsToResetDeactivate = [];
        $productVariantsMap = [];

        foreach ($data->productsChanged as $product) {

            $productOptionMap = array();
            $productOptions = $data->productModel->getProductOptions($product->id);
            $productsToResetDeactivate[] = $product;

            foreach ($productOptions as $productOption) {
                $productOptionMap[$productOption['option_id']] = array_map(function ($productOptionValue) use ($data) {
                    $productOptionValue['full_value'] = $data->ccProductModel->getOptionValue($productOptionValue['option_value_id'], $data->storeId);
                    return $productOptionValue;
                }, $productOption['product_option_value']);
            }

            if (count($productOptionMap) > 0) {
                foreach (cc_cartesian($productOptionMap) as $child) {
                    $childProduct = $data->ccProductModel->createChildProduct($data->session, $child, $product);
                    $productsChanged[] = $childProduct;
// TODO: keep this code a little for only short future reference when below code will be integrated with this code
//                    $productVariantsMap[$product->id][] = [
//                        'id' => $childProduct->id,
//                        'optionValueIds' => $childProduct->optionValueIds
//                    ];
                }
            }

            if (count($productsChanged) > CC_BATCH_SIZE) {
                $data->ccProductModel->deactivateChildrenFor($productsToResetDeactivate, $data->session);
                $data->ccProductModel->sendProductToApi($productsChanged, $data->session);
                $productsChanged = [];
                $productsToResetDeactivate = [];
            }
        }

        //TODO: A lot of duplicate code here to get a hold on to the productVariantsMap, though it performs well
        //      Refactor please
        foreach ($data->productMap as $product) {
            $productOptionMap = array();
            $productOptions = $data->productModel->getProductOptions($product->id);

            foreach ($productOptions as $productOption) {
                $productOptionMap[$productOption['option_id']] = array_map(function ($productOptionValue) use ($data) {
                    $productOptionValue['full_value'] = $data->ccProductModel->getOptionValue($productOptionValue['option_value_id'], $data->storeId);
                    return $productOptionValue;
                }, $productOption['product_option_value']);
            }

            if (count($productOptionMap) > 0) {
                foreach (cc_cartesian($productOptionMap) as $child) {
                    $childProduct = $data->ccProductModel->createChildProduct($data->session, $child, $product);
                    $productVariantsMap[$product->id][] = [
                        'product' => $childProduct,
                        'optionValueIds' => $childProduct->optionValueIds
                    ];
                }
            }
        }

        if (count($productsChanged)) {
            $data->ccProductModel->deactivateChildrenFor($productsToResetDeactivate, $data->session);
            $data->ccProductModel->sendProductToApi($productsChanged, $data->session);
        }
        $data->productVariantsMap = $productVariantsMap;
    }
}