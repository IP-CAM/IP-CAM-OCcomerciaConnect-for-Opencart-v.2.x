<?php
use comercia\Util;
use comerciaConnect\logic\Product;

class ModelCcSync7Cleanup extends Model
{
    public function sync($data)
    {
        $deletedProducts = Util::db()->select('ccDeletedEntities', [], [
            'type' => 'product',
            'isCleaned' => '0'
        ]);

        if (count($deletedProducts)) {
            foreach ($deletedProducts as $product) {
                $batch[] = $product['entityId'];
            }

            Product::deactivateBatch($data->session, $batch);

            foreach ($deletedProducts as &$product) {
                $product['isCleaned'] = 1;
            }

            Util::db()->saveDataObjectArray('ccDeletedEntities', $deletedProducts);
        }
    }
}