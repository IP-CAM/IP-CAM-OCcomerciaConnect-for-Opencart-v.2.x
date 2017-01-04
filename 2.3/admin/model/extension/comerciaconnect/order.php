<?php

class ModelExtensionComerciaconnectOrder extends Model
{


    public function sendOrderToApi($order,$session,$productMap){
        $this->load->model("sale/order");
        $orderlines=array();
        $lines=$this->model_sale_order->getOrderProducts($order["order_id"]);

        foreach($lines as $line){
           $orderLines[] = new OrderLine(array(
                "product" => $productMap[$line["product_id"]],
                "price" => $line["price"],
                "quantity" => $line["quantity"],
                "tax" => $line["tax"],
                "priceWithTax"=>$line["tax"]+$line["price"],
                //todo: fix tax group
                "taxGroup"=>""
            ));
        }

        //todo:export payment methods and delivery methods works same as products.. check samples in api

        $purchase = new Purchase(
            $session,
            array(
                "id" => 1,
                "date" => time(),
                "status" => "processing",
                "email"=>"info@comercia.nl",
                "phonenumber"=>"0123456789",
                "deliveryAddress" => array(
                    "firstName" => $order["shipping_firstname"],
                    "lastName" => $order["shipping_lastname"],
                    "street" => $order["shipping_address_1"],
                    //todo: split street and number
                    "number" => "",
                    "postalCode" => $order["shipping_postcode"],
                    "city" =>$order["shipping_city"] ,
                    "province" => $order["shipping_zone"],
                    "country" => $order["shipping_country"]
                ),
                "invoiceAddress" => array(
                    "firstName" => $order["payment_firstname"],
                    "lastName" => $order["payment_lastname"],
                    "street" => $order["payment_address_1"],
                    //todo: split street and number
                    "number" => "",
                    "postalCode" => $order["payment_postcode"],
                    "city" =>$order["payment_city"] ,
                    "province" => $order["payment_zone"],
                    "country" => $order["payment_country"]
                ),
                "orderLines"=>$orderlines

            )
        );
    }


