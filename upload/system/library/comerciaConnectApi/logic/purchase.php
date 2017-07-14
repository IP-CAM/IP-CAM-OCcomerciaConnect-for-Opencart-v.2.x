<?php
namespace comerciaConnect\logic;
class Purchase
{
    var $id;
    /**
     * @serializeIgnore()
     */
    var $external_id;

    var $date;
    var $status;
    var $deliveryAddress;
    var $invoiceAddress;
    var $orderLines;
    var $phoneNumber;
    var $email;
    var $lastUpdate = 0;
    var $invoiceNumber;

    private $session;

    function __construct($session, $data = [])
    {
        $this->session = $session;
        foreach ($data as $key => $value) {
            $this->{$key} = $value;
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

    function save()
    {
        if ($this->session) {
            $this->session->post("purchase/save", $this);

            return true;
        }

        return false;
    }

    function delete()
    {
        if ($this->session) {
            $this->session->get("purchase/delete/" . $this->id);

            return true;
        }

        return false;
    }

    static function getById($session, $id)
    {
        if ($session) {
            $data = $session->get("purchase/getById/" . $id);

            return new Purchase($session, $data["data"]);
        }

        return false;
    }

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

    static function createFilter($session)
    {
        if ($session) {
            return new PurchaseFilter($session);
        }

        return false;
    }

    function changeId($new)
    {
        if($this->session) {
            $data = $this->session->get('purchase/changeId/' . $this->id . '/' . $new);
            $this->id=$new;
            return true;
        }

        return false;
    }

    function touch(){
        if($this->session) {
            $this->session->get('purchase/touch/'.$this->id);
            return true;
        }
        return false;
    }

    static function saveBatch($session,$data){
        $requestData=["data"=>$data];
        $session->post("purchase/saveBatch",$requestData);
    }

    static function touchBatch($session,$data){
        $requestData=["data"=>$data];
        $session->post("purchase/touchBatch",$requestData);
    }

}
?>