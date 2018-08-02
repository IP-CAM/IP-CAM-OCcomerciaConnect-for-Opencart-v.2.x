<?php
use comercia\Util;

return function () {
    $syncModelFields = array_map(function ($syncModel) {
        return "comerciaConnect_sync_" . $syncModel;
    }, Util::load()->model("module/comerciaconnect/general")->getSyncModels());

    foreach($syncModelFields as $field){
        Util::config()->set("comerciaConnect",$field,true);
    }

};