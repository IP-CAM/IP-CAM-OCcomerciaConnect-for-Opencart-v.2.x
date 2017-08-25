<?php
namespace comerciaConnect\logic;
class Script
{
    var $script;
    var $event;

    private $session;

    function __construct($session)
    {
        $this->session = $session;

    }

    function save()
    {
        if ($this->session) {
            $this->session->post("script/save", $this);
            return true;
        }

        return false;
    }
}

?>