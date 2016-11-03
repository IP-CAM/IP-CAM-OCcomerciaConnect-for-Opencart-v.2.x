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
        $this->session->post("purchase/save",$this);
    }

    function delete(){
        $this->session->get("purchase/delete/".$this->id);
    }

    static function getById($session,$id){
        $data = $session->get("purchase/getById/".$id);
        return new Purchase($session,$data["data"]);
    }

    static function getAll($session){
        $data = $session->get("purchase/getAll");
        $result=array();
        foreach($data["data"] as $product){
            $result[]=new Purchase($session,$product);
        }
        return $result;
    }

    static function createFilter($session){
        return new PurchaseFilter($session);
    }



}