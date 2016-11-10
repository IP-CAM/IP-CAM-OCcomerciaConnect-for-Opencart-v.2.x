<?php
namespace comerciaConnect\logic;
class Purchase{
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

    private $session;
    function __construct($session,$data=array())
    {
        $this->session=$session;
        foreach($data as $key => $value){
            $this->{$key} = $value;
        }

        $this->deliveryAddress=new Address($data["deliveryAddress"]);
        $this->invoiceAddress=new Address($data["invoiceAddress"]);

        $this->orderLines=array();
        if(@$data["orderLines"]) {
            foreach ($data["orderLines"] as $orderLine) {
                $this->orderLines[] = new OrderLine($session,$orderLine);
            }
        }

     }

    function save(){
        if($this->session) {
            $this->session->post("purchase/save", $this);
            return true;
        }
        return false;
    }

    function delete(){
        if($this->session) {
            $this->session->get("purchase/delete/" . $this->id);
            return true;
        }
        return false;
    }

    static function getById($session,$id){
        if($session) {
            $data = $session->get("purchase/getById/" . $id);
            return new Purchase($session, $data["data"]);
        }
        return false;
    }

    static function getAll($session){
        if($session) {
            $data = $session->get("purchase/getAll");
            $result = array();
            foreach ($data["data"] as $product) {
                $result[] = new Purchase($session, $product);
            }
            return $result;
        }return false;
    }

    static function createFilter($session){
        if($session) {
            return new PurchaseFilter($session);
        }
        return false;
    }



}