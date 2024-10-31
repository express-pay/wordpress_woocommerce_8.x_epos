<?php
/**
 * WC_Gateway_ExpressPay_Epos class
 *
 * @author   LLC "TriInkom"
 * @package  WooCommerce Expresspay Epos Payments Gateway
 * @since    1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Expresspay_Epos Gateway.
 *
 * @class    WC_Gateway_ExpressPay_Epos
 * @version  1.0.7
 */
class WC_Gateway_ExpressPay_Epos extends WC_Payment_Gateway {

	/**
	 * Payment gateway instructions.
	 * @var string
	 *
	 */
	protected $instructions;

	/**
	 * Whether the gateway is visible for non-admin users.
	 * @var boolean
	 *
	 */
	protected $hide_for_non_admin_users;

	/**
	 * Unique id for the gateway.
	 * @var string
	 *
	 */
	public $id = 'expresspay_epos';

	/**
	 * Constructor for the gateway.
	 */
	public function __construct() {
		
		$this->icon               = apply_filters( 'woocommerce_expresspay_epos_gateway_icon', '' );
		$this->has_fields         = false;
		$this->supports           = array(
			'pre-orders',
			'products',
		);

		$this->method_title       = __( 'Express Payments: EPOS', 'wordpress_epos_expresspay' );
		$this->method_description = __( 'Acceptance of payments in the EPOS system, service «Express Payments».', 'wordpress_epos_expresspay' );

		$this->title = $this->get_option('payment_method_title');
		$this->description = $this->get_option('payment_method_description');

		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		// Define user set variables.
		$this->title                    = $this->get_option( 'payment_method_title' );
		$this->description              = $this->get_option( 'payment_method_description' );
		$this->instructions             = $this->get_option( 'instructions', $this->description );
		$this->hide_for_non_admin_users = $this->get_option( 'hide_for_non_admin_users' );

		$this->token = $this->get_option('token');
		$this->service_id = $this->get_option('service_id');
		$this->secret_word = $this->get_option('secret_key');
		$this->is_use_signature_notify = ( $this->get_option('is_use_signature_notify') == 'yes' ) ? 1 : 0;
		$this->secret_key_notify = $this->get_option('secret_key_notify');
		$this->service_id = $this->get_option('service_id');
		$this->service_provider_epos_code =$this->get_option('service_provider_epos_code');
		$this->name_editable = ($this->get_option('name_editable') == 'yes') ? 1 : 0;
		$this->address_editable = ($this->get_option('address_editable') == 'yes') ? 1 : 0;
		$this->amount_editable = ($this->get_option('amount_editable') == 'yes') ? 1 : 0;
		$this->send_client_email = ($this->get_option('send_client_email') == 'yes') ? 1 : 0;
		$this->show_qr_code = ($this->get_option('show_qr_code') == 'yes') ? 1 : 0;
		$this->url_qr_code = ( $this->get_option('test_mode') != 'yes' ) ? $this->get_option('url_api') : $this->get_option('url_sandbox_api');
		$this->url_qr_code .= '/v1/qrcode/getqrcode/';
			
		$this->url = ($this->get_option('test_mode') != 'yes') ? $this->get_option('url_api') : $this->get_option('url_sandbox_api');
		$this->url .= "/v1/web_invoices";
		$this->test_mode = ( $this->get_option('test_mode') == 'yes' ) ? 1 : 0;

		$this->status_after_placing = $this->get_option('status_after_placing');
		$this->status_after_payment = $this->get_option('status_after_payment');
		$this->status_after_cancellation = $this->get_option('status_after_cancellation');
		
		// Actions.
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_receipt_' . $this->id, array($this, 'receipt_page') );
		add_action( 'woocommerce_api_' . $this->id, array($this, 'check_ipn_response') );
	}

