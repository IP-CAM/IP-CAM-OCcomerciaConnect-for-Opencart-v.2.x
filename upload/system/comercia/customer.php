<?php

namespace comercia;

//todo: implementation for functions in this class differ per oc version. implement differences in future.
class Customer
{
    function getId()
    {
        return Util::registry()->get("customer")->getId();
    }

    function getGroupId()
    {
        return Util::registry()->get("customer")->getGroupId();
    }

    function getEmail()
    {
    	return Util::registry()->get("customer")->getEmail();
    }

    function getFirstName()
    {
        return Util::registry()->get("customer")->getFirstName();
    }

    function getLastName()
    {
        return Util::registry()->get("customer")->getLastName();
    }

}
