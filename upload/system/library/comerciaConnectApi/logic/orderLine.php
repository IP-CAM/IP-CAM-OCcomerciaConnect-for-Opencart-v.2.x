<?php
namespace comerciaConnect\logic;
use ForceUTF8\Encoding;

/**
 * This class represents an orderline. It will only be used as part of an order. Orderlines are not separately usable.
 * @author Mark Smit <m.smit@comercia.nl>
 */
class OrderLine
{
    /** @var Product | Should contain an product which is already saved to Comercia Connect */
    var $product;
    /** @var decimal | Should contain the price excluding tax */
    var $price;
    /** @var integer */
    var $quantity;
    /** @var decimal | Should contain  the amount of tax as value */
    var $tax;

    /** @var string | should contain price + tax */
    var $priceWithTax;

    /** @var string |  should contain the same taxgroup of the product */
    var $taxGroup;

    /*
     * @param array $data The data to initialize the address with
     * @param Session $session The session object to connect with Comercia Connect
     */
    function __construct($session, $data)
    {
        foreach ($data as $key => $value) {
            if(is_string($value)) {
                $this->{$key} = Encoding::fixUTF8($value);
            }else{
                $this->{$key} = $value;
            }
        }

        $this->product = new Product($session, $data["product"]);
    }

}