<?php
namespace comerciaConnect\logic;
class Address
{

    var $firstName;
    var $lastName;
    var $street;
    var $number;
    var $suffix;
    var $postalCode;
    var $city;
    var $province;
    var $country;

    function __construct($data)
    {
        foreach($data as $key => $value){
            $this->{$key} = $value;
        }
    }



}