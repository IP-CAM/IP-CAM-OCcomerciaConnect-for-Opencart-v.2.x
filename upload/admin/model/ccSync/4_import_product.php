<?php
use comerciaConnect\logic\Product;

class ModelCcSync4ImportProduct extends Model
{
    public function sync($data)
    {
        $filter = Product::createFilter($data->session);
        $filter->filter("lastTouchedBy", TOUCHED_BY_API, "!=");
        $filter->filter("type", PRODUCT_TYPE_PRODUCT);
        $filter->filter("parent_product_id", "0", "=");
        $products = $filter->getData();

        foreach ($products as $product) {
            $data->ccProductModel->saveProduct($product);
        }

        $data->ccProductModel->touchBatch($data->session,$products);
    }
}