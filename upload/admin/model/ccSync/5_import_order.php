<?php
use comerciaConnect\logic\Purchase;

class ModelCcSync5ImportOrder extends Model
{
    public function sync($data)
    {
        $filter = Purchase::createFilter($data->session);
        $filter->filter("lastTouchedBy", TOUCHED_BY_API, "!=");
        $orders = $filter->getData();

        foreach ($orders as $order) {
            $data->ccOrderModel->saveOrder($order);
        }
        $data->ccOrderModel->touchBatch($data->session,$orders);
    }
}