<?php
/**
 * @package   WCGatewayElavon/Classes/Payment
 * @author    Justin Stern
 * @copyright Copyright (c) 2013, Justin Stern
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Gateway class
 */
class WC_Gateway_Elavon_VM extends WC_Payment_Gateway {

	private $demo_endpoint_url = "https://demo.myvirtualmerchant.com/VirtualMerchantDemo/processxml.do";
	private $live_endpoint_url = "https://www.myvirtualmerchant.com/VirtualMerchant/processxml.do";

	private $account;
	private $testmode;
	private $debug;
	private $log;
	private $settlement;
	private $cvv;
	private $cardtypes;
	private $ssl_merchant_id;
	private $ssl_user_id;
	private $ssl_pin;
	private $demo_ssl_merchant_id;
	private $demo_ssl_user_id;
	private $demo_ssl_pin;


	/**
	 * __construct function.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {

		global $woocommerce;

		$this->id                 = 'elavon_vm';
		$this->method_title       = __( 'Elavon VM', 'wc_elavon' );
		$this->method_description = __( 'Elavon VM Payment Gateway provides a seamless and secure checkout process for your customers', 'wc_elavon' );

		// to set up the images icon for your shop, use the included images/cards.png
		//  for the card images you accept, and hook into this filter with a return
		//  value like: plugins_url( '/images/cards.png', __FILE__ );
		$this->icon               = apply_filters( 'woocommerce_elavon_vm_icon', '' );

		// define the default card type options, and allow plugins to add in additional ones.
		//  Additional display names can be associated with a single card type by using the
		//  following convention: VISA: Visa, VISA-1: Visa Debit, etc
		$default_card_type_options = array(
			'VISA' => 'Visa',
			'MC'   => 'MasterCard',
			'AMEX' => 'American Express',
			'DISC' => 'Discover',
			'JCB'  => 'JCB'
		);
		$this->card_type_options = apply_filters( 'woocommerce_elavon_card_types', $default_card_type_options );

		// Load the form fields.
		$this->init_form_fields();

		// Load the settings.
		$this->init_settings();

		// Define user set variables
		$this->enabled              = $this->settings['enabled'];
		$this->title                = $this->settings['title'];
		$this->description          = $this->settings['description'];
		$this->account              = $this->settings['account'];
		$this->testmode             = $this->settings['testmode'];
		$this->debug                = $this->settings['debug'];
		$this->log                  = $this->settings['log'];
		$this->settlement           = $this->settings['settlement'];
		$this->cvv                  = $this->settings['cvv'];
		$this->cardtypes            = $this->settings['cardtypes'];
		$this->ssl_merchant_id      = $this->settings['sslmerchantid'];
		$this->ssl_user_id          = $this->settings['ssluserid'];
		$this->ssl_pin              = $this->settings['sslpin'];
		$this->demo_ssl_merchant_id = $this->settings['demo_ssl_merchant_id'];
		$this->demo_ssl_user_id     = $this->settings['demo_ssl_user_id'];
		$this->demo_ssl_pin         = $this->settings['demo_ssl_pin'];

		if ( $this->is_test_mode() ) $this->description .= ' ' . __( 'TEST MODE ENABLED', 'wc_elavon' );
		if ( $this->account == 'demo' ) $this->description .= ' ' . __( 'ENVIRONMENT: DEMO', 'wc_elavon' );

		add_action( 'woocommerce_receipt_' . $this->id, array( $this, 'receipt_page' ) );

		if ( is_admin() ) {
			add_action( 'woocommerce_update_options_payment_gateways',              array( $this, 'process_admin_options' ) );  // WC < 2.0
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );  // WC >= 2.0
		}
	}


	/**
	 * Initialise Settings Form Fields
	 *
	 * Add an array of fields to be displayed
	 * on the gateway's settings screen.
	 */
	function init_form_fields() {
		$this->form_fields = array(
			'enabled' => array(
				'title'       => __( 'Enable', 'wc_elavon' ),
				'label'       => __( 'Enable Elavon VM', 'wc_elavon' ),
				'type'        => 'checkbox',
				'description' => '',
				'default'     => 'no'
			),
			'title' => array(
				'title'       => __( 'Title', 'wc_elavon' ),
				'type'        => 'text',
				'description' => __( 'Payment method title that the customer will see on your website.', 'wc_elavon' ),
				'default'     => __( 'Credit Card', 'wc_elavon' )
			),
			'description' => array(
				'title'       => __( 'Description', 'wc_elavon' ),
				'type'        => 'textarea',
				'description' => __( 'Payment method description that the customer will see on your website.', 'wc_elavon' ),
				'default'     => __( 'Pay securely using your credit card.', 'wc_elavon' )
			),
			'account' => array(
				'title'       => __( 'Account', 'wc_elavon' ),
				'type'        => 'select',
				'description' => __( 'What account do you want your transactions posted to?', 'wc_elavon' ),
				'default'     => 'production',
				'options'     => array(
					'production' => __( 'Production', 'wc_elavon' ),
					'demo'       => __( 'Demo',       'wc_elavon' )
				),
			),
			'testmode' => array(
				'title'       => __( 'Test Mode', 'wc_elavon' ),
				'label'       => __( 'Enable Test Mode', 'wc_elavon' ),
				'type'        => 'checkbox',
				'description' => __( 'Place the payment gateway in test mode for your production account, transactions will not be posted to your account or credit card processor.', 'wc_elavon' ),
				'default'     => 'no'
			),
			'debug' => array(
				'title'       => __( 'Debug Mode', 'wc_elavon' ),
				'label'       => __( 'Enable Debug Mode', 'wc_elavon' ),
				'type'        => 'checkbox',
				'description' => __( 'Output the response from Elavon on the payment page for debugging purposes.', 'wc_elavon' ),
				'default'     => 'no'
			),
			'log' => array(
				'title'       => __( 'Communication Log', 'wc_elavon' ),
				'label'       => __( 'Enable the communication log', 'wc_elavon' ),
				'type'        => 'checkbox',
				'description' => __( 'Log all Elavon gateway communication to woocommerce/logs/elavon_vm.txt', 'wc_elavon' ),
				'default'     => 'no'
			),
			'settlement' => array(
				'title'       => __( 'Submit for Settlement', 'wc_elavon' ),
				'label'       => __( 'Submit all transactions for settlement immediately.', 'wc_elavon' ),
				'type'        => 'checkbox',
				'description' => '',
				'default'     => 'yes'
			),
			'cvv' => array(
				'title'       => __( 'Card Verification', 'wc_elavon' ),
				'label'       => __( 'Require customer to enter credit card verification code', 'wc_elavon' ),
				'type'        => 'checkbox',
				'default'     => 'no'
			),
			'cardtypes'	=> array(
				'title'        => __( 'Accepted Card Logos', 'wc_elavon' ),
				'type'        => 'multiselect',
				'description' => __( 'Select which card types you accept to display the logos for on your checkout page.  This is purely cosmetic and optional, and will have no impact on the cards actually accepted by your account.', 'wc_elavon' ),
				'default'     => array( 'VISA', 'MC', 'AMEX', 'DISC' ),
				'options'     => $this->card_type_options,
			),
			'sslmerchantid' => array(
				'title'       => __( 'Merchant ID', 'wc_elavon' ),
				'type'        => 'text',
				'description' => __( 'VirtualMerchant ID/Account ID as provided by Elavon.  This will be six digits long, and start with the number 5 or 6.', 'wc_elavon' ),
				'default'     => ''
			),
			'ssluserid' => array(
				'title'       => __( 'User ID', 'wc_elavon' ),
				'type'        => 'text',
				'description' => __( 'Virtual Merchant user ID as configured on Virtual Merchant', 'wc_elavon' ),
				'default'     => ''
			),
			'sslpin' => array(
				'title'       => __( 'PIN', 'wc_elavon' ),
				'type'        => 'password',
				'description' => __( 'VirtualMerchant PIN as generated within VirtualMerchant', 'wc_elavon' ),
				'default'     => ''
			),
			'demo_ssl_merchant_id' => array(
				'title'       => __( 'Demo Merchant ID', 'wc_elavon' ),
				'type'        => 'text',
				'description' => __( 'VirtualMerchant ID/Account ID as provided by Elavon for your demo account.  This will be six digits long, and start with the number 5 or 6.', 'wc_elavon' ),
				'default'     => ''
			),
			'demo_ssl_user_id' => array(
				'title'       => __( 'Demo User ID', 'wc_elavon' ),
				'type'        => 'text',
				'description' => __( 'Virtual Merchant demo user ID as configured on Virtual Merchant', 'wc_elavon' ),
				'default'     => ''
			),
			'demo_ssl_pin' => array(
				'title'       => __( 'Demo PIN', 'wc_elavon' ),
				'type'        => 'password',
				'description' => __( 'VirtualMerchant demo PIN as generated within VirtualMerchant', 'wc_elavon' ),
				'default'     => ''
			)
		);
	}


