<?php

	if ( ! defined( 'ABSPATH' ) ) {
		die;
	}

	final class Edd_Invoiced_Handler {

		/**
		 * @var $instance \Edd_Invoice_Handler | null
		 */

		private static $instance;

		/**
		 * @var $settings \EDD_Invoiced_Settings
		 */

		private $settings;

		public static function get_instance() {

			if ( empty( self::$instance ) && ! ( self::$instance instanceof Edd_Invoiced_Handler ) ) {
				self::$instance           = new self;
				self::$instance->settings = EDD_Invoiced_Settings::get_instance();
				self::$instance->hooks();
			}

			return self::$instance;

		}

		public function add_invoice_link( $row_actions = array(), EDD_Payment $payment = null ) {
			$row_actions['invoice'] = '<a class="invoice-print" data-last="' . edd_get_option( "edd_invd_seq_order" ) . '" href="/wp-json/edd-invoice/pdf/' . $payment->ID . '">' . __( 'Invoice',
					'edd-invoiced-plugin' ) . '</a>';

			return $row_actions;
		}

		public function get_invoice( WP_REST_Request $request ) {

			global $EDDINVTRANS;

			$payment_id = $request->get_param( "id" );

			/**
			 * @var $payment EDD_Payment | false
			 */

			if ( $payment_id === false ) {
				return "Payment not valid";
			}

			$products = edd_get_payment_meta_cart_details( $payment_id );

			$invd_sequence_no = get_post_meta( $payment_id, "invd_sequence_no", true );

			if ( empty( $invd_sequence_no ) ) {
				$invd_last_sequence_no   = edd_get_option( "invd_last_sequence_no" );
				$invd_last_sequence_year = (int) edd_get_option( "invd_last_sequence_year" );

				$invd_year = (int) date( "Y", strtotime( edd_get_payment_completed_date( $payment_id ) ) );

				if ( $invd_year > $invd_last_sequence_year ) {
					edd_update_option( "invd_last_sequence_no", 1 );
					edd_update_option( "invd_last_sequence_year", $invd_year );
					update_post_meta( $payment_id, "invd_sequence_no", 1 );
					$invd_sequence_no = 1;
				} else {
					$new_invd_sequence_no = (int) $invd_last_sequence_no + 1;
					update_post_meta( $payment_id, "invd_sequence_no", $new_invd_sequence_no );
					$invd_sequence_no = $new_invd_sequence_no;
				}

			}

			$items = array();

			$discount = 0;

			foreach ( $products as $key => $product ) {
				$items[] = array(
					"name"      => $product["name"],
					"quantity"  => $product["quantity"],
					"unit_cost" => $product["item_price"]
				);
				if ( isset( $product["discount"] ) && $product["discount"] > 0 ) {
					$discount += $product["discount"];
				}
			}

			$user         = edd_get_payment_meta_user_info( $payment_id );
			$customer_id  = edd_get_payment_customer_id( $payment_id );
			$customer     = new EDD_Customer( $customer_id );
			$business_vat = edd_get_payment_meta( $payment_id,
				"_edd_payment_meta" )[ edd_get_option( "edd_invd_cf_field" ) ];

			$to = $customer->name . "\n" .
			      "P.IVA / CF: " . $business_vat . "\n" .
			      $user["email"] . "\n" .
			      $user["address"]["line1"] . "\n" .
			      $user["address"]["city"] . " - " . $user["address"]["state"] . " - " . $user["address"]["zip"] . "\n" .
			      $user["address"]["country"];

			$invoice_no = edd_get_option( "edd_invd_prefix" ) . $invd_sequence_no . edd_get_option( "appendix" );

			$data = array(
				"from"     => edd_get_option( "edd_invd_from" ),
				"to"       => $to,
				"logo"     => edd_get_option( "edd_invd_logo" ),
				"number"   => $invoice_no,
				"date"     => date( get_option( 'date_format' ),
					strtotime( edd_get_payment_completed_date( $payment_id ) ) ),
				"items"    => $items,
				"tax"      => edd_get_tax_rate() * 100,
				"currency" => edd_get_currency(),
				"fields"   => array(
					"tax"       => "%",
					"discounts" => ( $discount <= 0 ) ? false : true,
					"shipping"  => false
				)
			);

			foreach ( $EDDINVTRANS as $trans => $value ) {
				if ( $trans === "header" ) {
					continue;
				}


				$invd = edd_get_option( "invd_" . $trans );

				if ( ! empty( $invd ) ) {
					$data[ $trans ] = $invd;
				}

			}

			if ( $discount > 0 ) {
				$data["discounts"] = $discount;
			}

			$request = wp_remote_post( EDD_INVOICE_ENDPOINT, array(
				'headers' => array( 'Content-Type' => 'application/json; charset=utf-8' ),
				'body'    => json_encode( $data ),
				'method'  => 'POST'
			) );

			if ( ! is_wp_error( $request ) ) {
				header( "Content-type:application/pdf" );
				header( "Content-Disposition:attachment;filename=invoice-" . $invoice_no . ".pdf" );
				die( wp_remote_retrieve_body( $request ) );
			}

			die( $request->get_error_message() );

		}

		public function rest_api() {
			$current_user = wp_get_current_user();
			if ( user_can( $current_user, 'manage_options' ) ) {

				register_rest_route( 'edd-invoice', '/pdf/(?P<id>\d+)', array(
					'methods'  => "GET",
					'callback' => array( $this, 'get_invoice' ),
					'args'     => array(
						'id'
					)
				) );
			}
		}

		public function check_sync() {
			?>
            <div class="notice notice-error is-dismissible">
                <p><?php _e( 'You cannot use the plugin', 'edd-invoiced-plugin' ); ?> <strong>EDD
                        Invoiced</strong> <?php _e( 'without sync all of the payments with the invoice system.',
						'edd-invoiced-plugin' ); ?></p>
                <p><?php _e( 'Start sync now.', 'edd-invoiced-plugin' ); ?>
                    <button class="button button-secondary" id="edd_invd_first_sync"><?php _e( "Start!",
							"edd-invoiced-plugin" ); ?></button>
                </p>
            </div>
			<?php
		}

		public function edd_invd_sync_orders() {

			wp_doing_ajax();

			$date = null;

			$payments = edd_get_payments( array(
				"order"  => "ASC",
				"number" => - 1,
				"status" => "publish"
			) );

			$date     = null;
			$position = 1;

			$history_payments = array(
				"payments" => array(),
				"stats"    => array()
			);

			foreach ( $payments as $payment ) {

				if ( $payment->ID == 0 ) {
					continue;
				}

				$year = date( "Y", strtotime( edd_get_payment_completed_date( $payment->ID ) ) );
				if ( $year !== $date ) {
					$date     = $year;
					$position = 1;
				} else {
					$position ++;
				}

				update_post_meta( $payment->ID, "invd_sequence_no", $position );
				update_post_meta( $payment->ID, "invd_sequence_year", $year );
				update_post_meta( $payment->ID, "invd_downloaded", false );

				$history_payments["payments"][] = array(
					"seq_no"   => $position,
					"seq_year" => $year,
					"dwl"      => false
				);

			}

			edd_update_option( "invd_start_sync", 1 );
			edd_update_option( "invd_last_sequence_no", $position );
			edd_update_option( "invd_last_sequence_year", $year );

			$history_payments["stats"] = array(
				"start_sync"    => 1,
				"last_seq_no"   => $position,
				"last_seq_year" => $year
			);

			die(
			wp_json_encode( array(
				"type"    => "success",
				"history" => $history_payments
			) )
			);

		}

		public function add_js_script() {
			?>
            <script type="text/javascript">
                jQuery(function ($) {
                    $("#edd_invd_first_sync").on("click", function () {

                        $("#edd_invd_first_sync").prop("disabled", true);
                        $("#edd_invd_first_sync").text("<?php _e( "Loading...", "edd-invoiced-plugin" ); ?>");

                        $.ajax({
                            'type': 'POST',
                            'dataType': 'json',
                            'url': '<?php echo admin_url( "admin-ajax.php" ); ?>',
                            'async': true,
                            'data': {
                                'action': 'edd_invd_sync_orders'
                            },
                            'success': function () {
                                location.reload();
                            },
                            'error': function () {
                                alert("<?php _e( "Error while executing the first sync, please retry!",
									"edd-invoiced-plugin" ); ?>");
                            },
                            'complete': function () {
                                $("#edd_invd_first_sync").prop("disabled", false);
                                $("#edd_invd_first_sync").text("<?php _e( "Start!", "edd-invoiced-plugin" ); ?>");
                            }
                        });

                    });
                });
            </script>
			<?php
		}

		public function save_pdf( $payment_id, \EDD_Payment $payment ) {
			die( "CIAO" );
		}

		public function hooks() {

			$start_sync = edd_get_option( "invd_start_sync" );

			if ( ! empty( $start_sync ) ) {

				add_filter( 'edd_settings_sections_extensions', array( $this->settings, 'section' ), 10, 1 );
				add_filter( 'edd_settings_extensions', array( $this->settings, 'extension' ) );
				//add_action('edd_payment_saved', array($this, 'save_pdf'), 10, 2);
				add_filter( "edd_payment_row_actions", array( $this, 'add_invoice_link' ), 10, 2 );
				add_action( 'rest_api_init', array( $this, 'rest_api' ) );

			} else {
				add_action( 'admin_notices', array( $this, 'check_sync' ) );
				add_action( 'wp_ajax_edd_invd_sync_orders', array( $this, 'edd_invd_sync_orders' ) );
				add_action( "admin_head", array( $this, "add_js_script" ) );
			}

		}

	}