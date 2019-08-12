<?php

namespace comercia;
class Filesystem
{
    function removeDirectory($path)
    {
        $files = glob($path . '/*');
        foreach ($files as $file) {
            is_dir($file) ? $this->removeDirectory($file) : unlink($file);
        }
        rmdir($path);
    }

    function getLatestVersion($before, $after)
    {
        $possibilities = glob(DIR_APPLICATION . $before . "*" . $after);
        arsort($possibilities);
        reset($possibilities);
        if (count($possibilities)) {
            return str_replace(DIR_APPLICATION, "", $possibilities[0]);
        } else {
            return "";
        }
    }

    function search($dir, $pattern)
    {
        return glob($dir . $pattern . ".*");
    }

    function dirmtime($directory)
    {
        $last_modified_time = 0;
        $handler = opendir($directory);
        while ($file = readdir($handler)) {
            if (is_file($directory . DIRECTORY_SEPARATOR . $file)) {
                $files[] = $directory . DIRECTORY_SEPARATOR . $file;
                $filemtime = filemtime($directory . DIRECTORY_SEPARATOR . $file);
                if ($filemtime > $last_modified_time) {
                    $last_modified_time = $filemtime;
                }
            }
        }
        closedir($handler);
        return $last_modified_time;
    }

    function relativeAppPath($path, $env = false)
    {
        $dir_root = DIR_CATALOG . '/../';
        return str_replace($dir_root, "", $this->getAppPathForEnv($env)) . $path;
    }

    function getAppPathForEnv($env)
    {
        if ($env == "system") {
            return DIR_SYSTEM;
        } elseif ($env == "admin") {
            if (Util::info()->isInAdmin()) {
                return DIR_APPLICATION;
            } elseif (defined(DIR_ADMIN)) {
                return DIR_ADMIN;
            } else {
                return DIR_CATALOG . '/../' . "admin/";
            }
        } elseif ($env == "catalog") {
            if (Util::info()->isInAdmin()) {
                return DIR_CATALOG;
            } else {
                return DIR_APPLICATION;
            }
        }
        return DIR_APPLICATION;
    }

    function removeFile($file) {

        if(is_array($file)) {
            foreach($file as $file) {
                if(file_exists($file)) {
                    unlink($file);
                }
            }
        } else {
            if(file_exists($file)) {
                unlink($file);
            }
        }
    }
}