	/**
	 * Override the admin options method to add a little javascript to control
	 * how the gateway settings behave
	 */
	function admin_options() {

		global $woocommerce;

		// allow parent to do its thing
		parent::admin_options();

		// 'testmode' only applies to production accounts
		ob_start();
		?>
		$('#woocommerce_elavon_vm_account').change(
			function() {
				var testmode_row = $('#woocommerce_elavon_vm_testmode').closest('tr');
				if ($(this).val() == 'production') {
					testmode_row.show();
				} else {
					testmode_row.hide();
				}
			}).change();
		<?php
		$javascript = ob_get_clean();
		$woocommerce->add_inline_js( $javascript );
	}


	/**
	 * get_icon function.
	 *
	 * @access public
	 * @return string
	 */
	function get_icon() {
		global $woocommerce, $wc_elavon_vm;

		$icon = '';
		if ( $this->icon ) {
			// default behavior
			$icon = '<img src="' . esc_url( $woocommerce->force_ssl( $this->icon ) ) . '" alt="' . esc_attr( $this->title ) . '" />';
		} elseif ( $this->cardtypes ) {
			// display icons for the selected card types
			$icon = '';
			foreach ( $this->cardtypes as $cardtype ) {
				if ( file_exists( $wc_elavon_vm->plugin_path() . '/images/card-' . strtolower( $cardtype ) . '.png' ) ) {
					$icon .= '<img src="' . esc_url( $woocommerce->force_ssl( $wc_elavon_vm->plugin_url() . '/images/card-' . strtolower( $cardtype ) . '.png' ) ) . '" alt="' . esc_attr( strtolower( $cardtype ) ) . '" />';
				}
			}
		}

		return apply_filters( 'woocommerce_gateway_icon', $icon, $this->id );
	}


