<?php
namespace comerciaConnect;

use comerciaConnect\lib\HttpClient;
use comerciaConnect\logic\Session;


/**
 * This is the start point for the api
 * It is used to create sessions, load the required libraries and domain logic.
 * @author Mark Smit <m.smit@comercia.nl>
 * @param string $auth_url The url used for authentication
 * @param string $api_url The url used for the rest of the api requests
 */
class Api
{
    var $path = __DIR__;
    var $auth_url;
    var $api_url;
    var $base_url;

    function __construct($base_url, $auth_url = '', $api_url = '')
    {
        $this->loadLibs();
        $this->loadDomain();
        $this->base_url = $base_url;
        $this->auth_url = $auth_url ?: $base_url . '?route=user/token';
        $this->api_url = $api_url ?: $base_url . '?route=api' ;
    }

    private function loadLibs()
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

    private function loadDomain()
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

    /**
     * Starts an api session
     * @param string $key This is the api key from Comercia Connect
     * @return Session A new session
     */
    function createSession($key)
    {
        $client = new HttpClient();
        $data = $client->post($this->auth_url . "/request", ["apiKey" => $key]);

        if ($data["success"]) {
            return new Session($this, $data["token"]);
        }

        return false;
    }

    /**
     * Restores
     * @param string $token This is the token from the other session
     * @returns Session An old session
     */
    function restoreSession($token)
    {
        return new Session($this, $token);
    }
}

?>