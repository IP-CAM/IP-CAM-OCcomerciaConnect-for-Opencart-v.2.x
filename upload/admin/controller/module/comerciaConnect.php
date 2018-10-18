<?php
include_once(DIR_SYSTEM . "/comercia/util.php");
if (version_compare(phpversion(), '5.5.0', '<') == true) {
    include_once(DIR_SYSTEM . "/library/comerciaConnectApi/helpers/cartesian_5_4.php");
} else {
    include_once(DIR_SYSTEM . "/library/comerciaConnectApi/helpers/cartesian.php");
}

define("CC_VERSION", "2.3");
define("CC_RELEASE", CC_VERSION . ".2");
define("CC_VERSION_URL", "https://api.github.com/repos/comercia-nl/OCcomerciaConnect/releases/latest");

define("CC_SYNC_METHOD_SINGLE", 0);
define("CC_SYNC_METHOD_MULTI", 1);

if (!defined("CC_HASH_LENGTH")) {
    define("CC_HASH_LENGTH", 10);
}


if (!defined("CC_BATCH_SIZE")) {
    define("CC_BATCH_SIZE", 100);
}

if (!defined("CC_DEBUG")) {
    define("CC_DEBUG", false);
}


if (!defined("CC_TMP")) {
    define("CC_TMP", sys_get_temp_dir());
}
if (!defined("CC_PATH_LOG")) {
    define("CC_PATH_LOG", DIR_LOGS . "cc.log");
}

use comercia\Util;
use comerciaConnect\logic\Website;

class ControllerModuleComerciaConnect extends Controller
{

    static $subHash = "";
    private $error = array();

    public function index()
    {

        //this is how we reconize a corrupted ccSync folder from before 2.2.4
        if(file_exists(DIR_APPLICATION."model/ccSync/3_export_order.php")){
            //if corrupted, do a dummy update
            Util::response()->redirect("module/comerciaConnect/update");
        }

        if (isset($_POST["SimpleConnect"])) {
            return $this->simpleConnect();
        }
        //initial load
        $data = array("version" => CC_RELEASE);
        Util::load()->language('module/comerciaConnect', $data);
        $form = Util::form($data);

        $form->finish(function ($data) {

            $stores = Util::info()->stores();
            foreach ($stores as $store) {
                $configSet = Util::request()->post()->allPrefixed($store["store_id"] . "_");
                if (!$store["store_id"]) {
                    $configSet = array_merge($configSet, Util::request()->post()->allPrefixed("comerciaConnect", false));
                }
                Util::config($store["store_id"])->set("comerciaConnect", $configSet);
            }

            Util::session()->success = @$data['msg_settings_saved']?:"";
        });


        $form->selectboxOptions("syncMethods")
            ->add($data["syncMethod_single"], CC_SYNC_METHOD_SINGLE)
            ->add($data["syncMethod_multi"], CC_SYNC_METHOD_MULTI);


        //title
        Util::document()
            ->setTitle(Util::language()->heading_title)
            ->addStyle("view/stylesheet/comerciaConnect.css")
            ->addScript("view/javascript/comerciaConnect.js");


        //place the prepared data into the form
        $form
            ->fillFromSessionClear("error_warning", "success");


        $syncModelFields = array_map(function ($syncModel) {
            return "comerciaConnect_sync_" . $syncModel;
        }, Util::load()->model("module/comerciaconnect/general")->getSyncModels());

        $formGeneralFields = array_merge($syncModelFields, ["comerciaConnect_syncMethod"]);

        $form
            ->fillFromPost($formGeneralFields)
            ->fillFromConfig($formGeneralFields);

        $storeFormFields = array("comerciaConnect_status", "comerciaConnect_base_url", "comerciaConnect_auth_url", "comerciaConnect_api_key", "comerciaConnect_api_url");
        $data['stores'] = Util::info()->stores();
        foreach ($data['stores'] as &$store) {

            Util::form($store, $store["store_id"])
                ->fillFromPost($storeFormFields)
                ->fillFromConfig($storeFormFields);


            //set up api session
            $connect = Util::load()->library("comerciaConnect");
            $api = $connect->getApi(
                $store['comerciaConnect_base_url'],
                $store['comerciaConnect_auth_url'],
                $store['comerciaConnect_api_url']
            );

            $apiSession = $api->createSession($store['comerciaConnect_api_key']);
            $website = Website::getWebsite($apiSession);
            if ($website) {
                $store['control_panel_url'] = $website->controlPanelUrl();
                $store['login_success'] = true;
            } else {
                $store['control_panel_url'] = false;
                $store['login_success'] = false;
            }

            $store['sync_url'] = Util::url()->link('module/comerciaConnect/sync', "store_id=" . $store["store_id"]);

        }

        Util::breadcrumb($data)
            ->add("text_home", "common/home")
            ->add("settings_title", "module/comerciaConnect");


        //actions
        $data['action'] = Util::url()->link('module/comerciaConnect');
        $data['cancel'] = Util::url()->link('modules');
        $data['simple_connect_url'] = Util::url()->link('module/comerciaConnect/simpleConnect');
        $data['update_url'] = $this->getUpdateUrl();

        $data["sync_models"] = array_map(function ($syncModel) {
            $langKey = "sync_model_" . substr($syncModel, strpos($syncModel, "_") + 1);
            return [
                "key" => "comerciaConnect_sync_" . $syncModel,
                "text" => Util::language()->get($langKey)
            ];
        },
            Util::load()->model("module/comerciaconnect/general")->getSyncModels());

        //This god mode is for development troubleshooting purposes
        if (Util::request()->get()->mode == "CCG0D") {
            $data["godMode"] = true;
            $data["syncModels"] = [];

            $dir = DIR_APPLICATION . 'model/ccSync';
            if ($handle = opendir($dir)) {
                while (false !== ($entry = readdir($handle))) {
                    if ($entry != '.' && $entry != '..' && !is_dir($dir . '/' . $entry) && substr($entry, -3) === 'php') {
                        $data["syncModels"][] = substr($entry, 0, -4);
                    }
                }

                sort($data["syncModels"]);
                closedir($handle);
            }
        } else {
            $data["godMode"] = false;
        }

        Util::response()->view("comerciaConnect/form", $data);
    }

