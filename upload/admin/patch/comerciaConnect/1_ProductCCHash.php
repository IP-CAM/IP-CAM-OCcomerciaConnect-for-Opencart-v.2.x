<?php
use comercia\Util;

return function () {
    Util::patch()->table("product")
        ->addField("ccHash", "varchar(50)")
        ->update();

    Util::patch()->table("order")
        ->addField("ccHash", "varchar(50)")
        ->update();

    Util::patch()->table("category")
        ->addField("ccHash", "varchar(50)")
        ->update();
};