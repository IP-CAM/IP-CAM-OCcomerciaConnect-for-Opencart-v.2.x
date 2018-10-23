<?php

use comerciaConnect\logic\Purchase;

class ModelCcSync7ImportOrder extends Model
{
    public function sync($data)
    {
        $filter = Purchase::createFilter($data->session);
        $filter->filter("lastTouchedBy", TOUCHED_BY_API, "!=");
        $filter->filter("limit", CC_BATCH_SIZE);

        $lastResultHash = false;

        while ($orders = $filter->getData()) {

            if (empty($orders)) {
                break;
            }

            //break if same data is fetched, Most probably something went wrong
            $resultHash = md5(print_r($orders));
            if ($resultHash == $lastResultHash) {
                \comerciaConnect\lib\Debug::writeMemory("Error: Same result hash and last result hash");
                break;
            }


            \comerciaConnect\lib\Debug::writeMemory("Received order data");

            foreach ($orders as $order) {
                $data->ccOrderModel->saveOrder($order, $data->storeId);
                \comerciaConnect\lib\Debug::writeMemory("Saved order " . $order->id);
            }
            $data->ccOrderModel->touchBatch($data->session, $orders);
            \comerciaConnect\lib\Debug::writeMemory("Sent product touched by batch");

            $lastResultHash = $resultHash;
        }
    }
}