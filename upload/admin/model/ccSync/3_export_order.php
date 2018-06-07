<?php

class ModelCcSync3ExportOrder extends Model
{
    public function sync($data)
    {
        $orders = $data->ccOrderModel->getOrders($data->storeId,$data->syncMethod);
        $ordersChanged = array();
        $toSaveHash = [];

        \comerciaConnect\lib\Debug::writeMemory("Loaded orders");
        foreach ($orders as $order) {
            \comerciaConnect\lib\Debug::writeMemory("Start prepare order ". $order["order_id"]);
            if ($order['ccHash'] != $data->ccOrderModel->getHashForOrder($order)) {
                $ordersChanged[] = $data->ccOrderModel->createApiOrder($order, $data->session, $data->productMap,$data->storeId);
                $toSaveHash[] = $order;
            }
            \comerciaConnect\lib\Debug::writeMemory("Start prepare order ". $order["order_id"]);
            if (count($ordersChanged) > CC_BATCH_SIZE) {
                if ($data->ccOrderModel->sendOrderToApi($ordersChanged, $data->session)) {
                    foreach ($toSaveHash as $toSaveHashOrder) {
                        $data->ccOrderModel->saveHashForOrder($toSaveHashOrder);
                    }
                }
                $ordersChanged = [];
                $toSaveHash = [];
                \comerciaConnect\lib\Debug::writeMemory("Saved batch of orders");
            }
        }
        if (count($ordersChanged)) {
            if ($data->ccOrderModel->sendOrderToApi($ordersChanged, $data->session)) {
                foreach ($toSaveHash as $toSaveHashOrder) {
                    $data->ccOrderModel->saveHashForOrder($toSaveHashOrder);
                }
            }
            \comerciaConnect\lib\Debug::writeMemory("Saved batch of orders");
        }
    }
}