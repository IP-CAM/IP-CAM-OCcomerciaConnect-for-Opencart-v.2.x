<?php

include_once(DIR_SYSTEM . "/comercia/util.php");
use comercia\Util;

class ModelCcSync1ExportCategory extends Model
{
    public function sync($data)
    {
        $categories = $data->categoryModel->getCategories(array());
        $categoriesMap = array();
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
                $data->ccProductModel->saveHashForCategory($category);
            }
            $categoriesMap[$category["category_id"]] = $apiCategory;
        }

        if (count($categoriesChanged)) {
            $data->ccProductModel->sendCategoryToApi($categoriesChanged,$data->session);
            $data->ccProductModel->updateCategoryStructure($data->session, $categories);
        }

        $data->categoriesMap = $categoriesMap;
    }
}