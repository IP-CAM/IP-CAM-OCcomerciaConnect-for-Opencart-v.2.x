<?php
namespace comerciaConnect\logic;
use ForceUTF8\Encoding;

/**
 * This class represents an address. It will only be used as part of an order. addresses are not separately usable.
 * @author Mark Smit <m.smit@comercia.nl>
 */
class Address
{
    /** @var string */
    var $firstName;
    /** @var string */
    var $lastName;
    /** @var string */
    var $street;
    /** @var string */
    var $number;
    /** @var string */
    var $suffix;
    /** @var string */
    var $postalCode;
    /** @var string */
    var $city;
    /** @var string */
    var $province;
    /** @var string */
    var $country;

    /** @var string */
    var $company;

    /** @var string */
    var $salutationCode;


    /** @param array $data The data to initialize the address with */
    function __construct($data)
    {
        foreach ($data as $key => $value) {
            if(is_string($value)) {
                $this->{$key} = Encoding::fixUTF8($value);
            }else{
                $this->{$key} = $value;
            }
        }
    }
}