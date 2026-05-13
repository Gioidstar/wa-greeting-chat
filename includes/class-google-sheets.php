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
            'date'          => current_time('mysql'),
            'name'          => $data['name'],
            'email'         => $data['email'],
            'company'       => $data['company'],
            'service_group' => $data['service_group'],
            'service'       => $data['plugin'],
            'number'        => $data['number'],
            'message'       => $data['message'],
            'url'           => $data['url'],
        ];

        // Get mapping and build the row based on position
        $mapping = get_option('wa_gsheets_mapping', []);
        $final_row = [];
        
        if (!empty($mapping)) {
            // Find the maximum column position
            $max_pos = 0;
            foreach ($mapping as $pos) {
                if ($pos > $max_pos) $max_pos = $pos;
            }

            if ($max_pos > 0) {
                // Initialize array with empty strings up to max position
                $final_row = array_fill(0, $max_pos, '');
                
                foreach ($mapping as $key => $pos) {
                    if ($pos > 0 && isset($raw_row[$key])) {
                        // Position 1 maps to index 0, position 2 to index 1, etc.
                        $final_row[$pos - 1] = $raw_row[$key];
                    }
                }
            }
        } else {
            // Default order if no mapping exists
            $final_row = array_values($raw_row);
        }

        $values = [$final_row];

        $range = urlencode($this->sheet_name . '!A1');
        $url = "https://sheets.googleapis.com/v4/spreadsheets/{$this->spreadsheet_id}/values/{$range}:append?valueInputOption=RAW";

        $response = wp_remote_post($url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type'  => 'application/json',
            ],
            'body' => json_encode([
                'values' => $values
            ]),
        ]);

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
}
