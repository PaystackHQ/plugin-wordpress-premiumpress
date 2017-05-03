<?php
/**
 * Plugin Name: Paystack gateway for premiumpress themes
 * Plugin URI: https://paystack.com
 * Description: Plugin to add Paystack payment gateway into Premium 
 * Version: 1.0
 * Author: Kendysond
 * Author URI: https://github.com/kendysond
 * License: GPLv2 or later
 */
//1. HOOK INTO THE GATEWAY ARRAY
function kkd_ppp_gateway_paystack_admin($gateways){
	$nId = count($gateways)+1;
	$gateways[$nId]['name'] 		= "Paystack";
	$gateways[$nId]['logo'] 		= plugins_url( 'images/logo.png' , __FILE__ );
	$gateways[$nId]['function'] 	= "kkd_ppp_gateway_paystack_form";
	$gateways[$nId]['website'] 		= "http://www.paystack.com";
	$gateways[$nId]['callback'] 	= "yes";
	$gateways[$nId]['ownform'] 		= "yes";
	$gateways[$nId]['fields'] 		= array(
	'1' => array('name' => 'Enable Gateway', 'type' => 'listbox','fieldname' => $gateways[$nId]['function'],'list' => array('yes'=>'Enable','no'=>'Disable',) ),

	'2' => array('name' => 'Enable Test mode', 'type' => 'listbox','fieldname' => 'paystack_test','list' => array( 1 =>'Yes',2=>'No',) ),
 	
	'3' => array('name' => 'Test Secret Key', 'type' => 'text', 'fieldname' => 'paystack_tsk'),
 
	'4' => array('name' => 'Test Public Key', 'type' => 'text', 'fieldname' => 'paystack_tpk'),
 	
	'5' => array('name' => 'Live Secret Key', 'type' => 'text', 'fieldname' => 'paystack_lsk'),
 
	'6' => array('name' => 'Live Public Key', 'type' => 'text', 'fieldname' => 'paystack_lpk'),
 	
	'7' => array('name' => 'Display Name', 'type' => 'text', 'fieldname' => 'paystack_displayname', 'default' => 'Pay with Paystack(Master,Visa and Verve)'),
	 
	
	);
	$gateways[$nId]['notes'] 	= "You can get your API keys <a href='https://dashboard.paystack.co/#/settings/developer' target='_blank' style='text-decoration:underline;'>here</a>
 
	";
	return $gateways;
}
add_action('hook_payments_gateways','kkd_ppp_gateway_paystack_admin');
//2. BUILD THE PAYMENT FORM DATA
function kkd_ppp_gateway_paystack_form($data=""){


	global $wpdb,$userdata;
	
	$test_key = get_option('paystack_tpk');
	$live_key = get_option('paystack_lpk');
	$mode = get_option('paystack_test');

	if ($mode == 1) {
		$key = $test_key;
		# code...
	}else{
		
		$key = $live_key;

	}
	
	
	
	if($GLOBALS['description'] == ""){ $GLOBALS['description'] = $GLOBALS['orderid']; }
	// echo "<pre>";

	$metadata = [
		[
			'display_name' => 'Description',
			'variable_name' => 'description',
			'value' => $GLOBALS["description"]
		]
	];
	$gatewaycode = '
	<div class="row-old">
	<div class="col-md-12"><b>'.get_option('paystack_displayname').'</b><br>
	<form action="'.$GLOBALS['CORE_THEME']['links']['callback'].'" method="POST" >
	<input type="hidden" name="orderid" value="'.$GLOBALS['orderid'] .'" />
	<input type="hidden" name="amount" value="'.$GLOBALS['total'] .'" />
	<input type="hidden" name="desc" value="'.$GLOBALS['description'] .'" />
	<input type="hidden" name="shipping" value="'.$GLOBALS['shipping'] .'" />
	<input type="hidden" name="tax" value="'.$GLOBALS['tax'] .'" />

	  <script
	    src="https://js.paystack.co/v1/inline.js" 
	    data-key="'.$key.'"
	    data-email="'.$userdata->user_email.'"
	    data-amount="'.($GLOBALS['total'] *100).'"
	    data-ref="'.$GLOBALS['orderid'].'"
	      data-metadata=\'{ "custom_fields":'.json_encode($metadata).'}\'
	  >
	  </script>
	</form>	   
	</div> <div class="clearfix"></div></div>'; 
	return $gatewaycode;
}
 
function kkd_ppp_gateway_paystack_callback($orderID){ global $CORE, $userdata;

	
 	if(isset($_POST['reference']) && isset($_POST['orderid'])){		 
		$test_key = get_option('paystack_tsk');
		$live_key = get_option('paystack_lsk');
		$mode = get_option('paystack_test');
		if ($mode == 1) {
			$key = $test_key;
		}else{
			$key = $live_key;

		}
				
		$paystack_url = 'https://api.paystack.co/transaction/verify/' . $_POST['reference'];

		$headers = array(
			'Authorization' => 'Bearer ' . $key
		);

		$args = array(
			'headers'	=> $headers,
			'timeout'	=> 60
		);

		$request = wp_remote_get( $paystack_url, $args );

        if ( ! is_wp_error( $request ) && 200 == wp_remote_retrieve_response_code( $request ) ) {

        	$paystack_response = json_decode( wp_remote_retrieve_body( $request ) );

			if ( 'success' == $paystack_response->data->status ) {


				core_generic_gateway_callback($_POST['orderid'], array(
				'description' =>  $_POST['desc'], 
				'email' => $userdata->user_email, 
				'shipping' => $_POST['shipping'], 
				'shipping_label' => '', 
				'tax' => $_POST['tax'], 
				'total' => str_replace(",","",$_POST['amount']) ) );

				return "success";	

			} else {
				return "error";
			}

        }
		
	} 	
}
add_action('hook_callback','kkd_ppp_gateway_paystack_callback');
?>