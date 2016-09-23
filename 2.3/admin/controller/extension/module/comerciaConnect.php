<?php

use comerciaConnect\logic\ProductCategory;
use comerciaConnect\logic\ProductDescription;

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
        $data['text_sync'] = $this->language->get('text_sync');

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


        //actions
        $data['action'] = $this->url->link('extension/module/comerciaConnect', 'token=' . $this->session->data['token'], true);
        $data['sync_url'] = $this->url->link('extension/module/comerciaConnect/sync', 'token=' . $this->session->data['token'], true);

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

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


        $this->response->setOutput($this->load->view('extension/module/comerciaConnect', $data));
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
        $is_in_debug=true;
        //load models
        $this->load->model("catalog/product");
        $this->load->model("catalog/category");
        $this->load->model("localisation/language");

        //prepare variables
        $authUrl = $this->config->get('comerciaConnect_auth_url');
        $apiKey = $this->config->get('comerciaConnect_api_key');
        $apiUrl = $this->config->get('comerciaConnect_api_url');

        //create session
        $this->load->library("comerciaConnect");
        $api = $api = $this->comerciaConnect->getApi($authUrl, $apiUrl);
        $session = $api->createSession($apiKey);

        //synchronise categories
        $categories = $this->model_catalog_category->getCategories();
        $categoriesMap = arraY();
        foreach ($categories as $category) {
            $apiCategory = new ProductCategory($session);
            $apiCategory->name = $category["name"];
            $apiCategory->id = $category["category_id"];
            $apiCategory->save();
            $categoriesMap[$category["category_id"]] = $apiCategory;
        }

        $languages=$this->model_localisation_language->getLanguages();

        //synchronise products
        $products = $this->model_catalog_product->getProducts();
        foreach($products as $product){
            //add descriptions
            $productDescriptions=$this->model_catalog_product->getProductDescriptions($product["product_id"]);
            $descriptions=array();
            foreach($languages as $language){
                $descriptions[]= new ProductDescription($language["code"], $productDescriptions[$language["language_id"]]["name"], $productDescriptions[$language["language_id"]]["description"]);
            }

            //decide categories
            $productCategories=$this->model_catalog_product->getProductCategories($product["product_id"]);
            $categories=array();
            foreach($productCategories as $category) {
                $categories[]=$categoriesMap[$category["category_id"]];
            }

            //create new api product
            $apiProduct=new \comerciaConnect\logic\Product($session);

            //product basic information
            $apiProduct->id = $product["product_id"];
            $apiProduct->name = $product["name"];
            $apiProduct->quantity =$product["quantity"];
            $apiProduct->price = $product["price"];
            $apiProduct->url = HTTP_CATALOG;
            $apiProduct->ean=$product["ean"];
            $apiProduct->isbn=$product["isbn"];
            $apiProduct->sku=$product["sku"];


            //add arrays
            $apiProduct->categories=$categories;
            $apiProduct->descriptions=$descriptions;

            //save product to comercia connect
            $apiProduct->save();


        }

    }

}