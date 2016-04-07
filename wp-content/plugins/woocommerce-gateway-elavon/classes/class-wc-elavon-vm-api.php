<?php
/**
 * @package   WCGatewayElavon/Classes/Payment
 * @author    Justin Stern
 * @copyright Copyright (c) 2013, Justin Stern
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Elavon VM Payment Gateway API Class
 *
 * The Elavon VM Payment Gateway API class manages the communication between the
 * WooCommerce and Elavon payment servers
 */
class Elavon_VM_API {

	/**
	 * Communication URL
	 */
	var $endpoint_url;
	var $ssl_merchant_id;
	var $ssl_user_id;
	var $ssl_pin;


	/**
	 * @param string $endpoint_url Elavon VM endpoint url
	 * @param string $ssl_merchant_id
	 * @param string $ssl_user_id
	 * @param string $ssl_pin
	 */
	public function __construct( $endpoint_url, $ssl_merchant_id, $ssl_user_id, $ssl_pin ) {
		$this->endpoint_url    = $endpoint_url;
		$this->ssl_merchant_id = $ssl_merchant_id;
		$this->ssl_user_id     = $ssl_user_id;
		$this->ssl_pin         = $ssl_pin;
	}


	/**
	 * Perform the transaction request
	 *
	 * @param object $request request object
	 *
	 * @return SimpleXMLElement response, or false on error
	 */
	public function transaction_request( $request ) {

		// build the simplexml object
		$request_xml = simplexml_load_string( "<txn />" );

		$request_xml->addChild( 'ssl_merchant_id', $this->ssl_merchant_id );
		$request_xml->addChild( 'ssl_user_id',     $this->ssl_user_id );
		$request_xml->addChild( 'ssl_pin',         $this->ssl_pin );

		$request_xml->addChild( 'ssl_test_mode',        $request->ssl_test_mode );
		$request_xml->addChild( 'ssl_transaction_type', $request->ssl_transaction_type );
		$request_xml->addChild( 'ssl_invoice_number',   $this->stripspecialchars( $request->ssl_invoice_number ) );

		$request_xml->addChild( 'ssl_card_number',   $request->ssl_card_number );
		$request_xml->addChild( 'ssl_exp_date',      $request->ssl_exp_date );
		$request_xml->addChild( 'ssl_amount',        $request->ssl_amount );
		$request_xml->addChild( 'ssl_salestax',      $request->ssl_salestax );
		$request_xml->addChild( 'ssl_customer_code', $request->ssl_customer_code );

		$request_xml->addChild( 'ssl_cvv2cvc2_indicator', $request->ssl_cvv2cvc2_indicator );
		if ( isset( $request->ssl_cvv2cvc2 ) ) $request_xml->addChild( 'ssl_cvv2cvc2',     $request->ssl_cvv2cvc2 );

		$request_xml->addChild( 'ssl_first_name',  $this->stripspecialchars( $request->ssl_first_name ) );
		$request_xml->addChild( 'ssl_last_name',   $this->stripspecialchars( $request->ssl_last_name ) );
		$request_xml->addChild( 'ssl_company',     $this->stripspecialchars( $request->ssl_company ) );
		$request_xml->addChild( 'ssl_avs_address', $this->stripspecialchars( $request->ssl_avs_address ) );
		$request_xml->addChild( 'ssl_address2',    $this->stripspecialchars( $request->ssl_address2 ) );
		$request_xml->addChild( 'ssl_city',        $this->stripspecialchars( $request->ssl_city ) );
		$request_xml->addChild( 'ssl_state',       $this->stripspecialchars( $request->ssl_state ) );
		$request_xml->addChild( 'ssl_avs_zip',     $this->stripspecialchars( $request->ssl_avs_zip ) );
		$request_xml->addChild( 'ssl_country',     $this->stripspecialchars( $request->ssl_country ) );
		$request_xml->addChild( 'ssl_email',       $this->stripspecialchars( $request->ssl_email ) );
		$request_xml->addChild( 'ssl_phone',       $this->stripspecialchars( $request->ssl_phone ) );

		// According to Elavon's tech support, their "XML" protocol isn't actually
		//  true XML, and will report the request as invalid if it contains the
		//  normal XML header, so strip it out of our requests
		$request = str_replace("<?xml version=\"1.0\"?>\n", '', $request_xml->asXML() );

		$response = $this->perform_request( $this->endpoint_url, $request );

		return simplexml_load_string( $response );
	}


	/**
	 * Strip HTML special characters (&, <, >).  Encoding the special chars as
	 * entities, while technically valid XML, breaks the weirdo Elavon gateway
	 * implementation, so the best we can do is strip the problem characters
	 * out entirely.  The Elavon gateway an't handle CDATA sections either
	 *
	 * @param string $str a string
	 * @return string with &, < and > stripped out
	 */
	private function stripspecialchars( $str ) {
		return str_replace( array( '&', '<', '>' ), '', $str );
	}


	/**
	 * Perform the request
	 *
	 * @param string $url endpoint URL
	 * @param string $request XML request data
	 *
	 * @return string XML response
	 */
	private function perform_request( $url, $request ) {

		$post_response = wp_remote_post( $url, array(
			'method'     => 'POST',
			'body'       => array( "xmldata" => $request ),
			'timeout'    => 60,
			'sslverify'  => false,
			'user-agent' => "PHP " . PHP_VERSION
		) );

		return $post_response['body'];
	}
}
