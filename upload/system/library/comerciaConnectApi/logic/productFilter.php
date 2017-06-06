<?php
namespace comerciaConnect\logic;
class ProductFilter
{
    private $session;
    var $filters = [];

    function __construct($session)
    {
        $this->session = $session;
    }

    function filter($field, $value, $operator = "=")
    {
        $this->filters[] = ["field" => $field, "operator" => $operator, "value" => $value];

        return $this;
    }

    function getData()
    {
        $data = $this->session->post("product/getByFilter", $this);
        $result = [];
        if(isset($data['data'])) {
            foreach ($data["data"] as $product) {
                $result[] = new Product($this->session, $product);
            }
        }

        return $result;
    }
}
?>