<?php
namespace comerciaConnect\logic;
class Product{
    var $id;
    var $name="";
    var $quantity=0;
    var $price=0;
    var $url="";
    var $descriptions=array();
    var $categories=array();
    var $ean="";
    var $isbn="";
    var $sku="";
    var $taxGroup="";
    var $type=PRODUCT_TYPE_PRODUCT;
    var $code="";

    private $session;
    function __construct($session,$data=array())
    {
        $this->session=$session;
        foreach($data as $key => $value){
            $this->{$key} = $value;
        }

        $this->descriptions=array();
        if(@$data["descriptions"]) {
            foreach ($data["descriptions"] as $description) {
                $this->descriptions[] = new ProductDescription($description);
            }
        }

        $this->categories=array();
        if(@$data["categories"]) {
            foreach ($data["categories"] as $category) {
                $this->categories[] = new ProductCategory($this->session,$category);
            }
        }

     }

    function save(){
        if($this->session) {
            $this->session->post("product/save", $this);
            return true;
        }
        return false;
    }

    function delete(){
        if($this->session) {
            $this->session->get("product/delete/" . $this->id);
            return true;
        }
        return false;
    }

    static function getById($session,$id){
        if($session) {
            $data = $session->get("product/getById/" . $id);
            return new Product($session, $data["data"]);
        }
        return false;
    }

    static function getAll($session){
        if($session) {
            $data = $session->get("product/getAll");
            $result = array();
            foreach ($data["data"] as $product) {
                $result[] = new Product($session, $product);
            }
            return $result;
        }
        return false;
    }


    static function createFilter($session){
        return new ProductFilter($session);
    }


}
