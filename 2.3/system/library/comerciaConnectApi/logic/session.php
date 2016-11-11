<?php
    namespace comerciaConnect\logic;
    use comerciaConnect\lib\HttpClient;

    class Session{

        var $api;
        var $token;

        function __construct($api,$token)
        {
            $this->token=$token;
            $this->api=$api;
        }

        function get($url){
            $client = new HttpClient();
            return $client->get($this->api->api_url."/".$url,$this->token);
        }

        function post($url,$data){
            $client = new HttpClient();
            return $client->post($this->api->api_url."/".$url,$data,$this->token);
        }
    }
?>