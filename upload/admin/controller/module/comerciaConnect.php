<?php
include_once(DIR_SYSTEM . "/comercia/util.php");
if (version_compare(phpversion(), '5.5.0', '<') == true) {
    include_once(DIR_SYSTEM . "/library/comerciaConnectApi/helpers/cartesian_5_4.php");
} else {
    include_once(DIR_SYSTEM . "/library/comerciaConnectApi/helpers/cartesian.php");
}

use comercia\Util;
use comerciaConnect\logic\Product;
use comerciaConnect\logic\Purchase;
use comerciaConnect\logic\Website;

class ControllerModuleComerciaConnect extends Controller
{
    private $error = array();

    public function index()
    {
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


        $formFields = array("comerciaConnect_status", "comerciaConnect_auth_url", "comerciaConnect_api_key", "comerciaConnect_api_url", "comerciaConnect_last_sync");
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
            Util::response()->view("module/comerciaConnect_simpleConnect", $data);
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

    function sync()
    {
        global $is_in_debug;
        //  $is_in_debug=true;

        //load models
        $productModel = Util::load()->model("catalog/product");
        $optionModel = Util::load()->model("catalog/option");
        $categoryModel = Util::load()->model("catalog/category");
        $ccOrderModel = Util::load()->model("module/comerciaconnect/order");
        $ccProductModel = Util::load()->model("module/comerciaconnect/product");
        $orderModel = Util::load()->model("sale/order");

        //last sync
        $lastSync = Util::config()->comerciaConnect_last_sync;
        if (!$lastSync) {
            //make sure lastSync is an int. and not an empty string.
            $lastSync = 0;
        }

        //prepare variables
        $authUrl = Util::config()->comerciaConnect_auth_url;
        $apiKey = Util::config()->comerciaConnect_api_key;
        $apiUrl = Util::config()->comerciaConnect_api_url;


        //create session
        $connect = Util::load()->library("comerciaConnect");
        $api = $connect->getApi($authUrl, $apiUrl);
        $session = $api->createSession($apiKey);

        //export categories
        $categories = $categoryModel->getCategories(array());
        $categoriesMap = array();
        $categoriesChanged = array();
        foreach ($categories as $category) {
            $category = $categoryModel->getCategory($category['category_id']);
            $apiCategory = $ccProductModel->createApiCategory($category, $session);
            if (strtotime($category["date_modified"]) > $lastSync) {
                $categoriesChanged[]=$apiCategory;
            }
            $categoriesMap[$category["category_id"]] = $apiCategory;
        }

        if (count($categoriesChanged)) {
            $ccProductModel->sendCategoryToApi($categoriesChanged,$session);
            $ccProductModel->updateCategoryStructure($session, $categories);
        }

        //export products
        $products = $productModel->getProducts();
        $productMap = array();
        $productsChanged=array();
        foreach ($products as $product) {
            $apiProduct = $ccProductModel->createApiProduct($product, $session, $categoriesMap);
            $productMap[$product["product_id"]] = $apiProduct;


            //save product to comercia connect
            if (strtotime($product['date_modified']) > $this->config->get('comerciaConnect_last_sync')) {
                $productsChanged[]=$apiProduct;


                $productOptionMap = array();
                $productOptions = $productModel->getProductOptions($product['product_id']);

                foreach ($productOptions as $productOption) {
                    $productOptionMap[$productOption['option_id']] = array_map(function ($productOptionValue) use ($optionModel) {
                        $productOptionValue['full_value'] = $optionModel->getOptionValue($productOptionValue['option_value_id']);
                        return $productOptionValue;
                    }, $productOption['product_option_value']);
                }

                if (count($productOptionMap) > 0) {
                    foreach (cc_cartesian($productOptionMap) as $child) {
                        $productsChanged[]=$this->createChildProduct($session, $child, $productMap[$product["product_id"]]);
                    }
                }
            }
        }

        if(count($productsChanged)){
            $ccProductModel->sendProductToApi($productsChanged,$session);
        }

        //export orders
        $orders = $ccOrderModel->getOrders();
        $ordersChanged=array();
        foreach ($orders as $order) {
            if (strtotime($order['date_modified']) > $this->config->get('comerciaConnect_last_sync')) {
                $ordersChanged[] = $ccOrderModel->createApiOrder($order, $session, $productMap);
            }
        }
        if(count($ordersChanged)){
            $ccOrderModel->sendOrderToApi($ordersChanged,$session);
        }


        Util::config()->set("comerciaConnect", 'comerciaConnect_last_sync', time());

        //import products
        $filter = Product::createFilter($session);
        $filter->filter("lastTouchedBy", TOUCHED_BY_API, "!=");
        $filter->filter("lastUpdate", $lastSync, ">");
        $filter->filter("type", PRODUCT_TYPE_PRODUCT);
        $filter->filter("parent_product_id", "0", "=");
        $products = $filter->getData();

        foreach ($products as $product) {
            $ccProductModel->saveProduct($product);
        }

        $ccProductModel->touchBatch($session,$products);

        //import orders
        $filter = Purchase::createFilter($session);
        $filter->filter("lastTouchedBy", TOUCHED_BY_API, "!=");
        $filter->filter("lastUpdate", $lastSync, ">");
        $orders = $filter->getData();

        foreach ($orders as $order) {
            $ccOrderModel->saveOrder($order);
        }
        $ccOrderModel->touchBatch($session,$order);

        Util::config()->set("comerciaConnect", 'comerciaConnect_last_sync', time());
        if (@$this->request->get['mode'] == "api") {
            header("content-type:application/json");
            echo "true";
        } else {
            Util::response()->redirect("module/comerciaConnect");
        }
    }




}
