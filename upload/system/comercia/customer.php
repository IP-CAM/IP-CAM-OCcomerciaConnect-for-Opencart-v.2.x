<?php
namespace comercia;

//todo: implementation fo functions in this class differ per oc version. implement diffirences in future.
class Customer
{
    function getId()
    {
        return Util::registry()->get("customer")->getId();
    }

}
