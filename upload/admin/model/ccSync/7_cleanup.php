<?php
use comercia\Util;
use comerciaConnect\logic\Event;
use comerciaConnect\logic\Product;

class ModelCcSync7Cleanup extends Model
{
    public function sync($data)
    {
        $deletedItems = Util::db()->select('ccDeletedEntities', [], [
            'isCleaned' => '0'
        ]);

        if (count($deletedItems)) {

            $batch=[];
            foreach ($deletedItems as $item) {
                if($item["type"]=="product_to_category"){
                    $exp=explode("_",$item["entityId"]);
                    $batch[]=new Event($data->session,EVENT_DELETE_PRODUCT_FROM_CATEGORY,["product_id"=>$exp[0],"category_id"=>$exp[1]]);
                }elseif($item["type"]=="product"){
                    $batch[]=new Event($data->session,EVENT_DELETE_PRODUCT,["id"=>$exp[0]]);
                }elseif($item["type"]=="category"){
                    $batch[]=new Event($data->session,EVENT_DELETE_CATEGORY,["id"=>$exp[0]]);
                }elseif($item["type"]=="purchase"){
                    $batch[]=new Event($data->session,EVENT_DELETE_PURCHASE,["id"=>$exp[0]]);
                }
            }

           Event::raiseBatch($data->session,$batch);
            foreach ($deletedItems as &$item) {
                $item['isCleaned'] = 1;
            }

            Util::db()->saveDataObjectArray('ccDeletedEntities', $deletedItems);
        }
    }
}