	public function admin_options() {
		?>
		<h3><?php _e('Express Payments: EPOS', 'wordpress_epos_expresspay'); ?></h3>
		<div style="float: left; display: inline-block;">
			 <a target="_blank" href="https://express-pay.by"><img src="<?php echo WC_ExpressPay_Epos_Payments::plugin_url(); ?>/assets/images/erip_expresspay_big.png" width="270" height="91" alt="exspress-pay.by" title="express-pay.by"></a>
		</div>
		<div style="margin-left: 6px; margin-top: 15px; display: inline-block;">
			<?php _e('Express Payments: EPOS - plugin for integration with the Express Payments service (express-pay.by) via API.
			<br/>The plugin allows you to issue an invoice for a card payment, receive and process a payment notification.
			<br/>The plugin description is available at: ', 'wordpress_epos_expresspay'); ?><a target="blank" href="https://express-pay.by/cms-extensions/wordpress#woocommerce_8_x">https://express-pay.by/cms-extensions/wordpress#woocommerce_8_x</a>
		</div>

		<table class="form-table">
			<?php		
				$this->generate_settings_html();
			?>
		</table>
		
		<div class="copyright" style="text-align: center;">
			<?php _e("© All rights reserved | ООО «TriInkom»,", 'woocommerce-gateway-expresspay-card'); ?> 2013-<?php echo date("Y"); ?><br/>
			<?php echo __('Version', 'wordpress_epos_expresspay') . " " . EXPRESSPAY_EPOS_VERSION ?>			
		</div>
		<?php
	}

	/**
	 * Initialise Gateway Settings Form Fields.
	 */
	public function init_form_fields() {
		
		$this->form_fields = array(
			'enabled' => array(
				'title'   => __('Enable/Disable', 'wordpress_epos_expresspay'),
				'type'    => 'checkbox',
				'default' => 'no'
			),
			'hide_for_non_admin_users' => array(
				'type'    => 'checkbox',
				'label'   => __( 'Hide at checkout for non-admin users', 'wordpress_epos_expresspay' ),
				'default' => 'no',
			),
			'token' => array(
				'title'   => __('Token', 'wordpress_epos_expresspay'),
				'type'    => 'text',
				'description' => __('Generated in the panel express-pay.by', 'wordpress_epos_expresspay'),
				'desc_tip'    => true
			),
			'service_id' => array(
				'title'   => __('Service number', 'wordpress_epos_expresspay'),
				'type'    => 'text',
				'description' => __('Service number in express-pay.by', 'wordpress_epos_expresspay'),
				'desc_tip'    => true
			),
			'secret_key' => array(
				'title'   => __('Secret word for signing invoices', 'wordpress_epos_expresspay'),
				'type'    => 'text',
				'description' => __('A secret word that is known only to the server and the client. Used to generate a digital signature. Set in the panel express-pay.by', 'wordpress_epos_expresspay'),
				'desc_tip'    => true
			),
			'handler_url' => array(
				'title'   => __('Address for notifications', 'wordpress_epos_expresspay'),
				'type'    => 'text',
				'css' => 'display: none;',
				'description' => get_site_url() . '/?wc-api=expresspay_epos&action=notify'
			),
			'is_use_signature_notify' => array(
				'title'   => __('Use digitally sign notifications', 'wordpress_epos_expresspay'),
				'type'    => 'checkbox',
				'description' => __('Use digitally sign notifications', 'wordpress_epos_expresspay'),
				'desc_tip'    => true
			),
			'secret_key_notify' => array(
				'title'   => __('Secret word for signing notifications', 'wordpress_epos_expresspay'),
				'type'    => 'text',
				'description' => __('A secret word that is known only to the server and the client. Used to generate a digital signature. Set in the panel express-pay.by', 'wordpress_epos_expresspay'),
				'desc_tip'    => true
			),
			'test_mode' => array(
				'title'   => __('Use test mode', 'wordpress_epos_expresspay'),
				'type'    => 'checkbox'
			),
			'url_api' => array(
				'title'   => __('API address', 'wordpress_epos_expresspay'),
				'type'    => 'text',
				'default' => 'https://api.express-pay.by'
			),
			'url_sandbox_api' => array(
				'title'   => __('Test API address', 'wordpress_epos_expresspay'),
				'type'    => 'text',
				'default' => 'https://sandbox-api.express-pay.by'
			),
			'show_qr_code' => array(
				'title'   => __('Show QR code for payment', 'wordpress_epos_expresspay'),
				'type'    => 'checkbox',
				'description' => __('Show QR code for payment', 'wordpress_epos_expresspay'),
				'desc_tip'    => true
			),
			'service_provider_epos_code' => array(
				'title'   => __('Code in EPOS', 'wordpress_epos_expresspay'),
				'type'    => 'text',
				'description' => __('The code in EPOS specified in my personal account', 'wordpress_epos_expresspay'),
				'desc_tip'    => true
			),
			'name_editable' => array(
				'title'   => __('It is allowed to change the name of the payer', 'wordpress_epos_expresspay'),
				'type'    => 'checkbox',
				'description' => __('It is allowed to change the name of the payer when paying the invoice', 'wordpress_epos_expresspay'),
				'desc_tip'    => true
			),
			'address_editable' => array(
				'title'   => __("Allowed to change the payer's address", 'wordpress_epos_expresspay'),
				'type'    => 'checkbox',
				'description' => __("It is allowed to change the payer's address when paying an invoice", 'wordpress_epos_expresspay'),
				'desc_tip'    => true
			),
			'amount_editable' => array(
				'title'   => __('Allowed to change the amount of payment', 'wordpress_epos_expresspay'),
				'type'    => 'checkbox',
				'description' => __('It is allowed to change the payment amount when paying an invoice', 'wordpress_epos_expresspay'),
				'desc_tip'    => true
			),
			'send_client_email' => array(
				'title'   => __('Send email notification to client', 'wordpress_epos_expresspay'),
				'type'    => 'checkbox'
			),
			'test_mode' => array(
				'title'   => __('Use test mode', 'wordpress_epos_expresspay'),
				'type'    => 'checkbox'
			),
			'url_api' => array(
				'title'   => __('API address', 'wordpress_epos_expresspay'),
				'type'    => 'text',
				'default' => 'https://api.express-pay.by'
			),
			'url_sandbox_api' => array(
				'title'   => __('Test API address', 'wordpress_epos_expresspay'),
				'type'    => 'text',
				'default' => 'https://sandbox-api.express-pay.by'
			),
			'status_after_placing' => array(
				'title'       => __('Status after invoicing', 'wordpress_epos_expresspay'),
				'type'        => 'select',
				'description' => __('The status that the order will have after invoicing', 'wordpress_epos_expresspay'),
				'options'     => wc_get_order_statuses(),
				'desc_tip'    => true,
			),
			'status_after_payment' => array(
				'title'       => __( 'Status after payment', 'wordpress_epos_expresspay' ),
				'type'        => 'select',
				'description' => __( 'The status that the order will have after payment', 'wordpress_epos_expresspay' ),
				'options'     => wc_get_order_statuses(),
				'desc_tip'    => true,
			),
			'status_after_cancellation'    => array(
				'title'       => __( 'Status after cancellation', 'wordpress_epos_expresspay' ),
				'type'        => 'select',
				'description' => __( 'The status that the order will have after cancellation', 'wordpress_epos_expresspay' ),
				'options'     => wc_get_order_statuses(),
				'desc_tip'    => true,
			),
			'payment_method_title' => array(
				'title'   => __('Payment method name', 'wordpress_epos_expresspay'),
				'type'    => 'text',
				'description' => __('The name that will be displayed in the cart when choosing a payment method', 'wordpress_epos_expresspay'),
				'default' 	=> __("Express Payments: EPOS",'wordpress_epos_expresspay'),
				'desc_tip'    => true
			),
			'payment_method_description' => array(
				'title'   => __('Description of the payment method', 'wordpress_epos_expresspay'),
				'type'    => 'text',
				'description' => __('Description that will be displayed in the payment method settings', 'wordpress_epos_expresspay'),
				'default' 	=> __("Payment by epos service Express Payments",'wordpress_epos_expresspay'),
				'desc_tip'    => true
			),
		);
	}

	/**
	 * Process the payment and return the result.
	 *
	 * @param  int  $order_id
	 * @return array
	 */
	public function process_payment( $order_id ) {

		$this->log_info('process_payment', 'Initialization request for add invoice');
		
		$order = wc_get_order( $order_id );

		return array(
			'result' => 'success',
			'redirect'	=> add_query_arg('order-pay', $order->get_order_number( ), add_query_arg('key', $order->get_order_key(), get_permalink(wc_get_page_id('pay'))))
		);
	}
	
	
	function receipt_page($order_id)
	{
		$this->log_info('receipt_page', 'Initialization request for add invoice');
		
		$order = new WC_Order($order_id);

		$price = preg_replace('#[^\d.]#', '', $order->get_total());
		$price = str_replace('.', ',', $price);
		$client_email = "";

		$client_phone = preg_replace('/[^0-9]/', '', $order->get_billing_phone());
		$client_phone = substr($client_phone, -9);
		$client_phone = "375$client_phone";

		if ($this->send_client_email) {
			$client_email = $order->get_billing_email();
		}

		$currency = (date('y') > 16 || (date('y') <= 16 && date('n') >= 7)) ? '933' : '974';

		$request_params = array(
			"ServiceId" => $this->service_id,
			"AccountNo" => $order_id,
			"Amount" => $price,
			"Currency" => $currency,
			"Surname" => mb_strimwidth($order->get_billing_last_name(), 0, 30),
			"FirstName" => mb_strimwidth($order->get_billing_first_name(), 0, 30),
			"City" => mb_strimwidth($order->get_billing_city(), 0, 30),
			"IsNameEditable" => $this->name_editable,
			"IsAddressEditable" => $this->address_editable,
			"IsAmountEditable" => $this->amount_editable,
			"EmailNotification" => $client_email,
			"SmsPhone" => $client_phone,
			"ReturnType" => "json",
			"ReturnUrl" => get_site_url(),
			"FailUrl" => get_site_url(),
			"ReturnInvoiceUrl" => 0
		);

		$request_params['Signature'] = $this->compute_signature($request_params, $this->secret_word, 'add_invoice');

		$args = array(
			'body'        => $request_params,
			'timeout'     => '5',
			'redirection' => '5',
			'httpversion' => '1.0',
			'blocking'    => true,
			'headers'     => array(),
			'cookies'     => array(),
		);

		$response = wp_remote_post($this->url, $args);

		$this->log_info('receipt_page', 'Get response; ORDER ID - ' . $order_id . '; RESPONSE - ' . $response['body']);

		try {
			$response = json_decode($response['body']);
		} catch (Exception $e) {
			$this->log_error_exception('receipt_page', 'Get response; ORDER ID - ' . $order_id . '; RESPONSE - ' . $response, $e);

			$this->fail($order_id, $e);
		}

		if (isset($response->ExpressPayInvoiceNo))
			$this->success($order_id, $response->ExpressPayInvoiceNo);
		else
			$this->fail($order_id, $response->Errors);
	}

	private function success($order_id, $invoiceId)	{

		global $woocommerce;

		$order = new WC_Order($order_id);

		$this->log_info('receipt_page', 'End request for add invoice');
		$this->log_info('success', 'Initialization render success page; ORDER ID - ' . $order->get_order_number());

		$woocommerce->cart->empty_cart();

		$order->update_status($this->status_after_placing, __('Invoice successfully issued and awaiting payment', 'wordpress_epos_expresspay'));

		$message_success = __('<h3>Account added to the EPOS system for payment</h3>
		<h4>Your order number: ##epos_code##</h4>
		You need to make a payment in any system that allows you to pay through ERIP (items banking services, ATMs,
		payment terminals, Internet banking systems, client banking, etc.).
		<br/> 1. To do this, in the list of ERIP services go to the section:<br/><b>##erip_path##</b>
		<br/> 2. Next, enter the order number <b>##epos_code##</b> and click "Continue"
		<br/> 3. Check if the information is correct. 
		<br/> 4. Make a payment.</td> ', 'wordpress_epos_expresspay');

		$epos_code  = $this->service_provider_epos_code ."-";
		$epos_code .= $order->get_order_number();

		$message_success = str_replace("##epos_code##", $epos_code, $message_success);
		$message_success = str_replace("##erip_path##", '<b>Система "Расчет" (ЕРИП)-&gt;Сервис E-POS-&gt;E-POS - оплата товаров и услуг</b>', $message_success);

		if ($this->show_qr_code) {
			$request_params = array(
				'Token' => $this->token,
				'InvoiceId' => $invoiceId,
				'ViewType' => 'base64'
			);

			$request_params["Signature"] =  $this->compute_signature($request_params, $this->secret_word, 'get_qr_code');

			$request_params = http_build_query($request_params);

			$response = wp_remote_get($this->url_qr_code . '?' . $request_params);

			try {
				$response = json_decode($response['body']);
			} catch (Exception $e) {
				$this->log_error_exception('receipt_page', 'Get response; ORDER ID - ' . $order_id . '; RESPONSE - ' . $response['body'], $e);
				return;
			}

			if (isset($response->QrCodeBody)) {
				$qr_code = $response->QrCodeBody;

				$message_success = $message_success . "<td style=\"text-align: center;padding: 40px 20px 0 0;vertical-align: middle\">
				<br/>##OR_CODE##<br/><p><b>##OR_CODE_DESCRIPTION##</b></p></td></tr></tbody></table>";

				$message_success = str_replace('##OR_CODE##', '<img src="data:image/jpeg;base64,' . $qr_code . '"  width="200" height="200"/>',  $message_success);
				$message_success = str_replace('##OR_CODE_DESCRIPTION##', __('Scan the QR code to pay', 'wordpress_epos_expresspay'),  $message_success);
				echo $message_success;
			}
		} else echo $message_success;


		echo '<br/><br/><p class="return-to-shop"><a class="button wc-backward" href="' . get_permalink(wc_get_page_id("shop")) . '">' . __('Proceed', 'wordpress_epos_expresspay') . '</a></p>';

		$signature_success = $signature_cancel = "";

		if ($this->is_use_signature_notify) {
			$signature_success = $this->compute_signature_from_json('{"CmdType": 1, "AccountNo": ' . $order->get_order_number() . '}', $this->secret_key_notify);
			$signature_cancel = $this->compute_signature_from_json('{"CmdType": 2, "AccountNo": ' . $order->get_order_number() . '}', $this->secret_key_notify);
		}

		if ($this->test_mode) : ?>
			<div class="test_mode">
				<?php echo __('Test mode:', 'wordpress_epos_expresspay'); ?> <br />
				<input type="button" style="margin: 6px 0;" class="button" id="send_notify_success" value="<?php echo __('Send notification of successful payment', 'wordpress_epos_expresspay'); ?>" />
				<input type="button" class="button" style="margin: 6px 0;" id="send_notify_cancel" class="btn btn-primary" value="<?php echo __('Send payment cancellation notification', 'wordpress_epos_expresspay'); ?>" />

				<script type="text/javascript">
					jQuery(document).ready(function() {
						jQuery('#send_notify_success').click(function() {
							send_notify(1, '<?php echo $signature_success; ?>');
						});

						jQuery('#send_notify_cancel').click(function() {
							send_notify(2, '<?php echo $signature_cancel; ?>');
						});

						function send_notify(type, signature) {
							jQuery.post('<?php echo get_site_url() . "/?wc-api=expresspay_epos&action=notify" ?>', 'Data={"CmdType": ' + type + ', "AccountNo": <?php echo $order->get_order_number(); ?>}&Signature=' + signature, function(data) {
									alert(data);
								})
								.fail(function(data) {
									alert(data.responseText);
								});
						}
					});
				</script>

			</div>
<?php
		endif;

		$this->log_info('success', 'End render success page; ORDER ID - ' . $order->get_order_number());
	}

	private function fail($order_id, $errors) {

		global $woocommerce;

		$order = new WC_Order($order_id);

		$this->log_info('receipt_page', 'End request for add invoice');
		$this->log_info('fail', 'Initialization render fail page; ORDER ID - ' . $order->get_order_number());

		$order->update_status($this->status_after_cancellation, $errors[0]);

		echo '<h2>' . __('Error billing in the EPOS system', 'wordpress_epos_expresspay') . '</h2>';
		echo __("An unexpected error occurred while executing the request. Please try again later or contact the store's technical support", 'wordpress_epos_expresspay');

		echo '<br/><br/><p class="return-to-shop"><a class="button wc-backward" href="' . wc_get_checkout_url() . '">' . __('Try again', 'wordpress_epos_expresspay') . '</a></p>';

		$this->log_info('fail', 'End render fail page; ORDER ID - ' . $order->get_order_number());

		die();
	}

	function check_ipn_response() {
		$this->log_info('check_ipn_response', 'Get notify from server; REQUEST METHOD - ' . $_SERVER['REQUEST_METHOD']);

		if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_REQUEST['action']) && $_REQUEST['action'] == 'notify') {
			$data = ( isset($_REQUEST['Data']) ) ? htmlspecialchars_decode($_REQUEST['Data']) : '';
			$data = stripcslashes($data);
			$signature = ( isset($_REQUEST['Signature']) ) ? $_REQUEST['Signature'] : '';

			if($this->is_use_signature_notify) {
				if($signature == $this->compute_signature_from_json($data, $this->secret_key_notify))
					$this->notify_success($data);
				else  
					$this->notify_fail($data, $signature, $this->compute_signature_from_json($data, $this->secret_key_notify), $this->secret_key_notify);
			} else 
				$this->notify_success($data);
		}

		$this->log_info('check_ipn_response', 'End (Get notify from server); REQUEST METHOD - ' . $_SERVER['REQUEST_METHOD']);

		die();
	}

