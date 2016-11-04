<?php
namespace comerciaConnect\logic;
class ProductCategory
{
    var $id;
    var $name;

    private $session;

    function __construct($session, $data = array())
    {
        $this->session = $session;

        foreach ($data as $key => $value) {
            $this->{$key} = $value;
        }

    }

    function save()
    {
        $this->session->post("productCategory/save", $this);
    }


    static function getById($session, $id)
    {
        $data = $session->get("productCategory/getById/" . $id);
        return new ProductCategory($session, $data["data"]);
    }

    static function getAll($session)
    {
        $data = $session->get("productCategory/getAll");
        $result = array();
        foreach ($data["data"] as $product) {
            $result[] = new ProductCategory($product);
        }
        return $result;
    }

}