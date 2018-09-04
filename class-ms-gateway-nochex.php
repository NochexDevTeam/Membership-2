<?php

/**

 * Gateway: Nochex

 *

 *

 * @since  1.0.0

 * @package Membership2

 * @subpackage Model

 */

class MS_Gateway_Nochex extends MS_Gateway {



	const ID = 'nochex';



	/**

	 * Gateway singleton instance.

	 *

	 * @since  1.0.0

	 * @var string $instance

	 */

	public static $instance;



	/**

	 * Nochex merchant email.

	 *

	 * @since  1.0.0

	 * @var bool nochex_email

	 */

	protected $nochex_email;

	/**

	 * Hook to add custom transaction status.

	 * This is called by the MS_Factory

	 *

	 * @since  1.0.0

	 */

	public function after_load() {

		parent::after_load();



		$this->id 				= self::ID;

		$this->name 			= __( 'Nochex', 'membership2' );

		$this->group 			= 'Nochex';

		$this->manual_payment 	= true; // Recurring billed/paid manually

		$this->pro_rate 		= true;

	}



	/**

	 * Processes gateway IPN return.

	 *

	 * @since  1.0.0

	 * @param  MS_Model_Transactionlog $log Optional. A transaction log item

	 *         that will be updated instead of creating a new log entry.

	 */

	public function handle_return( $log = false ) {
	 
		// Get the POST information from Nochex server
		$postvars = http_build_query($_POST);
		ini_set("SMTP","mail.nochex.com" ); 
		$header = "From: apc@nochex.com";

		// Set parameters for the email
		$to = 'james.lugton@nochex.com';
		$url = "https://secure.nochex.com/callback/callback.aspx";

		// Curl code to post variables back
		$ch = curl_init(); // Initialise the curl tranfer
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_VERBOSE, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postvars); // Set POST fields
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 60); // set connection time out variable - 60 seconds	
		//curl_setopt ($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1); // set openSSL version variable to CURL_SSLVERSION_TLSv1
		$output = curl_exec($ch); // Post back
		curl_close($ch);

		// Put the variables in a printable format for the email
		$debug = "IP -> " . $_SERVER['REMOTE_ADDR'] ."\r\n\r\nPOST DATA:\r\n"; 
		foreach($_POST as $Index => $Value) 
		$debug .= "$Index -> $Value\r\n"; 
		$debug .= "\r\nRESPONSE:\r\n$output";
		 
		//If statement
		if (!strstr($output, "AUTHORISED")) {  // searches response to see if AUTHORISED is present if it isn’t a failure message is displayed
			$msg = "Callback was not AUTHORISED for " . $_POST['optional_1'];  // displays debug message
		} 
		else { 
			$msg = "Callback was AUTHORISED for " . $_POST['optional_1']; // if AUTHORISED was found in the response then it was successful
			// whatever else you want to do 
		}

		//Email the response 
			$external_id 	= $_POST['transaction_id'];
			
			$invoice_id 	= intval( $_POST['order_id'] );

			$invoice 		= MS_Factory::load( 'MS_Model_Invoice', $invoice_id );			
			
			$subscription 	= $invoice->get_subscription();

			$membership 	= $subscription->get_membership();

			$member 		= $subscription->get_member();

			$subscription_id = $subscription->id;
				
			$success = true;

			$notes = 'Payment successful for ' . $_POST['optional_1'];

			$status = MS_Model_Invoice::STATUS_PAID;	
	
			$invoice->pay_it( self::ID, $external_id );
			
			$invoice->add_notes( $msg );

			$invoice->status = $status;

			$invoice->save();

			$invoice->changed();
 
				do_action(

					'ms_gateway_transaction_log',

					self::ID, // gateway ID

					'handle', // request|process|handle

					$success, // success flag

					$subscription_id, // subscription ID

					$invoice_id, // invoice ID

					$_POST["amount"], // charged amount

					$msg, // Descriptive text

					$external_id // External ID

				);
 
				do_action(

					'ms_gateway_nochex_payment_processed_' . $status,

					$invoice,

					$subscription

				);
			
	}
 
	/**

	 * Verify required fields.

	 *

	 * @since  1.0.0

	 *

	 * @return boolean

	 */

	public function is_configured() {

		$is_configured 	= true;

		$required 		= array( 'nochex_email' );

		foreach ( $required as $field ) {

			$value = $this->$field;

			if ( empty( $value ) ) {

				$is_configured = false;

				break;

			}

		}

		return apply_filters(

			'ms_gateway_nochex_is_configured',

			$is_configured

		);

	}



	/**

	 * Validate specific property before set.

	 *

	 * @since  1.0.0

	 *

	 * @access public

	 * @param string $name The name of a property to associate.

	 * @param mixed $value The value of a property.

	 */

	public function __set( $property, $value ) {

		if ( property_exists( $this, $property ) ) {

			switch ( $property ) {

				default:

					parent::__set( $property, $value );

					break;

			}

		}

		do_action(

			'ms_gateway_nochex_set_after',

			$property,

			$value,

			$this

		);

	}



}