<?php
use comerciaConnect\Api;
use comerciaConnect\logic\Purchase;


include_once("config.php");
include_once("../api.php");

class Example
{
    function work()
    {
        $api = new Api(API_AUTH_URL, API_URL);
        $session = $api->createSession(API_KEY);
        $purchase=Purchase::getById($session,1);
        $purchase->delete();
    }
}

(new Example())->work();


?>