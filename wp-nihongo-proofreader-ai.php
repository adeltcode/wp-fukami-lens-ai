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

// Post metabox, add a settings shortcut icon
function npa_render_metabox($post) {
    $settings_url = admin_url('options-general.php?page=npa-settings');
    $ai_provider = get_option('npa_ai_provider', 'openai'); // NEW
    $provider_label = $ai_provider === 'anthropic' ? 'Anthropic' : 'OpenAI'; // NEW
    ?>
    <div id="npa-grammar-results"></div>

    <div style="display: flex; align-items: center; justify-content: space-between;">
        <div id="npa-check-btn-container">
            <button type="button" class="button" id="npa-check-btn">Proofread</button>
            <span style="margin-left: 12px; color: #666; font-size: 13px;">
                <strong>Active:</strong> <?php echo esc_html($provider_label); ?> API
            </span>
        </div>
        <div id="npa-token-usage"></div>   
        <a href="<?php echo esc_url($settings_url); ?>" class="npa-settings-link" target="_blank" title="Settings" style="margin-left: 8px;">
            <span class="dashicons dashicons-admin-generic"></span>
        </a>
    </div>
    <?php
}

// AJAX handler
add_action('wp_ajax_npa_check_grammar', 'npa_check_grammar_callback');
function npa_check_grammar_callback() {
    check_ajax_referer('npa_check_nonce', 'nonce');
    if (!current_user_can('edit_posts')) wp_send_json_error('Unauthorized');

    $content = wp_unslash($_POST['content'] ?? '');
    $title   = wp_unslash($_POST['title'] ?? '');
    $ai_provider = get_option('npa_ai_provider', 'openai');
    $model   = get_option('npa_gpt_model', 'gpt-3.5-turbo');
    $system_role_prompt = get_option(
        'npa_system_role_prompt',
        'あなたは日本語のスペルと文法の校正者です。まずスペルミスを優先的に指摘し修正案を出し、その後に文法ミスを指摘し修正案を出してください。'
    );
    $temperature = floatval(get_option('npa_temperature', 1));
    $max_tokens = intval(get_option('npa_max_tokens', 1024));

    // API key selection
    $api_key = $ai_provider === 'anthropic'
        ? get_option('npa_anthropic_api_key', '')
        : get_option('npa_openai_api_key', '');

    if (!$api_key) {
        $provider_label = $ai_provider === 'anthropic' ? 'Anthropic' : 'OpenAI';
        wp_send_json_error("API key missing for provider: {$provider_label}");
    }

    // Remove all <img ...> tags from the content
    $content = preg_replace('/<img[^>]*>/i', '', $content);

    // Combine title and content
    $full_content = "タイトル: {$title}\n\n本文:\n{$content}";

    // --- Measure time ---
    $start_time = microtime(true);

    // Call the selected API
    if ($ai_provider === 'anthropic') {
        $response = npa_call_anthropic_api($full_content, $api_key, $model, $system_role_prompt, $temperature, $max_tokens);
    } else {
        $response = npa_call_openai_api($full_content, $api_key, $model, $system_role_prompt, $temperature, $max_tokens);
    }

    // Check for broken links
    $broken_links = npa_check_broken_links($content);

    // Check for invalid anchor tags
    $invalid_anchors = npa_check_invalid_anchors($content);

    $end_time = microtime(true);
    $time_spent = $end_time - $start_time;

    if (is_wp_error($response)) {
        wp_send_json_error($response->get_error_message());
    } else {
        $response['time_spent'] = $time_spent;
        $response['broken_links'] = $broken_links;
        $response['invalid_anchors'] = $invalid_anchors;
        wp_send_json_success($response);
    }
}

// Update function signature to accept temperature and max_tokens
function npa_call_openai_api($content, $api_key, $model, $system_role_prompt, $temperature = 1, $max_tokens = 1024) {
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
        ],
        'temperature' => $temperature,
        'max_tokens' => $max_tokens,
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

function npa_call_anthropic_api($content, $api_key, $model, $system_role_prompt, $temperature = 1, $max_tokens = 1024) {
    $body = [
        'model' => $model,
        'max_tokens' => $max_tokens,
        'temperature' => $temperature,
        'system' => $system_role_prompt,
        'messages' => [
            [
                'role' => 'user',
                'content' => "以下の日本語テキストについて、まずスペルミスを指摘し修正案を提示し、その後に文法ミスを指摘し修正案を提示してください。\n\n" . $content
            ]
        ]
    ];
    $response = wp_remote_post('https://api.anthropic.com/v1/messages', [
        'headers' => [
            'x-api-key' => $api_key,
            'anthropic-version' => '2023-06-01',
            'content-type' => 'application/json'
        ],
        'body' => wp_json_encode($body),
        'timeout' => 90
    ]);
    if (is_wp_error($response)) return $response;
    $data = json_decode(wp_remote_retrieve_body($response), true);

    // Prepare result
    return [
        'result' => $data['content'][0]['text'] ?? 'No suggestions found.',
        'prompt_tokens' => $data['usage']['input_tokens'] ?? 0,
        'completion_tokens' => $data['usage']['output_tokens'] ?? 0,
        'total_tokens' => ($data['usage']['input_tokens'] ?? 0) + ($data['usage']['output_tokens'] ?? 0),
    ];
}

function npa_check_invalid_anchors($content) {
    $invalids = [];
    // Match <a ...> tags
    preg_match_all('/<a\s+[^>]*>/i', $content, $matches);
    foreach ($matches[0] as $tag) {
        // Check for href attribute with mismatched or missing quotes
        if (!preg_match('/href\s*=\s*("([^"]*)"|\'([^\']*)\')/i', $tag)) {
            $invalids[] = $tag;
        }
    }
    return $invalids;
}

// Function to check for broken links in content
function npa_check_broken_links($content) {
    preg_match_all('/<a\s[^>]*href=["\']([^"\']+)["\']/i', $content, $matches);
    $urls = $matches[1] ?? [];
    $broken = [];
    foreach ($urls as $url) {
        $head = wp_remote_head($url, ['timeout' => 5]);
        if (is_wp_error($head) || wp_remote_retrieve_response_code($head) != 200) {
            $broken[] = $url;
        }
    }
    return $broken;
}
