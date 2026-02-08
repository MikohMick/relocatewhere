<?php
/**
 * Admin settings page for RelocateWhere.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class RW_Admin {

    public static function init() {
        add_action( 'admin_menu', array( __CLASS__, 'add_menu' ) );
        add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
    }

    /**
     * Add admin menu pages.
     */
    public static function add_menu() {
        add_menu_page(
            'RelocateWhere',
            'RelocateWhere',
            'manage_options',
            'relocatewhere',
            array( __CLASS__, 'settings_page' ),
            'dashicons-location-alt',
            30
        );

        add_submenu_page(
            'relocatewhere',
            'Settings',
            'Settings',
            'manage_options',
            'relocatewhere',
            array( __CLASS__, 'settings_page' )
        );

        add_submenu_page(
            'relocatewhere',
            'Subscribers',
            'Subscribers',
            'manage_options',
            'rw-subscribers',
            array( __CLASS__, 'subscribers_page' )
        );

        add_submenu_page(
            'relocatewhere',
            'Contributions',
            'Contributions',
            'manage_options',
            'rw-contributions',
            array( __CLASS__, 'contributions_page' )
        );
    }

    /**
     * Register settings.
     */
    public static function register_settings() {
        register_setting( 'rw_settings', 'rw_openai_api_key', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        ) );
        register_setting( 'rw_settings', 'rw_openai_model', array(
            'type'              => 'string',
            'default'           => 'gpt-4o-mini',
            'sanitize_callback' => 'sanitize_text_field',
        ) );
        register_setting( 'rw_settings', 'rw_default_currency', array(
            'type'              => 'string',
            'default'           => 'KES',
            'sanitize_callback' => 'sanitize_text_field',
        ) );
        register_setting( 'rw_settings', 'rw_usd_exchange_rate', array(
            'type'              => 'number',
            'default'           => 154,
            'sanitize_callback' => 'absint',
        ) );
        register_setting( 'rw_settings', 'rw_adsense_code', array(
            'type'              => 'string',
            'sanitize_callback' => 'wp_kses_post',
        ) );
        register_setting( 'rw_settings', 'rw_disclaimer_text', array(
            'type'    => 'string',
            'default' => 'Our data is generated from AI sources. We are still in development and will soon have confirmed human sources. Help us improve by contributing your local knowledge.',
            'sanitize_callback' => 'sanitize_textarea_field',
        ) );
        register_setting( 'rw_settings', 'rw_cache_days', array(
            'type'              => 'integer',
            'default'           => 7,
            'sanitize_callback' => 'absint',
        ) );
        register_setting( 'rw_settings', 'rw_privacy_page_id', array(
            'type'              => 'integer',
            'sanitize_callback' => 'absint',
        ) );
        register_setting( 'rw_settings', 'rw_donate_link', array(
            'type'              => 'string',
            'sanitize_callback' => 'esc_url_raw',
        ) );

        // Sections.
        add_settings_section( 'rw_api_section', 'API Settings', null, 'rw_settings' );
        add_settings_section( 'rw_general_section', 'General Settings', null, 'rw_settings' );
        add_settings_section( 'rw_monetization_section', 'Monetization', null, 'rw_settings' );

        // API fields.
        add_settings_field( 'rw_openai_api_key', 'OpenAI API Key', array( __CLASS__, 'render_password_field' ), 'rw_settings', 'rw_api_section', array( 'name' => 'rw_openai_api_key', 'desc' => 'Your OpenAI API key for generating cost-of-living data.' ) );
        add_settings_field( 'rw_openai_model', 'OpenAI Model', array( __CLASS__, 'render_select_field' ), 'rw_settings', 'rw_api_section', array(
            'name'    => 'rw_openai_model',
            'desc'    => 'Which model to use. gpt-4o-mini is cheaper, gpt-4o is more accurate.',
            'options' => array(
                'gpt-4o-mini' => 'GPT-4o Mini (cheaper)',
                'gpt-4o'      => 'GPT-4o (more accurate)',
                'gpt-4-turbo' => 'GPT-4 Turbo',
            ),
        ) );

        // General fields.
        add_settings_field( 'rw_default_currency', 'Default Currency', array( __CLASS__, 'render_select_field' ), 'rw_settings', 'rw_general_section', array(
            'name'    => 'rw_default_currency',
            'desc'    => 'Default currency shown to users.',
            'options' => array( 'KES' => 'KES (Kenya Shillings)', 'USD' => 'USD (US Dollars)' ),
        ) );
        add_settings_field( 'rw_usd_exchange_rate', 'USD Exchange Rate', array( __CLASS__, 'render_number_field' ), 'rw_settings', 'rw_general_section', array( 'name' => 'rw_usd_exchange_rate', 'desc' => 'KES per 1 USD. Used for currency conversion.' ) );
        add_settings_field( 'rw_cache_days', 'Cache Duration (days)', array( __CLASS__, 'render_number_field' ), 'rw_settings', 'rw_general_section', array( 'name' => 'rw_cache_days', 'desc' => 'Days before AI results are refreshed.' ) );
        add_settings_field( 'rw_disclaimer_text', 'Disclaimer Text', array( __CLASS__, 'render_textarea_field' ), 'rw_settings', 'rw_general_section', array( 'name' => 'rw_disclaimer_text', 'desc' => 'Shown to users before results.' ) );
        add_settings_field( 'rw_privacy_page_id', 'Privacy Policy Page', array( __CLASS__, 'render_page_select_field' ), 'rw_settings', 'rw_general_section', array( 'name' => 'rw_privacy_page_id', 'desc' => 'Select the privacy policy page.' ) );

        // Monetization fields.
        add_settings_field( 'rw_adsense_code', 'Google AdSense Code', array( __CLASS__, 'render_textarea_field' ), 'rw_settings', 'rw_monetization_section', array( 'name' => 'rw_adsense_code', 'desc' => 'Paste your AdSense ad unit code here.' ) );
        add_settings_field( 'rw_donate_link', 'Donate Button Link', array( __CLASS__, 'render_text_field' ), 'rw_settings', 'rw_monetization_section', array( 'name' => 'rw_donate_link', 'desc' => 'URL for the donate button (PayPal, M-Pesa link, etc.).' ) );
    }

    /**
     * Enqueue admin assets.
     */
    public static function enqueue_assets( $hook ) {
        if ( strpos( $hook, 'relocatewhere' ) === false && strpos( $hook, 'rw-' ) === false ) {
            return;
        }
        wp_enqueue_style( 'rw-admin', RW_PLUGIN_URL . 'assets/css/admin.css', array(), RW_VERSION );
    }

    /**
     * Render settings page.
     */
    public static function settings_page() {
        ?>
        <div class="wrap rw-admin">
            <h1>RelocateWhere Settings</h1>
            <p class="description">Configure your RelocateWhere plugin. Use the shortcode <code>[relocatewhere]</code> to display the tool on any page.</p>

            <form method="post" action="options.php">
                <?php
                settings_fields( 'rw_settings' );
                do_settings_sections( 'rw_settings' );
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Subscribers admin page.
     */
    public static function subscribers_page() {
        $subscribers = RW_DB::get_subscribers( 50 );
        ?>
        <div class="wrap rw-admin">
            <h1>Subscribers</h1>
            <p class="description">Users who signed up to contribute local cost-of-living data.</p>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Email</th>
                        <th>County</th>
                        <th>Household</th>
                        <th>Income Range</th>
                        <th>Contributed</th>
                        <th>Signed Up</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( empty( $subscribers ) ) : ?>
                        <tr><td colspan="7">No subscribers yet.</td></tr>
                    <?php else : ?>
                        <?php foreach ( $subscribers as $sub ) : ?>
                            <tr>
                                <td><?php echo esc_html( $sub['id'] ); ?></td>
                                <td><?php echo esc_html( $sub['email'] ); ?></td>
                                <td><?php echo esc_html( $sub['county'] ); ?></td>
                                <td><?php echo esc_html( $sub['household_type'] ); ?></td>
                                <td><?php echo esc_html( $sub['income_range'] ); ?></td>
                                <td><?php echo $sub['contributed'] ? 'Yes' : 'No'; ?></td>
                                <td><?php echo esc_html( $sub['created_at'] ); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Contributions admin page.
     */
    public static function contributions_page() {
        // Handle approve/reject actions.
        if ( isset( $_GET['rw_action'], $_GET['contribution_id'], $_GET['_wpnonce'] ) ) {
            if ( wp_verify_nonce( $_GET['_wpnonce'], 'rw_contribution_action' ) ) {
                $action = sanitize_text_field( $_GET['rw_action'] );
                $id     = absint( $_GET['contribution_id'] );

                if ( in_array( $action, array( 'approved', 'rejected' ), true ) ) {
                    RW_DB::update_contribution_status( $id, $action );
                    echo '<div class="notice notice-success"><p>Contribution ' . esc_html( $action ) . '.</p></div>';
                }
            }
        }

        $contributions = RW_DB::get_pending_contributions();
        ?>
        <div class="wrap rw-admin">
            <h1>Pending Contributions</h1>
            <p class="description">Review human-submitted cost-of-living data before it goes live.</p>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Email</th>
                        <th>County</th>
                        <th>Town</th>
                        <th>Data</th>
                        <th>Submitted</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( empty( $contributions ) ) : ?>
                        <tr><td colspan="7">No pending contributions.</td></tr>
                    <?php else : ?>
                        <?php foreach ( $contributions as $contrib ) : ?>
                            <tr>
                                <td><?php echo esc_html( $contrib['id'] ); ?></td>
                                <td><?php echo esc_html( $contrib['email'] ); ?></td>
                                <td><?php echo esc_html( $contrib['county_key'] ); ?></td>
                                <td><?php echo esc_html( $contrib['town'] ); ?></td>
                                <td><pre style="max-width:300px;overflow:auto;font-size:11px;"><?php echo esc_html( $contrib['cost_data'] ); ?></pre></td>
                                <td><?php echo esc_html( $contrib['created_at'] ); ?></td>
                                <td>
                                    <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=rw-contributions&rw_action=approved&contribution_id=' . $contrib['id'] ), 'rw_contribution_action' ) ); ?>" class="button button-primary button-small">Approve</a>
                                    <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=rw-contributions&rw_action=rejected&contribution_id=' . $contrib['id'] ), 'rw_contribution_action' ) ); ?>" class="button button-small">Reject</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    // ----- Field renderers -----

    public static function render_text_field( $args ) {
        $value = get_option( $args['name'], '' );
        printf(
            '<input type="text" name="%s" value="%s" class="regular-text" /><p class="description">%s</p>',
            esc_attr( $args['name'] ),
            esc_attr( $value ),
            esc_html( $args['desc'] )
        );
    }

    public static function render_password_field( $args ) {
        $value = get_option( $args['name'], '' );
        $masked = $value ? str_repeat( '*', max( 0, strlen( $value ) - 4 ) ) . substr( $value, -4 ) : '';
        printf(
            '<input type="password" name="%s" value="%s" class="regular-text" autocomplete="off" /><p class="description">%s</p>',
            esc_attr( $args['name'] ),
            esc_attr( $value ),
            esc_html( $args['desc'] )
        );
    }

    public static function render_number_field( $args ) {
        $value = get_option( $args['name'], '' );
        printf(
            '<input type="number" name="%s" value="%s" class="small-text" /><p class="description">%s</p>',
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

    public static function render_select_field( $args ) {
        $value = get_option( $args['name'], '' );
        printf( '<select name="%s">', esc_attr( $args['name'] ) );
        foreach ( $args['options'] as $key => $label ) {
            printf(
                '<option value="%s" %s>%s</option>',
                esc_attr( $key ),
                selected( $value, $key, false ),
                esc_html( $label )
            );
        }
        echo '</select>';
        printf( '<p class="description">%s</p>', esc_html( $args['desc'] ) );
    }

    public static function render_page_select_field( $args ) {
        $value = get_option( $args['name'], 0 );
        wp_dropdown_pages( array(
            'name'              => $args['name'],
            'selected'          => $value,
            'show_option_none'  => '-- Select Page --',
            'option_none_value' => 0,
        ) );
        printf( '<p class="description">%s</p>', esc_html( $args['desc'] ) );
    }
}
