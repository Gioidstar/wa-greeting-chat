<?php

if (!defined('ABSPATH')) {
    exit;
}

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class WA_Greeting_Chat_Google_Sheets {

    private $spreadsheet_id;
    private $sheet_name;
    private $credentials;

    public function __construct() {
        $this->spreadsheet_id = get_option('wa_gsheets_spreadsheet_id', '');
        $this->sheet_name     = get_option('wa_gsheets_sheet_name', 'Sheet1');
        $this->credentials    = get_option('wa_gsheets_credentials', '');
    }

    /**
     * Check if the service is enabled and configured correctly
     */
    public function is_enabled() {
        return get_option('wa_gsheets_enabled', 'no') === 'yes' && 
               !empty($this->spreadsheet_id) && 
               !empty($this->credentials);
    }

    /**
     * Get Access Token using JWT for Service Account
     */
    private function get_access_token() {
        $creds = json_decode($this->credentials, true);
        if (empty($creds) || !isset($creds['private_key']) || !isset($creds['client_email'])) {
            return new WP_Error('invalid_credentials', 'Invalid JSON credentials');
        }

        $now = time();
        $payload = [
            'iss'   => $creds['client_email'],
            'scope' => 'https://www.googleapis.com/auth/spreadsheets',
            'aud'   => 'https://oauth2.googleapis.com/token',
            'exp'   => $now + 3600,
            'iat'   => $now,
        ];

        try {
            $jwt = JWT::encode($payload, $creds['private_key'], 'RS256');
        } catch (Exception $e) {
            return new WP_Error('jwt_error', $e->getMessage());
        }

        $response = wp_remote_post('https://oauth2.googleapis.com/token', [
            'body' => [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion'  => $jwt,
            ],
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (empty($body['access_token'])) {
            return new WP_Error('token_error', isset($body['error_description']) ? $body['error_description'] : 'Failed to get access token');
        }

        return $body['access_token'];
    }

    /**
     * Append data to the Google Spreadsheet
     * 
     * @param array $data Submission data
     * @return bool|WP_Error
     */
    public function append_data($data) {
        if (!$this->is_enabled()) {
            return false;
        }

        $access_token = $this->get_access_token();
        if (is_wp_error($access_token)) {
            error_log('WA Greeting Chat - Auth Error: ' . $access_token->get_error_message());
            return $access_token;
        }

        // Prepare raw data
        $raw_row = [
            'date'          => current_time('j F Y'),
            'name'          => $data['name'],
            'email'         => $data['email'],
            'company'       => $data['company'],
            'service_group' => $data['service_group'],
            'service'       => $data['plugin'],
            'number'        => $data['number'],
            'message'       => $data['message'],
            'url'           => $data['url'],
            'year'          => current_time('Y'),
            'status_new'    => 'NEW',
            'wa_source'     => $this->classify_url_source($data['url']),
            'utm_data'      => $this->extract_utm_params($data['url']),
        ];

        // Get mapping and build the row based on position
        $mapping = get_option('wa_gsheets_mapping', []);
        $final_row = [];
        
        $min_pos = 1;
        $max_pos = 0;
        
        if (!empty($mapping)) {
            $active_positions = [];
            foreach ($mapping as $pos) {
                $pos = intval($pos);
                if ($pos > 0) {
                    $active_positions[] = $pos;
                }
            }
            if (!empty($active_positions)) {
                $min_pos = min($active_positions);
                $max_pos = max($active_positions);
            }
        }

        if ($max_pos > 0) {
            // Initialize array with empty strings up to max position
            $final_row = array_fill(0, $max_pos, '');
            
            foreach ($mapping as $key => $pos) {
                $pos = intval($pos);
                if ($pos > 0 && isset($raw_row[$key])) {
                    // Position 1 maps to index 0, position 2 to index 1, etc.
                    $final_row[$pos - 1] = $raw_row[$key];
                }
            }
        } else {
            // Default order if no mapping exists or all mappings are 0
            $final_row  = array_values($raw_row);
            $max_pos    = count($raw_row);
            $min_pos    = 1;
        }

        // We slice $final_row starting at $min_pos - 1 to only transmit the active range.
        // This prevents overwriting any manually entered data in columns preceding $min_pos.
        $sliced_row = array_slice($final_row, $min_pos - 1);
        $values = [$sliced_row];
        $values_fallback = [$final_row];

        // 1. Get the current sheet values to find the next empty row in the active range
        $get_range = urlencode($this->sheet_name);
        $get_url = "https://sheets.googleapis.com/v4/spreadsheets/{$this->spreadsheet_id}/values/{$get_range}";
        
        $get_response = wp_remote_get($get_url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $access_token,
            ],
            'timeout' => 10,
        ]);

        $next_row = 1;
        $use_append_fallback = false;

        if (!is_wp_error($get_response)) {
            $status_code = wp_remote_retrieve_response_code($get_response);
            if ($status_code === 200) {
                $get_body = json_decode(wp_remote_retrieve_body($get_response), true);
                if (isset($get_body['values']) && is_array($get_body['values'])) {
                    $rows = $get_body['values'];
                    $total_rows = count($rows);
                    $found_row = false;
                    
                    // Scan rows starting from row 2 (index 1) to find the first one
                    // where all columns from $min_pos to $max_pos are empty.
                    for ($r = 1; $r < $total_rows; $r++) {
                        $row_data = $rows[$r];
                        $is_range_empty = true;
                        
                        for ($col = $min_pos; $col <= $max_pos; $col++) {
                            $col_idx = $col - 1;
                            if (isset($row_data[$col_idx]) && trim($row_data[$col_idx]) !== '') {
                                $is_range_empty = false;
                                break;
                            }
                        }
                        
                        if ($is_range_empty) {
                            $next_row = $r + 1;
                            $found_row = true;
                            break;
                        }
                    }
                    
                    if (!$found_row) {
                        $next_row = $total_rows + 1;
                    }
                } else {
                    $next_row = 1;
                }
            } else {
                error_log('WA Greeting Chat - GET values returned status ' . $status_code . '. Fallback to append.');
                $use_append_fallback = true;
            }
        } else {
            error_log('WA Greeting Chat - GET values failed: ' . $get_response->get_error_message() . '. Fallback to append.');
            $use_append_fallback = true;
        }

        if (!$use_append_fallback) {
            // 2. Perform PUT (update) starting exactly at the minimum active column (min_letter) of the found row
            $min_letter = $this->get_column_letter($min_pos);
            $range = urlencode($this->sheet_name . '!' . $min_letter . $next_row);
            $url = "https://sheets.googleapis.com/v4/spreadsheets/{$this->spreadsheet_id}/values/{$range}?valueInputOption=RAW";
            
            $response = wp_remote_request($url, [
                'method'  => 'PUT',
                'headers' => [
                    'Authorization' => 'Bearer ' . $access_token,
                    'Content-Type'  => 'application/json',
                ],
                'body' => json_encode([
                    'values' => $values
                ]),
                'timeout' => 10,
            ]);
        } else {
            // Fallback: Use standard append API with A:MaxLetter range (using the full unsliced row)
            $max_letter = $this->get_column_letter($max_pos);
            $range = urlencode($this->sheet_name . '!A:' . $max_letter);
            $url = "https://sheets.googleapis.com/v4/spreadsheets/{$this->spreadsheet_id}/values/{$range}:append?valueInputOption=RAW";
            
            $response = wp_remote_post($url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $access_token,
                    'Content-Type'  => 'application/json',
                ],
                'body' => json_encode([
                    'values' => $values_fallback
                ]),
                'timeout' => 10,
            ]);
        }

        if (is_wp_error($response)) {
            error_log('WA Greeting Chat - API Error: ' . $response->get_error_message());
            return $response;
        }

        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code !== 200) {
            $body = wp_remote_retrieve_body($response);
            error_log('WA Greeting Chat - API Error (' . $status_code . '): ' . $body);
            return new WP_Error('api_error', 'Google Sheets API returned status ' . $status_code);
        }

        return true;
    }

    /**
     * Classify URL to determine the WA source column value
     *
     * @param string $url Page URL
     * @return string WA Source category
     */
    private function classify_url_source($url) {
        if (empty($url)) {
            return 'WA Artikel';
        }
        
        $path = parse_url($url, PHP_URL_PATH);
        if ($path === null || $path === false) {
            return 'WA Artikel';
        }
        
        $path = trim($path, '/');
        $path_lower = strtolower($path);
        
        if (strpos($path_lower, 'id/') === 0) {
            $path_lower = substr($path_lower, 3);
        } elseif ($path_lower === 'id') {
            $path_lower = '';
        }
        
        if ($path_lower === '') {
            return 'WA Home';
        }
        
        if ($path_lower === 'contact-us') {
            return 'WA Contact Us';
        }
        
        $services_paths = [
            'it-outsourcing-services',
            'it-headhunter-idstar',
            'support-maintenance-teams',
            'talent-creation-program',
            'agentic-ai-automation',
            'robotic-process-automation',
            'ai-document-processing-valida',
            'tax-automation',
            'digital-transformation-consulting',
            'software-development-services',
            'ai-development-service',
            'quality-assurance-testing',
            'josys-saas',
        ];
        
        if (in_array($path_lower, $services_paths, true)) {
            return 'WA Services';
        }
        
        return 'WA Artikel';
    }

    /**
     * Extract UTM parameters from a URL if present.
     *
     * @param string $url Page URL
     * @return string Query string containing only UTM parameters, or empty string if none.
     */
    private function extract_utm_params($url) {
        if (empty($url)) {
            return '';
        }
        
        $query = parse_url($url, PHP_URL_QUERY);
        if (empty($query)) {
            return '';
        }
        
        parse_str($query, $params);
        
        $utm_params = [];
        foreach ($params as $key => $val) {
            if (strpos(strtolower($key), 'utm_') === 0) {
                $utm_params[$key] = $val;
            }
        }
        
        if (empty($utm_params)) {
            return '';
        }
        
        return http_build_query($utm_params);
    }

    /**
     * Convert 1-based column number to Excel-style column letter
     * 
     * @param int $col_num 1-based column number
     * @return string
     */
    private function get_column_letter($col_num) {
        $letter = '';
        while ($col_num > 0) {
            $code = ($col_num - 1) % 26;
            $letter = chr(65 + $code) . $letter;
            $col_num = intval(($col_num - $code) / 26);
        }
        return $letter;
    }
}