    public function simpleConnect()
    {
        if (Util::request()->server()->REQUEST_METHOD != 'POST') {
            $data = array();
            Util::load()->language("module/comerciaConnect", $data);
            if (defined("CC_URL")) {
                $url = CC_URL;
            } else {
                $url = "https://app.comerciaconnect.nl";
            }
            $url .= "/index.php?route=simpleConnect/start/" . base64_encode(Util::url()->link("module/comerciaConnect/sync", "mode=api&store_id=" . Util::request()->get()->store_id, true, false));
            Util::response()->redirectToUrl($url);
        } else {
            $data = array();
            $data["base_url"] = Util::request()->post()->baseUrl;
            $data["auth_url"] = Util::request()->post()->authUrl;
            $data["api_url"] = Util::request()->post()->apiUrl;
            $data["key"] = Util::request()->post()->key;
            Util::response()->view("comerciaConnect/simpleConnect_finish", $data);
        }
    }

    protected function validate()
    {
        return true;
    }

    function patch()
    {
        Util::patch()->runPatchesFromFolder('comerciaConnect', __FILE__);
    }

    function sync()
    {
        global $is_in_debug;

        $this->patch();

        $syncMethod = Util::config()->comerciaConnect_syncMethod;
        $storeId = Util::request()->get()->store_id ?: 0;
        $status = Util::config($storeId)->get("comerciaConnect_status", true);
        if ($status) {

            if (util::request()->get()->reset) {
                $this->db->query("update `" . DB_PREFIX . "product` set ccHash=''");
                $this->db->query("update `" . DB_PREFIX . "order` set ccHash=''");
                $this->db->query("update `" . DB_PREFIX . "category` set ccHash=''");
            }

            //prepare variables
            $baseUrl = Util::config($storeId)->comerciaConnect_base_url;
            $authUrl = Util::config($storeId)->comerciaConnect_auth_url;
            $apiKey = Util::config($storeId)->get("comerciaConnect_api_key", true);
            $apiUrl = Util::config($storeId)->comerciaConnect_api_url;

            self::$subHash = md5($apiKey . "_" . CC_VERSION);

            //create session
            $connect = Util::load()->library("comerciaConnect");
            $api = $connect->getApi($baseUrl, $authUrl, $apiUrl);

            //load models
            $data = (object)[
                'syncMethod' => $syncMethod,
                'storeId' => $storeId,
                'productModel' => Util::load()->model("catalog/product"),
                'optionModel' => Util::load()->model("catalog/option"),
                'categoryModel' => Util::load()->model("catalog/category"),
                'ccOrderModel' => Util::load()->model("module/comerciaconnect/order"),
                'ccProductModel' => Util::load()->model("module/comerciaconnect/product"),
                'ccGeneralModel' => Util::load()->model("module/comerciaconnect/general"),
                'orderModel' => Util::load()->model("sale/order"),
                'session' => $api->createSession($apiKey),
            ];

            $syncModels = Util::load()->model("module/comerciaconnect/general")->getSyncModels();

            foreach ($syncModels as $model) {
                \comerciaConnect\lib\Debug::writeMemory("started sync " . $model);
                if (
                    (!Util::request()->get()->syncModel && Util::config()->get("comerciaConnect_sync_" . $model))
                    || $model == Util::request()->get()->syncModel
                ) {
                    Util::load()->model('ccSync/' . $model)->sync($data);
                } else {
                    $modelObj = Util::load()->model('ccSync/' . $model);
                    if (method_exists($modelObj, "resultOnly")) {
                        $modelObj->resultOnly($data);
                    }
                }
                \comerciaConnect\lib\Debug::writeMemory("finished sync " . $model);
            }
        }

        if (@$this->request->get['mode'] == "api") {
            header("content-type:application/json");
            echo $status ? "true" : "false";
        } else {
            Util::response()->redirectBack();
        }
    }

