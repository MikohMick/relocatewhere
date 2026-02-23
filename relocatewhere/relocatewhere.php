<?php
/**
 * Plugin Name: myjobmap
 * Plugin URI: https://myjobmap.co.ke
 * Description: Browse jobs across Kenya's 47 counties on an interactive map, powered by myjobmag.co.ke.
 * Version: 2.0.0
 * Author: myjobmap
 * License: GPL v2 or later
 * Text Domain: relocatewhere
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'RW_VERSION', '2.0.0' );
define( 'RW_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'RW_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'RW_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// Include files.
require_once RW_PLUGIN_DIR . 'includes/class-rw-counties.php';
require_once RW_PLUGIN_DIR . 'includes/class-rw-scraper.php';
require_once RW_PLUGIN_DIR . 'includes/class-rw-admin.php';
require_once RW_PLUGIN_DIR . 'includes/class-rw-ajax.php';
require_once RW_PLUGIN_DIR . 'includes/class-rw-shortcode.php';

/**
 * Activation hook.
 */
function rw_activate() {
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