	private function notify_success($dataJSON) {
		global $woocommerce;

		try {
			$data = json_decode($dataJSON);
		} catch(Exception $e) {
			$this->log_error('notify_success', "Fail to parse the server response; RESPONSE - " . $dataJSON);

			$this->notify_fail($dataJSON);
		}

		$order = new WC_Order($data->AccountNo);

		if(isset($data->CmdType)) {
			switch ($data->CmdType) {
				case '1':
					$order->update_status($this->status_after_payment, __('The bill is paid', 'wordpress_epos_expresspay'));
					$this->log_info('notify_success', 'Initialization to update status. STATUS ID - Счет ожидает оплаты; RESPONSE - ' . $dataJSON);

					break;
				case '2':
					$order->update_status($this->status_after_cancellation, __('Payment canceled', 'wordpress_epos_expresspay'));
					$this->log_info('notify_success', 'Initialization to update status. STATUS ID - Платеж отменён; RESPONSE - '. $dataJSON);

					break;
				case '3':
					if($data->Status == '1'){
						$order->update_status($this->status_after_placing, __('Invoice awaiting payment', 'wordpress_epos_expresspay'));
						$this->log_info('notify_success', 'Initialization to update status. STATUS ID - Счет ожидает оплаты; RESPONSE - '. $dataJSON);
					}
					elseif($data->Status == '2'){
						$order->update_status($this->status_after_cancellation, __('Invoice expired', 'wordpress_epos_expresspay'));
						$this->log_info('notify_success', 'Initialization to update status. STATUS ID - Счет просрочен; RESPONSE - '. $dataJSON);
					}
					elseif($data->Status == '3'){
						$order->update_status($this->status_after_payment, __('The bill is paid', 'wordpress_epos_expresspay'));
						$this->log_info('notify_success', 'Initialization to update status. STATUS ID - Счет оплачен; RESPONSE - '. $dataJSON);
					}
					elseif($data->Status == '5'){
					
						$order->update_status($this->status_after_cancellation, __('Invoice canceled', 'wordpress_epos_expresspay'));
						$this->log_info('notify_success', 'Initialization to update status. STATUS ID - Счет отменен; RESPONSE - '. $dataJSON);
					}
					elseif($data->Status == '6'){
					
						$order->update_status($this->status_after_payment, __('Invoice paid by card', 'wordpress_epos_expresspay'));
						$this->log_info('notify_success', 'Initialization to update status. STATUS ID - Счет оплачен картой; RESPONSE - '. $dataJSON);
					}
					break;
				default:
					$this->notify_fail($dataJSON);
					die();
			}

			header("HTTP/1.0 200 OK");
			echo 'SUCCESS';
		} else
			$this->notify_fail($dataJSON);	
	}

