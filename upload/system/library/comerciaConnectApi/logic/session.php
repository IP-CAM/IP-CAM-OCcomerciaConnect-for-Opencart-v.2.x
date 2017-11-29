<?php
namespace comerciaConnect\logic;
use comerciaConnect\lib\HttpClient;

/**
 * This class contains the connection with Comercia Connect
 * @author Mark Smit <m.smit@comercia.nl>
 */
class Session
{

    /** @var Api */
    var $api;
    /** @var string */
    var $token;

    /**
     * @param Api $api The api object used to start a session
     * @param String token The token from Comercia Connect
     */
    function __construct($api, $token)
    {
        $this->token = $token;
        $this->api = $api;
    }

    /**
     * Do a get request for this session
     * @param string $endpoint
     */
    function get($endpoint, $parse = true)
    {
        $client = new HttpClient();

        if (substr($endpoint, 0, 1) === '?') {
            return $client->get($this->api->base_url . $endpoint, $this->token, $parse);
        }

        return $client->get($this->api->api_url . "/" . $endpoint, $this->token, $parse);
    }

    /**
     * Do a post request for this session
     * @param string $endpoint
     * @param string $data
     */
    function post($endpoint, $data, $parse = true)
    {
        $client = new HttpClient();

        return $client->post($this->api->api_url . "/" . $endpoint, $data, $this->token, $parse);
    }
}
?>