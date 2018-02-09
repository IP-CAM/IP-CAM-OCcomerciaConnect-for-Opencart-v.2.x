<?php
use comerciaConnect\Api;
use comerciaConnect\logic\Purchase;
use comerciaConnect\logic\PurchaseFilter;

include_once("config.php");
include_once("../api.php");

class Example
{
    function work()
    {
//setup session
        $api = new Api(API_AUTH_URL, API_URL);
        $session = $api->createSession(API_KEY);
        $filter= Purchase::createFilter($session);
        $filter->filter("lastTouchedBy",TOUCHED_BY_API)
            ->filter("id",1);
        print_r($filter->getData());
    }
}

(new Example())->work();


?>