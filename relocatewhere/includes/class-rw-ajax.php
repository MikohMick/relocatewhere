<?php
/**
 * AJAX handlers for RelocateWhere.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class RW_Ajax {

    public static function init() {
        // Public AJAX endpoints (no login required).
        add_action( 'wp_ajax_rw_search_counties', array( __CLASS__, 'search_counties' ) );
        add_action( 'wp_ajax_nopriv_rw_search_counties', array( __CLASS__, 'search_counties' ) );

        add_action( 'wp_ajax_rw_get_results', array( __CLASS__, 'get_results' ) );
        add_action( 'wp_ajax_nopriv_rw_get_results', array( __CLASS__, 'get_results' ) );

        add_action( 'wp_ajax_rw_save_email', array( __CLASS__, 'save_email' ) );
        add_action( 'wp_ajax_nopriv_rw_save_email', array( __CLASS__, 'save_email' ) );

        add_action( 'wp_ajax_rw_submit_contribution', array( __CLASS__, 'submit_contribution' ) );
        add_action( 'wp_ajax_nopriv_rw_submit_contribution', array( __CLASS__, 'submit_contribution' ) );
    }

    /**
     * Search counties as user types.
     */
    public static function search_counties() {
        check_ajax_referer( 'rw_nonce', 'nonce' );

        $query   = isset( $_GET['query'] ) ? sanitize_text_field( $_GET['query'] ) : '';
        $results = array();

        if ( strlen( $query ) >= 1 ) {
            $counties = RW_Counties::search( $query );
            foreach ( $counties as $key => $county ) {
                $results[] = array(
                    'key'  => $key,
                    'name' => $county['name'],
                );
            }
        } else {
            // Return all counties.
            $counties = RW_Counties::get_all();
            foreach ( $counties as $key => $county ) {
                $results[] = array(
                    'key'  => $key,
                    'name' => $county['name'],
                );
            }
        }

        wp_send_json_success( $results );
    }

    /**
     * Get cost-of-living results for a county.
     */
    public static function get_results() {
        check_ajax_referer( 'rw_nonce', 'nonce' );

        $county_key     = isset( $_POST['county'] ) ? sanitize_text_field( $_POST['county'] ) : '';
        $household_type = isset( $_POST['household_type'] ) ? sanitize_text_field( $_POST['household_type'] ) : 'single';
        $income_range   = isset( $_POST['income_range'] ) ? sanitize_text_field( $_POST['income_range'] ) : '50000-80000';

        if ( empty( $county_key ) ) {
            wp_send_json_error( array( 'message' => 'Please select a county.' ) );
        }

        $county = RW_Counties::get( $county_key );
        if ( ! $county ) {
            wp_send_json_error( array( 'message' => 'Invalid county selected.' ) );
        }

        // Check cache first.
        $cached = RW_DB::get_cached_results( $county_key, $household_type );

        if ( ! empty( $cached ) ) {
            $towns = array();
            foreach ( $cached as $row ) {
                $cost_data = json_decode( $row['cost_data'], true );
                if ( $cost_data ) {
                    $cost_data['ai_source_pct']    = (int) $row['ai_source_pct'];
                    $cost_data['human_source_pct']  = (int) $row['human_source_pct'];
                    $towns[] = $cost_data;
                }
            }

            if ( ! empty( $towns ) ) {
                wp_send_json_success( array(
                    'county'      => $county,
                    'county_key'  => $county_key,
                    'towns'       => $towns,
                    'from_cache'  => true,
                    'exchange_rate' => (int) get_option( 'rw_usd_exchange_rate', 154 ),
                ) );
            }
        }

        // Generate fresh data via OpenAI.
        $result = RW_API::generate_cost_data(
            $county_key,
            $county['name'],
            $county['towns'],
            $household_type,
            $income_range
        );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'message' => $result->get_error_message() ) );
        }

        // Add source percentages to results.
        foreach ( $result['towns'] as &$town ) {
            $town['ai_source_pct']   = 100;
            $town['human_source_pct'] = 0;
        }

        wp_send_json_success( array(
            'county'        => $county,
            'county_key'    => $county_key,
            'towns'         => $result['towns'],
            'from_cache'    => false,
            'exchange_rate' => (int) get_option( 'rw_usd_exchange_rate', 154 ),
        ) );
    }

    /**
     * Save email subscription.
     */
    public static function save_email() {
        check_ajax_referer( 'rw_nonce', 'nonce' );

        $email          = isset( $_POST['email'] ) ? sanitize_email( $_POST['email'] ) : '';
        $county         = isset( $_POST['county'] ) ? sanitize_text_field( $_POST['county'] ) : '';
        $household_type = isset( $_POST['household_type'] ) ? sanitize_text_field( $_POST['household_type'] ) : '';
        $income_range   = isset( $_POST['income_range'] ) ? sanitize_text_field( $_POST['income_range'] ) : '';

        if ( ! is_email( $email ) ) {
            wp_send_json_error( array( 'message' => 'Please enter a valid email address.' ) );
        }

        $result = RW_DB::save_subscriber( array(
            'email'          => $email,
            'county'         => $county,
            'household_type' => $household_type,
            'income_range'   => $income_range,
        ) );

        if ( ! $result ) {
            wp_send_json_error( array( 'message' => 'Something went wrong. Please try again.' ) );
        }

        wp_send_json_success( array(
            'message' => 'Thank you for subscribing! Check your email for a link to contribute local data.',
            'token'   => $result['token'],
        ) );
    }

    /**
     * Submit a human contribution.
     */
    public static function submit_contribution() {
        check_ajax_referer( 'rw_nonce', 'nonce' );

        $token      = isset( $_POST['token'] ) ? sanitize_text_field( $_POST['token'] ) : '';
        $county_key = isset( $_POST['county_key'] ) ? sanitize_text_field( $_POST['county_key'] ) : '';
        $town       = isset( $_POST['town'] ) ? sanitize_text_field( $_POST['town'] ) : '';

        $cost_fields = array( 'rent', 'food', 'transport', 'utilities', 'entertainment', 'health', 'other' );
        $cost_data   = array();

        foreach ( $cost_fields as $field ) {
            $cost_data[ $field ] = isset( $_POST[ $field ] ) ? absint( $_POST[ $field ] ) : 0;
        }

        if ( empty( $token ) ) {
            wp_send_json_error( array( 'message' => 'Invalid access token.' ) );
        }

        $subscriber = RW_DB::get_subscriber_by_token( $token );
        if ( ! $subscriber ) {
            wp_send_json_error( array( 'message' => 'Invalid or expired token.' ) );
        }

        if ( empty( $county_key ) || empty( $town ) ) {
            wp_send_json_error( array( 'message' => 'Please select a county and town.' ) );
        }

        $id = RW_DB::save_contribution( $subscriber['id'], $county_key, $town, $cost_data );

        if ( ! $id ) {
            wp_send_json_error( array( 'message' => 'Failed to save contribution.' ) );
        }

        wp_send_json_success( array(
            'message' => 'Thank you! Your contribution is under review and will help others make informed decisions.',
        ) );
    }
}
