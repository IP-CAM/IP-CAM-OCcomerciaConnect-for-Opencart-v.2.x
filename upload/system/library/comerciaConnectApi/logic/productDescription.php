<?php
namespace comerciaConnect\logic;
/**
 * This class represents a description. It will only be used as part of a product. Descriptions are not separately usable.
 * @author Mark Smit <m.smit@comercia.nl>
 */
class ProductDescription
{
    /**
     * @var string | ISO 3166-1 alpha-2 format
     * @link https://en.wikipedia.org/wiki/ISO_3166-1_alpha-2 <Inforamtion about the format>
     */
    var $language;
    /** @var string */
    var $name;
    /** @var string */
    var $description;
    /**  @param array $data The data to initialize the address with */
    function __construct($language = "", $name = "", $description = "")
    {
        if (is_array($language)) {
            $data = $language;
            foreach ($data as $key => $value) {
                $this->{$key} = $value;
            }
        } else {
            $this->name = $name;
            $this->language = $language;
            $this->description = $description;
        }
    }
}