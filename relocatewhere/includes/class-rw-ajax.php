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

        // Admin-only: debug the scraper connection.
        add_action( 'wp_ajax_rw_debug_scraper', array( __CLASS__, 'debug_scraper' ) );
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

    /**
     * Admin-only: hit the myjobmag widget and report what comes back.
     * Helps diagnose whether the scraper can reach the feed at all.
     */
    public static function debug_scraper() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Unauthorized.' ) );
        }

        check_ajax_referer( 'rw_nonce', 'nonce' );

        // 1. Raw HTTP request (bypass cache).
        $params = array(
            'field'   => 0,
            'industry'=> 0,
            'keyword' => '',
            'count'   => 10,
            'width'   => 600,
            'height'  => 2000,
            'bgcolor' => 'FFFFFF',
        );
        $url      = RW_Scraper::WIDGET_URL . '?' . http_build_query( $params );
        $response = wp_remote_get( $url, array(
            'timeout' => 15,
            'headers' => array(
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
                'Accept'     => 'text/html,application/xhtml+xml,*/*;q=0.8',
                'Referer'    => home_url( '/' ),
            ),
        ) );

        $http_error = is_wp_error( $response ) ? $response->get_error_message() : null;
        $http_code  = $http_error ? 0 : wp_remote_retrieve_response_code( $response );
        $body       = $http_error ? '' : wp_remote_retrieve_body( $response );

        // 2. Run the full scraper (may use cache).
        $scraped       = RW_Scraper::fetch_jobs( '', 10 );
        $jobs_count    = count( $scraped['jobs'] ?? array() );
        $scrape_error  = $scraped['error'] ?? null;

        wp_send_json_success( array(
            'http_status'        => $http_code,
            'http_error'         => $http_error,
            'body_length'        => strlen( $body ),
            'body_preview'       => substr( strip_tags( $body ), 0, 600 ),
            'parsed_jobs_count'  => $jobs_count,
            'first_job'          => isset( $scraped['jobs'][0] ) ? $scraped['jobs'][0] : null,
            'scrape_error'       => $scrape_error,
            'widget_url'         => $url,
        ) );
    }
}
