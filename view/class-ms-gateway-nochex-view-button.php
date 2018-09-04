<?php

class MS_Gateway_Nochex_View_Button extends MS_View {

	public function to_html() {

		$fields 		= $this->prepare_fields();

		$subscription 	= $this->data['ms_relationship'];		

		$gateway 		= $this->data['gateway'];

		$invoice 		= $subscription->get_next_billable_invoice();

		$action_url 	= apply_filters(

			'ms_gateway_nochex_view_button_form_action_url',

			$this->data['action_url']

		);

		$row_class 		= 'gateway_' . $gateway->id;

		if ( ! $gateway->is_live_mode() ) {

			$row_class .= ' sandbox-mode';

		}

		ob_start();

		?>

		<form action="<?php echo esc_url( $action_url ); ?>" method="post">

			<?php

			foreach ( $fields as $field ) {

				MS_Helper_Html::html_element( $field );

			}

			?>

		</form>

		<?php

		$payment_form = apply_filters(

			'ms_gateway_form',

			ob_get_clean(),

			$gateway,

			$invoice,

			$this

		);

		ob_start();

		?>

		<tr class="<?php echo esc_attr( $row_class ); ?>">

			<td class="ms-buy-now-column" colspan="2">

				<?php echo $payment_form; ?>

			</td>

		</tr>

		<?php

		$html = ob_get_clean();

		$html = apply_filters(

			'ms_gateway_button-' . $gateway->id,

			$html,

			$this

		);

		$html = apply_filters(

			'ms_gateway_button',

			$html,

			$gateway->id,

			$this

		);

		return $html;

	}




	private function prepare_fields() {

		$subscription 	= $this->data['ms_relationship'];
		$membership 	= $subscription->get_membership();
		$sub_member		= $subscription->get_member();
		
		if ( 0 === $membership->price ) {
			return;
		}
 
		$gateway 	= $this->data['gateway']; 
		$invoice 	= $subscription->get_next_billable_invoice();
		
		
		$pay_cycle = $membership->pay_cycle_period;
		$total_payments = $membership->pay_cycle_repetitions;
				
		$description = "Membership: " . $membership->name . " , Payments every " . $pay_cycle["period_unit"] . "  " . $pay_cycle["period_type"] . ", Total Payments (" . $total_payments . ")";
		
		
		if($gateway->mode == "sandbox"){
		
			$test_transaction = "100";
		
		}else{
		
			$test_transaction = "";
		
		}

		$fields = array(

			'merchant_id' => array(

				'id' 	=> 'merchant_id',

				'type' 	=> MS_Helper_Html::INPUT_TYPE_HIDDEN,

				'value' => $gateway->nochex_email,

			),
			
			'billing_fullname' => array(

				'id' 	=> 'billing_fullname',

				'type' 	=> MS_Helper_Html::INPUT_TYPE_HIDDEN,

				'value' => $sub_member->first_name . ", " . $sub_member->last_name,

			),			
			
			'email_address' => array(

				'id' 	=> 'email_address',

				'type' 	=> MS_Helper_Html::INPUT_TYPE_HIDDEN,

				'value' => $sub_member->email,

			),
			
			'test_transaction' => array(

				'id' 	=> 'test_transaction',

				'type' 	=> MS_Helper_Html::INPUT_TYPE_HIDDEN,

				'value' => $test_transaction,

			),

			'amount' => array(

				'id' 	=> 'amount',

				'type' 	=> MS_Helper_Html::INPUT_TYPE_HIDDEN,

				'value' => MS_Helper_Billing::format_price( $invoice->total ),

			),

			'description' => array(

				'id' 	=> 'description',

				'type' 	=> MS_Helper_Html::INPUT_TYPE_HIDDEN,

				'value' => $description,

			),

			'test_success_url' => array(

				'id' 	=> 'test_success_url',

				'type' 	=> MS_Helper_Html::INPUT_TYPE_HIDDEN,

				'value' => esc_url_raw(

					add_query_arg(

						array( 'ms_relationship_id' => $invoice->id ),

						MS_Model_Pages::get_page_url( MS_Model_Pages::MS_PAGE_REG_COMPLETE, false )

					)

				),

			),
			
			'success_url' => array(

				'id' 	=> 'success_url',

				'type' 	=> MS_Helper_Html::INPUT_TYPE_HIDDEN,

				'value' => esc_url_raw(

					add_query_arg(

						array( 'ms_relationship_id' => $invoice->id ),

						MS_Model_Pages::get_page_url( MS_Model_Pages::MS_PAGE_REG_COMPLETE, false )

					)

				),

			),

			'cancel_url' => array(

				'id' 	=> 'cancel_return',

				'type' 	=> MS_Helper_Html::INPUT_TYPE_HIDDEN,

				'value' => MS_Model_Pages::get_page_url( MS_Model_Pages::MS_PAGE_REGISTER ),

			),

			'callback_url' 	=> array(

				'id' 	=> 'callback_url',

				'type' 	=> MS_Helper_Html::INPUT_TYPE_HIDDEN,

				'value' => $gateway->get_return_url(),

			),

			'invoice' 		=> array(

				'id' 	=> 'order_id',

				'type' 	=> MS_Helper_Html::INPUT_TYPE_HIDDEN,

				'value' => $invoice->id,

			),

		);

		$this->data['action_url'] = 'https://secure.nochex.com/default.aspx';
		
		$fields['submit'] = array(

			'id' 	=> 'submit',

			'type' 	=> MS_Helper_Html::INPUT_TYPE_IMAGE,

			'value' => 'https://ssl.nochex.com/images/buttons/nochex_pay.png',

			'alt' 	=> __( 'Nochex', 'membership2' ),

		);

		// custom pay button defined in gateway settings

		$custom_label = $gateway->pay_button_url;

		if ( ! empty( $custom_label ) ) {

			if ( false !== strpos( $custom_label, '://' ) ) {

				$fields['submit']['value'] = $custom_label;

			} else {

				$fields['submit'] = array(

					'id' 	=> 'submit',

					'type' 	=> MS_Helper_Html::INPUT_TYPE_SUBMIT,

					'value' => $custom_label,

				);

			}

		}
  
		return apply_filters(

			'ms_gateway_nochex_view_prepare_fields',

			$fields, $invoice

		);

	}

}
