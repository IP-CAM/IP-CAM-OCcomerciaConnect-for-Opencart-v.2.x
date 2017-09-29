<?php
namespace comerciaConnect\logic;
/**
 * This class is used to do a filtered purchase request
 * @author Mark Smit <m.smit@comercia.nl>

 */
class PurchaseFilter
{
    private $session;
    /** @var Filter[] */
    var $filters = [];
    /** @param Session $session The session object to connect with Comercia Connect */
    function __construct($session)
    {
        $this->session = $session;
    }

    /**
     * Adds condition to filter
     * @param string $field
     * @param string $value
     * @param string("=","!=","<",">",">=","<=") $operator
     * @return PurchaseFilter Itself
     */
    function filter($field, $value, $operator = "=")
    {
        $this->filters[] = ["field" => $field, "operator" => $operator, "value" => $value];

        return $this;
    }

    /**
     * Gets filtered order results from Comercia Connect
     * @return Order[]
     */
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