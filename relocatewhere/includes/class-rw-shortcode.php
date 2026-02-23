<?php
/**
 * Shortcode handler for [myjobmap].
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class RW_Shortcode {

    public static function init() {
        // Primary shortcode.
        add_shortcode( 'myjobmap', array( __CLASS__, 'render' ) );
        // Backward-compat alias.
        add_shortcode( 'relocatewhere', array( __CLASS__, 'render' ) );

        add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_assets' ) );
    }

    public static function register_assets() {
        wp_register_style(
            'leaflet',
            'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',
            array(),
            '1.9.4'
        );
        wp_register_script(
            'leaflet',
            'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',
            array(),
            '1.9.4',
            true
        );
        wp_register_style(
            'rw-frontend',
            RW_PLUGIN_URL . 'assets/css/frontend.css',
            array( 'leaflet' ),
            RW_VERSION
        );
        wp_register_script(
            'rw-frontend',
            RW_PLUGIN_URL . 'assets/js/frontend.js',
            array( 'jquery', 'leaflet' ),
            RW_VERSION,
            true
        );
    }

    public static function render( $atts ) {
        wp_enqueue_style( 'leaflet' );
        wp_enqueue_style( 'rw-frontend' );
        wp_enqueue_script( 'leaflet' );
        wp_enqueue_script( 'rw-frontend' );

        // Build counties array for JS.
        $counties    = RW_Counties::get_all();
        $counties_js = array();
        foreach ( $counties as $key => $county ) {
            $counties_js[] = array(
                'key'  => $key,
                'name' => $county['name'],
                'lat'  => $county['lat'],
                'lng'  => $county['lng'],
            );
        }

        wp_localize_script( 'rw-frontend', 'rwData', array(
            'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
            'nonce'      => wp_create_nonce( 'rw_nonce' ),
            'counties'   => $counties_js,
            'donateLink' => get_option( 'rw_donate_link', '' ),
        ) );

        ob_start();
        include RW_PLUGIN_DIR . 'templates/form.php';
        return ob_get_clean();
    }
}
