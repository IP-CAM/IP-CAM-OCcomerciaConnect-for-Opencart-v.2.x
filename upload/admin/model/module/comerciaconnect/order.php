<?php

use comercia\Util;
use comerciaConnect\logic\OrderLine;
use comerciaConnect\logic\Product;
use comerciaConnect\logic\Purchase;

class ModelModuleComerciaconnectOrder extends Model
{
    var $orderModel;
    var $orderStatusModel;
    var $taxClassModel;
    var $taxRateModel;
    var $geoZoneModel;

    function __construct($registry)
    {
        parent::__construct($registry);

        Util::load()->model('sale/order');
        Util::load()->model('localisation/order_status');
        Util::load()->model('localisation/tax_class');
        Util::load()->model('localisation/tax_rate');
        Util::load()->model('localisation/geo_zone');
    }

    public function createApiOrder($order, $session, $productMap, $productVariantsMap, $storeId)
    {
        static $voucherProduct;
        static $couponProduct;

        if (!$voucherProduct || !$couponProduct) {
            $voucherProduct = new Product($session);
            $voucherProduct->id = "voucher";
            $voucherProduct->name = "voucher";
            $voucherProduct->type = PRODUCT_TYPE_DISCOUNT;
            $voucherProduct->code = "voucher";
            $voucherProduct->save();


            $couponProduct = new Product($session);
            $couponProduct->id = "coupon";
            $couponProduct->name = "coupon";
            $couponProduct->type = PRODUCT_TYPE_DISCOUNT;
            $couponProduct->code = "voucher";
            $couponProduct->save();
        }


        $orderLines = [];
        $order = $this->model_sale_order->getOrder($order['order_id']);
        $lines = $this->model_sale_order->getOrderProducts($order["order_id"]);

        foreach ($lines as $line) {
            $tcId = $this->db->query("SELECT tax_class_id FROM " . DB_PREFIX . "product p WHERE p.product_id = '" . (int)$line['product_id'] . "'")->row;

            // Check if we need to cope with options for this line
            $orderOptions = $this->model_sale_order->getOrderOptions($order["order_id"], $line["order_product_id"]);
            if ( ! empty($orderOptions)) {
                $ovidsForOrderLine = array();
                foreach ($orderOptions as $orderOption) {
                    $query = $this->db->query("SELECT option_value_id FROM " . DB_PREFIX . "product_option_value WHERE product_option_value_id = '" . $orderOption['product_option_value_id'] . "'");
                    if ($query->num_rows) {
                        $ovidsForOrderLine[] = $query->row['option_value_id'];
                    }
                }

                foreach ($productVariantsMap[$line['product_id']] as $variantDetails) {
                    // check if the intersect of the two arrays result in the same number of products, then we have our variant matched
                    if (count(array_intersect($ovidsForOrderLine, $variantDetails['optionValueIds'])) == count($variantDetails['optionValueIds'])) {
                        $product = $variantDetails['product'];
                        break;
                    }
                }
            }
            else {
                $product = $productMap[$line["product_id"]];
            }

            $orderLines[] = new OrderLine($session, [
                "product" => $product,
                "price" => $line["price"],
                "quantity" => $line["quantity"],
                "tax" => $line["tax"],
                "priceWithTax" => $line["tax"] + $line["price"],
                "taxGroup" => $tcId['tax_class_id']
            ]);
        }

        $paymentMethod = new Product($session);
        $paymentMethod->id = $order['payment_code'] ?: 'connect_payment';
        $paymentMethod->name = $order['payment_method'];
        $paymentMethod->type = PRODUCT_TYPE_PAYMENT;
        $paymentMethod->code = $order['payment_code'];
        static $savedPayment = [];
        if (isset($order['ccHash']) && $order['ccHash'] != $this->getHashForOrder($order) && !isset($savedPayment[$paymentMethod->id])) {
            $savedPayment[$paymentMethod->id] = true;
            $paymentMethod->save();
        }

        $orderTotals = $this->model_sale_order->getOrderTotals($order['order_id']);

        if (strpos($order['shipping_code'], '.') !== false) {
            $order['shipping_code'] = explode('.', $order['shipping_code'])[0];
        }

        $shippingTaxClassId = Util::config($storeId)->get($order['shipping_code'] . '_tax_class_id');

        $shippingMethod = new Product($session);
        $shippingMethod->id = $order['shipping_code'] ?: 'connect_shipping';
        $shippingMethod->name = $order['shipping_method'];
        $shippingMethod->type = PRODUCT_TYPE_SHIPPING;
        $shippingMethod->taxGroup = $shippingTaxClassId;
        $shippingMethod->code = $order['shipping_code'];
        foreach ($orderTotals as $orderTotal) {
            if ($orderTotal['code'] == 'shipping') {
                $shippingMethod->price = $orderTotal['value'];
            }
            if ($orderTotal['code'] == 'coupon') {
                $orderLines[] = new OrderLine($session, [
                    'product' => $couponProduct,
                    'price' => $orderTotal["value"],
                    'quantity' => 1,
                    'tax' => 0,
                    'priceWithTax' => $orderTotal["value"]
                ]);
            }

            if ($orderTotal['code'] == 'voucher') {
                $orderLines[] = new OrderLine($session, [
                    'product' => $voucherProduct,
                    'price' => $orderTotal["value"],
                    'quantity' => 1,
                    'tax' => 0,
                    'priceWithTax' => $orderTotal["value"]
                ]);
            }
        }

        static $savedShipping = [];
        if (isset($order['ccHash']) && $order['ccHash'] != $this->getHashForOrder($order) && !isset($savedShipping[$shippingMethod->id])) {
            $savedShipping[$shippingMethod->id] = true;
            $shippingMethod->save();
        }

        $taxRules = $this->model_localisation_tax_class->getTaxRules($shippingTaxClassId);
        $taxRates = 0.00;

        foreach ($taxRules as $rule) {
            $rate = $this->model_localisation_tax_rate->getTaxRate($rule['tax_rate_id']);
            $geoZones = $this->model_localisation_geo_zone->getZoneToGeoZones($rate['geo_zone_id']);
            $found = false;
            foreach ($geoZones as $zone) {
                if ($order[$rule['based'] . '_zone_id'] == $zone['zone_id']) {
                    $found = true;
                    break;
                }
            }

            if ($found) {
                if ($rate['type'] == 'F') {
                    $taxRates += $rate['rate'];
                } else if ($rate['type'] == 'P') {
                    $taxRates += ($rate['rate'] / 100) * $shippingMethod->price;
                }
            }
        }

        $orderLines[] = new OrderLine($session, [
            'product' => $paymentMethod,
            'quantity' => 1,
        ]);
        $orderLines[] = new OrderLine($session, [
            'product' => $shippingMethod,
            'price' => $shippingMethod->price,
            'quantity' => 1,
            'tax' => $taxRates,
            'priceWithTax' => $shippingMethod->price + $taxRates,
            'taxGroup' => $shippingMethod->taxGroup
        ]);

        $shippingAddress = $this->splitAddress($order['shipping_address_1'] . " \n " . $order['shipping_address_2']);
        $paymentAddress = $this->splitAddress($order['payment_address_1'] . " \n " . $order['payment_address_2']);

        $purchase = new Purchase($session, [
            "id" => $order['order_id'],
            "date" => strtotime($order['date_modified']),
            "invoiceNumber" => $order['invoice_no'],
            "status" => $this->model_localisation_order_status->getOrderStatus($order['order_status_id'])['name'],
            "email" => $order['email'],
            "phonenumber" => $order['telephone'],
            "originalData" => $order,
            "trackingCode" => $this->getTrackingForOrder($order['order_id']),
            "deliveryAddress" => [
                "firstName" => $order["shipping_firstname"],
                "lastName" => $order["shipping_lastname"],
                "street" => $shippingAddress->street,
                "number" => $shippingAddress->number,
                "suffix" => $shippingAddress->suffix,
                "postalCode" => $order["shipping_postcode"],
                "city" => $order["shipping_city"],
                "province" => $order["shipping_zone"],
                "country" => $order["shipping_country"],
                "company" => $order["shipping_company"]
            ],
            "invoiceAddress" => [
                "firstName" => $order["payment_firstname"],
                "lastName" => $order["payment_lastname"],
                "street" => $paymentAddress->street,
                "number" => $paymentAddress->number,
                "suffix" => $paymentAddress->suffix,
                "postalCode" => $order["payment_postcode"],
                "city" => $order["payment_city"],
                "province" => $order["payment_zone"],
                "country" => $order["payment_country"],
                "company" => $order["payment_company"]
            ],
            "orderLines" => $orderLines
        ]);
        $this->log->write("PURCHASE");
        $this->log->write($purchase);

        return $purchase;

    }

