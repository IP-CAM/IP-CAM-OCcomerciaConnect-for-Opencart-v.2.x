<?php

namespace comercia;

use comercia\controllers\Import;
use comercia\controllers\ModuleSettings;

class ControllerHelper
{
    //the first module built with this controller helper was 2.2 so to keep everything backwards compatible, use 2.2 by default.
    function moduleSettingsController($name, $baseVersion = "2.2")
    {
        require_once __DIR__ . "/controllers/moduleSettings.php";
        return new ModuleSettings($name, $baseVersion);
    }

    function importController()
    {
        require_once __DIR__ . "/controllers/import.php";
        return new Import();
    }
}
