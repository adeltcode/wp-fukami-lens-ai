<?php
/**
 * OpenAI API call for Nihongo Proofreader AI
 *
 * @package NihongoProofreaderAI
 */

/**
 * Call the OpenAI API
 *
 * @param string $content
 * @param string $api_key
 * @param string $model
 * @param string $system_role_prompt
 * @param float $temperature
 * @param int $max_tokens
 * @return array|WP_Error
 */
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
        'result' => $data['choices'][0]['message']['content'] ?? esc_html__('No suggestions found.', 'wp-nihongo-proofreader-ai'),
        'prompt_tokens' => $data['usage']['prompt_tokens'] ?? 0,
        'completion_tokens' => $data['usage']['completion_tokens'] ?? 0,
        'total_tokens' => $data['usage']['total_tokens'] ?? 0,
    ];
}
