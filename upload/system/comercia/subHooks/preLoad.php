<?php

namespace comercia\subHooks;

use comercia\Util;

class PreLoad extends \Model
{
    /**
     * @param \comercia\BaseHook $hook
     * @param string $languageFile
     * @param string $languageKey
     * @param string[string] $values
     */
    function run($hook, $function, $safeMode)
    {
        static $loaded = false;
        Util::hooks()->injectPhp($hook, "controller/startup/startup", "after", "new Openbay", $function, 0, $safeMode);

    }

}