<?php

class WC_kryptova_Payment_Gateway extends WC_Payment_Gateway{

    private $order_status;

	public function __construct(){
		$this->id = 'kryptova_payment';
		$this->method_title = __('Kryptova Payment','woocommerce-kryptova-payment-gateway');
		$this->method_description = __('Kryptova Payment getway provide direct payment','woocommerce-kryptova-payment-gateway');
		$this->title = __('Kryptova Payment','woocommerce-kryptova-payment-gateway');
		$this->has_fields = true;
		$this->init_form_fields();
		$this->init_settings();
		$this->enabled = $this->get_option('enabled');
		$this->title = $this->get_option('title');
		$this->description = $this->get_option('description');
		$this->hide_text_box = $this->get_option('hide_text_box');
		$this->text_box_required = $this->get_option('text_box_required');
		$this->order_status = $this->get_option('order_status');


		add_action('woocommerce_update_options_payment_gateways_'.$this->id, array($this, 'process_admin_options'));
	}

	public function init_form_fields(){
				$this->form_fields = array(
					'enabled' => array(
					'title' 		=> __( 'Enable/Disable', 'woocommerce-kryptova-payment-gateway' ),
					'type' 			=> 'checkbox',
					'label' 		=> __( 'Enable Kryptova Payment', 'woocommerce-kryptova-payment-gateway' ),
					'default' 		=> 'no'
					),

		            'title' => array(
						'title' 		=> __( 'Method Title', 'woocommerce-kryptova-payment-gateway' ),
						'type' 			=> 'text',
						'description' 	=> __( 'This controls the title', 'woocommerce-kryptova-payment-gateway' ),
						'default'		=> __( 'Custom Payment', 'woocommerce-kryptova-payment-gateway' ),
						'desc_tip'		=> true,
					),
					'description' => array(
						'title' => __( 'Customer Message', 'woocommerce-kryptova-payment-gateway' ),
						'type' => 'textarea',
						'css' => 'width:500px;',
						'default' => 'None of the other payment options are suitable for you? please drop us a note about your favourable payment option and we will contact you as soon as possible.',
						'description' 	=> __( 'The message which you want it to appear to the customer in the checkout page.', 'woocommerce-kryptova-payment-gateway' ),
					),
					'testmode' => array(
						'title' 		=> __( 'TestMode', 'woocommerce-kryptova-payment-gateway' ),
						'type' 			=> 'checkbox',
						'label' 		=> __( 'TestMode Enable for test Kryptova Payment', 'woocommerce-kryptova-payment-gateway' ),
						'default' 		=> 'yes'
					),
					'api_key' => array(
						'title' 		=> __( 'API Key', 'woocommerce-kryptova-payment-gateway' ),
						'type' 			=> 'text',
						'description' 	=> __( 'Api key', 'woocommerce-kryptova-payment-gateway' ),
						'default'		=> __( 'Api Key', 'woocommerce-kryptova-payment-gateway' ),
						'desc_tip'		=> true,
					),
					'order_status' => array(
						'title' => __( 'Order Status After The Checkout', 'woocommerce-kryptova-payment-gateway' ),
						'type' => 'select',
						'options' => wc_get_order_statuses(),
						'default' => 'wc-on-hold',
						'description' 	=> __( 'The default order status if this gateway used in payment.', 'woocommerce-kryptova-payment-gateway' ),
					),
			 );
	}
	

	public function validate_fields() {
	    if($this->text_box_required === 'no'){
	        return true;
        }

		return true;
	}
	

