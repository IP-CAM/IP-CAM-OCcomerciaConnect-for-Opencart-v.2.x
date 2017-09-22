<?php
include_once(DIR_SYSTEM . "/comercia/util.php");
if (version_compare(phpversion(), '5.5.0', '<') == true) {
    include_once(DIR_SYSTEM . "/library/comerciaConnectApi/helpers/cartesian_5_4.php");
} else {
    include_once(DIR_SYSTEM . "/library/comerciaConnectApi/helpers/cartesian.php");
}

use comercia\Util;
use comerciaConnect\logic\Website;

class ControllerModuleComerciaConnect extends Controller
{
    private $error = array();

    public function index()
    {
        if(isset($_POST["SimpleConnect"])) {
            return $this->simpleConnect();
        }
        //initial load
        $data = array();
        Util::load()->language('module/comerciaConnect', $data);
        $form = Util::form($data);

        $form->finish(function ($data) {
                Util::config()->set("comerciaConnect", Util::request()->post()->all());
                Util::session()->success = $data['msg_settings_saved'];
                Util::response()->redirect(Util::route()->extension());
        });

        //title
        Util::document()->setTitle(Util::language()->heading_title);


        $formFields = array("comerciaConnect_status", "comerciaConnect_auth_url", "comerciaConnect_api_key", "comerciaConnect_api_url");
        //place the prepared data into the form

        $form
            ->fillFromSessionClear("error_warning", "success")
            ->fillFromPost($formFields)
            ->fillFromConfig($formFields);

        Util::breadcrumb($data)
            ->add("text_home", "common/home")
            ->add("settings_title", "module/comerciaConnect");

        //set up api session
        $connect = Util::load()->library("comerciaConnect");
        $api = $connect->getApi($data['comerciaConnect_auth_url'], $data['comerciaConnect_api_url']);
        $apiSession = $api->createSession($data['comerciaConnect_api_key']);
        $website = Website::getWebsite($apiSession);

        if ($website) {
            $data['control_panel_url'] = $website->controlPanelUrl();
            $data['login_success'] = true;
        } else {
            $data['control_panel_url'] = false;
            $data['login_success'] = false;
        }

        //actions
        $data['action'] = Util::url()->link('module/comerciaConnect');
        $data['sync_url'] = Util::url()->link('module/comerciaConnect/sync');
        $data['simple_connect_url'] = Util::url()->link('module/comerciaConnect/simpleConnect');

        Util::response()->view("module/comerciaConnect", $data);
    }

    public function simpleConnect()
    {
        if (Util::request()->server()->REQUEST_METHOD != 'POST') {
            $data = array();
            Util::load()->language("module/comerciaConnect", $data);
            if(defined("CC_URL")){
                $url=CC_URL;
            }else{
                $url="https://app.comerciaconnect.nl";
            }
            $url.="/index.php?route=simpleConnect";
            Util::response()->redirectToUrl($url);
        } else {
            $data = array();
            $data["auth_url"] = Util::request()->post()->authUrl;
            $data["api_url"] = Util::request()->post()->apiUrl;
            $data["key"] = Util::request()->post()->key;
            Util::response()->view("module/comerciaConnect_simpleConnect_finish", $data);
        }
    }

    protected function validate()
    {
        return true;
    }

    function patch(){
        Util::patch()->runPatchesFromFolder('comerciaConnect',__FILE__);
    }

    function sync()
    {
        global $is_in_debug;
        //  $is_in_debug=true;

        $this->patch();

        //prepare variables
        $authUrl = Util::config()->comerciaConnect_auth_url;
        $apiKey = Util::config()->comerciaConnect_api_key;
        $apiUrl = Util::config()->comerciaConnect_api_url;

        //create session
        $connect = Util::load()->library("comerciaConnect");
        $api = $connect->getApi($authUrl, $apiUrl);

        //load models
        $data = (object)[
        	'productModel' => Util::load()->model("catalog/product"),
        	'optionModel' => Util::load()->model("catalog/option"),
        	'categoryModel' => Util::load()->model("catalog/category"),
        	'ccOrderModel' => Util::load()->model("module/comerciaconnect/order"),
        	'ccProductModel' => Util::load()->model("module/comerciaconnect/product"),
        	'orderModel' => Util::load()->model("sale/order"),
            'session' => $api->createSession($apiKey)
        ];

        $dir = DIR_APPLICATION . 'model/ccSync';
        if($handle = opendir($dir)) {
            while (false !== ($entry = readdir($handle))) {
                if ($entry != '.' && $entry != '..' && !is_dir($dir . '/' . $entry) && substr($entry, -3) === 'php') {
                    $syncModels[] = substr($entry, 0, -4);
                }
            }

            sort($syncModels);
            closedir($handle);
        }

        foreach ($syncModels as $model) {
            Util::load()->model('ccSync/' . $model)->sync($data);
        }

        if (@$this->request->get['mode'] == "api") {
            header("content-type:application/json");
            echo "true";
        } else {
            Util::response()->redirect("module/comerciaConnect");
        }
    }
}
