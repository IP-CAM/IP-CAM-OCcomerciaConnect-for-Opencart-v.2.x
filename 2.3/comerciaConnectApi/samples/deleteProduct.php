<?php
use comerciaConnect\Api;
use comerciaConnect\logic\Product;

include_once("config.php");
include_once("../api.php");

class Example
{
    function work()
    {
        //setup session
        $api = new Api(API_AUTH_URL, API_URL);
        $session = $api->createSession(API_KEY);

        $product=Product::getById($session,1); //create an instance with this a specific id works too.
        $product->delete();
    }
}

(new Example())->work();


?>