	/**
	 * Payment fields
	 **/
	function payment_fields() {
		?>
		<style type="text/css">#payment ul.payment_methods li label[for='payment_method_elavon_vm'] img:nth-child(n+2) { margin-left:1px; }</style>
		<fieldset>
			<?php if ( $this->get_description() ) : ?><?php echo wpautop( wptexturize( $this->get_description() ) ); ?><?php endif; ?>

			<p class="form-row form-row-first">
				<label for="elavon_vm_accountNumber"><?php echo __( "Credit Card number", 'wc_elavon' ) ?> <span class="required">*</span></label>
				<input type="text" class="input-text" id="elavon_vm_accountNumber" name="elavon_vm_accountNumber" maxlength="19" autocomplete="off" />
			</p>
			<p class="form-row form-row-last">
				<label for="elavon_vm_expirationMonth"><?php echo __( "Expiration date", 'wc_elavon' ) ?> <span class="required">*</span></label>
				<select name="elavon_vm_expirationMonth" id="elavon_vm_expirationMonth" class="woocommerce-select woocommerce-cc-month" style="width:auto;">
					<option value=""><?php _e( 'Month', 'wc_elavon' ) ?></option>
					<?php foreach ( range( 1, 12 ) as $month ) : ?>
						<option value="<?php echo sprintf( '%02d', $month ) ?>"><?php echo sprintf( '%02d', $month ) ?></option>
					<?php endforeach; ?>
				</select>
				<select name="elavon_vm_expirationYear" id="elavon_vm_expirationYear" class="woocommerce-select woocommerce-cc-year" style="width:auto;">
					<option value=""><?php _e( 'Year', 'wc_elavon' ) ?></option>
					<?php foreach ( range( date( 'Y' ), date( 'Y' ) + 6 ) as $year ) : ?>
						<option value="<?php echo $year ?>"><?php echo $year ?></option>
					<?php endforeach; ?>
				</select>
			</p>
			<div class="clear"></div>

			<?php if ( $this->cvv_required() ) : ?>

			<p class="form-row form-row-first">
				<label for="elavon_vm_cvNumber"><?php _e( "Card security code", 'wc_elavon' ) ?> <span class="required">*</span></label>
				<input type="text" class="input-text" id="elavon_vm_cvNumber" name="elavon_vm_cvNumber" maxlength="4" style="width:60px" autocomplete="off" />
			</p>
			<?php endif ?>
		</fieldset>
		<?php
	}


