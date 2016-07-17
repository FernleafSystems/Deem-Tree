<?php
/*
Plugin Name: Deemtree, Voucher Marketing Made Easy
Plugin URI: http://www.deemtree.com/?utm_source=wp-plugin&utm_medium=link&utm_campaign=plugins
Description: Sell your branded vouchers, gift cards, deals and offers from 0% Commission
Version: 1.0.0
Author: Deemtree
Author URI: http://www.deemtree.com
License: GPLv3
*/

if ( !class_exists( 'ICWP_DeemTree' ) ) {
	require_once( dirname( __FILE__ ).DIRECTORY_SEPARATOR.'deemtree.php' );
}
ICWP_DeemTree::GetInstance( __FILE__ );