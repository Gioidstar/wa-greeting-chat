<?php

if (!defined('ABSPATH')) {
    exit;
}

class WA_Greeting_Chat_Brevo {

    private $api_key;
    private $list_ids;
    private $enabled;

    public function __construct() {
        $this->api_key  = get_option('wa_brevo_api_key', '');
        $this->list_ids = get_option('wa_brevo_list_ids', '');
        $this->enabled  = get_option('wa_brevo_enabled', 'no');
    }

    /**
     * Check if Brevo integration is enabled and configured correctly
     *
     * @return bool
     */
    public function is_enabled() {
        return $this->enabled === 'yes' && !empty($this->api_key) && !empty($this->list_ids);
    }

    /**
     * Send contact data to Brevo API v3
     *
     * @param array $data Submission data
     * @return bool|WP_Error
     */
    public function add_or_update_contact($data) {
        if (!$this->is_enabled()) {
            return false;
        }

        // Parse Brevo list IDs (comma separated integers)
        $lists = array_map('intval', explode(',', $this->list_ids));
        $lists = array_filter($lists);

        if (empty($lists)) {
            error_log('WA Greeting Chat - Brevo Error: No valid List IDs provided');
            return new WP_Error('invalid_lists', 'No valid Brevo List IDs provided');
        }

        // Split Name into First Name & Last Name
        $full_name = isset($data['name']) ? trim($data['name']) : '';
        $name_parts = explode(' ', $full_name, 2);
        $first_name = isset($name_parts[0]) ? $name_parts[0] : '';
        $last_name  = isset($name_parts[1]) ? $name_parts[1] : '';

        // Get Brevo Attributes mappings from WP Options
        $mapping = [
            'first_name' => get_option('wa_brevo_map_first_name', 'FIRSTNAME'),
            'last_name'  => get_option('wa_brevo_map_last_name', 'LASTNAME'),
            'number'     => get_option('wa_brevo_map_number', 'SMS'),
            'company'    => get_option('wa_brevo_map_company', 'COMPANY'),
            'service'    => get_option('wa_brevo_map_service', ''),
            'message'    => get_option('wa_brevo_map_message', ''),
            'url'        => get_option('wa_brevo_map_url', ''),
        ];

        $attributes = [];

        // Map fields to Brevo Attributes (keys must be uppercase in Brevo)
        if (!empty($mapping['first_name']) && !empty($first_name)) {
            $attributes[strtoupper($mapping['first_name'])] = $first_name;
        }
        if (!empty($mapping['last_name']) && !empty($last_name)) {
            $attributes[strtoupper($mapping['last_name'])] = $last_name;
        }
        if (!empty($mapping['number']) && !empty($data['number'])) {
            // Brevo SMS attribute expects number with country code, digits only (e.g. 6281234567890)
            $phone = preg_replace('/[^0-9]/', '', $data['number']);
            $attributes[strtoupper($mapping['number'])] = $phone;
        }
        if (!empty($mapping['company']) && !empty($data['company'])) {
            $attributes[strtoupper($mapping['company'])] = $data['company'];
        }
        if (!empty($mapping['service']) && !empty($data['plugin'])) {
            $attributes[strtoupper($mapping['service'])] = $data['plugin'];
        }
        if (!empty($mapping['message']) && !empty($data['message'])) {
            $attributes[strtoupper($mapping['message'])] = $data['message'];
        }
        if (!empty($mapping['url']) && !empty($data['url'])) {
            $attributes[strtoupper($mapping['url'])] = $data['url'];
        }

        // Build API payload
        $body = [
            'email'         => sanitize_email($data['email']),
            'listIds'       => array_values($lists),
            'updateEnabled' => true, // updates existing contacts instead of returning 400 error
        ];

        if (!empty($attributes)) {
            $body['attributes'] = $attributes;
        }

        // Call Brevo API v3 Contacts endpoint
        $response = wp_remote_post('https://api.brevo.com/v3/contacts', [
            'headers' => [
                'api-key'      => $this->api_key,
                'content-type' => 'application/json',
                'accept'       => 'application/json',
            ],
            'body'    => json_encode($body),
            'timeout' => 15,
        ]);

        if (is_wp_error($response)) {
            error_log('WA Greeting Chat - Brevo API Connection Error: ' . $response->get_error_message());
            return $response;
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);

        // Success statuses in Brevo API: 201 (Created), 204 (Updated/No Content), 200 (Success)
        if ($status_code !== 201 && $status_code !== 204 && $status_code !== 200) {
            error_log('WA Greeting Chat - Brevo API Error (' . $status_code . '): ' . $response_body);
            return new WP_Error('api_error', 'Brevo API returned status ' . $status_code . ': ' . $response_body);
        }

        return true;
    }
}