	/**
	 * Process the payment and return the result
	 */
	function process_payment( $order_id ) {
		global $woocommerce, $wc_elavon_vm;

		$order = new WC_Order( $order_id );

		require_once( 'class-wc-elavon-vm-api.php' );

		// create the elavon vm api client
		$elavon_client = new Elavon_VM_API( $this->get_endpoint_url(), $this->get_ssl_merchant_id(), $this->get_ssl_user_id(), $this->get_ssl_pin() );

		$response = $this->transaction_request( $elavon_client, $order );

		if ( $response ) {

			if ( $this->log_enabled() ) $wc_elavon_vm->log( "Response:\n" . print_r( $response, true ) );

			if ( $response->ssl_result == '0' ) {
				// Successful payment

				// if debug mode load the response into the messages object
				if ( $this->is_debug_mode() ) {
					$this->response_debug_message( $response, 'message', true );
				}

				// update the order record with success

				$order->add_order_note( sprintf( __( 'Credit Card Transaction Approved: %s (%s)', 'wc_elavon' ),
				                                $response->ssl_card_number, substr( $response->ssl_exp_date, 0, 2 ) . '/' . substr( $response->ssl_exp_date, 2 ) ) );
				$order->payment_complete();

				// store the payment reference and card number in the order
				update_post_meta( $order->id, '_elavon_txn_id',      (string) $response->ssl_txn_id );
				update_post_meta( $order->id, '_elavon_card_number', (string) $response->ssl_card_number );

				$woocommerce->cart->empty_cart();

				// Return thank you redirect
				return array(
					'result'   => 'success',
					'redirect' => add_query_arg( array( 'key' => $order->order_key, 'order' => $order_id ), get_permalink( get_option( 'woocommerce_thanks_page_id' ) ) )
				);

			} else {
				$error_note = sprintf( __( 'Elavon VM Credit Card payment failed (Result: [%s] - "%s").', 'wc_elavon' ), $response->ssl_result, $response->ssl_result_message );

				if ( $response->ssl_result_message == 'CALL AUTH CENTER' || substr( $response->ssl_result_message, 0, 9 ) == 'CALL REF:' ) {
					// voice authorization required
					$error_note .= __( ' Voice authorization required to complete transaction, please call your merchant account.', 'wc_elavon' );

					$order->update_status( 'on-hold', $error_note );
				} else {
					// default behavior
					$this->order_failed( $order, $error_note );
				}

				// default customer message
				$message = __( 'An error occurred, please try again or try an alternate form of payment', 'wc_elavon' );

				// specific customer message
				if ( $response->ssl_result_message == 'CALL AUTH CENTER' || substr( $response->ssl_result_message, 0, 9 ) == 'CALL REF:' ) {
					// voice authorization required
					$message = __( 'This transaction request has not been approved. You may elect to use another form of payment to complete this transaction or contact customer service for additional options.', 'wc_elavon' );
				}

				$woocommerce->add_error( $message );

				// if debug mode load the response into the messages object
				if ( $this->is_debug_mode() ) {
					$this->response_debug_message( $response, 'error' );
				}
			}
		} else {

			if ( $this->log_enabled() ) $wc_elavon_vm->log( "ERROR: No response received" );

			$woocommerce->add_error( __( 'Connection error', 'wc_elavon' ) );
		}
	}


	/**
	 * Validate payment form fields
	 */
	public function validate_fields() {
		global $woocommerce;

		$account_number   = $this->get_post( 'elavon_vm_accountNumber' );
		$cv_number        = $this->get_post( 'elavon_vm_cvNumber' );
		$expiration_month = $this->get_post( 'elavon_vm_expirationMonth' );
		$expiration_year  = $this->get_post( 'elavon_vm_expirationYear' );
		$billing_postcode = $this->get_post( 'billing_postcode' );

		// VM doesn't allow postcodes greater than 9 characters
		if ( strlen( $billing_postcode ) > 9 ) {
			$woocommerce->add_error( __( 'The billing postcode is too long, 9 characters maximum are allowed.  Please fix the postcode and try again', 'wc_elavon' ) );
			return false;
		}

		if ( $this->cvv_required() ) {
			// check security code
			if ( empty( $cv_number ) ) {
				$woocommerce->add_error( __( 'Card security code is missing', 'wc_elavon' ) );
				return false;
			}

			if ( ! ctype_digit( $cv_number ) ) {
				$woocommerce->add_error( __( 'Card security code is invalid (only digits are allowed)', 'wc_elavon' ) );
				return false;
			}

			if ( strlen( $cv_number ) < 3 || strlen( $cv_number ) > 4 ) {
				$woocommerce->add_error( __( 'Card security code is invalid (wrong length)', 'wc_elavon' ) );
				return false;
			}
		}

		// check expiration data
		$current_year  = date( 'Y' );
		$current_month = date( 'n' );

		if ( ! ctype_digit( $expiration_month ) || ! ctype_digit( $expiration_year ) ||
			 $expiration_month > 12 ||
			 $expiration_month < 1 ||
			 $expiration_year < $current_year ||
			 ( $expiration_year == $current_year && $expiration_month < $current_month ) ||
			 $expiration_year > $current_year + 20
		) {
			$woocommerce->add_error( __( 'Card expiration date is invalid', 'wc_elavon' ) );
			return false;
		}

		// check card number
		$account_number = str_replace( array( ' ', '-' ), '', $account_number );

		if ( empty( $account_number ) || ! ctype_digit( $account_number ) ||
		     ! $this->luhn_check( $account_number ) ) {
			$woocommerce->add_error( __( 'Card number is invalid', 'wc_elavon' ) );
			return false;
		}

		return true;
	}