	private function notify_fail($dataJSON, $signature='', $computeSignature='', $secret_key='') {
		$this->log_error('notify_fail', "Fail to update status; RESPONSE - " . $dataJSON . ', signature - ' . $signature . ', Compute signature - '. $computeSignature . ', secret key - ' . $secret_key);
		
		header("HTTP/1.0 400 Bad Request");
		echo 'FAILED | Incorrect digital signature';
	}

	private function compute_signature_from_json($json, $secret_word)
	{
		$hash = NULL;
		$secret_word = trim($secret_word);

		if (empty($secret_word))
			$hash = strtoupper(hash_hmac('sha1', $json, ""));
		else
			$hash = strtoupper(hash_hmac('sha1', $json, $secret_word));

		return $hash;
	}

	private function compute_signature($request_params, $secret_word, $method = 'add_invoice')
	{
		$secret_word = trim($secret_word);
		$normalized_params = array_change_key_case($request_params, CASE_LOWER);
		$api_method = array(
			'add_invoice' => array(
				"serviceid",
				"accountno",
				"amount",
				"currency",
				"expiration",
				"info",
				"surname",
				"firstname",
				"patronymic",
				"city",
				"street",
				"house",
				"building",
				"apartment",
				"isnameeditable",
				"isaddresseditable",
				"isamounteditable",
				"emailnotification",
				"smsphone",
				"returntype",
				"returnurl",
				"failurl",
				"returninvoiceurl"
			),
			'get_qr_code' => array(
				"invoiceid",
				"viewtype",
				"imagewidth",
				"imageheight"
			)
		);

		$result = $this->token;

		foreach ($api_method[$method] as $item)
			$result .= (isset($normalized_params[$item])) ? $normalized_params[$item] : '';

		$this->log_info('compute_signature', 'RESULT - ' . $result);

		$hash = strtoupper(hash_hmac('sha1', $result, $secret_word));

		return $hash;
	}
	
	private function log_error_exception($name, $message, $e) {
		$this->log($name, "ERROR" , $message . '; EXCEPTION MESSAGE - ' . $e->getMessage() . '; EXCEPTION TRACE - ' . $e->getTraceAsString());
	}

	private function log_error($name, $message) {
		$this->log($name, "ERROR" , $message);
	}

	private function log_info($name, $message) {
		$this->log($name, "INFO" , $message);
	}

	private function log($name, $type, $message) {
		$log_url = wp_upload_dir();
		$log_url = $log_url['basedir'] . "/epos_expresspay";

		if(!file_exists($log_url)) {
			$is_created = mkdir($log_url, 0777);

			if(!$is_created)
				return;
		}

		$log_url .= '/express-pay-' . date('Y.m.d') . '.log';

		file_put_contents($log_url, $type . " - IP - " . sanitize_text_field($_SERVER['REMOTE_ADDR']) .  "; DATETIME - " .date("Y-m-d H:i:s").  "; USER AGENT - " . sanitize_text_field($_SERVER['HTTP_USER_AGENT']) . "; FUNCTION - " . $name . "; MESSAGE - " . $message . ';' . PHP_EOL, FILE_APPEND);
	}
}
