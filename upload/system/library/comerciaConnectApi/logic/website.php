<?php
namespace comerciaConnect\logic;

/**
 * This class represents a Website. This contains all website related settings.
 * @author Mark Smit <m.smit@comercia.nl>
 */

class Website
{
    /** @var string */
    var $id;

    //todo:implement logic to enforce datastructure of tax rates for when the api goes public.
    /** @var decimal[string|country][string|taxgroup] */
    var $taxRates;
    /**
     * @var string[] | Iso 639-1  supported languages by the webshop
     * @see https://en.wikipedia.org/wiki/List_of_ISO_639-1_codes For language code information
     */
    var $languages;

    /**
     * @var string[] | ISO 4217 code  supported currencies by the webshop
     * @see https://en.wikipedia.org/wiki/ISO_4217
     */
    var $currencies;

    /**
     * @var string[] | Supported weight units
     */
    var $weightUnits;
    /**
     * @var string[] | Supported length units
     */
    var $lengthUnits;

    /** @var string[] */
    var $orderStatus;

    /** @var string[] */
    var $fieldsOrder;

    /** @var string[] */
    var $fieldsProduct;

    /** @var string[] */
    var $stockStatus;

    /** @var string */
    var $address;

    /** @var string */
    var $storeName;

    /** @var string */
    var $email;

    /** @var string */
    var $phone;

    /** @var string */
    var $homepageUrl;

    /** @var string */
    var $userConditionsUrl;

    /** @var string */
    var $checkoutConditionsUrl;

    /** @var string */
    var $returnConditionsUrl;

    /** @var string */
    var $defaultOrderStatus;

    private $session;


    /**
     * @param Session $session The session object to connect with Comercia Connect
     * @param array $data The data to initialize the address with
     */
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
        if (isset($this->name)) {
            unset($this->name);
        }
        if (isset($this->url)) {
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