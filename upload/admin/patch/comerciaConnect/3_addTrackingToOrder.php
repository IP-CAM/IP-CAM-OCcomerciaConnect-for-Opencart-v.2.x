<?php
use comercia\Util;

return function () {

    if ( ! Util::patch()->table("order")->columnExists('tracking')) {
        Util::patch()->table("order")
            ->addField("tracking", "varchar(64)")
            ->update();
    }

};