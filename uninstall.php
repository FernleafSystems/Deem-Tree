<?php
/**
 * Uninstall
 * 
 * @package Deemtree
 * @author Paul Goodchild <paul@icontrolwp.com>
 * @version 1.0.0
 */

// Exit if not called from WordPress
if ( !defined( 'WP_UNINSTALL_PLUGIN' ) ) { exit; }

// Remove options
delete_option( 'deemtree_options' );