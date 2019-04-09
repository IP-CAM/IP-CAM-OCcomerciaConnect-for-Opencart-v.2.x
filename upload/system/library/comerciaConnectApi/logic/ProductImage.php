<?php
namespace comerciaConnect\logic;

/**
 * This class represents an Image. It will only be used as part of a product.
 * @author Mark Smit <m.smit@comercia.nl>
 */
class ProductImage
{
    /** @var  string | The url of the image */
    var $image;

    /** @param array $data The data to initialize the image with */
    function __construct($data)
    {
        if (is_array($data)) {
            $this->image = $data["image"];
        }
        else {
            $this->image = $data->image;
        }
    }
}