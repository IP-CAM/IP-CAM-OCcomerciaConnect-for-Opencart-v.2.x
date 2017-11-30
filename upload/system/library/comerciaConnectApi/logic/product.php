<?php
namespace comerciaConnect\logic;

use comercia\Util;
use comerciaConnect\lib\HttpClient;
use MongoDB\BSON\Binary;

/**
 * This class represents a product
 * @author Mark Smit <m.smit@comercia.nl>
 */
class Product
{
    /** @var string */
    var $id;
    /** @var string */
    var $name = "";
    /** @var integer */
    var $quantity = 0;
    /** @var decimal */
    var $price = 0;
    /** @var decimal */
    var $specialPrice = 0;
    /** @var string */
    var $url = "";
    /** @var Description[] | Descriptions will be saved when the product is saved */
    var $descriptions = [];
    /** @var Category[] | should contain only saved categories*/
    var $categories = [];
    /** @var string */
    var $ean = "";
    /** @var string */
    var $isbn = "";
    /** @var string */
    var $jan = "";
    /** @var string */
    var $upc = "";
    /** @var string */
    var $sku = "";
    /** @var string */
    var $taxGroup = "";

    /** @var  ProductImage[] */
    var $extraImages=[];

    /** @var enum(PRODUCT_TYPE_PRODUCT,PRODUCT_TYPE_SERVICE,PRODUCT_TYPE_VIRTUAL,PRODUCT_TYPE_PAYMENT,PRODUCT_TYPE_SHIPPING ) */
    var $type = PRODUCT_TYPE_PRODUCT;
    /** @var string | should be unique */
    var $code = "";
    /** @var string | the url of the image*/
    var $image = "";
    /** @var string */
    var $brand = "";
    /** @var Product | Used when the product is a subset of another product */
    var $parent = null;
    /** @var int | timestamp */
    var $lastUpdate = 0;
    /** @var enum(TOUCHED_BY_PORTAL,TOUCHED_BY_API,TOUCHED_BY_CONNECTOR) */
    var $createdBy;
    /** @var enum(TOUCHED_BY_PORTAL,TOUCHED_BY_API,TOUCHED_BY_CONNECTOR) */
    var $lastTouchedBy;
    /** @var string | which status  Comercia Connect should give when the product is in stock */
    var $inStockStatus;
    /** @var string | which status  Comercia Connect should give when the product is not in stock */
    var $noStockStatus;
    /** @var boolean | Product status */
    var $active;
    /** @var float | Product height */
    var $height;
    /** @var float | Product width */
    var $width;
    /** @var float | Product length */
    var $length;
    /** @var float | Product weight */
    var $weight;
    /** @var boolean | Product uses stock */
    var $usesStock;

    /** @var array | The original data from the client */
    var $originalData;

    private $session;

    /**
     * @param array $data The data to initialize the address with
     * @param Session $session The session object to connect with Comercia Connect
     */
    function __construct($session, $data = [])
    {
        $this->session = $session;
        foreach ($data as $key => $value) {
            $this->{$key} = $value;
        }

        if($this->parent) {
            $this->parent = new Product($session, $this->parent);
        }
        $data=(object)$data;
        $this->extraImages = [];
        if (!empty($data->extraImages)) {
            foreach ($data->extraImages as $image) {
                $this->extraImages[] = new ProductImage($image);
            }
        }

        $this->descriptions = [];
        if (!empty($data->descriptions)) {
            foreach ($data->descriptions as $description) {
                $this->descriptions[] = new ProductDescription($description);
            }
        }

        $this->categories = [];
        if (!empty($data->categories)) {
            foreach ($data->categories as $category) {
                $this->categories[] = new ProductCategory($this->session, $category);
            }
        }
    }


    /**
     * Saves the product
     * @return bool Indicates if the product is successfully saved
     */
    function save()
    {
        if ($this->session) {
            $this->session->post("product/save", $this);

            return true;
        }

        return false;
    }

    /**
     * Deletes the product
     * @return bool Indicates if the product is successfully deleted
     */
    function delete()
    {
        if ($this->session) {
            $this->session->get("product/delete/" . $this->id);

            return true;
        }

        return false;
    }


    /**
     * Gets a product from Comercia Connect
     * @param Session $session
     * @param string $id
     * @return Product
     */
    static function getById($session, $id)
    {
        if ($session) {
            $data = $session->get("product/getById/" . $id);

            return new Product($session, $data["data"]);
        }

        return false;
    }

    /**
     * Gets all products from Comercia Connect
     * @param Session $session
     * @return Product[]
     */
    static function getAll($session)
    {
        if ($session) {
            $data = $session->get("product/getAll");
            $result = [];
            foreach ($data["data"] as $product) {
                $result[] = new Product($session, $product);
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
        return new ProductFilter($session);
    }


    /**
     * Changes the id of a product in Comercia Connect
     * @param string $id
     * @return bool Indicates if the product is successfully saved
     */
    function changeId($new)
    {
        if ($new == $this->id) {
            return true;
        }

        if($this->session) {
            $data = $this->session->get('product/changeId/' . $this->id . '/' . $new);
            $this->id=$new;
            return true;
        }

        return false;
    }


    /**
     * Touches a product in Comercia Connect.. Used to tell Comercia Connect that the client touched a product
     * @return bool Indicates if the product is successfully touched
     */
    function touch(){
        if($this->session) {
            $this->session->get('product/touch/'.$this->id);
            return true;
        }
        return false;
    }


    /**
     * Saves products in bulk
     * @param Session $session
     * @param Product[] $data
     * @return bool Indicates if the product is successfully saved
     */
    static function saveBatch($session,$data){
        $requestData=["data"=>$data];
        return $session->post("product/saveBatch",$requestData);
    }


    /**
     * Touches products in bulk
     * @param Session $session
     * @param Product[] $data
     * @return bool Indicates if the product is successfully touched
     */
    static function touchBatch($session,$data){
        $requestData=["data"=>$data];
        $session->post("product/touchBatch",$requestData);
    }

    /**
     * Gets image
     * @param String $url
     * @return Binary Image data
     */
    function getImageData($url)
    {
        if(substr($url, 0, strlen($this->session->api->base_url)) == $this->session->api->base_url) {
            $url = str_replace($this->session->api->base_url, '', $url);
            $result = $this->session->get($url, false);
        } else {
            $client = new HttpClient();
            $result = $client->get($url, false, false);
        }

        return $result;
    }


    /**
     * Deactivates products in bulk
     * @param Session $session
     * @param int[] $data
     * @return bool Indicates if the product is successfully deactivated
     */
    static function deactivateBatch($session, $data)
    {
        $requestData = ["deletedData" => $data];
        return $session->post("product/deactivateBatch", $requestData);
    }
}