<?php
namespace comerciaConnect\logic;
class PurchaseFilter
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
        if ($this->session) {
            $data = $this->session->post("purchase/getByFilter", $this);
            $result = [];

            if(isset($data['data'])) {
                foreach ($data["data"] as $purchase) {
                    $result[] = new Purchase($this->session, $purchase);
                }
            }

            return $result;
        }

        return false;
    }
}

?>