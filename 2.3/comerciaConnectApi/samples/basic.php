<?php
use comerciaConnect\Api;
use comerciaConnect\logic\Product;
use comerciaConnect\logic\ProductCategory;
use comerciaConnect\logic\ProductDescription;
use comerciaConnect\logic\Website;

include_once("config.php");
include_once("../api.php");

class example
{
    function work()
    {
//setup session
        $api = new Api(API_AUTH_URL, API_URL);
        $session = $api->createSession(API_KEY);


//get website information
        $website = Website::getWebsite($session, "mysite");


        $category1=new ProductCategory($session);
        $category1->name="hoi";
        $category1->id=1;
        $category1->save();


        $category2=new ProductCategory($session);
        $category2->name="doei";
        $category2->id=2;
        $category2->save();

//add/update a product
        $product1 = new Product($session);
        $product1->id = 1;
        $product1->name = "Sword art online";
        $product1->quantity = 100;
        $product1->price = 10.50;
        $product1->url = "http://producturl.nl";
        $product1->ean="3391891985772";
        $product1->isbn="isb1234";
        $product1->sku="123";


        $product1->descriptions = array(
            new ProductDescription("en-gb", "lol", "and a description"),
            new ProductDescription("nl-nl", "lol", "en een description")
        );
        $product1->categories=array($category1);
        $product1->save();

        //add/update a product
        $product2 = new Product($session);
        $product2->id = 2;
        $product2->name = "productje";
        $product2->quantity = 100;
        $product2->price = 10.50;
        $product2->url = "http://producturl.nl";
        $product2->ean="5055856411154";
        $product2->isbn="isb1234";
        $product2->sku="123";

        $product2->descriptions = array(
            new ProductDescription("en-gb", "lol", "and a description"),
            new ProductDescription("nl-nl", "lol", "en een description")
        );
        $product2->categories=array($category2);
        $product2->save();

        //add/update a product
        $product3 = new Product($session);
        $product3->id = 3;
        $product3->name = "en nog 1";
        $product3->quantity = 100;
        $product3->price = 10.50;
        $product3->url = "http://producturl.nl";
        $product3->ean="3512899116573";
        $product3->isbn="isbn123";
        $product3->sku="123";

        $product3->descriptions = array(
            new ProductDescription("en-gb", "lol", "and a description"),
            new ProductDescription("nl-nl", "lol", "en een description")
        );
        $product3->categories=array($category1,$category2);
        $product3->save();

    }
}

(new example())->work();


?>