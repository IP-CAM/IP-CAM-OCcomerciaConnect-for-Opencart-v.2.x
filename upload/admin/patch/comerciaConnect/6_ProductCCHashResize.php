<?php
use comercia\Util;

return function () {
    Util::patch()->table("product")
        ->editField("ccHash", "varchar(255)")
        ->update();

    Util::patch()->table("order")
        ->editField("ccHash", "varchar(255)")
        ->update();

    Util::patch()->table("category")
        ->editField("ccHash", "varchar(255)")
        ->update();
};