<?php

use comercia\Util;

class ModelModuleComerciaconnectGeneral extends Model
{
    function getLanguageIdForStore($storeId){

            $languageCode=Util::config($storeId)->config_language;
            if($languageCode){
                $query= Util::db()->query("select * from ".DB_PREFIX."language where code= '".$languageCode."'");
            }else{
                $query= Util::db()->query("select * from ".DB_PREFIX."language limit 0,1");
            }

            return $query[0]["language_id"];
    }

    function getSyncModels(){
        $syncModels=[];
        $dir = DIR_APPLICATION . 'model/ccSync';
        if ($handle = opendir($dir)) {
            while (false !== ($entry = readdir($handle))) {
                if ($entry != '.' && $entry != '..' && !is_dir($dir . '/' . $entry) && substr($entry, -3) === 'php') {
                    $syncModels[] = substr($entry, 0, -4);
                }
            }

            sort($syncModels);
            closedir($handle);
        }
        return $syncModels;
    }

}

?>