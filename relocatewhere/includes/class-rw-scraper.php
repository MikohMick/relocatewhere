<?php
/**
 * Scraper for myjobmag.co.ke widget feed.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class RW_Scraper {

    const WIDGET_URL     = 'https://www.myjobmag.co.ke/widget/feed.php';
    const CACHE_DURATION  = 1800; // 30 minutes

    /**
     * Fetch and parse jobs from the myjobmag widget.
     *
     * @param string $county_name  County name to filter by, or empty for all Kenya.
     * @param int    $count        Max number of jobs to request.
     * @return array { jobs: [], location: string, error?: string }
     */
    public static function fetch_jobs( $county_name = '', $count = 50 ) {
        $county_name = sanitize_text_field( $county_name );
        $cache_key   = 'rw_jobs_' . md5( $county_name . '_' . intval( $count ) );
        $cached      = get_transient( $cache_key );

        if ( false !== $cached ) {
            return $cached;
        }

        $params = array(
            'field'            => 0,
            'industry'         => 0,
            'keyword'          => $county_name,
            'count'            => intval( $count ),
            'title'            => 'Jobs in Kenya',
            'width'            => 800,
            'height'           => 6000,
            'bgcolor'          => 'FFFFFF',
            'border_color'     => 'CCCCCC',
            'border_thickness' => 1,
            'font_type'        => 'Verdana',
            'title_font_size'  => 14,
            'title_font_color' => '000000',
            'font_size'        => 12,
            'font_color'       => '333333',
            'link_color'       => '031333',
            'show_logo'        => 'No',
            'scroll'           => 'No',
        );

        $url      = self::WIDGET_URL . '?' . http_build_query( $params );
        $response = wp_remote_get( $url, array(
            'timeout' => 15,
            'headers' => array(
                'User-Agent'      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
                'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
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

        $html = wp_remote_retrieve_body( $response );
        $jobs = self::parse_jobs( $html, $county_name );

        $result = array(
            'jobs'     => $jobs,
            'location' => $county_name ?: 'All Kenya',
        );

        set_transient( $cache_key, $result, self::CACHE_DURATION );

        return $result;
    }

    /**
     * Parse job listings from the widget HTML.
     *
     * @param string $html         Raw HTML from the widget.
     * @param string $location     Location label to attach to jobs.
     * @return array
     */
    private static function parse_jobs( $html, $location = '' ) {
        if ( empty( $html ) ) {
            return array();
        }

        $jobs = array();

        $dom = new DOMDocument();
        @$dom->loadHTML( '<?xml encoding="utf-8"?>' . $html, LIBXML_NOERROR | LIBXML_NOWARNING );
        $xpath = new DOMXPath( $dom );

        // Strategy 1: links whose href contains "myjobmag".
        $links = $xpath->query( '//a[contains(@href, "myjobmag")]' );

        $skip_texts = array(
            'home', 'jobs', 'login', 'register', 'about', 'contact',
            'privacy', 'more jobs', 'view all jobs', 'see all jobs',
            'post a job', 'find jobs', 'job seekers', 'employers',
            'career advice', 'cv builder',
        );

        foreach ( $links as $link ) {
            $href = trim( $link->getAttribute( 'href' ) );
            $text = trim( $link->textContent );

            if ( empty( $text ) || empty( $href ) ) {
                continue;
            }

            // Skip very short or nav-style links.
            if ( mb_strlen( $text ) < 6 ) {
                continue;
            }

            if ( in_array( strtolower( $text ), $skip_texts, true ) ) {
                continue;
            }

            // Must look like a job URL (contains /jobs/ or /view_job or /career).
            if (
                strpos( $href, '/jobs/' ) === false
                && strpos( $href, '/view_job' ) === false
                && strpos( $href, '/career' ) === false
            ) {
                // Allow if the text is long enough to be a job title.
                if ( mb_strlen( $text ) < 15 ) {
                    continue;
                }
            }

            // Parse "Job Title at Company" or "Job Title – Company" patterns.
            $title   = $text;
            $company = '';

            $separators = array( ' at ', ' – ', ' - ', ' | ' );
            foreach ( $separators as $sep ) {
                if ( strpos( $text, $sep ) !== false ) {
                    $parts = explode( $sep, $text, 2 );
                    if ( mb_strlen( trim( $parts[0] ) ) > 3 && mb_strlen( trim( $parts[1] ) ) > 1 ) {
                        $title   = trim( $parts[0] );
                        $company = trim( $parts[1] );
                        break;
                    }
                }
            }

            $title   = trim( $title, " \t\n\r\0\x0B-–|" );
            $company = trim( $company, " \t\n\r\0\x0B-–|" );

            // Get date from surrounding text.
            $parent      = $link->parentNode;
            $parent_text = $parent ? trim( $parent->textContent ) : '';
            $date        = self::extract_date( $parent_text );

            $jobs[] = array(
                'title'       => $title,
                'company'     => $company,
                'location'    => $location ?: 'Kenya',
                'url'         => $href,
                'date'        => $date,
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
     * Try to extract a relative date string from text.
     *
     * @param string $text
     * @return string
     */
    private static function extract_date( $text ) {
        if ( preg_match( '/(\d+)\s*(day|hour|week|month)s?\s*ago/i', $text, $m ) ) {
            return $m[0];
        }
        if ( preg_match( '/\d{1,2}\s+\w+\s+\d{4}/', $text, $m ) ) {
            return $m[0];
        }
        return 'Recent';
    }

    /**
     * Clear all cached job results.
     */
    public static function clear_cache() {
        global $wpdb;
        $wpdb->query(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_rw_jobs_%' OR option_name LIKE '_transient_timeout_rw_jobs_%'"
        );
    }
}
