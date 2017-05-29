<?php
use comerciaConnect\Api;
use comerciaConnect\logic\OrderLine;
use comerciaConnect\logic\Product;
use comerciaConnect\logic\Purchase;


include_once("config.php");
include_once("../api.php");

class Example
{
    function work()
    {
        $api = new Api(API_AUTH_URL, API_URL);
        $session = $api->createSession(API_KEY);
        $product = Product::getById($session,2);

        $orderLine = new OrderLine(array(
            "product" => $product,
            "price" => 100,
            "quantity" => 1,
            "tax" => 21,
            "priceWithTax"=>121,
            "taxGroup"=>"21%"
        ));

        $payment = new OrderLine(array(
            "product" => Product::getById($session,4),
            "price" => 10,
            "quantity" => 1,
            "priceWithTax"=>12.1,
            "tax" => 2.1,
            "taxGroup"=>"21%"
        ));

        $shipping = new OrderLine(array(
            "product" => Product::getById($session,5),
            "price" => 10,
            "quantity" => 1,
            "priceWithTax"=>12.1,
            "tax" => 2.1,
            "taxGroup"=>"21%"
        ));


        $purchase = new Purchase(
            $session,
            array(
                "id" => 1,
                "date" => time(),
                "status" => "processing",
                "email"=>"info@comercia.nl",
                "phonenumber"=>"0123456789",
                "deliveryAddress" => array(
                    "firstName" => "Mark",
                    "lastName" => "Smit",
                    "street" => "hoofdstraat",
                    "number" => "5",
                    "postalCode" => "7899ab",
                    "city" => "grollo",
                    "province" => "drenthe",
                    "country" => "Netherlands"
                ),
                "invoiceAddress" => array(
                    "firstName" => "Mark",
                    "lastName" => "Smit",
                    "street" => "hoofdstraat",
                    "number" => "5",
                    "postalCode" => "7899ab",
                    "city" => "grollo",
                    "province" => "drenthe",
                    "country" => "Netherlands"
                ),
                "orderLines"=>array($orderLine,$payment,$shipping)

            )
        );
        $purchase->save();
    }
}

(new Example())->work();


?>