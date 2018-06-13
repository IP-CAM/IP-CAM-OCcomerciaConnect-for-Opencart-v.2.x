
namespace comerciaConnect\logic;
use ForceUTF8\Encoding;

/**
 * This class represents a description. It will only be used as part of a product. Descriptions are not separately usable.
 * @author Mark Smit <m.smit@comercia.nl>
 */
class Translation
{
    /**
     * @var string | ISO 3166-1 alpha-2 format
     * @link https://en.wikipedia.org/wiki/ISO_3166-1_alpha-2 <Information about the format>
     */
    var $language;
    /** @var string */
    var $key;
    /** @var string */
    var $val;
    /** @var string */
    var $section;

    /**  @param array $data The data to initialize the address with */
    function __construct($language = "", $section = "", $key = "", $val = "")
    {
        if (is_array($language)) {
            $data = $language;
            foreach ($data as $key => $value) {
                $this->{$key} = Encoding::fixUTF8($value);
            }
        } else {
            $this->key = $key;
            $this->language = $language;
            $this->val = $val;
            $this->section = $section;
        }
    }
}
