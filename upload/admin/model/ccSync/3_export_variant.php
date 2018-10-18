<?php
include_once(DIR_SYSTEM . "/comercia/util.php");

class ModelCcSync3ExportVariant extends Model
{
    public function sync($data)
    {
        $productsChanged = [];
        $productsToResetDeactivate = [];
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
                    $productsChanged[] = $data->ccProductModel->createChildProduct($data->session, $child, $product);
                }
            }


            if (count($productsChanged) > CC_BATCH_SIZE) {
                $data->ccProductModel->deactivateChildrenFor($productsToResetDeactivate, $data->session);
                $data->ccProductModel->sendProductToApi($productsChanged, $data->session);
                $productsChanged = [];
                $productsToResetDeactivate = [];
            }
        }
        if (count($productsChanged)) {
            $data->ccProductModel->deactivateChildrenFor($productsToResetDeactivate, $data->session);
            $data->ccProductModel->sendProductToApi($productsChanged, $data->session);
        }

    }


}