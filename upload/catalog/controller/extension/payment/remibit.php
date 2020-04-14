<?php
class ControllerExtensionPaymentREMIBIT extends Controller {
    public function index() {
        $data['button_confirm'] = $this->language->get('button_confirm');

        $data['redirect'] = $this->url->link('extension/payment/remibit/postPayment');

        return $this->load->view('extension/payment/remibit', $data);
    }



    public function postPayment(){
        $this->load->model('checkout/order');
        $currency=$this->session->data['currency'];

        $order_id = $this->session->data['order_id'];
        $order_info = $this->model_checkout_order->getOrder($order_id);

        $timeStamp = time();
        $order_total = $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false);
        $transactionKey = $this->config->get('payment_remibit_transaction_key');


        if (function_exists('hash_hmac')) {
            $hash_d        = hash_hmac('md5', sprintf('%s^%s^%s^%s^%s',
                $this->config->get('payment_remibit_login_id'),
                $order_id,
                $timeStamp,
                $order_total,
                $currency
            ), $transactionKey);
        } else {
            $hash_d    = bin2hex(mhash(MHASH_MD5, sprintf('%s^%s^%s^%s^%s',
                $this->config->get('payment_remibit_login_id'),
                $order_id,
                $timeStamp,
                $order_total,
                $currency
            ), $transactionKey));
        }

        $data['x_login'] = $this->config->get('payment_remibit_login_id');
        $data['x_fp_sequence'] = $this->session->data['order_id'];
        $data['x_fp_timestamp'] = time();
        $data['x_amount'] = $order_total;
        $data['x_fp_hash'] = $hash_d; // calculated later, once all fields are populated
        $data['x_show_form'] = 'PAYMENT_FORM';
        $data['x_test_request'] = false;
        $data['x_type'] = 'AUTH_CAPTURE';
        $data['x_currency_code'] = $this->session->data['currency'];
        $data['x_invoice_num'] = $this->session->data['order_id'];
        $data['x_description'] = html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8');
        $data['x_first_name'] = $order_info['payment_firstname'];
        $data['x_last_name'] = $order_info['payment_lastname'];
        $data['x_company'] = $order_info['payment_company'];
        $data['x_address'] = $order_info['payment_address_1'] . ' ' . $order_info['payment_address_2'];
        $data['x_city'] = $order_info['payment_city'];
        $data['x_state'] = $order_info['payment_zone'];
        $data['x_zip'] = $order_info['payment_postcode'];
        $data['x_country'] = $order_info['payment_country'];
        $data['x_phone'] = $order_info['telephone'];
        $data['x_ship_to_first_name'] = $order_info['shipping_firstname'];
        $data['x_ship_to_last_name'] = $order_info['shipping_lastname'];
        $data['x_ship_to_company'] = $order_info['shipping_company'];
        $data['x_ship_to_address'] = $order_info['shipping_address_1'] . ' ' . $order_info['shipping_address_2'];
        $data['x_ship_to_city'] = $order_info['shipping_city'];
        $data['x_ship_to_state'] = $order_info['shipping_zone'];
        $data['x_ship_to_zip'] = $order_info['shipping_postcode'];
        $data['x_ship_to_country'] = $order_info['shipping_country'];
        $data['x_customer_ip'] = $this->request->server['REMOTE_ADDR'];
        $data['x_email'] = $order_info['email'];
        $data['x_relay_response'] = 'true';
        $data['x_relay_url'] = $this->url->link('extension/payment/remibit/checkoutReturn', '', true);
        $data['x_cancel_url'] = $this->url->link('checkout/failure');

        $url = $this->config->get('payment_remibit_getaway_url');
        return $this->jsRedirect($url, $data);
    }

    public function checkoutReturn()
    {
        //Payment successfully made
        if($this->validate()){
            $this->load->model('checkout/order');
            $this->model_checkout_order->addOrderHistory(
                $_POST['x_invoice_num'],
                $this->config->get('payment_remibit_order_status_id'),
                '[RemiBit] Paid. Tx: <a target="_blank" href="https://explorer.commercium.net/tx/'.$_POST['x_trans_id'].'">'.$_POST['x_trans_id'].'</a>',
                true
            );
            $url = $this->url->link('checkout/success&order_id=' . $_POST['x_invoice_num']);

        } else { // Go to failure page if payment failed
            $url = $this->url->link('checkout/failure');
        }

        $this->jsRedirect($url);
    }

    private function validate()
    {
        $hashData = implode('^', [
            $_POST['x_trans_id'],
            $_POST['x_test_request'],
            $_POST['x_response_code'],
            $_POST['x_auth_code'],
            $_POST['x_cvv2_resp_code'],
            $_POST['x_cavv_response'],
            $_POST['x_avs_code'],
            $_POST['x_method'],
            $_POST['x_account_number'],
            $_POST['x_amount'],
            $_POST['x_company'],
            $_POST['x_first_name'],
            $_POST['x_last_name'],
            $_POST['x_address'],
            $_POST['x_city'],
            $_POST['x_state'],
            $_POST['x_zip'],
            $_POST['x_country'],
            $_POST['x_phone'],
            $_POST['x_fax'],
            $_POST['x_email'],
            $_POST['x_ship_to_company'],
            $_POST['x_ship_to_first_name'],
            $_POST['x_ship_to_last_name'],
            $_POST['x_ship_to_address'],
            $_POST['x_ship_to_city'],
            $_POST['x_ship_to_state'],
            $_POST['x_ship_to_zip'],
            $_POST['x_ship_to_country'],
            $_POST['x_invoice_num'],
        ]);

        $digest = strtoupper(hash_hmac('sha512', "^" . $hashData . "^", hex2bin($this->config->get('payment_remibit_signature_key'))));
        if ($_POST['x_response_code'] == 1 && (strtoupper($_POST['x_SHA2_Hash']) == $digest)) {
            return true;
        } else {
            return false;
        }

    }

    private function jsRedirect($url, $data = array())
    {
        $post_string = array();

        foreach ($data as $key => $value) {
            $post_string[] = "<input type='hidden' name='$key' value='$value'/>";
        }

        $loading = ' <div style="width: 100%; height: 100%;top: 50%; padding-top: 10px;padding-left: 10px;  left: 50%; transform: translate(40%, 40%)"><div style="width: 150px;height: 150px;border-top: #CC0000 solid 5px; border-radius: 50%;animation: a1 2s linear infinite;position: absolute"></div> </div> <style>*{overflow: hidden;}@keyframes a1 {to{transform: rotate(360deg)}}</style>';

        $html_form = '<form action="' . $url . '" method="post" id="authorize_payment_form">' . implode('', $post_string) . '<input type="submit" id="submit_authorize_payment_form" style="display: none"/>' . $loading . '</form><script>document.getElementById("submit_authorize_payment_form").click();</script>';

        echo $html_form;
        die;
    }

}
