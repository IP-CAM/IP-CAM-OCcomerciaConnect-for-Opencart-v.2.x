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
    function get($endpoint)
    {
        $client = new HttpClient();

        return $client->get($this->api->api_url . "/" . $endpoint, $this->token);
    }

    /**
     * Do a post request for this session
     * @param string $endpoint
     * @param string $data
     */
    function post($endpoint, $data)
    {
        $client = new HttpClient();

        return $client->post($this->api->api_url . "/" . $endpoint, $data, $this->token);
    }
}
?>