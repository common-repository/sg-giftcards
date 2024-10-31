<?php


include_once('Suregift_LifeCycle.php');

class Suregift_Plugin extends Suregift_LifeCycle {

    /**
     * @return array of option meta data.
     */
    //admin panel configuration  fields and option
    public function getOptionMetaData() {
        return array(
            //'_version' => array('Installed Version'), // Leave this one commented-out. Uncomment to test upgrades.
            'UsernameInput' => array(__('Username', 'Suregift_Plugin')),
            'PasswordInput' => array(__('Password', 'Suregift_Plugin')),
            'WebsiteHostInput' => array(__('WebsiteHost', 'Suregift_Plugin')),
            'LiveMode' => array(__('Live Mode', 'Suregift_Plugin'), 'true', 'false'),
            'StoreLocation' => array(__('Store Location', 'Suregift_Plugin'), 'Nigeria', 'Kenya'),

        );
    }

    protected function initOptions() {
        $options = $this->getOptionMetaData();
        if (!empty($options)) {
            foreach ($options as $key => $arr) {
                if (is_array($arr) && count($arr > 1)) {
                    $this->addOption($key, $arr[1]);
                }
            }
        }
    }

    public function getPluginDisplayName() {
        return 'Suregift WooCommerce';
    }

    protected function getMainPluginFileName() {
        return 'Suregift.php';
    }

    /**
     * Called by install() to create any database tables if needed.
     * Best Practice:
     * (1) Prefix all table names with $wpdb->prefix
     * (2) make table names lower case only
     * @return void
     */
    protected function installDatabaseTables() {
        //        global $wpdb;
        //        $tableName = $this->prefixTableName('mytable');
        //        $wpdb->query("CREATE TABLE IF NOT EXISTS `$tableName` (
        //            `id` INTEGER NOT NULL");
    }

    /**
     *
     * Drop plugin-created tables on uninstall.
     * @return void
     */
    protected function unInstallDatabaseTables() {
        //        global $wpdb;
        //        $tableName = $this->prefixTableName('mytable');
        //        $wpdb->query("DROP TABLE IF EXISTS `$tableName`");
    }

    /**
     * Perform actions when upgrading from version X to version Y
     *
     * @return void
     */
    public function upgrade() {
    }

    //hook handlers
    public function addActionsAndFilters() {
        add_action('admin_menu', array(&$this, 'addSettingsSubMenuPage'));
        add_action('woocommerce_after_order_notes',array(&$this, 'AddVoucherForm'));
        add_action('woocommerce_after_cart_contents',array(&$this, 'Suregifts_woocommerce_after_cart_table'));
        add_action( 'woocommerce_cart_calculate_fees',array(&$this, 'updateCartFees')  );
        add_action('woocommerce_checkout_process',array(&$this, 'Suregifts_check_out_process') );
        add_action('woocommerce_order_status_completed',array(&$this, 'clear_session_variables') );
    }

    //add a sure a gift voucher form on the billing page
    public function AddVoucherForm() {
        global $woocommerce;
        $cart_url =  $woocommerce->cart->get_cart_url();
        if(!isset($woocommerce->session->Suregiftcard)){
            echo  '<div><h3>'.__('Have A Suregift Voucher?').'</h3>';
            echo "<p><a href='{$cart_url}'>".__('Click Here')."</a>".__(' to add Suregifts voucher in view cart page ')."</p>";
            echo'</div>';
        }

    }

