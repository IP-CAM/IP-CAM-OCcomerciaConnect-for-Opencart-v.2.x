<?php
use comercia\Util;
use comerciaConnect\logic\Product;
use comerciaConnect\logic\Website;

class ModelCcSync7Cleanup extends Model
{
    public function sync($data)
    {
        $deletedProducts = Util::db()->select('ccDeletedEntities', [], [
            'table' => 'product',
            'isCleaned' => '0'
        ]);

        foreach ($deletedProducts as $product)
        {
            $batch[] = $product['entityId'];
        }

        Product::deactivateBatch($data->session, $batch);

        foreach ($deletedProducts as &$product)
        {
            $product['isCleaned'] = 1;
        }

        Util::db()->saveDataObjectArray('ccDeletedEntities', $deletedProducts);
    }
}