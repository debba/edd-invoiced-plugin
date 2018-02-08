<?php

	if ( ! defined('ABSPATH')) {
		die;
	}

	final class Edd_Invoiced_Plugin
	{

		/**
		 * @var $instance \Edd_Invoiced_Plugin | null
		 */

		private static $instance;

		private $handler;

		private $prefix = "edd_invd";

		public static function get_instance()
		{

			if (empty(self::$instance) && ! (self::$instance instanceof Edd_Invoiced_Plugin)) {

				self::$instance = new self;
				self::$instance->includes();
				self::$instance->handler = Edd_Invoiced_Handler::get_instance();
			}

			return self::$instance;

		}

		public function get_prefixed_setting($setting){
			return $this->prefix."_".$setting;
		}

		public function get_prefix(){
			return $this->prefix;
		}

		public function includes()
		{
			require_once(EDD_INVOICE_PATH . "/includes/class-edd-invoiced-handler.php");
			require_once(EDD_INVOICE_PATH . "/includes/class-edd-invoiced-settings.php");
		}


	}