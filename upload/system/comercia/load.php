<?php
namespace comercia;

class Load
{
    function library($library)
    {
        $className = lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $library))));
        $className = $className;
        $libDir = DIR_SYSTEM . "library/";
        $bestOption = $this->findBestOption($libDir, $library, "php");
        if (!class_exists($className)) {
            if (class_exists("VQMod")) {
                require_once(\VQMod::modCheck($libDir . $bestOption["name"] . ".php"));
            } else {
                require_once($libDir . $bestOption["name"] . ".php");
            }
        }

        $result = new $className(Util::registry());
        Util::registry()->set(Util::stringHelper()->ccToUnderline($className), $result);
        return $result;
    }

    function findBestOption($dir, $name, $extension)
    {

        //fiend associated files
        $posibilities = glob($dir . "" . $name . "*." . $extension);
        $files = array();
        foreach ($posibilities as $file) {
            $file = str_replace(DIR_TEMPLATE, "", $file);
            $file = str_replace(".tpl", "", $file);
            $expFile = str_replace(")", "", $file);
            $exp = explode("(", $expFile);
            $files[] = array(
                "name" => $file,
                "version" => isset($exp[1]) ? explode("_", $exp[1]) : false
            );
        }

        //find best option
        $bestOption = false;
        foreach ($files as $file) {
            if (
                ($file["version"]) && //check if this file has a version if no version its never the best option
                (
                    $file["version"][0] == "min" && Util::version()->isMinimal($file["version"][1]) ||//decide if is valid in case of minimal
                    $file["version"][0] == "max" && Util::version()->isMaximal($file["version"][1]) //decide if is valid in case of maximal
                ) &&
                (!$bestOption || $file["version"][0] == "max" || $bestOption["version"][0] == "min") && //prioritize max version over min version
                (
                    !$bestOption || // if there is no best option its always the best option
                    ($file["version"][0] == "min" && version_compare($file["version"][1], $bestOption["version"][1], ">")) ||//if priority is by minimal , find the highest version
                    $file["version"][0] == "max" && version_compare($file["version"][1], $bestOption["version"][1], "<") //if priority is by maximal , find the lowest version
                )
            ) {
                $bestOption = $file;
            }

        }

        if (!$bestOption) {
            $bestOption = array(
                "name" => $name,
                "version" => false,
            );
        }

        return $bestOption;

    }

    function model($model)
    {
        $modelDir = DIR_APPLICATION . 'model/';
        $route = $this->getRouteInfo("model", $model, $modelDir);
        $className = $route["class"];
        if (!class_exists($className)) {
            if (class_exists("VQMod")) {
                require_once(\VQMod::modCheck($modelDir . $route["file"] . ".php"));
            } else {
                require_once($modelDir . $route["file"] . ".php");
            }
        }

        $result = new $className(Util::registry());
        Util::registry()->set(Util::stringHelper()->ccToUnderline($className), $result);
        return $result;
    }

    function getRouteInfo($prefix, $route, $dir)
    {
        $parts = explode('/', preg_replace('/[^a-zA-Z0-9_\/]/', '', (string)$route));

        $fileRoute = "";
        $method = "";
        while ($parts) {
            $file = $dir . implode('/', $parts) . '.php';

            if (is_file($file)) {
                $fileRoute = implode('/', $parts);
                break;
            } else {
                $method = array_pop($parts);
            }
        }

        $registry = Util::registry();

        $className = lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $fileRoute))));
        $className = lcfirst(str_replace(' ', '', ucwords(str_replace('/', ' ', $className))));
        $className = ucfirst($className);
        $className = ucfirst($prefix) . preg_replace('/[^a-zA-Z0-9]/', '', $className);

        $bestOption = $this->findBestOption($dir, $fileRoute, "php");

        return array(
            "file" => $bestOption["name"],
            "class" => $className,
            "method" => $method
        );
    }

    function view($view, $data = array())
    {
        if (Util::info()->IsInAdmin()) {
            $bestOption = $this->findBestOption(DIR_TEMPLATE, $view, "tpl");
        } else {
            $bestOption1 = $this->findBestOption(DIR_TEMPLATE . "default/template/", $view, "tpl");
            $bestOption2 = $this->findBestOption(DIR_TEMPLATE . Util::info()->theme() . "/template/", $view, "tpl");
            if ($bestOption1["version"] && !$bestOption2["version"]) {
                $bestOption = $bestOption1;
            } else {
                $bestOption = $bestOption2;
            }
        }

        $view = $bestOption["name"];

        $registry = Util::registry();
        if(Util::version()->isMinimal(2.0)) {
            if (Util::version()->isMinimal("2.2") || Util::version()->isMinimal("2") && Util::info()->IsInAdmin()) {
                return $registry->get("load")->view($view, $data);
            } else {
                if (file_exists(DIR_TEMPLATE . Util::info()->theme() . '/template/' . $view)) {
                    return $registry->get("load")->view($this->config->get('config_template') . "/template/" . $view, $data);
                } else {
                    return $registry->get("load")->view('default/template/' . $view, $data);
                }
            }
        }

        $fakeControllerFile = __DIR__ . "/fakeController.php";
        if (class_exists("VQMod")) {
            require_once(\VQMod::modCheck($fakeControllerFile));
        } else {
            require_once($fakeControllerFile);
        }
        $controller = new FakeController($registry);
        $result= $controller->getView($view, $data);
        return $result;
    }

    function language($file, &$data = array())
    {
        $registry = Util::registry();
        $result = $registry->get("load")->language($file);
        foreach ($result as $key => $val) {
            $data[$key] = $val;
        }
        return $result;
    }

    function pageControllers(&$data)
    {
        $data['header'] = Util::load()->controller('common/header');
        $data['column_left'] = Util::load()->controller('common/column_left');
        $data['footer'] = Util::load()->controller('common/footer');
    }

    function controller($controller)
    {

        $controllerDir = DIR_APPLICATION . 'controller/';
        $route = $this->getRouteInfo("controller", $controller, $controllerDir);

        $className = $route["class"];
        if (!class_exists($className)) {
            if (class_exists("VQMod")) {
                require_once(\VQMod::modCheck($controllerDir . $route["file"] . ".php"));
            } else {
                require_once($controllerDir . $route["file"] . ".php");
            }
        }

        $rc=new \ReflectionClass($className);
        if ($rc->isInstantiable()) {
            $method = $route["method"] ? $route["method"] : "index";
            $controller = new $className(Util::registry());
            $mr = new \ReflectionMethod($className, $method);
            $mr->setAccessible(true);
            $result = $mr->invoke($controller);

            if(!$result) {
                $pr = new \ReflectionProperty($className, "output");
                $pr->setAccessible(true);
                $result=$pr->getValue($controller);
            }

            return $result ?:"";
        }
        return "";
    }
}

?>