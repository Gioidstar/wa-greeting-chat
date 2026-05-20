<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Hook setelah data berhasil disimpan
 */
add_action('wa_after_submission_saved', 'wa_send_to_google_sheet', 10, 2);

/**
 * Kirim data ke Google Sheets via Apps Script (HMAC secured)
 */
function wa_send_to_google_sheet($post_id, $data) {

    $webhook_url = get_option('wa_google_sheet_webhook');

    if (empty($webhook_url)) {
        error_log('Google Sheet Webhook URL kosong');
        return;
    }

    $secret = get_option('wa_google_sheet_secret');

    if (empty($secret)) {
        error_log('Google Sheet Secret kosong');
        return;
    }

    $body = [
        'post_id' => $post_id,
        'name'    => $data['name'] ?? '',
        'email'   => $data['email'] ?? '',
        'company' => $data['company'] ?? '',
        'phone'   => $data['phone'] ?? '',
        'service' => $data['service'] ?? '',
        'message' => $data['message'] ?? '',
        'time'    => current_time('n/j/Y')
    ];

    $payload = wp_json_encode($body);

    $signature = hash_hmac('sha256', $payload, $secret);

    $body['signature'] = $signature;

    $payload = wp_json_encode($body);

    $signature = hash_hmac('sha256', $payload, $secret);

    $response = wp_remote_post($webhook_url, [
        'method'  => 'POST',
        'headers' => [
            'Content-Type' => 'application/json',
            'X-Signature'  => $signature
        ],
        'body'    => $payload,
        'timeout' => 5
    ]);

    if (is_wp_error($response)) {
        error_log('Gagal kirim ke Google Sheets: ' . $response->get_error_message());
        return;
    }

    $status_code = wp_remote_retrieve_response_code($response);

    if ($status_code !== 200) {
        error_log('Google Sheets response error: ' . $status_code . $response);
    }
}