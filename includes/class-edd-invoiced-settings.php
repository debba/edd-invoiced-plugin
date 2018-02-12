<?php

	if ( ! defined('ABSPATH')) {
		die;
	}

	final class Edd_Invoiced_Settings
	{

		private static $instance;

		private function get_required_fields(){
			$req = array();
			$fields = edd_purchase_form_required_fields();
			foreach ($fields as $field_name => $field){
				$req[$field_name] = $field_name;
			}
			return $req;
		}

		public function extension($settings)
		{

			global $EDDINVTRANS;

			$invoiced_settings = array(
				array(
					'id'   => $this->edd_invoice_setting_id('inv_id'),
					'name' => '<strong>' . __('Invoices', 'edd_invoiced-plugin') . '</strong>',
					'desc' => '',
					'type' => 'header',
					'size' => 'regular'
				),
				array(
					'id'   => $this->edd_invoice_setting_id('from'),
					'name' => __('From', 'edd-invoiced-plugin'),
					'desc' => __('Invoice info ', 'edd-invoiced-plugin'),
					'type' => 'textarea'
				),
				array(
					'id'   => $this->edd_invoice_setting_id('logo'),
					'name' => __('Logo', 'edd-invoiced-plugin'),
					'desc' => __('Your image logo ', 'edd-invoiced-plugin'),
					'type' => 'upload'
				),
				array(
					'id'            => $this->edd_invoice_setting_id('prefix'),
					'name'          => __('Prefix', 'edd-invoiced-plugin'),
					'desc'          => __('Prefix invoice (is required)', 'edd-invoiced-plugin'),
					'type'          => 'text',
					'tooltip_title' => __('What is prefix?', 'edd-invoiced-plugin'),
					'tooltip_desc'  => __('In sequential number of invoice is before the invoice number',
						'edd-invoiced-plugin')
				),
				array(
					'id'            => $this->edd_invoice_setting_id('appendix'),
					'name'          => __('Appendix', 'edd-invoiced-plugin'),
					'desc'          => __('Appendix invoice (is required)', 'edd-invoiced-plugin'),
					'type'          => 'text',
					'tooltip_title' => __('What is appending?', 'edd-invoiced-plugin'),
					'tooltip_desc'  => __('In sequential number of invoice is after the invoice number',
						'edd-invoiced-plugin')
				),
				array(
					'id'   => $this->edd_invoice_setting_id('spfield'),
					'name' => '<strong>' . __('Special fields', 'edd_invoiced_plugin') . '</strong>',
					'desc' => '',
					'type' => 'header',
					'size' => 'regular'
				),
				array(
					'id'   => $this->edd_invoice_setting_id('cf_field'),
					'name' => __('Field that indicates your business VAT number.'),
					'type' => 'select',
					'options' => $this->get_required_fields()
				),
				array(
					'id'   => $this->edd_invoice_setting_id('inv_trans'),
					'name' => '<strong>' . __('Translations', 'edd_invoiced_plugin') . '</strong>',
					'desc' => '',
					'type' => 'header',
					'size' => 'regular'
				)
			);

			foreach ($EDDINVTRANS as $id => $name){

				$invoiced_settings[] = array(
					'id'   => $this->edd_invoice_setting_id($id),
					'name' => $name,
					'type' => 'text',
				);

			}

			if (version_compare(EDD_VERSION, 2.5, '>=')) {
				$invoiced_settings = array('invoices' => $invoiced_settings);
			}

			return array_merge($settings, $invoiced_settings);

		}

		public function section($sections)
		{
			$sections['invoices'] = __('Invoices', 'edd_invoiced_plugin');

			return $sections;
		}

		public static function get_instance()
		{
			if (empty(self::$instance) && ! (self::$instance instanceof EDD_Invoiced_Settings)) {
				self::$instance = new self;
			}

			return self::$instance;

		}

		public function edd_invoice_setting_id($id)
		{
			global $EDDINV;
			return $EDDINV->get_prefixed_setting($id);
		}

	}