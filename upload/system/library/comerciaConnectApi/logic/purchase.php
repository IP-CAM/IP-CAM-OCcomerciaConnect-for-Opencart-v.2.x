<?php
namespace comerciaConnect\logic;
use ForceUTF8\Encoding;

/**
 * This class represents an order. It is named Purchase because order is a reserved keyword in some php versions.
 * @author Mark Smit <m.smit@comercia.nl>
 */
class Purchase
{    /** @var string */
    var $id;
    /** @var int | Unix Timestamp */
    var $date;
    /** @var string */
    var $status;
    /** @var Address */
    var $deliveryAddress;
    /** @var Address */
    var $invoiceAddress;
    /** @var Orderline[] */
    var $orderLines;
    /** @var string */
    var $phoneNumber;
    /** @var string */
    var $email;
    /** @var int |  Unix Timestamp */
    var $lastUpdate = 0;
    /** @var string */
    var $invoiceNumber;
    /** @var string */
    var $trackingCode;
    /** @var enum(TOUCHED_BY_PORTAL,TOUCHED_BY_API,TOUCHED_BY_CONNECTOR) */
    var $createdBy;
    /** @var enum(TOUCHED_BY_PORTAL,TOUCHED_BY_API,TOUCHED_BY_CONNECTOR) */
    var $lastTouchedBy;

    /** @var string | Which connector is responsible for this purchase */
    var $noStockStatus;

    /** @var array | The original data from the client */
    var $originalData;

    private $session;

    /**
     * @param Session $session The session object to connect with Comercia Connect
     * @param array $data The data to initialize the address with
     */
    function __construct($session, $data = [])
    {
        $this->session = $session;
        foreach ($data as $key => $value) {
            if(is_string($this->{$key})) {
                $this->{$key} = Encoding::fixUTF8($value);
            }else{
                $this->{$key} = $value;
            }
        }

        $this->deliveryAddress = new Address($data["deliveryAddress"]);
        $this->invoiceAddress = new Address($data["invoiceAddress"]);

        $this->orderLines = array();
        if (@$data["orderLines"]) {
            foreach ($data["orderLines"] as $orderLine) {
                $this->orderLines[] = is_array($orderLine) ? new OrderLine($session, $orderLine) : $orderLine;
            }
        }
    }


    /**
     * Saves the order
     * @return bool Indicates if the order is successfully saved
     */
    function save()
    {
        if ($this->session) {
            $this->session->post("purchase/save", $this);

            return true;
        }

        return false;
    }

    /**
     * Deletes the order
     * @return bool Indicates if the order is successfully deleted
     */
    function delete()
    {
        if ($this->session) {
            $this->session->get("purchase/delete/" . $this->id);

            return true;
        }

        return false;
    }
    
    /**
     * Gets an order from Comercia Connect
     * @param Session $session
     * @param string $id
     * @return Order
     */
    static function getById($session, $id)
    {
        if ($session) {
            $data = $session->get("purchase/getById/" . $id);

            return new Purchase($session, $data["data"]);
        }

        return false;
    }

    /**
     * Gets all orders from Comercia Connect
     * @param Session $session
     * @return Product[]
     */
    static function getAll($session)
    {
        if ($session) {
            $data = $session->get("purchase/getAll");
            $result = [];
            foreach ($data["data"] as $product) {
                $result[] = new Purchase($session, $product);
            }

            return $result;
        }

        return false;
    }

    /**
     * Creates a filter
     * @param Session $session
     * @return ProductFilter a filter object to create a filtered request
     */
    static function createFilter($session)
    {
        if ($session) {
            return new PurchaseFilter($session);
        }

        return false;
    }

    /**
     * Changes the id of an order in Comercia Connect
     * @param string $id
     * @return bool Indicates if the order is successfully saved
     */
    function changeId($new)
    {
        if ($new == $this->id) {
            return true;
        }
        
        if($this->session) {
            $data = $this->session->get('purchase/changeId/' . $this->id . '/' . $new);
            $this->id=$new;
            return true;
        }

        return false;
    }

    /**
     * Touches an order in Comercia Connect.. Used to tell Comercia Connect that the client touched a order
     * @return bool Indicates if the order is successfully touched
     */
    function touch(){
        if($this->session) {
            $this->session->get('purchase/touch/'.$this->id);
            return true;
        }
        return false;
    }

    /**
     * Saves orders in bulk
     * @param Session $session
     * @param Product[] $data
     * @return bool Indicates if the order is successfully saved
     */
    static function saveBatch($session,$data){
        $requestData=["data"=>$data];
        return $session->post("purchase/saveBatch",$requestData);
    }

    /**
     * Touches orders in bulk
     * @param Session $session
     * @param Product[] $data
     * @return bool Indicates if the order is successfully touched
     */
    static function touchBatch($session,$data){
        $requestData=["data"=>$data];
        $session->post("purchase/touchBatch",$requestData);
    }

}
?>