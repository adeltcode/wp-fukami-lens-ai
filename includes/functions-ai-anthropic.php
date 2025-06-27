<?php
/**
 * Anthropic API call for Nihongo Proofreader AI
 *
 * @package NihongoProofreaderAI
 */

/**
 * Call the Anthropic API 
 *
 * @param string $content
 * @param string $api_key
 * @param string $model
 * @param string $system_role_prompt
 * @param float $temperature
 * @param int $max_tokens
 * @return array|WP_Error
 */
function fukami_lens_call_anthropic_api($content, $api_key, $model, $system_role_prompt, $temperature = 1, $max_tokens = 1024) {
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
        'result' => $data['content'][0]['text'] ?? esc_html__('No suggestions found.', 'wp-fukami-lens-ai'),
        'prompt_tokens' => $data['usage']['input_tokens'] ?? 0,
        'completion_tokens' => $data['usage']['output_tokens'] ?? 0,
        'total_tokens' => ($data['usage']['input_tokens'] ?? 0) + ($data['usage']['output_tokens'] ?? 0),
    ];
}