	public function process_payment( $order_id ) {
		global $woocommerce;
		$order = new WC_Order( $order_id );
		$user_id = get_post_meta( $order_id, '_customer_user', true );

		// Get an instance of the WC_Customer Object from the user ID
		$customer = new WC_Customer( $user_id );
		$amount =  (float) $order->get_total();;
		$user_email   = $customer->get_billing_email();		// Get account email
		$currency = get_woocommerce_currency();
		$mobile = $customer->get_billing_phone();
		$billing_first_name = $customer->get_billing_first_name();
		$billing_last_name  = $customer->get_billing_last_name();
		$billing_company    = $customer->get_billing_company();
		$billing_address_1  = $customer->get_billing_address_1();
		$billing_address_2  = $customer->get_billing_address_2();
		$billing_city       = $customer->get_billing_city();
		$billing_state      = $customer->get_billing_state();
		$billing_postcode   = $customer->get_billing_postcode();
		$billing_country    = $customer->get_billing_country();
		
		$ip_address=file_get_contents('http://checkip.dyndns.com/');
		
		$ip = str_replace("Current IP Address: ","",$ip_address);
		
		$apikey = $this->woocommerce_kryptova_payment_api_key;
		$apikey = $this->settings['api_key'];
		$mode = $this->settings['testmode'];
		if($mode == 'yes'){
			$url = 'https://hello.kryptova.biz/api/test-transaction';
		}else if($mode == 'no'){
			$url = 'https://hello.kryptova.biz/api/transaction';
		}
		$args = array(
			'api_key' => $apikey,
			'first_name' => $_POST['billing_first_name'],
			'last_name' => $_POST['billing_last_name'],
			'address' => $_POST['billing_address_1'].''.$_POST['billing_address_2'],
			'country' => $_POST['billing_country'],
			'state' => $_POST['billing_state'],
			'city' => $_POST['billing_city'],
			'zip' => $_POST['billing_postcode'],
			'ip_address' => $ip,
			'email' => $_POST['billing_email'],
			'phone_no' => $_POST['billing_phone'],
			'amount' => sprintf('%0.2f', $amount),
			'currency' => $currency,
			'card_no' => $_POST['card']['cardno'],
			'ccExpiryMonth' => $_POST['card']['exiperymonth'],
			'ccExpiryYear' => $_POST['card']['exiperyyear'],
			'cvvNumber' => $_POST['card']['cvv'],
			'customer_order_id' => $order_id,
			'response_url' => site_url('kryptova-callback'),
		);

		if( $order->status == "failed" ){
			$args['first_name'] = $order->get_billing_first_name();
			$args['last_name'] = $order->get_billing_last_name();
			$args['address'] = $order->get_billing_address_1();
			$args['country'] = $order->get_billing_country();
			$args['state'] = $order->get_billing_state();
			$args['city'] = $order->get_billing_city();
			$args['zip'] = $order->get_billing_postcode();
			$args['email'] = $order->get_billing_email();
			$args['phone_no'] = $order->get_billing_phone();
		}

		$curl = curl_init();
		$postData = json_encode($args);
		curl_setopt_array($curl, array(
		  CURLOPT_URL => $url,
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => '',
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 0,
		  CURLOPT_FOLLOWLOCATION => true,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => 'POST',
		  CURLOPT_POSTFIELDS =>$postData,
		  CURLOPT_HTTPHEADER => array(
			'Content-Type: application/json'
		  ),
		));

		$response = curl_exec($curl);
		
		curl_close($curl);
		$result = json_decode($response, true);		

		if(isset($result['status']) && $result['status'] == 'success'){
		// Mark as on-hold (we're awaiting the cheque)
		$order->update_status($this->order_status, __( 'Awaiting payment', 'woocommerce-kryptova-payment-gateway' ));
		// Reduce stock levels
		wc_reduce_stock_levels( $order_id );
		add_action('woocommerce_before_thankyou', 'custome_message_payment_kryptova_success');
		
		$order->add_order_note(esc_html('payment_order_id : '.$result['data']['order_id']),1);
		
		// Remove cart
		$woocommerce->cart->empty_cart();
		// Return thankyou redirect
		return array(
			'result' => 'success',
			'order_no' => $result['data']['order_id'],
			'redirect' => $this->get_return_url( $order )
		);
		}else if(isset($result['status']) && $result['status'] == '3d_redirect'){
			wc_reduce_stock_levels( $order_id );
			$order->update_status($this->order_status, __( 'Awaiting payment', 'woocommerce-kryptova-payment-gateway' ));
			$order->add_order_note(esc_html('Order goes to the 3ds redirect : '.$result['redirect_3ds_url']),1);
		
		// Remove cart
		$woocommerce->cart->empty_cart();
			
			return array(
			'result' => 'success',
			'redirect' => $result['redirect_3ds_url']
		);
			
		}else{
			wc_add_notice( __($result['message'],'woocommerce-kryptova-payment-gateway'), 'error');
			return false;
		}
	}

