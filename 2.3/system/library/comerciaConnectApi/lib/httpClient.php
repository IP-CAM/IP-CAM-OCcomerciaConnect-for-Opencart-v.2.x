<?php
namespace comerciaConnect\lib;
class HttpClient
{
    function post($url, $data, $token = false)
    {
        global $is_in_debug;
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $headers = array(
            'Content-Type:application/json'
        );
        if ($token) {
            $headers[] = "Authorization:" . $token;
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $server_output = curl_exec($ch);
        curl_close($ch);
        if(isset($is_in_debug) && $is_in_debug) {
            print_r($server_output);
        }
        return json_decode($server_output, true);

    }

    function get($url, $token = false)
    {
        global $is_in_debug;
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);


        if ($token) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, array("Authorization:" . $token));
        };

        $server_output = curl_exec($ch);
        curl_close($ch);
        if(isset($is_in_debug) && $is_in_debug) {
            print_r($server_output);
        }
        return json_decode($server_output, true);
    }

}

?>