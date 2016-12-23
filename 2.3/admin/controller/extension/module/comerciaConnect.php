<?php

use comerciaConnect\logic\ProductCategory;
use comerciaConnect\logic\ProductDescription;
use comerciaConnect\logic\Purchase;
use comerciaConnect\logic\Website;

class ControllerextensionmodulecomerciaConnect extends Controller
{
    private $error = array();

    public function index()
    {
        //initial load
        $this->load->language('extension/module/comerciaConnect');
        $this->load->model('setting/setting');
        $this->document->setTitle($this->language->get('heading_title'));


        //save it
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting('comerciaConnect', $this->request->post);

            $this->session->data['success'] = $this->language->get('text_success');
            $this->response->redirect($this->url->link('extension/extension', 'token=' . $this->session->data['token'] . '&type=module', true));
        }

        //text
        $data['heading_title'] = $this->language->get('heading_title');

        $data['text_edit'] = $this->language->get('text_edit');
        $data['text_enabled'] = $this->language->get('text_enabled');
        $data['text_disabled'] = $this->language->get('text_disabled');

        $data['entry_status'] = $this->language->get('entry_status');
        $data['entry_auth_url'] = $this->language->get('entry_auth_url');
        $data['entry_api_url'] = $this->language->get('entry_api_url');
        $data['entry_api_key'] = $this->language->get('entry_api_key');

        $data['button_save'] = $this->language->get('button_save');
        $data['button_cancel'] = $this->language->get('button_cancel');

        $data['button_sync'] = $this->language->get('button_sync');
        $data['button_control_panel']=$this->language->get('button_control_panel');
        $data['text_actions'] = $this->language->get('text_actions');

        $data["entry_simple_connect"]=$this->language->get("entry_simple_connect");
        $data["text_simple_connect"]=$this->language->get("text_simple_connect");
        $data["button_simple_connect_start"]=$this->language->get("button_simple_connect_start");
        $data["button_simple_connect"]=$this->language->get("button_simple_connect");
        $data["title_simple_connect"]=$this->language->get("button_simple_connect");

