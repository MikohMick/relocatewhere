<?php
/**
 * Plugin Name: RelocateWhere
 * Plugin URI: https://relocatewhere.com
 * Description: Help users discover the best places to relocate within Kenya based on cost of living data.
 * Version: 1.0.0
 * Author: RelocateWhere
 * License: GPL v2 or later
 * Text Domain: relocatewhere
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'RW_VERSION', '1.0.0' );
define( 'RW_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'RW_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'RW_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// Include files.
require_once RW_PLUGIN_DIR . 'includes/class-rw-db.php';
require_once RW_PLUGIN_DIR . 'includes/class-rw-admin.php';
require_once RW_PLUGIN_DIR . 'includes/class-rw-api.php';
require_once RW_PLUGIN_DIR . 'includes/class-rw-ajax.php';
require_once RW_PLUGIN_DIR . 'includes/class-rw-shortcode.php';
require_once RW_PLUGIN_DIR . 'includes/class-rw-counties.php';

/**
 * Activation hook - create database tables.
 */
function rw_activate() {
    RW_DB::create_tables();
    flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'rw_activate' );

/**
 * Deactivation hook.
 */
function rw_deactivate() {
    flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'rw_deactivate' );

/**
 * Initialize plugin.
 */
function rw_init() {
    RW_Admin::init();
    RW_Ajax::init();
    RW_Shortcode::init();
}
add_action( 'plugins_loaded', 'rw_init' );
