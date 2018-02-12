<?php
use comerciaConnect\Api;
use comerciaConnect\logic\Product;
use comerciaConnect\logic\ProductCategory;
use comerciaConnect\logic\ProductDescription;

include_once("config.php");
include_once("../api.php");

class Example
{
    function work()
    {
//setup session
        $api = new Api(API_AUTH_URL, API_URL);
        $session = $api->createSession(API_KEY);

        $category1=new ProductCategory($session);
        $category1->name="Category 1";
        $category1->id=1;
        $category1->save();


        $category2=new ProductCategory($session);
        $category2->name="Category 2";
        $category2->id=2;
        $category2->save();

//add/update a product
        $product1 = new Product($session);
        $product1->id = 1;
        $product1->name = "Product 1";
        $product1->quantity = 100;
        $product1->price = 10.50;
        $product1->url = "http://producturl.nl";
        $product1->ean="3391891985772";
        $product1->isbn="isb1234";
        $product1->sku="123";
        $product1->type=PRODUCT_TYPE_PRODUCT;
        $product1->taxGroup="21%";


        $product1->descriptions = array(
            new ProductDescription("en-gb", "Product 1", "and a description"),
            new ProductDescription("nl-nl", "Product 1", "en een omschrijving")
        );
        $product1->categories=array($category1);
        $product1->save();

        //add/update a product
        $product2 = new Product($session);
        $product2->id = 2;
        $product2->name = "Product 2";
        $product2->quantity = 100;
        $product2->price = 10.50;
        $product2->url = "http://producturl.nl";
        $product2->ean="5055856411154";
        $product2->isbn="isb1234";
        $product2->sku="123";
        $product2->type=PRODUCT_TYPE_PRODUCT;
        $product2->taxGroup="21%";

        $product2->descriptions = array(
            new ProductDescription("en-gb", "Product 2", "and a description"),
            new ProductDescription("nl-nl", "Product 2", "en een omschrijving")
        );
        $product2->categories=array($category2);
        $product2->save();

        //add/update a product
        $product3 = new Product($session);
        $product3->id = 3;
        $product3->name = "Product 3";
        $product3->quantity = 100;
        $product3->price = 10.50;
        $product3->url = "http://producturl.nl";
        $product3->ean="3512899116573";
        $product3->isbn="isbn123";
        $product3->sku="123";
        $product3->type=PRODUCT_TYPE_PRODUCT;
        $product3->taxGroup="21%";

        $product3->descriptions = array(
            new ProductDescription("en-gb", "Product 3", "and a description"),
            new ProductDescription("nl-nl", "Product 3", "en een omschrijving")
        );
        $product3->categories=array($category1,$category2);
        $product3->save();


        //add/update a product
        $paymentMethod = new Product($session);
        $paymentMethod->id = 4;
        $paymentMethod->name = "invoice";
        $paymentMethod->price = 10.50;
        $paymentMethod->url = "http://producturl.nl";
        $paymentMethod->type=PRODUCT_TYPE_PAYMENT;
        $paymentMethod->taxGroup="21%";
	$paymentMethod->code="code";
        $paymentMethod->save();


        //add/update a product
        $shippingMethod = new Product($session);
        $shippingMethod->id = 5;
        $shippingMethod->name = "postnl";
        $shippingMethod->price = 10.50;
        $shippingMethod->url = "http://www.postnl.nl";
        $shippingMethod->type=PRODUCT_TYPE_SHIPPING;
        $shippingMethod->taxGroup="21%";
	$shippingMethod->code="code";
        $shippingMethod->save();

    }
}

(new Example())->work();


?>