	/**
	 * receipt_page
	 */
	public function receipt_page( $order ) {
		echo '<p>' . __( 'Thank you for your order.', 'wc_elavon' ) . '</p>';
	}


	function is_available() {
		global $wc_elavon_vm;

		// proper configuration
		if ( ! $this->get_ssl_merchant_id() || ! $this->get_ssl_user_id() || ! $this->get_ssl_pin() ) return false;

		// all dependencies met
		if ( count( $wc_elavon_vm->get_missing_extension_dependencies() ) > 0 ) return false;

		return parent::is_available();
	}


	/** Communication methods ******************************************************/


	/**
	 * Perform a credit card transaction request
	 *
	 * @param Elavon_VM_API $elavon_client elavon api client
	 * @param WC_Order $order the order
	 *
	 * @return SimpleXMLElement response, or false on error
	 */
	private function transaction_request( $elavon_client, $order ) {

		$request = new stdClass();

		$request->ssl_test_mode        = $this->is_test_mode() ? "true" : "false";
		$request->ssl_transaction_type = $this->auth_settle() ? "ccsale" : "ccauthonly";
		$request->ssl_invoice_number   = ltrim( $order->get_order_number(), _x( '#', 'hash before order number', 'woocommerce' ) );

		$request->ssl_card_number = $this->get_post( 'elavon_vm_accountNumber' );
		$request->ssl_exp_date    = $this->get_post( 'elavon_vm_expirationMonth' ) . substr( $this->get_post( 'elavon_vm_expirationYear' ), -2 );
		$request->ssl_amount      = $order->get_total();
		$request->ssl_salestax    = $order->get_total_tax();

		// Note:  this is a fix suggested by Elavon that should work "90%" of the time.  We'll go with it for now, until someone really needs a POID field collected on the frontend
		$request->ssl_customer_code = substr( $this->get_post( 'elavon_vm_accountNumber' ), -4 );

		$request->ssl_cvv2cvc2_indicator = $this->cvv_required() ? "1" : "0";
		if ( $this->cvv_required() ) {
			$request->ssl_cvv2cvc2 = $this->get_post( 'elavon_vm_cvNumber' );
		}

		$request->ssl_first_name  = $order->billing_first_name;
		$request->ssl_last_name   = $order->billing_last_name;
		$request->ssl_company     = $order->billing_company;
		$request->ssl_avs_address = $order->billing_address_1;
		$request->ssl_address2    = $order->billing_address_2;
		$request->ssl_city        = $order->billing_city;
		$request->ssl_state       = $order->billing_state;
		$request->ssl_avs_zip     = $order->billing_postcode;
		$request->ssl_country     = $order->billing_country;  // country code
		$request->ssl_email       = $order->billing_email;
		$request->ssl_phone       = preg_replace( '/[^0-9]/', '', $order->billing_phone );

		return $elavon_client->transaction_request( $request );
	}


	/** Helper methods ******************************************************/


	/**
	 * Mark the given order as failed, and set the order note
	 *
	 * @param WC_Order $order the order
	 * @param string $order_note the order note to set
	 */
	private function order_failed( $order, $order_note ) {
		if ( $order->status != 'failed' ) {
			$order->update_status( 'failed', $order_note );
		} else {
			// otherwise, make sure we add the order note so we can detect when someone fails to check out multiple times
			$order->add_order_note( $order_note );
		}
	}


