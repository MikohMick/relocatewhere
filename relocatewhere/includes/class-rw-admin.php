<?php
/**
 * Admin settings page for myjobmap.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class RW_Admin {

    public static function init() {
        add_action( 'admin_menu', array( __CLASS__, 'add_menu' ) );
        add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
        add_action( 'admin_post_rw_clear_cache', array( __CLASS__, 'handle_clear_cache' ) );
    }

    public static function add_menu() {
        add_menu_page(
            'myjobmap',
            'myjobmap',
            'manage_options',
            'myjobmap',
            array( __CLASS__, 'settings_page' ),
            'dashicons-location-alt',
            30
        );
    }

    public static function register_settings() {
        register_setting( 'rw_settings', 'rw_adsense_code', array(
            'type'              => 'string',
            'sanitize_callback' => 'wp_kses_post',
        ) );
        register_setting( 'rw_settings', 'rw_donate_link', array(
            'type'              => 'string',
            'sanitize_callback' => 'esc_url_raw',
        ) );

        add_settings_section( 'rw_monetization_section', 'Monetization', null, 'rw_settings' );

        add_settings_field(
            'rw_adsense_code',
            'Google AdSense Code',
            array( __CLASS__, 'render_textarea_field' ),
            'rw_settings',
            'rw_monetization_section',
            array( 'name' => 'rw_adsense_code', 'desc' => 'Paste your AdSense ad unit code here.' )
        );
        add_settings_field(
            'rw_donate_link',
            'Donate Button Link',
            array( __CLASS__, 'render_text_field' ),
            'rw_settings',
            'rw_monetization_section',
            array( 'name' => 'rw_donate_link', 'desc' => 'URL for the donate button (PayPal, M-Pesa link, etc.).' )
        );
    }

    public static function enqueue_assets( $hook ) {
        if ( strpos( $hook, 'myjobmap' ) === false ) {
            return;
        }
        wp_enqueue_style( 'rw-admin', RW_PLUGIN_URL . 'assets/css/admin.css', array(), RW_VERSION );
    }

    public static function handle_clear_cache() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Unauthorized' );
        }
        check_admin_referer( 'rw_clear_cache' );
        RW_Scraper::clear_cache();
        wp_redirect( admin_url( 'admin.php?page=myjobmap&cache_cleared=1' ) );
        exit;
    }

    public static function settings_page() {
        $cleared = isset( $_GET['cache_cleared'] ) && '1' === $_GET['cache_cleared'];
        ?>
        <div class="wrap">
            <h1>myjobmap Settings</h1>
            <p class="description">
                Use the shortcode <code>[myjobmap]</code> to display the job map on any page.
                Job data is sourced live from <a href="https://www.myjobmag.co.ke" target="_blank">myjobmag.co.ke</a>
                and cached for 30 minutes.
            </p>

            <?php if ( $cleared ) : ?>
                <div class="notice notice-success is-dismissible"><p>Job cache cleared successfully.</p></div>
            <?php endif; ?>

            <form method="post" action="options.php">
                <?php
                settings_fields( 'rw_settings' );
                do_settings_sections( 'rw_settings' );
                submit_button( 'Save Settings' );
                ?>
            </form>

            <hr />

            <h2>Cache</h2>
            <p>Job listings are cached for 30 minutes per county to reduce requests to myjobmag.co.ke. Clear the cache to force fresh results immediately.</p>
            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <input type="hidden" name="action" value="rw_clear_cache" />
                <?php wp_nonce_field( 'rw_clear_cache' ); ?>
                <?php submit_button( 'Clear Job Cache', 'secondary' ); ?>
            </form>
        </div>
        <?php
    }

    public static function render_text_field( $args ) {
        $value = get_option( $args['name'], '' );
        printf(
            '<input type="text" name="%s" value="%s" class="regular-text" /><p class="description">%s</p>',
            esc_attr( $args['name'] ),
            esc_attr( $value ),
            esc_html( $args['desc'] )
        );
    }

    public static function render_textarea_field( $args ) {
        $value = get_option( $args['name'], '' );
        printf(
            '<textarea name="%s" rows="4" class="large-text">%s</textarea><p class="description">%s</p>',
            esc_attr( $args['name'] ),
            esc_textarea( $value ),
            esc_html( $args['desc'] )
        );
    }
}
