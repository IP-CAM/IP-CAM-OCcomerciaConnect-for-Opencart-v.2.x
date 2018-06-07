<?php
use comerciaConnect\logic\Purchase;

class ModelCcSync5ImportOrder extends Model
{
    public function sync($data)
    {
        $filter = Purchase::createFilter($data->session);
        $filter->filter("lastTouchedBy", TOUCHED_BY_API, "!=");
        $orders = $filter->getData();

        \comerciaConnect\lib\Debug::writeMemory("Received order data");

        foreach ($orders as $order) {
            $data->ccOrderModel->saveOrder($order,$data->storeId);
            \comerciaConnect\lib\Debug::writeMemory("Saved order ".$order->id);
        }
        $data->ccOrderModel->touchBatch($data->session,$orders);
        \comerciaConnect\lib\Debug::writeMemory("Sent product touched by batch");
    }
}