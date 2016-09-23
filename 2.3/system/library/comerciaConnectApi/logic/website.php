<?php
    namespace comerciaConnect\logic;
    class Website{

        var $name;
        var $url;

        private $session;

        function __construct($session,$data=array())
        {
            $this->session=$session;
            foreach($data as $key => $value){
                $this->{$key} = $value;
            }

        }

        static function getWebsite($session){
            $data = $session->get("website/get");
            return new Website($session,$data);
        }



    }
?>