	/**
	 * Perform standard luhn check.  Algorithm:
	 *
	 * 1. Double the value of every second digit beginning with the second-last right-hand digit.
	 * 2. Add the individual digits comprising the products obtained in step 1 to each of the other digits in the original number.
	 * 3. Subtract the total obtained in step 2 from the next higher number ending in 0.
	 * 4. This number should be the same as the last digit (the check digit). If the total obtained in step 2 is a number ending in zero (30, 40 etc.), the check digit is 0.
	 *
	 * @param string $account_number the credit card number to check
	 *
	 * @return boolean true if $account_number passes the check, false otherwise
	 */
	private function luhn_check( $account_number ) {
		$sum = 0;
		for ( $i = 0, $ix = strlen( $account_number ); $i < $ix - 1; $i++) {
			$weight = substr( $account_number, $ix - ( $i + 2 ), 1 ) * ( 2 - ( $i % 2 ) );
			$sum += $weight < 10 ? $weight : $weight - 9;
		}

		return substr( $account_number, $ix - 1 ) == ( ( 10 - $sum % 10 ) % 10 );
	}


	/**
	 * Add the XML response to the woocommerce message object
	 *
	 * @param SimpleXMLElement $response response from the Elavon server
	 * @param string $type optional message type, one of 'message' or 'error', defaults to 'message'
	 * @param boolean $set_message optional whether to set the supplied
	 *        message so that it appears on the next page load (ie, a
	 *        message you want displayed on the 'thank you' page
	 *
	 * @return void
	 */
	private function response_debug_message( $response, $type = 'message', $set_message = false ) {
		global $woocommerce;

		$dom = dom_import_simplexml( $response )->ownerDocument;
		$dom->formatOutput = true;
		$debug_message = "<pre>" . htmlspecialchars( $dom->saveXML() ) . "</pre>";

		if ( $type == 'message' )
			$woocommerce->add_message( $debug_message );
		else
			$woocommerce->add_error( $debug_message );

		if ( $set_message ) {
			$woocommerce->set_messages();  // this will be displayed on the 'thank you' page
		}
	}


	/**
	 * Safely get post data if set
	 *
	 * @param string $name name of post argument to get
	 * @return mixed post data, or null
	 */
	private function get_post( $name ) {
		if ( isset( $_POST[ $name ] ) ) {
			return trim( $_POST[ $name ] );
		}
		return null;
	}


	/** Getter methods ******************************************************/


	/**
	 * Returns the SSL merchant id
	 *
	 * @return string SSL merchant id
	 */
	private function get_ssl_merchant_id() {
		return $this->account == 'demo' ? $this->demo_ssl_merchant_id : $this->ssl_merchant_id;
	}


	/**
	 * Returns the SSL user id
	 *
	 * @return string SSL user id
	 */
	private function get_ssl_user_id() {
		return $this->account == 'demo' ? $this->demo_ssl_user_id : $this->ssl_user_id;
	}


	/**
	 * Returns the SSL pin
	 *
	 * @return string SSL pin
	 */
	private function get_ssl_pin() {
		return $this->account == 'demo' ? $this->demo_ssl_pin : $this->ssl_pin;
	}


	/**
	 * Returns the endpoint url
	 *
	 * @return string endpoint URL
	 */
	private function get_endpoint_url() {
		return $this->account == 'demo' ? $this->demo_endpoint_url : $this->live_endpoint_url;
	}


	/**
	 * Is the card security code required?
	 *
	 * @return boolean true if the card security code is required
	 */
	public function cvv_required() {
		return $this->cvv == "yes";
	}


	/**
	 * Perform an authorization and settlement (capture funds)?
	 *
	 * @return boolean true if a settlement should be performed, false if authorize-only
	 */
	public function auth_settle() {
		return $this->settlement == "yes";
	}


	/**
	 * Is test mode enabled?
	 *
	 * @return boolean true if test mode is enabled
	 */
	public function is_test_mode() {
		// test mode only applies to production accounts
		return $this->account == 'production' && $this->testmode == "yes";
	}


	/**
	 * Is debug mode enabled?
	 *
	 * @return boolean true if debug mode is enabled
	 */
	public function is_debug_mode() {
		return $this->debug == "yes";
	}


	/**
	 * Should communication be logged?
	 *
	 * @return boolean true if log mode is enabled
	 */
	private function log_enabled() {
		return $this->log == "yes";
	}


	/**
	 * Returns true if the gateway is enabled.  This has nothing to do with
	 * whether the gateway is properly configured or functional.
	 *
	 * @return boolean true if the gateway is enabled
	 */
	private function is_enabled() {
		return $this->enabled;
	}

} // WC_Gateway_Elavon_VM
