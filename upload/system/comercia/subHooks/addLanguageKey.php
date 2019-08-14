<?php

namespace comercia\subHooks;

use comercia\Util;

class AddLanguageKey
{
    /**
     * @param \comercia\BaseHook $hook
     * @param string $languageFile
     * @param string $languageKey
     * @param string[string] $values
     */
    function run($hook, $languageFile, $languageKey, $values)
    {
        foreach ($values as $language => $value) {
            $hook->addOperation(Util::filesystem()->relativeAppPath("language/" . $language . "/" . $languageFile . ".php"), "bottom", "", '$_["' . $languageKey . '"]="' . $value . '";');
        }

    }
}