<?php

use comercia\Util;
use comerciaConnect\logic\Event;

class ModelCcSync9Cleanup extends Model
{
    public function sync($data)
    {
        $deletedItems = Util::db()->select('ccDeletedEntities', [], [
            'isCleaned' => '0'
        ]);

        if (count($deletedItems)) {

            $batch = [];
            foreach ($deletedItems as $key => $item) {
                if ($item["type"] == "productCategory") {
                    $exp = explode("_", $item["entityId"]);
                    $batch[] = new Event($data->session, EVENT_DELETE_PRODUCT_FROM_CATEGORY, ["product_id" => $exp[0], "category_id" => $exp[1]]);
                } elseif ($item["type"] == "productStore") {
                    $exp = explode("_", $item["entityId"]);
                    if ($exp[1] == $data->storeId) {
                        $batch[] = new Event($data->session, EVENT_DELETE_PRODUCT, ["id" => $exp[0]]);
                    } else {
                        unset($deletedItems[$key]);
                    }
                } elseif ($item["type"] == "categoryStore") {
                    $exp = explode("_", $item["entityId"]);
                    if ($exp[1] == $data->storeId) {
                        $batch[] = new Event($data->session, EVENT_DELETE_CATEGORY, ["id" => $exp[0]]);
                    } else {
                        unset($deletedItems[$key]);
                    }
                } elseif ($item["type"] == "product") {
                    $batch[] = new Event($data->session, EVENT_DELETE_PRODUCT, ["id" => $item["entityId"]]);
                } elseif ($item["type"] == "category") {
                    $batch[] = new Event($data->session, EVENT_DELETE_CATEGORY, ["id" => $item["entityId"]]);
                } elseif ($item["type"] == "purchase") {
                    $batch[] = new Event($data->session, EVENT_DELETE_PURCHASE, ["id" => $item["entityId"]]);
                }
            }

            Event::raiseBatch($data->session, $batch);
            foreach ($deletedItems as &$item) {
                $item['isCleaned'] = 1;
            }

            Util::db()->saveDataObjectArray('ccDeletedEntities', $deletedItems);
        }
    }
}