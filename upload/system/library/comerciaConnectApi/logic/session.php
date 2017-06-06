<?php
namespace comerciaConnect\logic;
use comerciaConnect\lib\HttpClient;

class Session
{

    var $api;
    var $token;

    function __construct($api, $token)
    {
        $this->token = $token;
        $this->api = $api;
    }

    function get($endpoint)
    {
        $client = new HttpClient();

        return $client->get($this->api->api_url . "/" . $endpoint, $this->token);
    }

    function post($endpoint, $data)
    {
        $client = new HttpClient();

        return $client->post($this->api->api_url . "/" . $endpoint, $data, $this->token);
    }
}
?>