<?php
namespace comerciaConnect;

use comerciaConnect\lib\HttpClient;
use comerciaConnect\logic\Session;

class Api
{
    var $path = __DIR__;

    function __construct($auth_url, $api_url)
    {
        $this->loadLibs();
        $this->loadDomain();
        $this->auth_url = $auth_url;
        $this->api_url = $api_url;
    }

    function loadLibs()
    {
        $dir = $this->path . "/lib/";
        if ($handle = opendir($dir)) {
            while (false !== ($entry = readdir($handle))) {
                if ($entry != "." && $entry != ".." && !is_dir($dir . "/" . $entry)) {
                    include_once($dir . $entry);
                }
            }
            closedir($handle);
        }

    }

    function loadDomain()
    {
        $dir = $this->path . "/logic/";
        if ($handle = opendir($dir)) {
            while (false !== ($entry = readdir($handle))) {
                if ($entry != "." && $entry != ".." && !is_dir($dir . "/" . $entry)) {
                    include_once($dir . $entry);
                }
            }
            closedir($handle);
        }
    }

    function createSession($key)
    {
        $client = new HttpClient();
        $data = $client->post($this->auth_url."/request" , array("apiKey" => $key));
        if ($data["success"]) {
            return new Session($this, $data["token"]);
        }
        return false;
    }

    function restoreSession($token){
        return new Session($this,$token);
    }

}

?>