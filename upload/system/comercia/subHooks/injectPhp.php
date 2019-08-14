<?php

namespace comercia\subHooks;

use comercia\Util;

class InjectPhp extends \Model
{
    /**
     * @param \comercia\BaseHook $hook
     * @param string $languageFile
     * @param string $languageKey
     * @param string[string] $values
     */
    function run($hook, $into, $position, $search, $code, $offset = 0, $safeMode)
    {
        $intoPath = $hook->dir . $into . ".php";

        if (is_callable($code)) {
            $code = Util::reflection()->getFunctionCode($code);
        }

        $code = str_replace("util::", "Util::", $code);
        $code = str_replace("\\comercia\\Util::", "Util::", $code);
        $code = str_replace("Util::", "\\comercia\\Util::", $code);

        //some code like } to close a loop or a continue statement while not in the loop in the code might cause syntax errors
        //in the case that happens the statement can be surrounded by /*** and ***/ so the php parser doesn't validate the code.
        $code = str_replace("/***", "", $code);
        $code = str_replace("***/", "", $code);

        $code = $safeMode ? "eval(base64_decode('" . base64_encode($code) . "'));" : $code;
        $hook->addOperation($intoPath, $position, $search, $code, $offset);
    }

}