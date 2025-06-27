<?php
/**
 * Core plugin logic and AJAX handlers for WP Fukami Lens AI
 *
 * @package FukamiLensAI
 */
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'FUKAMI_LENS_Core' ) ) {
    /**
     * Class FUKAMI_LENS_Core
     * Handles AJAX and core logic for WP Fukami Lens AI.
     */
    class FUKAMI_LENS_Core {
        public function __construct() {
            add_action('wp_ajax_fukami_lens_check_grammar', [ $this, 'fukami_lens_check_grammar_callback' ]);
            add_action('wp_ajax_fukami_lens_ask_ai', [ $this, 'fukami_lens_ask_ai_callback' ]);
        }

        /**
         * AJAX handler for grammar checking.
         */
        public function fukami_lens_check_grammar_callback() {
            check_ajax_referer('fukami_lens_check_nonce');

            if (!current_user_can('edit_posts')) wp_send_json_error( esc_html__('Unauthorized', 'wp-fukami-lens-ai') );

            $content = wp_unslash($_POST['content'] ?? '');
            $title   = wp_unslash($_POST['title'] ?? '');
            $ai_provider = get_option('fukami_lens_ai_provider', 'openai');
            $model   = get_option('fukami_lens_gpt_model', 'gpt-3.5-turbo');
            $system_role_prompt = get_option(
                'fukami_lens_system_role_prompt',
                'あなたは日本語のスペルと文法の校正者です。まずスペルミスを優先的に指摘し修正案を出し、その後に文法ミスを指摘し修正案を出してください。'
            );
            $temperature = floatval(get_option('fukami_lens_temperature', 1));
            $max_tokens = intval(get_option('fukami_lens_max_tokens', 1024));

            // API key selection
            $api_key = $ai_provider === 'anthropic'
                ? get_option('fukami_lens_anthropic_api_key', '')
                : get_option('fukami_lens_openai_api_key', '');

            if (!$api_key) {
                $provider_label = $ai_provider === 'anthropic' ? 'Anthropic' : 'OpenAI';
                wp_send_json_error( sprintf( esc_html__('API key missing for provider: %s', 'wp-fukami-lens-ai'), esc_html($provider_label) ) );
            }

            // Remove all <img ...> tags from the content
            $content = preg_replace('/<img[^>]*>/i', '', $content);

            // Combine title and content
            $full_content = "タイトル: {$title}\n\n本文:\n{$content}";

            // --- Measure time ---
            $start_time = microtime(true);

            // Call the selected API
            if ($ai_provider === 'anthropic') {
                $response = fukami_lens_call_anthropic_api($full_content, $api_key, $model, $system_role_prompt, $temperature, $max_tokens);
            } else {
                $response = fukami_lens_call_openai_api($full_content, $api_key, $model, $system_role_prompt, $temperature, $max_tokens);
            }

            // Check for broken links
            $broken_links = fukami_lens_check_broken_links($content);

            // Check for invalid anchor tags
            $invalid_anchors = fukami_lens_check_invalid_anchors($content);

            $end_time = microtime(true);
            $time_spent = $end_time - $start_time;

            if (is_wp_error($response)) {
                wp_send_json_error( $response->get_error_message() );
            } else {
                $response['time_spent'] = $time_spent;
                $response['broken_links'] = $broken_links;
                $response['invalid_anchors'] = $invalid_anchors;
                wp_send_json_success($response);
            }
        }

        /**
         * AJAX handler for AI Assistant (RAG) questions.
         */
        public function fukami_lens_ask_ai_callback() {
            if (!current_user_can('manage_options')) {
                wp_send_json_error('Permission denied');
            }
            check_ajax_referer('fukami_lens_ask_ai_nonce');

            $question = trim(stripslashes($_POST['question'] ?? ''));
            if (!$question) {
                wp_send_json_error('No question provided.');
            }

            // Date filters for RAG
            $start_date = sanitize_text_field($_POST['start_date'] ?? '');
            $end_date   = sanitize_text_field($_POST['end_date']   ?? '');

            // Get settings
            $provider = get_option('fukami_lens_ai_provider', 'openai');
            $use_rag = get_option('fukami_lens_rag_enabled', '0') === '1';
            $rag_context_window = intval(get_option('fukami_lens_rag_context_window', 2048));
            $system_prompt = get_option('fukami_lens_system_role_prompt', '');
            $dashboard_prompt = get_option('fukami_lens_dashboard_system_prompt', '');
            $proof_temp = floatval(get_option('fukami_lens_temperature', 1));
            $proof_tokens = intval(get_option('fukami_lens_max_tokens', 1000));
            $rag_temp = floatval(get_option('fukami_lens_rag_temperature', 1));
            $rag_tokens = intval(get_option('fukami_lens_rag_max_tokens', 1000));

            // Check if this is from dashboard widget
            $from_dashboard = isset($_POST['from_dashboard']) && $_POST['from_dashboard'] == '1';
            if ($from_dashboard && $dashboard_prompt) {
                $system_prompt = $dashboard_prompt;
            }

            // Compose prompt (very basic RAG logic for demo)
            $context = '';
            if ($use_rag && $rag_source) {
                // build context URL including dates
                $params = ['q'=>$question];
                if ($start_date) $params['start_date']=$start_date;
                if ($end_date)   $params['end_date']=$end_date;
                $url = $rag_source . '?' . http_build_query($params);
                $rag_response = wp_remote_get($url);

                if (!is_wp_error($rag_response)
                    && wp_remote_retrieve_response_code($rag_response)==200) {
                    $context = wp_remote_retrieve_body($rag_response);
                }
            }

            $prompt = $system_prompt . "\n";
            if ($context) {
                $prompt .= "Context:\n" . $context . "\n";
            }
            $prompt .= "User: " . $question;

            // Use RAG or proofreader temp/tokens
            $temperature = ($use_rag && $rag_source) ? $rag_temp : $proof_temp;
            $max_tokens = ($use_rag && $rag_source) ? $rag_tokens : $proof_tokens;

            // Call AI API (OpenAI example)
            $answer = '';
            if ($provider === 'openai') {
                $api_key = get_option('fukami_lens_openai_api_key', '');
                $model = get_option('fukami_lens_openai_gpt_model', 'gpt-3.5-turbo');
                if (!$api_key) wp_send_json_error('OpenAI API key not set.');
                $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $api_key,
                        'Content-Type' => 'application/json',
                    ],
                    'body' => json_encode([
                        'model' => $model,
                        'messages' => [
                            ['role' => 'system', 'content' => $system_prompt],
                            ['role' => 'user', 'content' => $prompt],
                        ],
                        'temperature' => $temperature,
                        'max_tokens' => $max_tokens,
                    ]),
                    'timeout' => 30,
                ]);
                if (is_wp_error($response)) wp_send_json_error('OpenAI API error.');
                $body = json_decode(wp_remote_retrieve_body($response), true);
                $answer = $body['choices'][0]['message']['content'] ?? 'No answer.';
            }
            // TODO: Add Anthropic support if needed

            wp_send_json_success(['answer' => nl2br(esc_html($answer))]);
        }
    }
}

// Instantiate only once
if ( ! isset( $GLOBALS['fukami_lens_core_instance'] ) ) {
    $GLOBALS['fukami_lens_core_instance'] = new FUKAMI_LENS_Core();
}
