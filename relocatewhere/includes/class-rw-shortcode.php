<?php
/**
 * Shortcode handler for [relocatewhere].
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class RW_Shortcode {

    public static function init() {
        add_shortcode( 'relocatewhere', array( __CLASS__, 'render' ) );
        add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_assets' ) );
    }

    /**
     * Register (but don't enqueue yet) frontend assets.
     */
    public static function register_assets() {
        // Leaflet CSS & JS.
        wp_register_style( 'leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', array(), '1.9.4' );
        wp_register_script( 'leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', array(), '1.9.4', true );

        // Plugin assets.
        wp_register_style( 'rw-frontend', RW_PLUGIN_URL . 'assets/css/frontend.css', array( 'leaflet' ), RW_VERSION );
        wp_register_script( 'rw-frontend', RW_PLUGIN_URL . 'assets/js/frontend.js', array( 'jquery', 'leaflet' ), RW_VERSION, true );
    }

    /**
     * Render the shortcode.
     */
    public static function render( $atts ) {
        // Enqueue assets only when shortcode is used.
        wp_enqueue_style( 'leaflet' );
        wp_enqueue_style( 'rw-frontend' );
        wp_enqueue_script( 'leaflet' );
        wp_enqueue_script( 'rw-frontend' );

        $default_currency = get_option( 'rw_default_currency', 'KES' );
        $disclaimer       = get_option( 'rw_disclaimer_text', 'These cost-of-living estimates are generated using AI and publicly available data sources. They are approximate and intended as a general guide only — actual costs may vary based on specific location, lifestyle, and market conditions. We recommend verifying with local sources, real estate agents, or recent residents before making relocation decisions. RelocateWhere is not a financial advisor.' );
        $privacy_page_id  = get_option( 'rw_privacy_page_id', 0 );
        $privacy_url      = $privacy_page_id ? get_permalink( $privacy_page_id ) : '#';
        $donate_link      = get_option( 'rw_donate_link', '' );
        $adsense_code     = get_option( 'rw_adsense_code', '' );

        wp_localize_script( 'rw-frontend', 'rwData', array(
            'ajaxUrl'         => admin_url( 'admin-ajax.php' ),
            'nonce'           => wp_create_nonce( 'rw_nonce' ),
            'defaultCurrency' => $default_currency,
            'exchangeRate'    => (int) get_option( 'rw_usd_exchange_rate', 154 ),
            'disclaimer'      => $disclaimer,
            'privacyUrl'      => $privacy_url,
            'donateLink'      => $donate_link,
        ) );

        ob_start();
        include RW_PLUGIN_DIR . 'templates/form.php';
        return ob_get_clean();
    }
}
