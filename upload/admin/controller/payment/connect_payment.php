<?php
include_once(DIR_SYSTEM . "/comercia/util.php");
use comercia\Util;

class ControllerPaymentConnectPayment extends Controller {

    public function index() {
        Util::response()->redirect("module/comerciaConnect");
    }

}