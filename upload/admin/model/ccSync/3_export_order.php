<?php

class ModelCcSync3ExportOrder extends Model
{
    public function sync($data)
    {
        $orders = $data->ccOrderModel->getOrders();
        $ordersChanged = array();
        $toSaveHash = [];

        foreach ($orders as $order) {
            if ($order['ccHash'] != $data->ccOrderModel->getHashForOrder($order)) {
                $ordersChanged[] = $data->ccOrderModel->createApiOrder($order, $data->session, $data->productMap);
                $toSaveHash[] = $order;
            }
            if (count($ordersChanged) > CC_BATCH_SIZE) {
                if ($data->ccOrderModel->sendOrderToApi($ordersChanged, $data->session)) {
                    foreach ($toSaveHash as $toSaveHashOrder) {
                        $data->ccOrderModel->saveHashForOrder($toSaveHashOrder);
                    }
                }
                $ordersChanged = [];
                $toSaveHash = [];
            }
        }
        if (count($ordersChanged)) {
            if ($data->ccOrderModel->sendOrderToApi($ordersChanged, $data->session)) {
                foreach ($toSaveHash as $toSaveHashOrder) {
                    $data->ccOrderModel->saveHashForOrder($toSaveHashOrder);
                }
            }
        }
    }
}