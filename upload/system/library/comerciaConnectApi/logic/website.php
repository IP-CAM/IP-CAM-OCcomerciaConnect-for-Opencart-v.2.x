<?php
namespace comerciaConnect\logic;
class Website
{
    var $id;
    var $name;
    var $url;

    private $session;

    function __construct($session, $data = [])
    {
        $this->session = $session;
        foreach ($data as $key => $value) {
            $this->{$key} = $value;
        }
    }

    function controlPanelUrl()
    {
        $loginUrl = $this->session->api->auth_url . "/toUserSession/" . $this->session->token;
        $redirect = "websites/" . $this->id;
        $url = $loginUrl . "&redirect=" . $redirect;

        return $url;
    }

    static function getWebsite($session)
    {
        if ($session) {
            $data = $session->get("website/get");

            return new Website($session, $data["data"]);
        }

        return false;
    }
}

?>