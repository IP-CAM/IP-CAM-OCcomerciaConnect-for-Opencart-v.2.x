<?php

include_once(DIR_SYSTEM . "/comercia/util.php");
use comercia\Util;

class ModelCcSync1ExportCategory extends Model
{
    public function sync($data)
    {
        $categories = $data->categoryModel->getCategories(array());
        $categoriesMap = array();
        $toSaveHash=array();
        $categoriesChanged = array();

        foreach ($categories as $category) {
            if (Util::version()->isMaximal("1.5.2.1")) {
                $category = $data->ccProductModel->getCategory($category['category_id']);
            } else {
                $category = $data->categoryModel->getCategory($category['category_id']);
            }
            $apiCategory = $data->ccProductModel->createApiCategory($category, $data->session);
            if ($category["ccHash"]!=$data->ccProductModel->getHashForCategory($category)) {
                $categoriesChanged[]=$apiCategory;
                $toSaveHash[]=$category;
            }
            $categoriesMap[$category["category_id"]] = $apiCategory;

            if (count($categoriesChanged) > 100) {
                if( $data->ccProductModel->sendCategoryToApi($categoriesChanged, $data->session)){
                    foreach($toSaveHash as $toSaveHashCategory){
                        $data->ccProductModel->saveHashForCategory($toSaveHashCategory);
                    }
                }
                $toSaveHash = [];
                $categoriesChanged = [];
            }
        }

        if (count($categoriesChanged)) {
            if( $data->ccProductModel->sendCategoryToApi($categoriesChanged,$data->session)){
                foreach($toSaveHash as $toSaveHashCategory){
                    $data->ccProductModel->saveHashForCategory($toSaveHashCategory);
                }
            }
        }

        $data->ccProductModel->updateCategoryStructure($data->session, $categories);
        $data->categoriesMap = $categoriesMap;
    }
}