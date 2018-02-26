<?php
include_once(DIR_SYSTEM . "/comercia/util.php");
if (version_compare(phpversion(), '5.5.0', '<') == true) {
    include_once(DIR_SYSTEM . "/library/comerciaConnectApi/helpers/cartesian_5_4.php");
} else {
    include_once(DIR_SYSTEM . "/library/comerciaConnectApi/helpers/cartesian.php");
}

if (!defined("CC_VERSION")) {
    define("CC_VERSION", "1.4");
}

if (!defined("CC_RELEASE")) {
    define("CC_RELEASE",CC_VERSION.".1");
}

if(!defined("CC_VERSION_URL")){
    define("CC_VERSION_URL","https://api.github.com/repos/comercia-nl/OCcomerciaConnect/releases/latest");
}

if(!defined("CC_BATCH_SIZE")){
    define("CC_BATCH_SIZE",100);
}

use comercia\Util;
use comerciaConnect\logic\Website;

class ControllerModuleComerciaConnect extends Controller
{

    static $subHash = "";
    private $error = array();

    public function index()
    {
        if (isset($_POST["SimpleConnect"])) {
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


        $formFields = array("comerciaConnect_status", "comerciaConnect_base_url", "comerciaConnect_auth_url", "comerciaConnect_api_key", "comerciaConnect_api_url");
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
        $api = $connect->getApi($data['comerciaConnect_base_url'], $data['comerciaConnect_auth_url'], $data['comerciaConnect_api_url']);
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
        $data['cancel'] = Util::url()->link('modules');
        $data['sync_url'] = Util::url()->link('module/comerciaConnect/sync');
        $data['simple_connect_url'] = Util::url()->link('module/comerciaConnect/simpleConnect');
        $data['update_url']=$this->getUpdateUrl();


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
            $url .= "/index.php?route=simpleConnect";
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
        //  $is_in_debug=true;

        $this->patch();

        if (util::request()->get()->reset) {
            $this->db->query("update `" . DB_PREFIX . "product` set ccHash=''");
            $this->db->query("update `" . DB_PREFIX . "order` set ccHash=''");
            $this->db->query("update `" . DB_PREFIX . "category` set ccHash=''");
        }

        //prepare variables
        $baseUrl = Util::config()->comerciaConnect_base_url;
        $authUrl = Util::config()->comerciaConnect_auth_url;
        $apiKey = Util::config()->comerciaConnect_api_key;
        $apiUrl = Util::config()->comerciaConnect_api_url;

        self::$subHash = md5($apiKey . "_" . CC_VERSION);

        //create session
        $connect = Util::load()->library("comerciaConnect");
        $api = $connect->getApi($baseUrl, $authUrl, $apiUrl);

        //load models
        $data = (object)[
            'productModel' => Util::load()->model("catalog/product"),
            'optionModel' => Util::load()->model("catalog/option"),
            'categoryModel' => Util::load()->model("catalog/category"),
            'ccOrderModel' => Util::load()->model("module/comerciaconnect/order"),
            'ccProductModel' => Util::load()->model("module/comerciaconnect/product"),
            'orderModel' => Util::load()->model("sale/order"),
            'session' => $api->createSession($apiKey),
        ];


        if (Util::request()->get()->syncModel) {
            $syncModels = [Util::request()->get()->syncModel];
        } else {
            $dir = DIR_APPLICATION . 'model/ccSync';
            if ($handle = opendir($dir)) {
                while (false !== ($entry = readdir($handle))) {
                    if ($entry != '.' && $entry != '..' && !is_dir($dir . '/' . $entry) && substr($entry, -3) === 'php') {
                        $syncModels[] = substr($entry, 0, -4);
                    }
                }

                sort($syncModels);
                closedir($handle);
            }
        }

        foreach ($syncModels as $model) {
            Util::load()->model('ccSync/' . $model)->sync($data);
        }

        if (@$this->request->get['mode'] == "api") {
            header("content-type:application/json");
            echo "true";
        } else {
            Util::response()->redirectBack();
        }
    }

    private function getUpdateUrl()
    {
        $client=new \comerciaConnect\lib\HttpClient();
        $info=$client->get(CC_VERSION_URL);
        if($info["tag_name"] && $info["tag_name"]!=CC_RELEASE){
            return Util::url()->link('module/comerciaConnect/update');
        }
        return false;
    }

    function update(){
        //load cc module for libraries
        $connect = Util::load()->library("comerciaConnect");
        $connect->getApi("");

        //get info
        $client=new \comerciaConnect\lib\HttpClient();
        $info=$client->get(CC_VERSION_URL);

        //save tmp file
        $temp_file = sys_get_temp_dir()."/ccUpdate.zip";
        $handle=fopen($temp_file,"w+");
        $content=$client->get($info["zipball_url"],false,false);
        fwrite($handle,$content);
        fclose($handle);


        //extract to temp dir
        $temp_dir= sys_get_temp_dir()."/ccUpdate";
        if(class_exists("ZipArchive")){
            $zip = new ZipArchive;
            $zip->open(  $temp_file );
            $zip->extractTo($temp_dir);
            $zip->close();
        }else{
            shell_exec("unzip ".$temp_file." -d ".$temp_dir);
        }

        //find upload path

        $handle = opendir($temp_dir);
        $upload_dir=$temp_dir."/upload";
        while($file= readdir($handle)) {
            if ($file != "." && $file != ".." && is_dir($temp_dir."/".$file."/upload")) {
                $upload_dir=$temp_dir."/".$file."/upload";
                break;
            }
        }

        //copy files
        $handle = opendir($upload_dir);
        while($file= readdir($handle)) {
            if ($file != "." && $file != "..") {
                $from=$upload_dir."/".$file;
                if($file=="admin"){
                    $to=DIR_APPLICATION;
                }elseif($file=="system"){
                    $to=DIR_SYSTEM;
                }else{
                    $to=DIR_CATALOG."../".$file."/";
                }
                $this->cpy($from,$to);
            }

        }

        //cleanup
        unlink($temp_file);
        rmdir($temp_dir);

        //go back
        Util::response()->redirectBack();
    }

    function cpy($source, $dest){
        if(is_dir($source)) {
            $dir_handle=opendir($source);
            while($file=readdir($dir_handle)){
                if($file!="." && $file!=".."){
                    if(is_dir($source."/".$file)){
                        if(!is_dir($dest."/".$file)){
                            mkdir($dest."/".$file);
                        }
                        $this->cpy($source."/".$file, $dest."/".$file);
                    } else {
                        copy($source."/".$file, $dest."/".$file);
                    }
                }
            }
            closedir($dir_handle);
        } else {
            copy($source, $dest);
        }
    }



    static function getWebsiteUrl(){
        static $url="unset";
        if($url=="unset"){
            $connect = Util::load()->library("comerciaConnect");
            $baseUrl = Util::config()->comerciaConnect_base_url;
            $authUrl = Util::config()->comerciaConnect_auth_url;
            $apiKey = Util::config()->comerciaConnect_api_key;
            $apiUrl = Util::config()->comerciaConnect_api_url;
            $api = $connect->getApi($baseUrl, $authUrl, $apiUrl);
            $session = $api->createSession($apiKey);
            $website = Website::getWebsite($session);
            if ($website) {
                $url=$website->controlPanelUrl();;
            }else{
                $url=false;
            }
        }
        return $url;
    }

    function renderPurchaseButton($purchaseId){
        $url=self::getWebsiteUrl();
        if($url && Util::load()->model("module/comerciaconnect/order")->isHashed($purchaseId)){
            $url.="/content/purchase/infoSite/".$purchaseId;
            $data["url"]=$url;
            Util::load()->language('module/comerciaConnect', $data);
            $content=Util::load()->view("comerciaConnect/ccButton",$data);
            return $content;
        }
    }

    function renderProductButton($productId){
        $url=self::getWebsiteUrl();

        if($url && Util::load()->model("module/comerciaconnect/product")->isHashed($productId)){
            $url.="/content/product/infoSite/".$productId;
            $data["url"]=$url;
            Util::load()->language('module/comerciaConnect', $data);
            $content=Util::load()->view("comerciaConnect/ccButton",$data);
            return $content;
        }
    }
}
