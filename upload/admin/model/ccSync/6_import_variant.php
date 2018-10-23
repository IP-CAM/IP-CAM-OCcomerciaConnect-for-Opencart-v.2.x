<?php

use comerciaConnect\logic\Product;

class ModelCcSync6ImportVariant extends Model
{
    public function sync($data)
    {
        $filter = Product::createFilter($data->session);
        $filter->filter("lastTouchedBy", TOUCHED_BY_API, "!=");
        $filter->filter("type", PRODUCT_TYPE_PRODUCT);
        $filter->filter("parent_product_id", "empty", "!=");
        $filter->filter("limit", CC_BATCH_SIZE);

        $lastResultHash = false;
        while ($products = $filter->getData()) {

            if (empty($products)) {
                break;
            }

            //break if same data is fetched, Most probably something went wrong
            $resultHash = md5(print_r($products,true));
            if ($resultHash == $lastResultHash) {
                \comerciaConnect\lib\Debug::writeMemory("Error: Same result hash and last result hash");
                break;
            }

            \comerciaConnect\lib\Debug::writeMemory("Received product data");

            foreach ($products as $product) {
                $data->ccProductModel->updateOptionQuantity($product, $data->storeId);
                \comerciaConnect\lib\Debug::writeMemory("Saved product " . $product->id);
            }

            $data->ccProductModel->touchBatch($data->session, $products);

            \comerciaConnect\lib\Debug::writeMemory("Sent product touched by batch");
            $lastResultHash = $resultHash;
        }

    }
}