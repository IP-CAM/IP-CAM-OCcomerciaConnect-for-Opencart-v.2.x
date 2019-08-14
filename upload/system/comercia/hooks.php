<?php

namespace comercia;

class Hooks
{
    function run()
    {
        $env = Util::info()->getEnv();
        $this->runHookForContext(DIR_APPLICATION, Util::info()->getEnv());
        $this->runHookForContext(DIR_SYSTEM, "system");

    }
    private function runHookForContext($dir, $env)
    {
        $dir = $dir . "hook/";
        $vqModFile = DIR_ROOT . "vqmod/xml/zz_comercia_autogenerate_" . $env . ".xml";

        if(!is_dir($dir)){
            return false;
        }
        $dirTime = Util::filesystem()->dirmtime($dir);

        if (!file_exists($vqModFile) || $dirTime > filemtime($vqModFile)) {

            $vqModStructure = [];
            if ($handle = opendir($dir)) {
                while (false !== ($entry = readdir($handle))) {
                    if ($entry != '.' && $entry != '..' && !is_dir($dir . '/' . $entry) && substr($entry, -3) === 'php') {
                        include_once($dir . "/" . $entry);
                        $className = "Hook" . explode(".", ucfirst($entry))[0];
                        $hookInstance = new $className(Util::registry());
                        $hookInstance->dir = Util::filesystem()->getAppPathForEnv($env);
                        $hookInstance->run();
                        if ($hookInstance->result) {
                            $vqModStructure = array_merge_recursive($vqModStructure, $hookInstance->result);
                        }
                    }
                }
            }

            if (!empty($vqModStructure)) {
                $vqModContent = "<modification>
                    <id>Comercia Utility autogenerate</id>
                    <version>1.0.6</version>
                    <vqmver>2.3.0</vqmver>
                    <author>Comercia util</author>";


                foreach ($vqModStructure as $fileName => $operations) {
                    $fileName = str_replace(DIR_ROOT, "", $fileName);
                    $vqModContent .= "    <file name=\"" . $fileName . "\">";
                    foreach ($operations as $operation) {
                        $offsetCode = $operation["offset"] ? "offset='" . $operation["offset"] . "'" : "";
                        $vqModContent .= "    
                        <operation error=\"skip\">
                            <search " . $offsetCode . "  position=\"" . $operation['position'] . "\"><![CDATA[" . $operation['search'] . "]]></search>
                            <add><![CDATA[" . $operation['add'] . "]]></add>
                        </operation>";
                    }
                    $vqModContent .= "</file>";
                }


                $vqModContent .= "</modification>";
                file_put_contents($vqModFile, $vqModContent);
                unlink(DIR_ROOT . "/vqmod/mods.cache");
                unlink(DIR_ROOT . "/vqmod/checked.cache");

            }

        }
    }

    private function getSubHookInstance($name)
    {
        include_once(__DIR__ . "/subHooks/" . $name . ".php");
        $name = "\\comercia\\subHooks\\" . $name;
        return new $name(Util::registry());
    }


    /**
     * @param \comercia\BaseHook $hook
     * @param string $languageFile
     * @param string $languageKey
     * @param string[string] $values
     */
    function addLanguageKey($hook, $languageFile, $languageKey, $values)
    {
        $subHookInstance = $this->getSubHookInstance("addLanguageKey");
        $subHookInstance->run($hook, $languageFile, $languageKey, $values);
        return $this;
    }

    /**
     * @param \comercia\BaseHook $hook
     * @param string $category
     * @param string $languageKey
     * @param $url
     */
    function addMenuLink($hook, $category, $languageKey, $url)
    {
        $subHookInstance = $this->getSubHookInstance("addMenuLink");
        $subHookInstance->run($hook, $category, $languageKey, $url);
        return $this;
    }

    function preLoad($hook, $function, $safeMode = true)
    {
        $subHookInstance = $this->getSubHookInstance("preLoad");
        $subHookInstance->run($hook, $function, $safeMode);
        return $this;
    }

    function injectView($hook, $into, $position, $search, $injectFile, $offset = 0)
    {
        $subHookInstance = $this->getSubHookInstance("injectView");
        $subHookInstance->run($hook, $into, $position, $search, $injectFile, $offset);
        return $this;
    }

    //todo: implement a safe mode in which only variables given in the function parameters will be available.
    function injectPhp($hook, $into, $position, $search, $injectCode, $offset = 0, $safeMode = false)
    {
        if(!is_array($search)){
            $search=[$search];
        }
        $subHookInstance = $this->getSubHookInstance("injectPhp");
        foreach($search as $key=>$searchEntry) {
            if(is_array($injectCode)){
                $currentInject=$injectCode[$key];
            }else{
                $currentInject=$injectCode;
            }
            $subHookInstance->run($hook, $into, $position, $searchEntry, $currentInject, $offset, $safeMode);
        }
        return $this;
    }
}

?>