	public function payment_fields(){
	    ?>
		<fieldset>
			
             <div class='form-row'>
              <div class='col-xs-12 form-group card required'>
                <label class='control-label'>Card Number</label>
                <input autocomplete='off' name="card[cardno]" class='form-control card-number' size='16' type='text'>
              </div>
            </div>
            <div class='form-row'>
              
              <div class='col-xs-6 col-md-4 form-group expiration required'>
                <label class='control-label'>Expiration</label>
                <input class='form-control card-expiry-month' name="card[exiperymonth]" placeholder='MM' size='2' type='text'>
              </div>
              <div class='col-xs-6 col-md-4 form-group expiration required'>
                <label class='control-label'> </label>
                <input class='form-control card-expiry-year' name="card[exiperyyear]" placeholder='YYYY' size='4' type='text'>
              </div>
			  <div class='col-xs-6 col-md-4 form-group cvc required'>
                <label class='control-label'>CVC</label>
                <input autocomplete='off' class='form-control card-cvc' name="card[cvv]" placeholder='ex. 311' size='4' type='text'>
              </div>
            </div>
		</fieldset>
		<?php
	}
}



add_filter('query_vars', 'kryptova_query_vars');
add_action('init', 'kryptova_payment_callback_urls');

function kryptova_query_vars($vars){
  $vars[] = 'order_id';
  $vars[] = 'status';
  $vars[] = 'message';
  $vars[] = 'customer_order_id';
  return $vars;
}

function kryptova_payment_callback_urls() {

  add_rewrite_rule(
    '^kryptova-callback/(\w)?',
    'index.php?customer_order_id=$matches[1]',
    'top'
  );

}
add_action('parse_request', 'kryptova_total_callback');
function kryptova_total_callback( $wp ){
	$kryptovaTotalCallback = new KryptovaTotalCallback();
	$kryptovaTotalCallback->kryptovaCallback($wp);
}


class KryptovaTotalCallback extends WC_Payment_Gateway {
    public function kryptovaCallback( $wp ) {
    	$valid_actions = array('customer_order_id');

		if( isset($wp->query_vars['customer_order_id']) && !empty($wp->query_vars['customer_order_id']) ) {
			
			$orderId = $wp->query_vars['customer_order_id'];
			$status = $wp->query_vars['status'];
			$message = isset($wp->query_vars['reason']) ? $wp->query_vars['reason'] : '';
			if( empty($message) ){
				$message = isset( $wp->query_vars['message'] ) ? $wp->query_vars['message'] : "";
			}
			
			if( $status == "success" ){
				
				global $woocommerce;

				// we need it to get any order detailes
				$order = wc_get_order( $orderId );

				$order->payment_complete();
				$order->reduce_order_stock();
				$order->add_order_note( $message, true );
                $order->update_status( 'wc-completed', $message );
				$woocommerce->cart->empty_cart();
                wc_add_notice($message,'Success');
				wc_add_notice( __( $message, 'woocommerce' ), 'success' );
				$order_url = $this->get_return_url( $order );
				wp_redirect($order_url);
				exit;
				
			}else{
				global $woocommerce;
				$order = wc_get_order( $orderId );
				$order->add_order_note( $message, true );
                $order->update_status( 'wc-failed', $message );
                $woocommerce->cart->empty_cart();
                wc_add_notice($message,'Error');
				wc_add_notice( __( $message, 'woocommerce' ), 'error' );
				// wp_safe_redirect( wc_get_checkout_url() );
				// exit;
				$order_url = $this->get_return_url( $order );
				wp_redirect($order_url);
				exit;
				
			}

		}
    }
}