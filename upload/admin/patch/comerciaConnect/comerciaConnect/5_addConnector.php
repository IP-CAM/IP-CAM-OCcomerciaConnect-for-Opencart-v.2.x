<?php
use comercia\Util;

return function () {
    Util::patch()->table("product")
        ->addField("ccConnector", "varchar(50)")
        ->update();

    Util::patch()->table("order")
        ->addField("ccConnector", "varchar(50)")
        ->update();

};