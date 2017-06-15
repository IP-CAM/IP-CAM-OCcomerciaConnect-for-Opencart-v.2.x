<?php
use comerciaConnect\Api;
use comerciaConnect\logic\Product;
use comerciaConnect\logic\ProductCategory;

include_once("config.php");
include_once("../api.php");

class Example
{
    function work()
    {
//setup session
        $api = new Api(API_AUTH_URL, API_URL);
        $session = $api->createSession(API_KEY);

        print_r(Product::getAll($session));
        print_r(Product::getById($session, 2));

        print_r(ProductCategory::getAll($session));
        print_r(ProductCategory::getById($session, 1));

    }
}

(new Example())->work();


?>