    //make a call to verify a voucher
    function sure_gift_verify($voucherCode){
        $username = $this->getOption('UsernameInput');
        $password = $this->getOption('PasswordInput');
        $storeLocation = $this->getOption('StoreLocation');
        $mode = $this->getOption('LiveMode');

        $auth = $username . ':' . $password;
        if($storeLocation == "Nigeria"){
            if ($mode == "false") {
                $ch = curl_init("http://sandbox.oms-suregifts.com/api/voucherredemption?vouchercode=" . $voucherCode);
            } else {
                $ch = curl_init("http://oms-suregifts.com/api/voucherredemption?vouchercode=" . $voucherCode);
            }
        }
        else if($storeLocation == "Kenya"){

            if ($mode == "false") {
                $ch = curl_init("http://kenyastaging.oms-suregifts.com/api/voucherredemption?vouchercode=" . $voucherCode);
            } else {
                $ch = curl_init("http://kenya.oms-suregifts.com/api/voucherredemption?vouchercode=$voucherCode");
            }
        }
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                "Authorization: Basic " . base64_encode($auth),
            )
        );
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }


    //make a call and establishes a connection to the surgift redeemtion api
    function sure_gift_redemption($amount,$vouchercode){
        $username = $this->getOption('UsernameInput');
        $password = $this->getOption('PasswordInput');
        $mode = $this->getOption('LiveMode');
        $storeLocation = $this->getOption('StoreLocation');
        $auth = $username . ':' . $password;
        $websitehost = $this-> getOption('WebsiteHostInput');
        $data = array(
            "AmountToUse" => $amount,
            "VoucherCode" => $vouchercode,
            "WebsiteHost" => $websitehost
        );
        $data_string = json_encode($data);
        if($storeLocation == "Nigeria"){
            if ($mode == "false") {
                $ch = curl_init("http://sandbox.oms-suregifts.com/api/voucherredemption");
            } else {
                $ch = curl_init("http://oms-suregifts.com/api/voucherredemption");
            }
        }
        else if($storeLocation == "Kenya"){
            if ($mode == "false") {
                $ch = curl_init("http://kenyastaging.oms-suregifts.com/api/voucherredemption");
            } else {
                $ch = curl_init("http://kenya.oms-suregifts.com/api/voucherredemption");
            }
        }
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($data_string),
                "Authorization: Basic ".base64_encode($auth),
            )
        );
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }

    //add Suregift add voucher form on the check out page
    function  Suregifts_woocommerce_after_cart_table(){
        global $woocommerce;
        echo '<div class="submit">';
        if (!$woocommerce->session->Suregiftcard) {
            echo '
              <table cellspacing="0" width="100%"><tbody>
              <tr><td colspan="4"><h3>Suregifts Card</h3></td></tr>
              <tr>
              <td width="24%">
                   <input type="text"  name="voucherCode" class="input-text"  value="" placeholder="Suregifts Code">
                </td>
                <td width="22%">
                    <input type="submit" class="button" name="submit" value="Apply GiftCard">
                </td>
                <td colspan="2" width="54%"></td>
              </tr></tbody><table>';
        }else {
            echo '
              <table cellspacing="0" width="100%"><tbody>
              <tr><td colspan="4"><h3>Suregifts Card</h3></td></tr>
              <tr><td width="24%">
              <input type="text" style="width:50%" name="Suregift_card" readonly  value="'.$woocommerce->session->Suregiftcard.'">
              </td>
              <td width="22%">
              <input type="submit" class="button" name="un-Suregift_card-btn" value="Remove"/></td>
              <td colspan="2" width="54%"></td>
              </tr></tbody><table>';
        }
        echo '</div>';
    }

    //make a call to Suregifts api to verify a voucher
    public function verify_process($coupon_code,$message){
        global $woocommerce;
        $response = $this->sure_gift_verify($coupon_code);
        $res = json_decode($response, true);
        if ($res['AmountToUse'] > 0.0) {
            $woocommerce->session->use_Suregiftcard = true;
            $woocommerce->session->Suregiftcard_amt = $res['AmountToUse'];
            $woocommerce->session->Suregiftcard = $coupon_code;
            $woocommerce->cart->discount_total = $res['AmountToUse'];
            $woocommerce->cart->add_fee(__('Suregifts Card: ' , 'woocommerce'), -$woocommerce->session->Suregiftcard_amt);
            wc_add_notice('Suregift gift card of '.$res['AmountToUse'].' has been applied successfully', $notice_type = "$message");
        }
        else {
            wc_add_notice('Suregift gift card is invalid or used', $notice_type = 'error');
        }
    }


    function woocommerce_validate_checkoutfields(){
        $country = new WC_Countries();
        $billingfields =  $country->get_address_fields($country->get_base_country(),'billing_');
        $shippingfields = $country->get_address_fields($country->get_base_country(),'shipping_');
        $mergedFields =  array_merge($billingfields,$shippingfields);
        $requiredfields  =  array();
        $errorfields = null;
        foreach($mergedFields as $key=>$value){
            if(isset($value['required']) and $value['required']==1){
                array_push($requiredfields,$key);
            }
        }
        foreach($requiredfields as $fields){
            if(isset($_POST[$fields]) and empty($_POST[$fields])){
                $errorfields.=$_POST[$fields].",";
            }
            else{
                if(isset($_POST[$fields]) and ($fields == "billing_phone")){
                    if(!is_numeric($_POST[$fields])){
                        $errorfields.=$_POST[$fields].",";
                    }
                }
            }
        }
        if(!empty($errorfields)){
            $flag = false;
        }
        else{
            $flag = true;
        }

        return $flag;
    }

    //handles check out process on the cart
    function Suregifts_check_out_process() {
        global $woocommerce;
        if($this->woocommerce_validate_checkoutfields() == true){
            if(isset($woocommerce->session->Suregiftcard_amt)){
                $woocommerce->cart->add_fee(__('Suregift Card: ' , 'woocommerce'), -$woocommerce->session->Suregiftcard_amt);
                $response = $this->sure_gift_redemption( $woocommerce->session->Suregiftcard_amt,$woocommerce->session->Suregiftcard );
                $coupon_res = json_decode($response, true);
                $coupon_res_code = $coupon_res['Response'];
                $desc=$coupon_res['Description'];
                if ($coupon_res_code != "00"){
                    $this->unsetVariables();
                    wc_add_notice( $desc.'Suregift gift card is invalid or used and has been unapplied', $notice_type = 'error' );
                }
            }

        }
    }

    //used to unsat variables
    function unsetVariables(){
        global $woocommerce;
        $woocommerce->session->use_Suregiftcard = false;
        $woocommerce->session->Suregiftcard = null;
        $woocommerce->session->Suregiftcard_amt = null;
        $woocommerce->cart->discount_total = 0.0;
        unset($woocommerce->session->use_Suregiftcard);
        unset($woocommerce->session->Suregiftcard);
        unset($woocommerce->session->Suregiftcard_amt);
    }


    //update cart fees at all times
    function updateCartFees(){
        global $woocommerce;
        if(isset($_POST['voucherCode'])  and (!empty($_POST['voucherCode']))){
            if(!isset($woocommerce->session->Suregiftcard)) {
                $coupon_code = $_POST['voucherCode'];
                echo $this->verify_process($coupon_code,'success');
            }else{
                $woocommerce->cart->add_fee(__('Suregift Card: ' , 'woocommerce'), -$woocommerce->session->Suregiftcard_amt);
            }
        }
        else if(isset($_POST['un-Suregift_card-btn'])){
            $this->unsetVariables();
        }
        else{
            if(isset($woocommerce->session->Suregiftcard_amt)){
                $woocommerce->cart->add_fee(__('Suregift Card: ' , 'woocommerce'), -$woocommerce->session->Suregiftcard_amt);
            }
        }
    }

    //unset all variables after order has been successful
    public function clear_session_variables(){
        $this->unsetVariables();
    }



}
