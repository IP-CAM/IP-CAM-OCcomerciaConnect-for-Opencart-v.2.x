<?php
namespace comerciaConnect\logic;
/**
 * This class is used to do a filtered product request
 * @author Mark Smit <m.smit@comercia.nl>
 */
class ProductFilter
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
     * @return ProductFilter Itself
     */
    function filter($field, $value, $operator = "=")
    {
        $this->filters[] = ["field" => $field, "operator" => $operator, "value" => $value];

        return $this;
    }

    /**
     * Gets filtered product results from Comercia Connect
     * @return Product[]
     */
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