    function saveOrder($order)
    {
        $this->load->language("extension/module/comerciaConnect");

        //initialize some basic variables
        $dbOrderInfo = array();
        $dbOrderproducts = array();
        $totals = array();
        $dbOrderHistory = array();

        //basic info
        $dbOrderInfo["order_id"]=$order->id;
        $dbOrderInfo["invoice_no"] = 0;
        //todo: lets see in the future how we can get this multi store for now take the default.
        $dbOrderInfo["store_id"] = 0;
        $dbOrderInfo["store_name"] = $this->config->get('config_name');
        $dbOrderInfo["store_url"] = $this->getCatlogUrl();
        //todo: lets implement customers later.. for now leave it as a guest..
        $dbOrderInfo["customer_id"] = 0;
        $dbOrderInfo["customer_group_id"] = $dbOrderInfo["store_name"] = $this->config->get('config_customer_group_id');
        //todo:maybe implment this in the future in comercia connect
        $dbOrderInfo["comment"] = "";
        $dbOrderInfo["order_status_id"] = $this->getOrderStatusId($order->status);

        $dbOrderInfo["affiliate_id"] = 0;
        $dbOrderInfo["commission"] = 0;
        $dbOrderInfo["marketing_id"] = 0;
        $dbOrderInfo["tracking"] = "";
        $dbOrderInfo["language_id"] = $this->config->get('config_language_id');
        $dbOrderInfo["currency_id"] = $this->config->get('config_currency_id');
        $dbOrderInfo["currency_value"] = 1;
        $dbOrderInfo["ip"] = "";
        $dbOrderInfo["forwarded_ip"] = "";
        $dbOrderInfo["user_agent"] = "";
        $dbOrderInfo["accept_language"] = "";
        $dbOrderInfo["date_added"] = date('Y-m-d H:i:s');
        $dbOrderInfo["date_modified"] = date('Y-m-d H:i:s');

        //customer info
        $dbOrderInfo["firstname"] = $order->invoiceAddress->firstName;
        $dbOrderInfo["lastname"] = $order->invoiceAddress->lastName;
        $dbOrderInfo["email"] = $order->email;
        $dbOrderInfo["telephone"] = $order->phoneNumber;
        //todo: Implement fax later into Comercia Connect.
        $dbOrderInfo["fax"] = "";
        $dbOrderInfo["custom_field"] = "[]";

        //invoice info
        $dbOrderInfo["payment_firstname"] = $order->invoiceAddress->firstName;
        $dbOrderInfo["payment_lastname"] = $order->invoiceAddress->lastName;
        //todo: Implement company in comerciaConnect in the future
        $dbOrderInfo["payment_company"] = "";
        $dbOrderInfo["payment_address_1"] = $order->invoiceAddress->street . " " . $order->invoiceAddress->number . $order->invoiceAddress->suffix;
        $dbOrderInfo["payment_city"] = $order->invoiceAddress->city;
        $dbOrderInfo["payment_postcode"] = $order->invoiceAddress->postalCode;
        $dbOrderInfo["payment_country"] = $order->invoiceAddress->country;
        $dbOrderInfo["payment_country_id"] = $this->getCountryId($order->invoiceAddress->country);
        $dbOrderInfo["payment_zone"] = $order->invoiceAddress->province;
        $dbOrderInfo["payment_zone_id"] = $this->getZoneId($dbOrderInfo["payment_country_id"], $order->invoiceAddress->province);
        //todo: maybe make this configurable in the future?
        $dbOrderInfo["payment_address_format"] = "";
        $dbOrderInfo["payment_custom_field"] = "[]";

        //shippinginfo
        $dbOrderInfo["shipping_firstname"] = $order->deliveryAddress->firstName;
        $dbOrderInfo["shipping_lastname"] = $order->deliveryAddress->lastName;
        //todo: Implement company in comerciaConnect in the future
        $dbOrderInfo["shipping_company"] = "";
        $dbOrderInfo["shipping_address_1"] = $order->deliveryAddress->street . " " . $order->deliveryAddress->number . $order->deliveryAddress->suffix;
        $dbOrderInfo["shipping_city"] = $order->deliveryAddress->city;
        $dbOrderInfo["shipping_postcode"] = $order->deliveryAddress->postalCode;
        $dbOrderInfo["shipping_country"] = $order->deliveryAddress->country;
        $dbOrderInfo["shipping_country_id"] = $this->getCountryId($order->deliveryAddress->country);
        $dbOrderInfo["shipping_zone"] = $order->deliveryAddress->province;
        $dbOrderInfo["shipping_zone_id"] = $this->getZoneId($dbOrderInfo["shipping_country_id"], $order->deliveryAddress->province);
        //todo: maybe make this configurable in the future?
        $dbOrderInfo["shipping_address_format"] = "";
        $dbOrderInfo["shipping_custom_field"] = "[]";



        //calculate totals
        foreach ($order->orderLines as $orderLine) {
            if (@$orderLine->product->type == "shipping") {
                $dbOrderInfo["shipping_method"] = $orderLine->product->name;
                $dbOrderInfo["shipping_code"] = $orderLine->product->code;
                $this->addToTotals($totals, "shipping", $orderLine->product->name, $orderLine->price * $orderLine->quantity);
            } else if (@$order->product->type == "payment") {
                $dbOrderInfo["payment_method"] = $orderLine->product->name;
                $dbOrderInfo["payment_code"] = $orderLine->product->code;
                $this->addToTotals($totals, "payment", $orderLine->product->name, $orderLine->price * $orderLine->quantity);
            } else {
                $this->addToTotals($totals, "sub_total", $this->language->get("sub_total"), $orderLine->price * $orderLine->quantity);
            }
            $this->addToTotals($totals, "tax", $orderLine->taxGroup, $orderLine->tax);
        }
        $dbOrderInfo["total"] = $this->calculateTotalValue($totals);

        //complete and save the order
        $order_id = \comercia\Util::db()->saveDataObject("order", $dbOrderInfo);

        //order history
        $dbOrderHistory["order_id"] = $order_id;
        $dbOrderHistory["order_status_id"] = $dbOrderInfo["order_status_id"];
        $dbOrderHistory["notify"] = 0;
        $dbOrderHistory["comment"] = "";
        $dbOrderHistory["date_added"] = date('Y-m-d H:i:s');
        \comercia\Util::db()->saveDataObject("order_history", $dbOrderHistory);

        //add products
        foreach ($order->orderLines as $orderLine) {
            $product = array();
            $product["order_id"] = $order_id;
            $product["product_id"] = $orderLine->product->id;
            $product["name"] = $orderLine->product->name;
            $product["model"] = $orderLine->product->code;
            $product["quantity"]=$orderLine->quantity;
            $product["price"]=$orderLine->price;
            $product["total"]=$orderLine->price*$orderLine->quantity;
            $product["tax"]=$orderLine->tax;
            $product["reward"]=0;
            \comercia\Util::db()->saveDataObject("order_product",$product,array("order_id","product_id"));
        }

        $dbTotals=$this->totalsToDbTotals($totals);
        \comercia\Util::db()->saveDataObjectArray("order_total",$dbTotals);

    }

    private function totalsToDbTotals($totals)
    {
        $newTotals=array();
        foreach ($totals as $inCode) {
            foreach ($inCode as $total) {
                $newTotals[]=$total;
            }
        }
        return $newTotals;
    }

    private function calculateTotalValue(&$totals)
    {
        $totalValue = 0;
        foreach ($totals as $inCode) {
            foreach ($inCode as $total) {
                $totalValue += $total["value"];
            }
        }
        $this->addToTotals($totals, "total", $this->language->get("total"), $totalValue);
        return $totalValue;
    }

    private function addToTotals(&$totals, $code, $title, $value)
    {
        if (!isset($totals[$code][$title])) {
            $totals[$code][$title] =
                array(
                    "code" => $code,
                    "title" => $title,
                    "value" => $value,
                );
        } else {
            $totals[$code][$title]["value"] += $value;
        }
    }

    private function getOrderStatusId($name)
    {
        $countryQ = $this->db->query("select order_status_id from " . DB_PREFIX . "order_status where `name` like '" . $name . "'");
        if ($countryQ->num_rows) {
            return $countryQ->row["order_status_id"];
        }
        return $this->config->get("config_order_status_id");
    }

    private function getZoneId($countryId, $name)
    {
        $countryQ = $this->db->query("select zone_id from " . DB_PREFIX . "zone where `name` like '" . $name . "' and country_id='" . $countryId . "'");
        if ($countryQ->num_rows) {
            return $countryQ->row["zone_id"];
        }
        return 0;
    }

    private function getCountryId($name)
    {
        $countryQ = $this->db->query("select country_id from " . DB_PREFIX . "country where `name` like '" . $name . "'");
        if ($countryQ->num_rows) {
            return $countryQ->row["country_id"];
        }
        return 0;
    }

    private function getCatlogUrl()
    {
        if (defined("HTTPS_CATALOG")) {
            return HTTPS_CATALOG;
        }
        return HTTP_CATALOG;
    }

}

?>