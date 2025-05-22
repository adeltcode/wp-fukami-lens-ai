<?php
/*
Plugin Name: Nihongo Proofreader AI
Description: Japanese spelling and grammar checker for Classic Editor using OpenAI.
Version: 1.0
Author: Patrick James Garcia
*/

if (!defined('ABSPATH')) exit;

// Define constants
define('NPA_PLUGIN_DIR', plugin_dir_path(__FILE__));

// Include settings page
require_once NPA_PLUGIN_DIR . 'admin/settings-page.php';

// Enqueue scripts/styles only on post editor
add_action('admin_enqueue_scripts', function($hook) {
    global $typenow;
    if ($hook === 'post.php' || $hook === 'post-new.php') {
        wp_enqueue_script('npa-checker', plugins_url('assets/checker.js', __FILE__), ['jquery'], null, true);
        wp_localize_script('npa-checker', 'npa_ajax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('npa_check_nonce')
        ]);
        wp_enqueue_style('npa-checker', plugins_url('assets/checker.css', __FILE__));
    }
});

// Add metabox
add_action('add_meta_boxes', function() {
    add_meta_box('npa_grammar_checker', 'Nihongo Proofreader AI', 'npa_render_metabox', null, 'normal', 'high');
});

function npa_render_metabox($post) {
    ?>
    <div id="npa-grammar-results"></div>
    <button type="button" class="button" id="npa-check-btn">Proofread</button>
    <?php
}

// AJAX handler
add_action('wp_ajax_npa_check_grammar', 'npa_check_grammar_callback');
function npa_check_grammar_callback() {
    check_ajax_referer('npa_check_nonce', 'nonce');
    if (!current_user_can('edit_posts')) wp_send_json_error('Unauthorized');

    $content = wp_unslash($_POST['content'] ?? '');
    $title   = wp_unslash($_POST['title'] ?? ''); // Get the title from AJAX
    $api_key = get_option('npa_openai_api_key', '');
    $model   = get_option('npa_gpt_model', 'gpt-3.5-turbo');
    $system_role_prompt = get_option(
        'npa_system_role_prompt',
        'あなたは日本語のスペルと文法の校正者です。まずスペルミスを優先的に指摘し修正案を出し、その後に文法ミスを指摘し修正案を出してください。'
    );

    if (!$api_key) wp_send_json_error('API key missing');

    // Remove all <img ...> tags from the content
    $content = preg_replace('/<img[^>]*>/i', '', $content);

    // Combine title and content
    $full_content = "タイトル: {$title}\n\n本文:\n{$content}";

    // --- Measure time ---
    $start_time = microtime(true);

    // Call OpenAI API
    $response = npa_call_openai_api($full_content, $api_key, $model, $system_role_prompt);

    $end_time = microtime(true);
    $time_spent = $end_time - $start_time;

    if (is_wp_error($response)) {
        wp_send_json_error($response->get_error_message());
    } else {
        // Add time spent to response
        $response['time_spent'] = $time_spent;
        wp_send_json_success($response);
    }
}

// Update function signature to accept $system_role_prompt
function npa_call_openai_api($content, $api_key, $model, $system_role_prompt) {
    $body = [
        'model' => $model,
        'messages' => [
            [
                'role' => 'system',
                'content' => $system_role_prompt
            ],
            [
                'role' => 'user',
                'content' => "以下の日本語テキストについて、まずスペルミスを指摘し修正案を提示し、その後に文法ミスを指摘し修正案を提示してください。\n\n" . $content
            ]
        ]
    ];
    $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
        'headers' => [
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type'  => 'application/json'
        ],
        'body' => wp_json_encode($body),
        'timeout' => 90
    ]);
    if (is_wp_error($response)) return $response;
    $data = json_decode(wp_remote_retrieve_body($response), true);

    // Prepare result
    return [
        'result' => $data['choices'][0]['message']['content'] ?? 'No suggestions found.',
        'prompt_tokens' => $data['usage']['prompt_tokens'] ?? 0,
        'completion_tokens' => $data['usage']['completion_tokens'] ?? 0,
        'total_tokens' => $data['usage']['total_tokens'] ?? 0,
    ];
}