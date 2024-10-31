<?php


if (!defined('ABSPATH')) exit;
/*
Plugin Name: Paycorp Sampath Bank IPG 
License URI: https://www.paycorp.com.au/
Description: Sampath IPG by Paycorp.
Version: 1.0
Author: Paycorp International

*/

/*
Paycorp Sampath Bank IPG is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.
 
Paycorp Sampath Bank IPG is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
 
You should have received a copy of the GNU General Public License
along with Paycorp Sampath Bank IPG. If not, see License URI: http://www.gnu.org/licenses/gpl-2.0.html.
*/



/** Initiating Methods to run after plugin loaded */
add_action('plugins_loaded', 'woocommerce_sampath_bank_gateway', 0);


function woocommerce_sampath_bank_gateway()
{


     if (!class_exists('WC_Payment_Gateway')) return;

    class WC_Sampath extends WC_Payment_Gateway
    {
        public function __construct()
        {

            $this->plugin_path = plugin_dir_path(__FILE__);


            $this->plugin_url = plugin_dir_url(__FILE__);

            $this->id = 'sampathipg';
            $this->method_title = 'Paycorp Sampath IPG';
            $this->icon = apply_filters('woocommerce_Paysecure_icon', '' . $this->plugin_url . 'images/sampath.jpg');
            $this->has_fields = false;

            $this->init_form_fields();
            $this->init_settings();

            $this->title = $this->settings['title'];
            $this->description = $this->settings['description'];

            $this->client_id = $this->settings['client_id'];
            $this->customer_id = $this->settings['customer_id'];
            $this->transaction_type = $this->settings['transaction_type'];
            $this->currency_code = $this->settings['currency_code'];
            $this->hmac_secret = $this->settings['hmac_secret'];
            $this->auth_token = $this->settings['auth_token'];
            $this->end_point = $this->settings['pg_domain'];

            $this->sucess_responce_code = $this->settings['sucess_responce_code'];
         
            add_action('init', array(&$this, 'check_SampathIPG_response'));


            $this->gateway_config();

            if (version_compare(WOOCOMMERCE_VERSION, '2.0.0', '>=')) {
                add_action('woocommerce_update_options_payment_gateways_' . $this->id, array(&$this, 'process_admin_options'));
            } else {
                add_action('woocommerce_update_options_payment_gateways', array(&$this, 'process_admin_options'));
            }

            
        }


        /**
         * GENERATE ADMIN IPG CONFIGURATION FORM
         */
        public function init_form_fields()
        {


            $this->form_fields = array(
                'enabled' => array(
                    'title' => __('Enable/Disable', 'paycorp'),
                    'type' => 'checkbox',
                    'label' => __('Enable Sampath IPG Module.', 'paycorp'),
                    'default' => 'no'),

                'title' => array(
                    'title' => __('Title:', 'paycorp'),
                    'type' => 'text',
                    'description' => __('This controls the title which the user sees during checkout.', 'paycorp'),
                    'default' => __('Paycorp Payment Gateway', 'paycorp')),

                'description' => array(
                    'title' => __('Description:', 'paycorp'),
                    'type' => 'textarea',
                    'description' => __('This controls the description which the user sees during checkout.', 'paycorp'),
                    'default' => __('This is a secure purchase through Paycorp Payment Gateway.After click place order button you will get error because this is demo version.Please contact us -: 0117445770.', 'paycorp')),

                'pg_domain' => array(
                    'title' => __('PG Domain:', 'paycorp'),
                    'type' => 'text',
                    'description' => __('IPG data submiting to this URL', 'paycorp'),
                    'default' => __('https://sampath.paycorp.com.au/rest/service/proxy', 'paycorp')),

                'client_id' => array(
                    'title' => __('PG Client Id:', 'paycorp'),
                    'type' => 'text',
		    'maxlength'=>'12',
                    'description' => __('Unique ID for the merchant acc, given by bank.', 'paycorp'),
                    'default' => __('', 'paycorp')),

                'customer_id' => array(
                    'title' => __('PG Customer Id:', 'paycorp'),
                    'type' => 'text',
		    'maxlength'=>'12',
                    'description' => __('collection of intiger numbers, given by bank.', 'paycorp'),
                    'default' => __('', 'paycorp')),

                'transaction_type' => array(
                    'title' => __('PG Transaction Type:', 'paycorp'),
                    'type' => 'text',
                    'description' => __('Indicates the transaction type, given by bank.', 'paycorp'),
                    'default' => __('PURCHASE', 'paycorp')),

                'hmac_secret' => array(
                    'title' => __('HMAC Secret:', 'paycorp'),
                    'type' => 'text',
 		    'maxlength'=>'20',
                    'description' => __('Collection of mix intigers and strings , given by bank.', 'paycorp'),
                    'default' => __('', 'paycorp')),

                'auth_token' => array(
                    'title' => __('Auth Token:', 'paycorp'),
                    'type' => 'text',
		    'maxlength'=>'42',
                    'description' => __('Collection of mix intigers and strings , given by bank.', 'paycorp'),
                    'default' => __('', 'paycorp')),

                'currency_code' => array(
                    'title' => __('currency:', 'paycorp'),
                    'type' => 'text',
                    'description' => __('Three character ISO code of the currency such as LKR,USD. ', 'paycorp'),
                    'default' => __(get_woocommerce_currency(), 'paycorp')),

                'sucess_responce_code' => array(
                    'title' => __('Sucess responce code :', 'paycorp'),
                    'type' => 'text',
                    'description' => __('00 - Transaction Passed', 'paycorp'),
                    'default' => __('00', 'paycorp')),

                'checkout_msg' => array(
                    'title' => __('Checkout Message:', 'paycorp'),
                    'type' => 'textarea',
                    'description' => __('Message display when checkout'),
                    'default' => __('Thank you for your order, please click the button below to pay with the secured Sampath Bank payment gateway.', 'paycorp')),

                    );
}


        /**
         * CONFIGURE SAMPATH IPG
         */
        public function gateway_config()
        {
            if (!class_exists('GatewayConfig')) {
                require $this->plugin_path . 'classes/GatewayConfig.php';
            }

            $config = array(
                'end_point' => $this->end_point,
                'auth_token' => $this->auth_token,
                'hmac_secret' => $this->hmac_secret,
                'client_id' => $this->client_id
                );

            $this->gateway = new GatewayConfig($this->plugin_path, $config);
        }

        /**
         * Generate admin panel fields
         */
        public function admin_options()
        {
            echo '<style type="text/css"> .wpimage {margin:1 auto;display: flex; justify-content: left;}</style>';
            echo '<h3>' . __('Sampath bank online payment gateway', 'paycorp') . '</h3>';
            echo '<p><b> Paycorp IPG Plugin is the only PCIDSS Compliant , Visa , MasterCard and Bank Certifed plugin in Srilanka. This is a demo version. To obtain the live secure version please contact us on 0117445770. </b> </p>';
           
            echo'<a href="https://support.paycorp.com.au/" target="_blank"><img src="',$this->plugin_url,'images/support.jpg" alt="payment gateway" class="wpimage" width="40%" height="20%"/></a>';

            echo '<table class="form-table">';
            $this->generate_settings_html();
            echo '</table>';
        }


        public function payment_fields()
        {
            if ($this->description) echo wpautop(wptexturize($this->description));
        }

        /**
         * PROCESS PAYMENT :: WOOCOMMERCE
         */
        public function process_payment($order_id)
        {
            global $wpdb;
            $order = new WC_Order($order_id);

            $actual_link = add_query_arg('order', $order_id, add_query_arg('key', $order->order_key, get_permalink(woocommerce_get_page_id('pay'))));

            
            $order_payment_info = array(
                'extra_data' => array('order_id' => $order_id),
                'transaction_data' => array(
                    'total_amount' => 0,
                    'service_fee' => 0,
                    'payment_amount' => (($order->order_total) * 100),
                    'currency_code' => $this->currency_code
                    ),
                'config_redirect' => array(
                    'url' => $actual_link,
                    'method' => 'POST'),
                'client_reference' => $order_id
                );

            $ipg_url = $this->gateway->initialize($order_payment_info);

            return array('result' => 'success', 'redirect' => $ipg_url);
        }


        //----------------------------------------
        //  Save response data and redirect
        //----------------------------------------
        function check_SampathIPG_response()
        {
            global $wpdb;
            global $woocommerce;

            if (isset($_POST['clientRef']) && isset($_POST['reqid'])) {
                $order_id = $_POST['clientRef'];
                $order = new WC_Order($order_id);
                $completeResponse = $this->gateway->completePayment($_POST);

                if (empty($completeResponse->error)) {
                    $completeResponse = $completeResponse->responseData;

                    $response_code = $completeResponse->responseCode;

                    if ($this->sucess_responce_code == $response_code) {
                        $comments = "<b>Payment Success</b>";
                        $comments .= '<br>Txn Reference : ' . $completeResponse->txnReference;
                        $comments .= '<br>Response Code : ' . $completeResponse->responseCode;
                        $comments .= '<br>Response Text : ' . $completeResponse->responseText;
                        $comments .= '<br>Settlement Date : ' . $completeResponse->settlementDate;
                        $comments .= '<br>Card Holder Name : ' . $completeResponse->creditCard->holderName;
                        $comments .= '<br>Card Number: ' . $completeResponse->creditCard->number . '-(' . $completeResponse->creditCard->type . ')';

                        $order->add_order_note($comments);
                        $order->add_order_note($this->msg['message']);
                        $woocommerce->cart->empty_cart();

                        $mailer = $woocommerce->mailer();
                        $admin_email = get_option('admin_email', '');

                        $message = $mailer->wrap_message(__('Order confirmed', 'woocommerce'), sprintf(__('Order ' . $completeResponse->txnReference . ' has been confirmed', 'woocommerce'), $order->get_order_number(), $posted['reason_code']));
                        $mailer->send($admin_email, sprintf(__('Payment for order %s confirmed', 'woocommerce'), $order->get_order_number()), $message);


                        $message = $mailer->wrap_message(__('Order confirmed', 'woocommerce'), sprintf(__('Order ' . $completeResponse->txnReference . ' has been confirmed', 'woocommerce'), $order->get_order_number(), $posted['reason_code']));
                        $mailer->send($order->billing_email, sprintf(__('Payment for order %s confirmed', 'woocommerce'), $order->get_order_number()), $message);

                        $order->payment_complete();
                        wp_redirect($order->get_checkout_order_received_url());
                    } else {
                        // echo "sdsdsds";
                        $comments = "<b>Payment Failed</b>";
                        $comments .= "<br> IPG RESPONSE CODE: " . $completeResponse->responseCode;
                        $order->update_status('failed');
                        $order->add_order_note($comments);

                        $error_codes = array('91', '92', 'A4', 'C5', 'T3', 'T4', 'U9', 'X1', 'X3', '-1', 'C0', 'A6');

                        if (in_array($response_code, $error_codes)) {
                             wp_redirect($order->get_checkout_order_received_url());
                        } else {
                             wp_redirect($order->get_checkout_order_received_url());
                        }
                    }
                } else {
                    
                    $comments = "<b>Payment Failed</b>";
                    $comments .= "<br> IPG RESPONSE CODE: " . $completeResponse->error->text;
                    $order->update_status('failed');
                    $order->add_order_note($comments);

                     wp_redirect($order->get_checkout_order_received_url());

                }
            }

            die;
        }

        function get_pages($title = false, $indent = true)
        {
            $wp_pages = get_pages('sort_column=menu_order');
            $page_list = array();
            if ($title) $page_list[] = $title;
            foreach ($wp_pages as $page) {
                $prefix = '';
                if ($indent) {
                    $has_parent = $page->post_parent;
                    while ($has_parent) {
                        $prefix .= ' - ';
                        $next_page = get_page($has_parent);
                        $has_parent = $next_page->post_parent;
                    }
                }
                $page_list[$page->ID] = $prefix . $page->post_title;
            }
            return $page_list;
        }
    }


    if (isset($_POST['clientRef']) && isset($_POST['reqid'])) {
        $WC = new WC_Sampath();
    }


    function woocommerce_add_sampath_gateway($methods)
    {
        $methods[] = 'WC_Sampath';
        return $methods;
    }

    add_filter('woocommerce_payment_gateways', 'woocommerce_add_sampath_gateway');


   

}


