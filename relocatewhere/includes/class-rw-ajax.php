<?php
/**
 * AJAX handlers for myjobmap.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class RW_Ajax {

    public static function init() {
        add_action( 'wp_ajax_rw_get_jobs',        array( __CLASS__, 'get_jobs' ) );
        add_action( 'wp_ajax_nopriv_rw_get_jobs', array( __CLASS__, 'get_jobs' ) );
    }

    /**
     * Fetch jobs from myjobmag for a given county (or all Kenya).
     */
    public static function get_jobs() {
        check_ajax_referer( 'rw_nonce', 'nonce' );

        $county_key  = isset( $_POST['county'] ) ? sanitize_text_field( $_POST['county'] ) : '';
        $county_name = '';

        if ( ! empty( $county_key ) ) {
            $county = RW_Counties::get( $county_key );
            if ( $county ) {
                $county_name = $county['name'];
            }
        }

        $result = RW_Scraper::fetch_jobs( $county_name, 50 );

        if ( ! empty( $result['error'] ) && empty( $result['jobs'] ) ) {
            wp_send_json_error( array(
                'message'  => 'Could not load jobs right now. Please try again shortly.',
                'location' => $result['location'],
            ) );
            return;
        }

        wp_send_json_success( array(
            'jobs'     => $result['jobs'],
            'location' => $result['location'],
            'count'    => count( $result['jobs'] ),
        ) );
    }
}
