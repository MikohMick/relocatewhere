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

            <hr />

            <h2>Scraper Connection Test</h2>
            <p>Verify that the server can reach the myjobmag.co.ke widget feed and parse job listings. Run this after first install or if the job list shows empty.</p>
            <button id="rw-test-scraper" class="button button-secondary">&#9654; Run Connection Test</button>
            <div id="rw-test-result" style="margin-top:14px;max-width:720px;font-family:monospace;font-size:13px;"></div>

            <script>
            (function($){
                var nonce = '<?php echo esc_js( wp_create_nonce( 'rw_nonce' ) ); ?>';

                $('#rw-test-scraper').on('click', function(){
                    var $btn = $(this);
                    var $out = $('#rw-test-result');

                    $btn.prop('disabled', true).text('Testing\u2026');
                    $out.html('<p style="color:#6b7280;">Connecting to myjobmag.co.ke &mdash; this may take up to 15 seconds&hellip;</p>');

                    $.ajax({
                        url:    ajaxurl,
                        method: 'POST',
                        data:   { action: 'rw_debug_scraper', nonce: nonce },
                        timeout: 20000,
                        success: function(r){
                            $btn.prop('disabled', false).text('\u25B6 Run Connection Test');

                            if (!r.success) {
                                $out.html(
                                    '<div style="background:#fef2f2;border:1px solid #fca5a5;border-radius:8px;padding:14px;color:#991b1b;">' +
                                    '<strong>Error:</strong> ' + (r.data ? escHtml(r.data.message) : 'Unknown error') + '</div>'
                                );
                                return;
                            }

                            var d   = r.data;
                            var ok  = d.http_status === 200;
                            var bg  = ok ? '#f0fdf4' : '#fef2f2';
                            var bc  = ok ? '#86efac' : '#fca5a5';
                            var clr = ok ? '#166534' : '#991b1b';
                            var status = ok ? '&#9989; ' : '&#10060; ';

                            var html = '<div style="background:' + bg + ';border:1px solid ' + bc + ';border-radius:8px;padding:16px;color:' + clr + ';">';
                            html += '<p style="margin:0 0 10px;font-size:14px;font-weight:600;">' + status + 'HTTP ' + d.http_status + (d.http_error ? ' &mdash; ' + escHtml(d.http_error) : '') + '</p>';
                            html += '<table style="border-collapse:collapse;width:100%;color:#374151;">';
                            html += row('Widget URL', '<a href="' + escHtml(d.widget_url) + '" target="_blank" style="color:#3b82f6;word-break:break-all;">' + escHtml(d.widget_url) + '</a>');
                            html += row('Response body', d.body_length + ' bytes received');
                            html += row('Jobs parsed', '<strong style="font-size:16px;color:' + (d.parsed_jobs_count > 0 ? '#166534' : '#991b1b') + ';">' + d.parsed_jobs_count + '</strong>');
                            if (d.scrape_error) html += row('Scraper error', '<span style="color:#991b1b;">' + escHtml(d.scrape_error) + '</span>');
                            if (d.first_job) html += row('First job', escHtml(d.first_job.title) + (d.first_job.company ? ' &mdash; ' + escHtml(d.first_job.company) : '') + '<br><a href="' + escHtml(d.first_job.url) + '" target="_blank" style="color:#3b82f6;">' + escHtml(d.first_job.url) + '</a>');
                            html += '</table>';

                            if (d.body_preview) {
                                html += '<details style="margin-top:12px;"><summary style="cursor:pointer;font-weight:600;color:#6b7280;">Raw body preview (first 600 chars)</summary>';
                                html += '<pre style="margin:8px 0 0;white-space:pre-wrap;word-break:break-all;font-size:12px;color:#374151;background:#fff;padding:10px;border-radius:6px;border:1px solid #e5e7eb;">' + escHtml(d.body_preview) + '</pre></details>';
                            }

                            html += '</div>';
                            $out.html(html);
                        },
                        error: function(){
                            $btn.prop('disabled', false).text('\u25B6 Run Connection Test');
                            $out.html('<div style="background:#fef2f2;border:1px solid #fca5a5;border-radius:8px;padding:14px;color:#991b1b;">Network error or timeout. The request to myjobmag may have exceeded 15 seconds.</div>');
                        }
                    });

                    function row(label, value){
                        return '<tr><td style="padding:5px 12px 5px 0;color:#6b7280;white-space:nowrap;vertical-align:top;">' + label + '</td><td style="padding:5px 0;">' + value + '</td></tr>';
                    }

                    function escHtml(s){
                        if (!s) return '';
                        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
                    }
                });
            })(jQuery);
            </script>
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
