<?php
/**
 * Database operations for RelocateWhere.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class RW_DB {

    /**
     * Create plugin database tables.
     */
    public static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $subscribers_table = $wpdb->prefix . 'rw_subscribers';
        $results_cache     = $wpdb->prefix . 'rw_results_cache';
        $contributions     = $wpdb->prefix . 'rw_contributions';

        $sql = "CREATE TABLE $subscribers_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            email varchar(255) NOT NULL,
            county varchar(100) NOT NULL,
            household_type varchar(50) NOT NULL,
            income_range varchar(100) NOT NULL,
            private_token varchar(64) NOT NULL,
            contributed tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY email (email),
            KEY private_token (private_token)
        ) $charset_collate;

        CREATE TABLE $results_cache (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            county_key varchar(100) NOT NULL,
            town varchar(200) NOT NULL,
            household_type varchar(50) NOT NULL,
            cost_data longtext NOT NULL,
            currency varchar(3) DEFAULT 'KES',
            source_type varchar(20) DEFAULT 'ai',
            ai_source_pct int DEFAULT 100,
            human_source_pct int DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY county_town_type (county_key, town, household_type),
            KEY county_key (county_key)
        ) $charset_collate;

        CREATE TABLE $contributions (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            subscriber_id bigint(20) unsigned NOT NULL,
            county_key varchar(100) NOT NULL,
            town varchar(200) NOT NULL,
            cost_data longtext NOT NULL,
            status varchar(20) DEFAULT 'pending',
            reviewed_at datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY subscriber_id (subscriber_id),
            KEY status (status)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    /**
     * Save subscriber email.
     */
    public static function save_subscriber( $data ) {
        global $wpdb;
        $table = $wpdb->prefix . 'rw_subscribers';
        $token = wp_generate_password( 64, false );

        $existing = $wpdb->get_row(
            $wpdb->prepare( "SELECT id, private_token FROM $table WHERE email = %s", $data['email'] )
        );

        if ( $existing ) {
            return array(
                'id'    => $existing->id,
                'token' => $existing->private_token,
            );
        }

        $wpdb->insert(
            $table,
            array(
                'email'          => sanitize_email( $data['email'] ),
                'county'         => sanitize_text_field( $data['county'] ),
                'household_type' => sanitize_text_field( $data['household_type'] ),
                'income_range'   => sanitize_text_field( $data['income_range'] ),
                'private_token'  => $token,
            ),
            array( '%s', '%s', '%s', '%s', '%s' )
        );

        return array(
            'id'    => $wpdb->insert_id,
            'token' => $token,
        );
    }

    /**
     * Get cached results for a county and household type.
     */
    public static function get_cached_results( $county_key, $household_type ) {
        global $wpdb;
        $table = $wpdb->prefix . 'rw_results_cache';

        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table WHERE county_key = %s AND household_type = %s AND updated_at > DATE_SUB(NOW(), INTERVAL 7 DAY)",
                $county_key,
                $household_type
            ),
            ARRAY_A
        );

        return $results;
    }

    /**
     * Save results to cache.
     */
    public static function save_results( $county_key, $town, $household_type, $cost_data ) {
        global $wpdb;
        $table = $wpdb->prefix . 'rw_results_cache';

        $existing = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT id FROM $table WHERE county_key = %s AND town = %s AND household_type = %s",
                $county_key,
                $town,
                $household_type
            )
        );

        $data = array(
            'county_key'     => sanitize_text_field( $county_key ),
            'town'           => sanitize_text_field( $town ),
            'household_type' => sanitize_text_field( $household_type ),
            'cost_data'      => wp_json_encode( $cost_data ),
            'currency'       => 'KES',
            'source_type'    => 'ai',
            'ai_source_pct'  => 100,
            'human_source_pct' => 0,
        );

        if ( $existing ) {
            $wpdb->update( $table, $data, array( 'id' => $existing->id ) );
        } else {
            $wpdb->insert( $table, $data );
        }
    }

    /**
     * Save a human contribution.
     */
    public static function save_contribution( $subscriber_id, $county_key, $town, $cost_data ) {
        global $wpdb;

        $wpdb->insert(
            $wpdb->prefix . 'rw_contributions',
            array(
                'subscriber_id' => absint( $subscriber_id ),
                'county_key'    => sanitize_text_field( $county_key ),
                'town'          => sanitize_text_field( $town ),
                'cost_data'     => wp_json_encode( $cost_data ),
                'status'        => 'pending',
            ),
            array( '%d', '%s', '%s', '%s', '%s' )
        );

        return $wpdb->insert_id;
    }

    /**
     * Get subscriber by token.
     */
    public static function get_subscriber_by_token( $token ) {
        global $wpdb;
        $table = $wpdb->prefix . 'rw_subscribers';

        return $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM $table WHERE private_token = %s", $token ),
            ARRAY_A
        );
    }

    /**
     * Get all pending contributions (admin).
     */
    public static function get_pending_contributions() {
        global $wpdb;
        $table = $wpdb->prefix . 'rw_contributions';

        return $wpdb->get_results(
            "SELECT c.*, s.email, s.county FROM $table c
             LEFT JOIN {$wpdb->prefix}rw_subscribers s ON c.subscriber_id = s.id
             WHERE c.status = 'pending'
             ORDER BY c.created_at DESC",
            ARRAY_A
        );
    }

    /**
     * Update contribution status.
     */
    public static function update_contribution_status( $id, $status ) {
        global $wpdb;
        $table = $wpdb->prefix . 'rw_contributions';

        $wpdb->update(
            $table,
            array(
                'status'      => sanitize_text_field( $status ),
                'reviewed_at' => current_time( 'mysql' ),
            ),
            array( 'id' => absint( $id ) ),
            array( '%s', '%s' ),
            array( '%d' )
        );
    }

    /**
     * Get all subscribers (admin).
     */
    public static function get_subscribers( $per_page = 20, $page = 1 ) {
        global $wpdb;
        $table  = $wpdb->prefix . 'rw_subscribers';
        $offset = ( $page - 1 ) * $per_page;

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table ORDER BY created_at DESC LIMIT %d OFFSET %d",
                $per_page,
                $offset
            ),
            ARRAY_A
        );
    }
}
