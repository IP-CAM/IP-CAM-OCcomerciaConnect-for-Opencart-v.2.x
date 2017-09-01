<?php

class ModelCcSync3ExportOrder extends Model
{
    public function sync($data)
    {
        $orders = $data->ccOrderModel->getOrders();
        $ordersChanged=array();
        foreach ($orders as $order) {
            if ($order['ccHash']!=$data->ccOrderModel->getHashForOrder($order)) {
                $ordersChanged[] = $data->ccOrderModel->createApiOrder($order, $data->session, $data->productMap);
                $data->ccOrderModel->saveHashForOrder($order);
            }
            if (count($ordersChanged)>100) {
                $data->ccOrderModel->sendOrderToApi($ordersChanged,$data->session);
                $ordersChanged=[];
            }
        }
        if(count($ordersChanged)){
            $data->ccOrderModel->sendOrderToApi($ordersChanged,$data->session);
        }
    }
}