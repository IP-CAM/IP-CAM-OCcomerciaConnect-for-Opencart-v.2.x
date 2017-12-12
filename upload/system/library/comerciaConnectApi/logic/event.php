<?php
namespace comerciaConnect\logic;

use comercia\Util;
use comerciaConnect\lib\HttpClient;
use MongoDB\BSON\Binary;

/**
 * This class represents a product
 * @author Mark Smit <m.smit@comercia.nl>
 */
class Event
{

    var $session;
    var $event;
    var $data;

    function __construct($session, $event,$data)
    {
        $this->session=$session;
        $this->event=$event;
        $this->data=$data;
    }

    function raise(){
        self::raiseBatch($this->session,[$this]);
    }

    static function raiseBatch($session,$events){
        $requestData=["events"=>$events];
        return $session->post("event/raiseBatch",$requestData);
    }
}