    public function sendOrderToApi($apiOrder, $session = false)
    {
        if (is_object($apiOrder)) {
            $apiOrder->save();
        } elseif (is_array($apiOrder) && $session) {
            return Purchase::saveBatch($session, $apiOrder)["success"];
        }
        return $apiOrder;
    }

    function touchBatch($session, $products)
    {
        Purchase::touchBatch($session, $products);
    }


    function saveOrder($order, $storeId)
    {
        Util::load()->language("module/comerciaConnect");
        Util::load()->model("localisation/currency");
        $orderModel = Util::load()->model("sale/order");

        static $taxGroupNames = [];

        //initialize some basic variables
        $dbOrderInfo = [];
        $dbOrderproducts = [];
        $totals = [];
        $dbOrderHistory = [];

        //basic info
        if (is_numeric($order->id)) {
            $dbOrderInfo["order_id"] = $order->id;
            $original = $orderModel->getOrder($order->id);
        }

        $dbOrderInfo["invoice_no"] = $order->invoiceNumber;
        //todo: lets see in the future how we can get this multi store for now take the default.
        $dbOrderInfo["store_id"] = $storeId;
        $dbOrderInfo["store_name"] = Util::config($storeId)->config_name;
        $dbOrderInfo["store_url"] = $this->getCatalogUrl();
        //todo: lets implement customers later.. for now leave it as a guest..
        $dbOrderInfo["customer_id"] = 0;
        $dbOrderInfo["customer_group_id"] = Util::config($storeId)->get('config_customer_group_id');
        //todo:maybe implment this in the future in comercia connect
        $dbOrderInfo["comment"] = "";
        $dbOrderInfo["order_status_id"] = $this->getOrderStatusId($order->status, $storeId);

        $dbOrderInfo["affiliate_id"] = 0;
        $dbOrderInfo["commission"] = 0;
        if (Util::version()->isMinimal("2.0")) {
            $dbOrderInfo["marketing_id"] = 0;
        }
        $dbOrderInfo["tracking"] = $order->trackingCode;
        $dbOrderInfo["language_id"] = Util::load()->model("module/comerciaconnect/general")->getLanguageIdForStore($storeId);

        $currency = $this->model_localisation_currency->getCurrencyByCode(Util::config($storeId)->get('config_currency'));
        $dbOrderInfo["currency_id"] = $currency['currency_id'];
        $dbOrderInfo["currency_value"] = $currency['value'];
        $dbOrderInfo["currency_code"] = Util::config($storeId)->get('config_currency');

        $dbOrderInfo["ip"] = "";
        $dbOrderInfo["forwarded_ip"] = "";
        $dbOrderInfo["user_agent"] = "";
        $dbOrderInfo["accept_language"] = "";
        $dbOrderInfo["date_added"] = date('Y-m-d H:i:s');
        $dbOrderInfo["date_modified"] = date('Y-m-d H:i:s');

        $dbOrderInfo["ccCreatedBy"] = $order->createdBy;
        $dbOrderInfo["ccConnector"] = $order->connector;

        //customer info
        $dbOrderInfo["firstname"] = $order->invoiceAddress->firstName;
        $dbOrderInfo["lastname"] = $order->invoiceAddress->lastName;
        $dbOrderInfo["email"] = $order->email;
        $dbOrderInfo["telephone"] = $order->phoneNumber;
        //todo: Implement fax later into Comercia Connect.
        $dbOrderInfo["fax"] = "";

        if (Util::version()->isMinimal("2.2")) {
            $dbOrderInfo["custom_field"] = "[]";
            $dbOrderInfo["payment_custom_field"] = "[]";
            $dbOrderInfo["shipping_custom_field"] = "[]";
        }

        $expPayment = explode("\n", $order->invoiceAddress->street);
        $separator = is_numeric($order->invoiceAddress->suffix) ? '-' : '';
        if (count($expPayment) > 1) {
            $dbOrderInfo["payment_address_1"] = trim($expPayment[0]);
            $dbOrderInfo["payment_address_2"] = trim($expPayment[1] . " " . $order->invoiceAddress->number . $separator . $order->invoiceAddress->suffix);
        } else {
            $dbOrderInfo["payment_address_1"] = trim($order->invoiceAddress->street . " " . $order->invoiceAddress->number . $separator . $order->invoiceAddress->suffix);
            $dbOrderInfo["payment_address_2"] = "";
        }

        $expShipping = explode("\n", $order->deliveryAddress->street);
        $separator = is_numeric($order->deliveryAddress->suffix) ? '-' : '';
        if (count($expShipping) > 1) {
            $dbOrderInfo["shipping_address_1"] = trim($expShipping[0]);
            $dbOrderInfo["shipping_address_2"] = trim($expShipping[1] . " " . $order->deliveryAddress->number . $separator . $order->deliveryAddress->suffix);
        } else {
            $dbOrderInfo["shipping_address_1"] = trim($order->deliveryAddress->street . " " . $order->deliveryAddress->number . $separator . $order->deliveryAddress->suffix);
            $dbOrderInfo["shipping_address_2"] = "";
        }

        //invoice info
        $dbOrderInfo["payment_firstname"] = $order->invoiceAddress->firstName;
        $dbOrderInfo["payment_lastname"] = $order->invoiceAddress->lastName;
        $dbOrderInfo["payment_city"] = $order->invoiceAddress->city;
        $dbOrderInfo["payment_postcode"] = $order->invoiceAddress->postalCode;
        $dbOrderInfo["payment_country"] = $this->getCountryName($order->invoiceAddress->country);
        $dbOrderInfo["payment_company"] = $order->invoiceAddress->company;
        $dbOrderInfo["payment_country_id"] = $this->getCountryId($order->invoiceAddress->country);
        $dbOrderInfo["payment_zone"] = $order->invoiceAddress->province;
        $dbOrderInfo["payment_zone_id"] = $this->getZoneId($dbOrderInfo["payment_country_id"], $order->invoiceAddress->province);
        //todo: maybe make this configurable in the future?
        $dbOrderInfo["payment_address_format"] = "";


        //shippinginfo
        $dbOrderInfo["shipping_firstname"] = $order->deliveryAddress->firstName;
        $dbOrderInfo["shipping_lastname"] = $order->deliveryAddress->lastName;
        $dbOrderInfo["shipping_city"] = $order->deliveryAddress->city;
        $dbOrderInfo["shipping_postcode"] = $order->deliveryAddress->postalCode;
        $dbOrderInfo["shipping_country"] = $this->getCountryName($order->deliveryAddress->country);
        $dbOrderInfo["shipping_company"] = $order->deliveryAddress->company;
        $dbOrderInfo["shipping_country_id"] = $this->getCountryId($order->deliveryAddress->country);
        $dbOrderInfo["shipping_zone"] = $order->deliveryAddress->province;
        $dbOrderInfo["shipping_zone_id"] = $this->getZoneId($dbOrderInfo["shipping_country_id"], $order->deliveryAddress->province);
        //todo: maybe make this configurable in the future?
        $dbOrderInfo["shipping_address_format"] = "";


        $dbOrderInfo['shipping_method'] = 'ConnectShipping';
        $dbOrderInfo['shipping_code'] = 'connect_shipping';
        $dbOrderInfo['payment_method'] = 'ConnectPayment';
        $dbOrderInfo['payment_code'] = 'connect_payment';

        //calculate totals
        foreach ($order->orderLines as $orderLine) {
            if (@$orderLine->product->type == PRODUCT_TYPE_SHIPPING) {
                $dbOrderInfo["shipping_method"] = $orderLine->product->name;
                $dbOrderInfo["shipping_code"] = $orderLine->product->code;
                $this->addToTotals($totals, "shipping", $orderLine->product->name, $orderLine->price * $orderLine->quantity);
            } elseif (@$orderLine->product->type == PRODUCT_TYPE_PAYMENT) {
                $dbOrderInfo["payment_method"] = $orderLine->product->name;
                $dbOrderInfo["payment_code"] = $orderLine->product->code;
                $this->addToTotals($totals, "payment", $orderLine->product->name, $orderLine->price * $orderLine->quantity);
            } elseif (@$orderLine->product->type == PRODUCT_TYPE_DISCOUNT) {
                $this->addToTotals($totals, "discount", $orderLine->product->name, $orderLine->price * $orderLine->quantity);
            } else {
                $this->addToTotals($totals, "sub_total", $this->language->get("sub_total"), $orderLine->price * $orderLine->quantity);
            }
            if ($orderLine->taxGroup) {
                if (is_numeric($orderLine->taxGroup)) {
                    $countryId = $this->getCountryId($order->deliveryAddress->country) ?: $this->getCountryId($order->invoiceAddress->country);
                    if (!isset($taxGroupNames[$orderLine->taxGroup][$countryId])) {
                        $query = $this->db->query("SELECT tr.name as name FROM " . DB_PREFIX . "tax_rule AS r 
                            LEFT JOIN " . DB_PREFIX . "tax_rate AS tr ON tr.tax_rate_id=r.tax_rate_id 
                            LEFT JOIN " . DB_PREFIX . "geo_zone AS gz ON gz.geo_zone_id=tr.geo_zone_id 
                            LEFT JOIN `" . DB_PREFIX . "zone_to_geo_zone` AS ztgz ON gz.geo_zone_id=ztgz.geo_zone_id             
                            WHERE  ztgz.country_id='" . $countryId . "' and r.tax_class_id='" . $orderLine->taxGroup . "'
        ");

                        if ($query->num_rows) {
                            $taxGroupNames[$orderLine->taxGroup][$countryId] = $query->row["name"];
                        } else {
                            $taxGroupNames[$orderLine->taxGroup][$countryId] = $orderLine->taxGroup;
                        }
                    }
                    $taxGroup = $taxGroupNames[$orderLine->taxGroup][$countryId];
                } else {
                    $taxGroup = $orderLine->taxGroup;
                }
                $this->addToTotals($totals, "tax", $taxGroup, $orderLine->tax * $orderLine->quantity);
            }
        }

        $dbOrderInfo["total"] = $this->calculateTotalValue($totals);

        //complete and save the order
        $order_id = Util::db()->saveDataObject("order", $dbOrderInfo)[0];
        $dbOrderInfo["order_id"] = $order_id;
        $order->changeId($order_id);
        $this->saveHashForOrder($dbOrderInfo);


        //order history
        if ($original && $original["order_status_id"] != $dbOrderInfo["order_status_id"]) {
            $dbOrderHistory["order_id"] = $order_id;
            $dbOrderHistory["order_status_id"] = $dbOrderInfo["order_status_id"];
            $dbOrderHistory["notify"] = 0;
            $dbOrderHistory["comment"] = "";
            $dbOrderHistory["date_added"] = date('Y-m-d H:i:s');
            Util::db()->saveDataObject("order_history", $dbOrderHistory);
        }

        //add products
        foreach ($order->orderLines as $orderLine) {
            if (
                @$orderLine->product->type == PRODUCT_TYPE_PRODUCT
                || @$orderLine->product->type == PRODUCT_TYPE_VIRTUAL
                || @$orderLine->product->type == PRODUCT_TYPE_SERVICE
                || @$orderLine->product->type == PRODUCT_TYPE_OTHER
            ) {
                $product = [];
                $product["order_id"] = $order_id;
                $product["product_id"] = $orderLine->product->id;
                $product["name"] = $orderLine->product->name;
                $product["model"] = $orderLine->product->code;
                $product["quantity"] = $orderLine->quantity;
                $product["price"] = $orderLine->price;
                $product["total"] = $orderLine->price * $orderLine->quantity;
                $product["tax"] = $orderLine->tax;
                $product["reward"] = 0;
                Util::db()->saveDataObject("order_product", $product, ["order_id", "model"]);
            }
        }
        $dbTotals = $this->totalsToDbTotals($totals);
        $dbTotals = array_map(function ($total) use ($order_id) {
            $total = $this->finishTotal($total, $order_id);
            return $total;
        }, $dbTotals);
        Util::db()->saveDataObjectArray("order_total", $dbTotals);
    }

    private function finishTotal($total, $order_id)
    {
        $total['order_id'] = $order_id;
        $query = $this->db->query("select * from " . DB_PREFIX . "order_total where order_id='" . $order_id . "' and title='" . $total["title"] . "' and code='" . $total['code'] . "'");
        if ($query->num_rows) {
            $total["order_total_id"] = $query->row["order_total_id"];
        }
        return $total;
    }

    private function getTrackingForOrder($orderId)
    {
        $query = $this->db->query("select tracking from `" . DB_PREFIX . "order` where order_id='" . $orderId . "'");
        if ($query->num_rows) {
            return $query->row['tracking'];
        }
        return '';
    }

    private function totalsToDbTotals($totals)
    {
        $newTotals = [];

        foreach ($totals as $inCode) {
            foreach ($inCode as $total) {
                $newTotals[] = $total;
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
            $totals[$code][$title] = array(
                "code" => $code,
                "title" => $title,
                "value" => $value
            );
        } else {
            $totals[$code][$title]["value"] += $value;
        }

        if (!Util::version()->isMinimal("2")) {
            $totals[$code][$title]["text"] = $this->currency->format($totals[$code][$title]["value"]);
        }


    }

    private function getOrderStatusId($name, $storeId)
    {
        $orderStatusQuery = $this->db->query("SELECT `order_status_id` FROM `" . DB_PREFIX . "order_status` WHERE `name` LIKE '" . $name . "'");

        if ($orderStatusQuery->num_rows) {
            return $orderStatusQuery->row["order_status_id"];
        }

        return Util::config($storeId)->get("config_order_status_id");
    }

    private function getZoneId($countryId, $name)
    {
        $zoneQuery = $this->db->query("SELECT `zone_id` FROM `" . DB_PREFIX . "zone` WHERE `name` LIKE '" . $name . "' AND `country_id` = '" . $countryId . "'");

        if ($zoneQuery->num_rows) {
            return $zoneQuery->row["zone_id"];
        }

        return 0;
    }

    private function getCountryId($name)
    {
        return $this->getCountryField($name, "country_id");
    }

    private function getCountryName($name)
    {
        return $this->getCountryField($name, "name");
    }


    function getCountryField($name, $field)
    {
        $countryQuery = $this->db->query("SELECT " . $field . " FROM `" . DB_PREFIX . "country` WHERE `name` LIKE '" . $name . "' OR iso_code_2='" . $name . "' OR iso_code_3='" . $name . "'");

        if ($countryQuery->num_rows) {
            return $countryQuery->row[$field];
        }

        if ($field == $name) {
            return $name;
        }
        return 0;
    }

    private function getCatalogUrl()
    {
        if (defined("HTTPS_CATALOG")) {
            return HTTPS_CATALOG;
        }

        return HTTP_CATALOG;
    }

    function splitAddress($addr)
    {
        $exp = explode(" ", $addr);
        $cnt = count($exp);
        $pos = $cnt - 1;
        for ($i = $cnt - 1; $i > 0; $i--) {
            if (is_numeric(substr($exp[$i], 0, 1))) {
                $pos = $i;
                break;
            }

        }
        $street = "";
        for ($i = 0; $i < $pos; $i++) {
            if ($i > 0) {
                $street .= " ";
            }
            $street .= $exp[$i];
        }

        $tmpnumber = $exp[$pos];
        $leng = strlen($tmpnumber);
        $foundLetter = false;
        $suffix = "";
        $number = "";
        for ($j = 0; $j < $leng; $j++) {
            $char = substr($tmpnumber, $j, 1);
            if (!is_numeric($char)) {
                $foundLetter = true;
            }
            if ($foundLetter) {
                $suffix .= $char;
            } else {
                $number .= $char;
            }
        }
        $pos++;

        if ($pos < $cnt) {

            for ($i = $pos; $i < $cnt; $i++) {
                if ($i > $pos || $suffix) {
                    $suffix .= " ";
                }
                $suffix .= $exp[$i];
            }
        }

        if (strpos($suffix, '-') === 0) {
            $suffix = substr($suffix, 1);
        }

        return (object)array(
            "suffix" => $suffix,
            "number" => $number,
            "street" => $street
        );
    }

    function getOrders($storeId = 0, $syncMode = 0)
    {
        $sql = "SELECT 
                 o.order_id, 
                 CONCAT(o.firstname, ' ', o.lastname) AS customer, 
                 (SELECT os.name FROM " . DB_PREFIX . "order_status os WHERE os.order_status_id = o.order_status_id AND os.language_id = '" . (int)Util::load()->model("module/comerciaconnect/general")->getLanguageIdForStore($storeId) . "') AS order_status,
                 o.shipping_code, 
                 o.total, 
                 o.currency_code, 
                 o.currency_value, 
                 o.date_added,
                 o.date_modified,
                 o.order_status_id,
                 o.tracking,
                 o.ccHash
             FROM
                `" . DB_PREFIX . "order` o
            WHERE
               o.date_added >= curdate() - INTERVAL DAYOFWEEK(curdate())+30 DAY
               AND o.order_status_id > 0
               " . ($syncMode ? "AND store_id='" . $storeId . "'" : "") . "
               AND md5(concat(COALESCE(o.date_modified,''),'_',COALESCE(o.order_status_id,''),'_',COALESCE(o.tracking,''),'_" . ControllerModuleComerciaConnect::$subHash . "')) != COALESCE(o.ccHash, '')
        ";
        $query = $this->db->query($sql);
        return $query->rows;
    }

    function getHashForOrder($order)
    {
        return md5($order['date_modified'] . "_" . $order['order_status_id'] . "_" . (!empty($order['tracking']) ? $order['tracking'] : '') . "_" . ControllerModuleComerciaConnect::$subHash);
    }

    function saveHashForOrder($order)
    {
        $this->db->query("update `" . DB_PREFIX . "order` set ccHash='" . $this->getHashForOrder($order) . "' where order_id='" . $order['order_id'] . "'");
    }

    function isHashed($orderId)
    {
        $result = $this->db->query("select ccHash from `" . DB_PREFIX . "order` where order_id='" . $orderId . "'")->row;
        return $result["ccHash"] ? true : false;
    }

}

?>
