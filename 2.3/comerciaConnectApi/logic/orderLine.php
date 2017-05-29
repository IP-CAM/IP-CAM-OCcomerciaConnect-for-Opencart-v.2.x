<?php
namespace comerciaConnect\logic;
class OrderLine
{
    var $product;
    var $price;
    var $quantity;
    var $tax;
    var $priceWithTax;
    var $taxGroup;

    function __construct($session, $data)
    {
        foreach($data as $key => $value){
            $this->{$key} = $value;
        }

        $this->product = new Product($session, $data["product"]);
    }

}