    private function getUpdateUrl()
    {
        $client = new \comerciaConnect\lib\HttpClient();
        $info = $client->get(CC_VERSION_URL);
        if ($info["tag_name"] && $info["tag_name"] != CC_RELEASE && version_compare(CC_RELEASE, $info["tag_name"], "<")) {
            return Util::url()->link('module/comerciaConnect/update');
        }
        return false;
    }

    function update()
    {
        //load cc module for libraries
        $connect = Util::load()->library("comerciaConnect");
        $connect->getApi("");

        //get info
        $client = new \comerciaConnect\lib\HttpClient();
        $info = $client->get(CC_VERSION_URL);

        //save tmp file
        $temp_file = CC_TMP . "/ccUpdate.zip";
        $handle = fopen($temp_file, "w+");
        $content = $client->get($info["zipball_url"], false, false);
        fwrite($handle, $content);
        fclose($handle);


        //extract to temp dir
        $temp_dir = CC_TMP . "/ccUpdate";
        if (class_exists("ZipArchive")) {
            $zip = new ZipArchive;
            $zip->open($temp_file);
            $zip->extractTo($temp_dir);
            $zip->close();
        } else {
            shell_exec("unzip " . $temp_file . " -d " . $temp_dir);
        }

        //find upload path

        $handle = opendir($temp_dir);
        $upload_dir = $temp_dir . "/upload";
        while ($file = readdir($handle)) {
            if ($file != "." && $file != ".." && is_dir($temp_dir . "/" . $file . "/upload")) {
                $upload_dir = $temp_dir . "/" . $file . "/upload";
                break;
            }
        }


        //delete sync models, they can potentially cause problems.
        if(is_dir(DIR_APPLICATION."model/ccSync")){
            $this->rmDirRecursive(DIR_APPLICATION."model/ccSync");
        }

        //copy files
        $handle = opendir($upload_dir);
        while ($file = readdir($handle)) {
            if ($file != "." && $file != "..") {
                $from = $upload_dir . "/" . $file;
                if ($file == "admin") {
                    $to = DIR_APPLICATION;
                } elseif ($file == "system") {
                    $to = DIR_SYSTEM;
                } else {
                    $to = DIR_CATALOG . "../" . $file . "/";
                }
                $this->cpy($from, $to);
            }

        }

        //cleanup
        unlink($temp_file);
        $this->rmDirRecursive($temp_dir);

        //go back
        Util::response()->redirectBack();
    }

    public function rmDirRecursive($dir)
    {
        $files = array_diff(scandir($dir), array('.', '..'));
        foreach ($files as $file) {
            (is_dir("$dir/$file")) ? $this->rmDirRecursive("$dir/$file") : unlink("$dir/$file");
        }
        return rmdir($dir);
    }

    function cpy($source, $dest)
    {
        if (is_dir($source)) {
            $dir_handle = opendir($source);
            while ($file = readdir($dir_handle)) {
                if ($file != "." && $file != "..") {
                    if (is_dir($source . "/" . $file)) {
                        if (!is_dir($dest . "/" . $file)) {
                            mkdir($dest . "/" . $file);
                        }
                        $this->cpy($source . "/" . $file, $dest . "/" . $file);
                    } else {
                        copy($source . "/" . $file, $dest . "/" . $file);
                    }
                }
            }
            closedir($dir_handle);
        } else {
            copy($source, $dest);
        }
    }


    static function getWebsiteUrl()
    {
        static $url = "unset";
        if ($url == "unset") {
            $connect = Util::load()->library("comerciaConnect");
            $baseUrl = Util::config()->comerciaConnect_base_url;
            $authUrl = Util::config()->comerciaConnect_auth_url;
            $apiKey = Util::config()->comerciaConnect_api_key;
            $apiUrl = Util::config()->comerciaConnect_api_url;
            $api = $connect->getApi($baseUrl, $authUrl, $apiUrl);
            $session = $api->createSession($apiKey);
            $website = Website::getWebsite($session);
            if ($website) {
                $url = $website->controlPanelUrl();;
            } else {
                $url = false;
            }
        }
        return $url;
    }

    function renderPurchaseButton($purchaseId)
    {
        $url = self::getWebsiteUrl();
        if ($url && Util::load()->model("module/comerciaconnect/order")->isHashed($purchaseId)) {
            $url .= "/content/purchase/infoSite/" . $purchaseId;
            $data["url"] = $url;
            Util::load()->language('module/comerciaConnect', $data);
            $content = Util::load()->view("comerciaConnect/ccButton", $data);
            return $content;
        }
    }

    function renderProductButton($productId)
    {
        $url = self::getWebsiteUrl();

        if ($url && Util::load()->model("module/comerciaconnect/product")->isHashed($productId)) {
            $url .= "/content/product/infoSite/" . $productId;
            $data["url"] = $url;
            Util::load()->language('module/comerciaConnect', $data);
            $content = Util::load()->view("comerciaConnect/ccButton", $data);
            return $content;
        }
    }
}
