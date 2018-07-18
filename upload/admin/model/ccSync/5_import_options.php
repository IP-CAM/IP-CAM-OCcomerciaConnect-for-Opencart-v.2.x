<?php
use comerciaConnect\logic\Product;

class ModelCcSync5ImportOptions extends Model
{
    public function sync($data)
    {
        $filter = Product::createFilter($data->session);
        $filter->filter("lastTouchedBy", TOUCHED_BY_API, "!=");
        $filter->filter("type", PRODUCT_TYPE_PRODUCT);
        $filter->filter("parent_product_id", "empty", "!=");
        $products = $filter->getData();

        \comerciaConnect\lib\Debug::writeMemory("Received product data");

        foreach ($products as $product) {
            $data->ccProductModel->updateOptionQuantity($product,$data->storeId);
            \comerciaConnect\lib\Debug::writeMemory("Saved product ".$product->id);
        }

        $data->ccProductModel->touchBatch($data->session,$products);

        \comerciaConnect\lib\Debug::writeMemory("Sent product touched by batch");

    }
}