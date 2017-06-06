<?php
include_once(DIR_SYSTEM . "/comercia/util.php");
use comercia\Util;
use comerciaConnect\logic\Product;
use comerciaConnect\logic\Purchase;
use comerciaConnect\logic\Website;

class ControllerModulecomerciaConnect extends Controller
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

    public function simpleconnect()
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
        $orderModel=Util::load()->model("sale/order");

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

        $categories = $categoryModel->getCategories();
        $categoriesMap = array();
        foreach ($categories as $category) {
            $category = $categoryModel->getCategory($category['category_id']);
            $apiCategory = $ccProductModel->sendCategoryToApi($category, $session);
            $categoriesMap[$category["category_id"]] = $apiCategory;
        }

        //export products
        $products = $productModel->getProducts();
        $productMap = array();

        foreach ($products as $product) {
            $productMap[$product["product_id"]] = $ccProductModel->sendProductToApi($product, $session, $categoriesMap);

            $productOptionMap = array();
            $productOptions = $productModel->getProductOptions($product['product_id']);

            foreach ($productOptions as $productOption) {
                $productOptionMap[$productOption['option_id']] = array_map(function ($productOptionValue) use ($optionModel) {
                    $productOptionValue['full_value'] = $optionModel->getOptionValue($productOptionValue['option_value_id']);
                    return $productOptionValue;
                }, $productOption['product_option_value']);
            }

            foreach ($this->cartesian($productOptionMap) as $child) {
                $this->createChildProduct($session, $child, $productMap[$product["product_id"]]);
            }
        }

        //export orders
        $orders = $orderModel->getOrders();
        foreach ($orders as $order) {
            $ccOrderModel->sendOrderToApi($order, $session, $productMap);
        }
        Util::config()->set("comerciaConnect", 'comerciaConnect_last_sync', time());

        //import products
        $filter = Product::createFilter($session);
        $filter->filter("lastTouchedBy", TOUCHED_BY_API, "!=");
        $filter->filter("lastUpdate", $lastSync, ">");
        $filter->filter("type", PRODUCT_TYPE_PRODUCT);
        $filter->filter("parent_product_id", "null", "IS");
        $products = $filter->getData();

        foreach ($products as $product) {
            $ccProductModel->saveProduct($product);
        }

        //import orders
        $filter = Purchase::createFilter($session);
        $filter->filter("lastTouchedBy", TOUCHED_BY_API, "!=");
        $filter->filter("lastUpdate", $lastSync, ">");
        $orders = $filter->getData();

        foreach ($orders as $order) {
            $ccOrderModel->saveOrder($order);
        }

        Util::config()->set("comerciaConnect", 'comerciaConnect_last_sync', time());
        Util::response()->redirect("module/comerciaConnect");
    }

    private function cartesian($input)
    {
        if ($input) {
            if ($layer = array_pop($input)) //If there is data in the array
                foreach ($this->cartesian($input) as $cartesian) //Recursively loop through the array
                    foreach ($layer as $value) { //Loop through the cartesian result
                        yield $cartesian + [count($cartesian) => $value]; //Return single item
                    }
        } else
            yield array(); //No input means empty array to avoid complicated if statements later
    }

    function createChildProduct($session, $child, $parent)
    {
        $id = $parent->id . '_';
        $name = $parent->name . ' - ';
        $price = $parent->price;
        $quantity = $parent->quantity;
        foreach ($child as $key => $value) {
            if ($value['quantity'] < $quantity) {
                $quantity = $value['quantity'];
            }
            $price = ($value['price_prefix'] == '-') ? $price - (float)$value['price'] : $price + (float)$value['price'];
            $name .= $value['full_value']['name'] . ' ';
            $id .= $value['option_value_id'] . '_';
        }
        $product = new Product($session, [
            'id' => rtrim($id, '_'),
            'name' => rtrim($name),
            'quantity' => $quantity,
            'price' => $price,
            'descriptions' => $parent->descriptions,
            'categories' => $parent->categories,
            'taxGroup' => $parent->taxGroup,
            'type' => PRODUCT_TYPE_PRODUCT,
            'code' => $parent->code . '_' . $id,
            'image' => $parent->image,
            'brand' => $parent->brand,
            'parent' => $parent
        ]);

        $product->save();
    }
}