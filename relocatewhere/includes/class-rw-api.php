<?php
/**
 * OpenAI API integration for cost-of-living data generation.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class RW_API {

    /**
     * Generate cost-of-living data for towns in a county.
     *
     * @param string $county_key    The county identifier.
     * @param string $county_name   The display name.
     * @param array  $towns         Array of town names.
     * @param string $household_type One of: single, couple, family.
     * @param string $income_range  E.g., "50000-80000".
     * @return array|WP_Error
     */
    public static function generate_cost_data( $county_key, $county_name, $towns, $household_type, $income_range ) {
        $api_key = get_option( 'rw_openai_api_key', '' );

        if ( empty( $api_key ) ) {
            return new WP_Error( 'no_api_key', 'OpenAI API key is not configured. Please set it in RelocateWhere settings.' );
        }

        $model = get_option( 'rw_openai_model', 'gpt-4o-mini' );

        $household_label = array(
            'single' => 'a single person',
            'couple' => 'a couple (2 people)',
            'family' => 'a family with children (4 people)',
        );

        $household_desc = isset( $household_label[ $household_type ] ) ? $household_label[ $household_type ] : 'a single person';
        $towns_list     = implode( ', ', $towns );

        $prompt = "You are a cost-of-living data expert for Kenya. Provide realistic monthly cost-of-living estimates in Kenya Shillings (KES) for {$household_desc} living in these towns within {$county_name} County, Kenya: {$towns_list}.

The user's expected monthly income range is KES {$income_range}.

For each town, provide the following monthly cost estimates in KES:
1. rent - Estimated monthly rent for appropriate housing
2. food - Food & groceries
3. transport - Transportation costs
4. utilities - Electricity, water, internet
5. entertainment - Leisure & entertainment
6. health - Healthcare costs
7. other - Miscellaneous expenses
8. total - Total estimated monthly cost
9. description - A brief 1-2 sentence description of the town's character
10. safety - Rate as: Very Safe, Safe, Moderate, or Exercise Caution
11. tags - 2-3 relevant tags like: affordable, urban, suburban, rural, historic, growing, family-friendly, vibrant
12. sources - An array of 2-3 real, publicly accessible URLs that were used or are relevant as references for the cost-of-living data in this town. These should be real websites about Kenya cost of living, housing, or local information (e.g. from numbeo.com, expatistan.com, livingcost.org, property listing sites like buyrentkenya.com, or local news sites). Include a short title for each source.

Also calculate what percentage of the given income range (use the midpoint) the total cost represents, and flag it as:
- comfortable (under 60%)
- reasonable (60-80%)
- stretched (over 80%)

Return ONLY valid JSON in this exact format with no additional text:
{
  \"towns\": [
    {
      \"name\": \"TownName\",
      \"description\": \"Brief description\",
      \"safety\": \"Safe\",
      \"tags\": [\"affordable\", \"suburban\"],
      \"income_pct\": 45,
      \"affordability\": \"comfortable\",
      \"costs\": {
        \"rent\": 15000,
        \"food\": 8000,
        \"transport\": 3000,
        \"utilities\": 4000,
        \"entertainment\": 2000,
        \"health\": 2000,
        \"other\": 1500,
        \"total\": 35500
      },
      \"sources\": [
        {\"title\": \"Numbeo Cost of Living\", \"url\": \"https://www.numbeo.com/cost-of-living/in/Nairobi\"},
        {\"title\": \"BuyRentKenya Listings\", \"url\": \"https://www.buyrentkenya.com/houses-for-rent\"}
      ]
    }
  ]
}";

        $response = wp_remote_post(
            'https://api.openai.com/v1/chat/completions',
            array(
                'timeout' => 60,
                'headers' => array(
                    'Authorization' => 'Bearer ' . $api_key,
                    'Content-Type'  => 'application/json',
                ),
                'body' => wp_json_encode( array(
                    'model'       => $model,
                    'messages'    => array(
                        array(
                            'role'    => 'system',
                            'content' => 'You are a helpful data assistant that returns only valid JSON. No markdown, no code fences, just raw JSON.',
                        ),
                        array(
                            'role'    => 'user',
                            'content' => $prompt,
                        ),
                    ),
                    'temperature' => 0.3,
                    'max_tokens'  => 8000,
                ) ),
            )
        );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = wp_remote_retrieve_body( $response );

        if ( $code !== 200 ) {
            $error_data = json_decode( $body, true );
            $message    = isset( $error_data['error']['message'] ) ? $error_data['error']['message'] : 'Unknown API error (HTTP ' . $code . ')';
            return new WP_Error( 'api_error', $message );
        }

        $data = json_decode( $body, true );

        if ( ! isset( $data['choices'][0]['message']['content'] ) ) {
            return new WP_Error( 'parse_error', 'Unexpected API response format.' );
        }

        $content = $data['choices'][0]['message']['content'];
        // Strip markdown fences if present.
        $content = preg_replace( '/^```(?:json)?\s*/i', '', $content );
        $content = preg_replace( '/\s*```$/', '', $content );
        $content = trim( $content );

        $result = json_decode( $content, true );

        if ( json_last_error() !== JSON_ERROR_NONE ) {
            return new WP_Error( 'json_error', 'Failed to parse AI response: ' . json_last_error_msg() );
        }

        if ( ! isset( $result['towns'] ) || ! is_array( $result['towns'] ) ) {
            return new WP_Error( 'format_error', 'AI response missing expected towns array.' );
        }

        // Cache each town result.
        foreach ( $result['towns'] as $town_data ) {
            if ( isset( $town_data['name'] ) ) {
                RW_DB::save_results( $county_key, $town_data['name'], $household_type, $town_data );
            }
        }

        return $result;
    }
}
