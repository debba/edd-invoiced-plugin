<?php

/*
Plugin Name:  EDD PDF Invoice
Description: Invoice generator for EDD using invoice-generator.com
Version: 1.0
Author: dueclic
Author URI: https://www.dueclic.com
License: GPL3
*/

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * Full path to the invoiced plugin
 */

define( "EDD_INVOICE_FILE", __FILE__ );
define( "EDD_INVOICE_PATH", dirname( __FILE__ ) );
define( "EDD_INVOICE_ENDPOINT", "https://invoice-generator.com" );


/**
 *
 * The main plugin class
 *
 */

require_once( "includes/class-edd-invoiced-plugin.php" );

function EddInvoicePlugin() {
	return Edd_Invoiced_Plugin::get_instance();
}

load_plugin_textdomain( 'edd-invoiced-plugin', false,
	dirname( plugin_basename( EDD_INVOICE_FILE ) ) . '/languages' );

$EDDINVTRANS = array(
	"header"               => __( "INVOICE", "edd-invoiced-plugin" ),
	"to_title"             => __( "Client", "edd-invoiced-plugin" ),
	"invoice_number_title" => "#",
	"date_title"           => __( "Date", "edd-invoiced-plugin" ),
	"payment_terms_title"  => __( "Payment Terms", "edd-invoiced-plugin" ),
	"due_date_title"       => __( "Due Date", "edd-invoiced-plugin" ),
	"purchase_order_title" => __( "Purchase Order", "edd-invoiced-plugin" ),
	"quantity_header"      => __( "Quantity Order", "edd-invoiced-plugin" ),
	"item_header"          => __( "Item", "edd-invoiced-plugin" ),
	"unit_cost_header"     => __( "Rate", "edd-invoiced-plugin" ),
	"amount_header"        => __( "Amount", "edd-invoiced-plugin" ),
	"subtotal_title"       => __( "Subtotal", "edd-invoiced-plugin" ),
	"discounts_title"      => __( "Discounts", "edd-invoiced-plugin" ),
	"tax_title"            => __( "Tax", "edd-invoiced-plugin" ),
	"shipping_title"       => __( "Shipping", "edd-invoiced-plugin" ),
	"total_title"          => __( "Total", "edd-invoiced-plugin" ),
	"amount_paid_title"    => __( "Amount Paid", "edd-invoiced-plugin" ),
	"balance_title"        => __( "Balance", "edd-invoiced-plugin" ),
	"terms_title"          => __( "Terms", "edd-invoiced-plugin" ),
	"notes_title"          => __( "Notes", "edd-invoiced-plugin" ),
);

$EDDINV = EddInvoicePlugin();