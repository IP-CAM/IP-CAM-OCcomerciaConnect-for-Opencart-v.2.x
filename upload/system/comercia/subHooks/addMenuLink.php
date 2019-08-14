<?php

namespace comercia\subHooks;

use comercia\Util;

class AddMenuLink
{
    /**
     * @param \comercia\BaseHook $hook
     * @param string $languageFile
     * @param string $languageKey
     * @param string[string] $values
     */
    function run($hook, $category, $languageKey, $url, $params = "")
    {
        $hook->addOperation("admin/controller/common/column_left.php", "after", '$' . $category . " = array();",
            '
                    $system[] = array(
                    \'name\'	   => $this->language->get(\'' . $languageKey . '\'),
                    \'href\'     => \comercia\Util::url()->link("'.$url.'","'.$params.'"),
                    \'children\' => array()		
                    );	
                 ');
    }
}