        //breadcrumb
        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'token=' . $this->session->data['token'], true),
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link('extension/extension', 'token=' . $this->session->data['token'] . '&type=module', true),
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/module/comerciaConnect', 'token=' . $this->session->data['token'], true)
        );

        //the rest of the page
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');


        //fields
        if (isset($this->request->post['comerciaConnect_status'])) {
            $data['comerciaConnect_status'] = $this->request->post['comerciaConnect_status'];
        } else {
            $data['comerciaConnect_status'] = $this->config->get('comerciaConnect_status');
        }

        if (isset($this->request->post['comerciaConnect_auth_url'])) {
            $data['comerciaConnect_auth_url'] = $this->request->post['comerciaConnect_auth_url'];
        } else {
            $data['comerciaConnect_auth_url'] = $this->config->get('comerciaConnect_auth_url');
        }


        if (isset($this->request->post['comerciaConnect_api_key'])) {
            $data['comerciaConnect_api_key'] = $this->request->post['comerciaConnect_api_key'];
        } else {
            $data['comerciaConnect_api_key'] = $this->config->get('comerciaConnect_api_key');
        }

        if (isset($this->request->post['comerciaConnect_api_url'])) {
            $data['comerciaConnect_api_url'] = $this->request->post['comerciaConnect_api_url'];
        } else {
            $data['comerciaConnect_api_url'] = $this->config->get('comerciaConnect_api_url');
        }

        //set up api session
        $this->load->library("comerciaConnect");
        $api = $this->comerciaConnect->getApi($data['comerciaConnect_auth_url'], $data['comerciaConnect_api_url']);
        $apiSession = $api->createSession($data['comerciaConnect_api_key']);
        $website=Website::getWebsite($apiSession);

        //actions
        $data['action'] = $this->url->link('extension/module/comerciaConnect', 'token=' . $this->session->data['token'], true);
        $data['sync_url'] = $this->url->link('extension/module/comerciaConnect/sync', 'token=' . $this->session->data['token'], true);
        $data['simple_connect_url'] = $this->url->link('extension/module/comerciaConnect/simpleConnect', 'token=' . $this->session->data['token'], true);

        if($website) {
            $data['control_panel_url'] = $website->controlPanelUrl();
            $data['login_success']=true;
        }else{
            $data['control_panel_url']=false;
            $data['login_success']=false;
        }

        $this->response->setOutput($this->load->view('extension/module/comerciaConnect', $data));
    }

    public function simpleconnect(){
        if ($this->request->server['REQUEST_METHOD'] != 'POST') {
            $this->load->language('extension/module/comerciaConnect');
            $data["entry_simple_connect"] = $this->language->get("entry_simple_connect");
            $data["text_simple_connect"] = $this->language->get("text_simple_connect");
            $data["button_simple_connect_start"] = $this->language->get("button_simple_connect_start");
            $data["button_simple_connect"] = $this->language->get("button_simple_connect");
            $data["title_simple_connect"] = $this->language->get("button_simple_connect");
            $this->response->setOutput($this->load->view('extension/module/comerciaConnect_simpleConnect', $data));
        }else{
           $data["auth_url"]=$this->request->post["authUrl"];
            $data["api_url"]=$this->request->post["apiUrl"];
            $data["key"]=$this->request->post["key"];
            $this->response->setOutput($this->load->view('extension/module/comerciaConnect_simpleConnect_finish', $data));
        }
    }

    protected function validate()
    {
        return true;
    }

    public function install()
    {
        $this->load->model('extension/event');

        $this->model_extension_event->addEvent('pp_login', 'catalog/controller/account/logout/after', 'extension/module/pp_login/logout');
    }

    function sync()
    {
        global $is_in_debug;
        //  $is_in_debug=true;
        //load models
        $this->load->model("catalog/product");
        $this->load->model("catalog/category");
        $this->load->model("sale/order");
        $this->load->model("extension/comerciaconnect/order");
        $this->load->model("extension/comerciaconnect/product");
        $this->load->model("localisation/language");

        //last sync
        $lastSync= $this->config->get('comerciaConnect_last_sync');
        if(!$lastSync){
            //make sure lastSync is an int. and not an empty string.
            $lastSync=0;
        }


        //prepare variables
        $authUrl = $this->config->get('comerciaConnect_auth_url');
        $apiKey = $this->config->get('comerciaConnect_api_key');
        $apiUrl = $this->config->get('comerciaConnect_api_url');

        //create session
        $this->load->library("comerciaConnect");
        $api = $this->comerciaConnect->getApi($authUrl, $apiUrl);
        $session = $api->createSession($apiKey);

        //export categories
        //todo:filter on last sync date
        $categories = $this->model_catalog_category->getCategories();
        $categoriesMap = array();
        foreach ($categories as $category) {
            $apiCategory= $this->model_extension_comerciaconnect_product->sendCategoryToApi($category,$session);
            $categoriesMap[$category["category_id"]] = $apiCategory;
        }

        //export products
        //todo:filter on last sync date
        $products = $this->model_catalog_product->getProducts();
        $productMap=array();
        foreach ($products as $product) {
            //add descriptions
            $productMap[$product["product_id"]]=$this->model_extension_comerciaconnect_product->sendProductToApi($product,$session,$categoriesMap);
        }

        //export orders
        //todo:filter on last sync date
        $orders = $this->model_sale_order->getOrders();
        foreach ($orders as $order) {
            $this->model_extension_comerciaconnect_order->sendOrderToApi($order,$session,$productMap);
        }
        $this->model_setting_setting->editSetting('comerciaConnect', array("comerciaConnect_last_sync",time()));

        //import orders
        $filter = Purchase::createFilter($session);
        $filter->filter("lastTouchedBy", TOUCHED_BY_API, "!=");
        $filter->filter("lastUpdate",$lastSync,">");
        $orders = $filter->getData();
        foreach ($orders as $order) {
            $this->model_extension_comerciaconnect_order->addOrder($order);
        }

        //import products
        $filter = Product::createFilter($session);
        $filter->filter("lastTouchedBy", TOUCHED_BY_API, "!=");
        $filter->filter("lastUpdate",$lastSync,">");
        $products = $filter->getData();
        foreach ($products as $product) {
            $this->model_extension_comerciaconnect_product->addProduct($product);
        }


        $this->model_setting_setting->editSetting('comerciaConnect', array("comerciaConnect_last_sync",time()));
    }

}