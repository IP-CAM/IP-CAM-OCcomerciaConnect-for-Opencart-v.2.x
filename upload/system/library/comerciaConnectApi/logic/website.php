<?php
namespace comerciaConnect\logic;
class Website
{
    var $id;

    //todo:implement logic to enforce datastructure of tax rates for when the api goes public.
    var $taxRates;
    var $languages;
    var $currencies;
    var $weightUnits;
    var $lengthUnits;
    var $orderStatus;

    var $address;
    var $storeName;
    var $email;
    var $phone;
    var $homepageUrl;
    var $userConditionsUrl;
    var $checkoutConditionsUrl;
    var $returnConditionsUrl;
    var $defaultOrderStatus;

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

    function save()
    {
        if(isset($this->name)) {
            unset($this->name);
        }
        if(isset($this->url)) {
            unset($this->url);
        }
        if ($this->session) {
            $this->session->post("website/save", $this);

            return true;
        }

        return false;
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