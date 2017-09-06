<?php
use comercia\Util;

return function () {
    Util::patch()->table("product")
        ->addField("ccCreatedBy", "int")
        ->update();

    Util::patch()->table("order")
        ->addField("ccCreatedBy", "int")
        ->update();


};