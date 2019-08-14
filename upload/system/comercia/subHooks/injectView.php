<?php

namespace comercia\subHooks;

use comercia\Util;

class InjectView extends \Model
{
    /**
     * @param \comercia\BaseHook $hook
     * @param string $languageFile
     * @param string $languageKey
     * @param string[string] $values
     */
    function run($hook, $into, $position, $search, $injectFile, $offset = 0)
    {
        if (Util::version()->isMinimal("3.0")) {
            $extension = ".twig";
        } else {
            $extension = ".tpl";
        }
        if (Util::info()->isInAdmin()) {
            $dir = DIR_TEMPLATE;
        } else {
            $dir = DIR_APPLICATION . "" . Util::info()->theme(true)."template/";
        }

        $injectFilePath = str_replace(DIR_TEMPLATE, "", $dir . $injectFile . $extension);
        $intoPath = $dir . $into . $extension;

        $hook->addOperation($intoPath, $position, $search, "{% include '" . $injectFilePath . "' %}",$offset);


    }

}