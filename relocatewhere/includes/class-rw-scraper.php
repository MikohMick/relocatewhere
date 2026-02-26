<?php
/**
 * Fetches jobs from the myjobmag.co.ke XML feed.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class RW_Scraper {

    const XML_FEED_URL   = 'https://www.myjobmag.co.ke/jobsxml_by_categories.xml';
    const CACHE_DURATION  = 1800; // 30 minutes

    /**
     * Fetch and parse jobs from the myjobmag XML feed.
     *
     * @param string $county_name  County name to filter by, or empty for all Kenya.
     * @param int    $count        Max number of jobs to return.
     * @return array { jobs: [], location: string, error?: string }
     */
    public static function fetch_jobs( $county_name = '', $count = 50 ) {
        $county_name = sanitize_text_field( $county_name );
        $cache_key   = 'rw_jobs_v3_' . md5( $county_name . '_' . intval( $count ) );
        $cached      = get_transient( $cache_key );

        if ( false !== $cached ) {
            return $cached;
        }

        // Pass keyword param — myjobmag may honour it for server-side filtering.
        $params = array( 'count' => min( intval( $count ) * 4, 200 ) );
        if ( ! empty( $county_name ) ) {
            $params['keyword'] = $county_name;
        }

        $url      = self::XML_FEED_URL . '?' . http_build_query( $params );
        $response = wp_remote_get( $url, array(
            'timeout' => 15,
            'headers' => array(
                'User-Agent'      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
                'Accept'          => 'application/xml,text/xml,*/*;q=0.8',
                'Accept-Language' => 'en-US,en;q=0.9',
                'Referer'         => home_url( '/' ),
            ),
        ) );

        if ( is_wp_error( $response ) ) {
            return array(
                'jobs'     => array(),
                'location' => $county_name ?: 'All Kenya',
                'error'    => $response->get_error_message(),
            );
        }

        $code = wp_remote_retrieve_response_code( $response );
        if ( 200 !== $code ) {
            return array(
                'jobs'     => array(),
                'location' => $county_name ?: 'All Kenya',
                'error'    => 'HTTP ' . $code,
            );
        }

        $xml_body = wp_remote_retrieve_body( $response );
        $jobs     = self::parse_xml_jobs( $xml_body, $county_name );
        $jobs     = array_slice( $jobs, 0, intval( $count ) );

        $result = array(
            'jobs'     => $jobs,
            'location' => $county_name ?: 'All Kenya',
        );

        set_transient( $cache_key, $result, self::CACHE_DURATION );

        return $result;
    }

    /**
     * Parse jobs from the myjobmag RSS/XML feed body.
     *
     * @param string $xml_body    Raw XML string.
     * @param string $county_name Optional county name for local filtering.
     * @return array
     */
    private static function parse_xml_jobs( $xml_body, $county_name = '' ) {
        if ( empty( $xml_body ) ) {
            return array();
        }

        libxml_use_internal_errors( true );
        $xml = simplexml_load_string( $xml_body );
        libxml_clear_errors();

        if ( ! $xml || ! isset( $xml->channel->item ) ) {
            return array();
        }

        $jobs         = array();
        $county_lower = strtolower( trim( $county_name ) );

        foreach ( $xml->channel->item as $item ) {
            $position  = trim( (string) $item->position );
            $company   = trim( (string) $item->company );
            $location  = trim( (string) $item->location );
            $link      = trim( (string) $item->link );
            $pub_date  = trim( (string) $item->pubDate );
            $expiry    = trim( (string) $item->expiryDate );
            $desc_html = trim( (string) $item->description );

            if ( empty( $link ) || empty( $position ) ) {
                continue;
            }

            // Local county filter: if a county was specified, only keep jobs whose
            // location field contains the county name (case-insensitive).
            if ( ! empty( $county_lower ) ) {
                if ( stripos( $location, $county_lower ) === false ) {
                    continue;
                }
            }

            // Relative posted-date string.
            $date = ! empty( $pub_date ) ? self::format_date( $pub_date ) : 'Recent';

            // Expiry as Unix timestamp (0 = no deadline).
            $expiry_ts = ! empty( $expiry ) ? (int) strtotime( $expiry ) : 0;

            // Plain-text description, capped at 600 chars to keep JSON lean.
            $description = '';
            if ( ! empty( $desc_html ) ) {
                $description = html_entity_decode(
                    wp_strip_all_tags( $desc_html ),
                    ENT_QUOTES | ENT_HTML5,
                    'UTF-8'
                );
                $description = preg_replace( '/\s+/', ' ', $description );
                $description = trim( $description );
                if ( mb_strlen( $description ) > 600 ) {
                    $description = mb_substr( $description, 0, 597 ) . '...';
                }
            }

            $jobs[] = array(
                'title'       => $position,
                'company'     => $company,
                'location'    => $location ?: ( $county_name ?: 'Kenya' ),
                'url'         => $link,
                'date'        => $date,
                'expiry_ts'   => $expiry_ts,
                'description' => $description,
                'source_name' => 'MyJobMag',
                'source_url'  => 'https://www.myjobmag.co.ke',
            );
        }

        // Deduplicate by URL.
        $seen   = array();
        $unique = array();
        foreach ( $jobs as $job ) {
            $key = md5( $job['url'] );
            if ( ! isset( $seen[ $key ] ) ) {
                $seen[ $key ] = true;
                $unique[]     = $job;
            }
        }

        return $unique;
    }

    /**
     * Convert an RFC-2822 date string into a human-readable relative label.
     *
     * @param string $date_str
     * @return string
     */
    private static function format_date( $date_str ) {
        $ts = strtotime( $date_str );
        if ( ! $ts ) {
            return 'Recent';
        }

        $diff_days = (int) floor( ( time() - $ts ) / DAY_IN_SECONDS );

        if ( $diff_days < 1 )  return 'Today';
        if ( $diff_days === 1 ) return '1 day ago';
        if ( $diff_days < 7 )  return $diff_days . ' days ago';
        if ( $diff_days < 14 ) return '1 week ago';
        return date( 'M j, Y', $ts );
    }

    /**
     * Clear all cached job results.
     */
    public static function clear_cache() {
        global $wpdb;
        $wpdb->query(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_rw_jobs%' OR option_name LIKE '_transient_timeout_rw_jobs%'"
        );
    }
}
