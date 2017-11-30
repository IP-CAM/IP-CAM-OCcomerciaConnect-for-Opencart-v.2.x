<?php
    class ComerciaConnect{
        function __construct(){
            include_once(DIR_SYSTEM."library/comerciaConnectApi/api.php");
        }

        function getApi($base_url, $auth_url, $api_url){
            return new \comerciaConnect\Api($base_url, $auth_url, $api_url);
        }
    }
?>