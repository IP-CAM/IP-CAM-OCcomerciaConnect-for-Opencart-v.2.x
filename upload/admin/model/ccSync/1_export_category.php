<?php

include_once(DIR_SYSTEM . "/comercia/util.php");

class ModelCcSync1ExportCategory extends Model
{
    public function sync($data)
    {
        $categories = $data->ccProductModel->getCategories($data->storeId,$data->syncMethod);

        $categoriesMap = array();
        $toSaveHash = array();
        $categoriesChanged = array();
        $triggerStructure = false;

        \comerciaConnect\lib\Debug::writeMemory("Loaded categories");
        foreach ($categories as $category) {
            \comerciaConnect\lib\Debug::writeMemory("Start prepare category " . $category["category_id"]);
            $category = $data->categoryModel->getCategory($category['category_id']);

            $apiCategory = $data->ccProductModel->createApiCategory($category, $data->session);
            if ($category["ccHash"] != $data->ccProductModel->getHashForCategory($category,$data->storeId)) {
                $categoriesChanged[] = $apiCategory;
                $toSaveHash[] = $category;
            }
            $categoriesMap[$category["category_id"]] = $apiCategory;


            \comerciaConnect\lib\Debug::writeMemory("Prepared category " . $category["category_id"]);

            if (count($categoriesChanged) > CC_BATCH_SIZE) {
                if ($data->ccProductModel->sendCategoryToApi($categoriesChanged, $data->session)) {
                    foreach ($toSaveHash as $toSaveHashCategory) {
                        $data->ccProductModel->saveHashForCategory($toSaveHashCategory,$data->storeId);
                    }
                    $triggerStructure = true;
                }
                $toSaveHash = [];
                $categoriesChanged = [];

                \comerciaConnect\lib\Debug::writeMemory("Saved batch of categories");
            }
        }

        if (count($categoriesChanged)) {
            if ($data->ccProductModel->sendCategoryToApi($categoriesChanged, $data->session)) {
                foreach ($toSaveHash as $toSaveHashCategory) {
                    $data->ccProductModel->saveHashForCategory($toSaveHashCategory,$data->storeId);
                }
                $triggerStructure = true;
            }
            \comerciaConnect\lib\Debug::writeMemory("Saved batch of categories");
        }

        if ($triggerStructure) {
            $data->ccProductModel->updateCategoryStructure($data->session, $categories);
            \comerciaConnect\lib\Debug::writeMemory("Built category structure");
        }

        $data->categoriesMap = $categoriesMap;
    }

    function resultOnly($data)
    {
        $categories = $data->ccProductModel->getCategories($data->storeId,$data->syncMethod);
        $categoriesMap = array();
        foreach ($categories as $category) {
            $categoriesMap[$category["category_id"]] = $data->ccProductModel->createApiCategory($category, $data->session);
        }
        $data->categoriesMap = $categoriesMap;
    }
}