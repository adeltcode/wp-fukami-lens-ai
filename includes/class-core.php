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
            add_action('wp_ajax_fukami_lens_chunk_posts', [ $this, 'fukami_lens_chunk_posts_callback' ]);
            add_action('wp_ajax_fukami_lens_store_embeddings', [ $this, 'fukami_lens_store_embeddings_callback' ]);
            add_action('wp_ajax_fukami_lens_search_similar', [ $this, 'fukami_lens_search_similar_callback' ]);
            add_action('wp_ajax_fukami_lens_get_lancedb_stats', [ $this, 'fukami_lens_get_lancedb_stats_callback' ]);
            add_action('wp_ajax_fukami_lens_get_posts_count', [ $this, 'fukami_lens_get_posts_count_callback' ]);
            add_action('wp_ajax_fukami_lens_check_embeddings_batch', [ $this, 'fukami_lens_check_embeddings_batch_callback' ]);
            add_action('wp_ajax_fukami_lens_store_embeddings_batch', [ $this, 'fukami_lens_store_embeddings_batch_callback' ]);
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

        /**
         * AJAX handler for chunking posts.
         */
        public function fukami_lens_chunk_posts_callback() {
            if (!current_user_can('manage_options')) {
                wp_send_json_error('Permission denied');
            }
            check_ajax_referer('fukami_lens_chunk_posts_nonce');

            // Get date range parameters if provided
            $start_date = sanitize_text_field($_POST['start_date'] ?? '');
            $end_date = sanitize_text_field($_POST['end_date'] ?? '');
            
            // Build query arguments
            $query_args = [];
            if (!empty($start_date)) {
                $query_args['start_date'] = $start_date;
            }
            if (!empty($end_date)) {
                $query_args['end_date'] = $end_date;
            }

            try {
                $chunking_service = new FUKAMI_LENS_Chunking_Service();
                $result = $chunking_service->get_chunking_results($query_args);
                
                if ($result['success']) {
                    wp_send_json_success($result['data']);
                } else {
                    wp_send_json_error($result['data']);
                }
            } catch (Exception $e) {
                wp_send_json_error('Chunking failed: ' . $e->getMessage());
            }
        }

        /**
         * AJAX handler for storing embeddings in LanceDB.
         */
        public function fukami_lens_store_embeddings_callback() {
            if (!current_user_can('manage_options')) {
                wp_send_json_error('Permission denied');
            }
            check_ajax_referer('fukami_lens_chunk_posts_nonce');

            // Get date range parameters if provided
            $start_date = sanitize_text_field($_POST['start_date'] ?? '');
            $end_date = sanitize_text_field($_POST['end_date'] ?? '');
            $post_ids = isset($_POST['post_ids']) ? array_map('intval', $_POST['post_ids']) : [];
            
            try {
                $lancedb_service = new FUKAMI_LENS_LanceDB_Service();
                
                if (!empty($post_ids)) {
                    // Update specific posts
                    $result = $lancedb_service->update_embeddings($post_ids);
                } else {
                    // Get posts based on date range
                    $chunking_service = new FUKAMI_LENS_Chunking_Service();
                    $query_args = [];
                    if (!empty($start_date)) {
                        $query_args['start_date'] = $start_date;
                    }
                    if (!empty($end_date)) {
                        $query_args['end_date'] = $end_date;
                    }
                    
                    // Get the actual WordPress posts instead of HTML
                    $query_args['post_status'] = 'publish';
                    $query_args['orderby'] = 'date';
                    $query_args['order'] = 'DESC';
                    $query_args['numberposts'] = -1;
                    
                    $wordpress_posts = get_posts($query_args);
                    
                    // Convert WordPress posts to the format expected by LanceDB
                    $posts = [];
                    
                    foreach ($wordpress_posts as $post) {
                        $categories = get_the_category($post->ID);
                        $category_names = array_map(function($cat) { return $cat->name; }, $categories);
                        $tags = get_the_tags($post->ID);
                        $tag_names = $tags ? array_map(function($tag) { return $tag->name; }, $tags) : [];
                        
                        $posts[] = [
                            'id' => $post->ID,
                            'title' => $post->post_title,
                            'content' => wp_strip_all_tags($post->post_content),
                            'date' => $post->post_date,
                            'permalink' => get_permalink($post->ID),
                            'categories' => $category_names,
                            'tags' => $tag_names
                        ];
                    }
                    
                    if (!empty($posts)) {
                        // Use the new method that checks for existing embeddings
                        $result = $lancedb_service->store_embeddings_with_check($posts);
                    } else {
                        $result = [
                            'success' => false,
                            'data' => 'No posts found to embed'
                        ];
                    }
                }
                
                if ($result['success']) {
                    wp_send_json_success($result['data']);
                } else {
                    wp_send_json_error($result['data']);
                }
            } catch (Exception $e) {
                wp_send_json_error('Embedding storage failed: ' . $e->getMessage());
            }
        }

        /**
         * AJAX handler for searching similar content using LanceDB.
         */
        public function fukami_lens_search_similar_callback() {
            if (!current_user_can('manage_options')) {
                wp_send_json_error('Permission denied');
            }
            check_ajax_referer('fukami_lens_chunk_posts_nonce');

            $query_text = sanitize_textarea_field($_POST['query_text'] ?? '');
            $limit = intval($_POST['limit'] ?? 5);
            $start_date = sanitize_text_field($_POST['start_date'] ?? '');
            $end_date = sanitize_text_field($_POST['end_date'] ?? '');
            
            if (empty($query_text)) {
                wp_send_json_error('No query text provided');
            }
            
            try {
                $lancedb_service = new FUKAMI_LENS_LanceDB_Service();
                
                // Get embedding for query text
                $embedding_result = $lancedb_service->get_embedding($query_text);
                
                if (!$embedding_result['success']) {
                    wp_send_json_error('Failed to get query embedding: ' . $embedding_result['data']);
                }
                
                // Prepare filters
                $filters = [];
                if (!empty($start_date)) {
                    $filters['start_date'] = $start_date;
                }
                if (!empty($end_date)) {
                    $filters['end_date'] = $end_date;
                }
                
                // Search for similar content
                $search_result = $lancedb_service->search_similar(
                    $embedding_result['data']['embedding'],
                    $limit,
                    $filters
                );
                
                if ($search_result['success']) {
                    wp_send_json_success($search_result['data']);
                } else {
                    wp_send_json_error($search_result['data']);
                }
            } catch (Exception $e) {
                wp_send_json_error('Search failed: ' . $e->getMessage());
            }
        }

        /**
         * AJAX handler for getting LanceDB statistics.
         */
        public function fukami_lens_get_lancedb_stats_callback() {
            if (!current_user_can('manage_options')) {
                wp_send_json_error('Permission denied');
            }
            check_ajax_referer('fukami_lens_chunk_posts_nonce');

            try {
                $lancedb_service = new FUKAMI_LENS_LanceDB_Service();
                $result = $lancedb_service->get_stats();
                
                if ($result['success']) {
                    wp_send_json_success($result['data']);
                } else {
                    wp_send_json_error($result['data']);
                }
            } catch (Exception $e) {
                wp_send_json_error('Failed to get stats: ' . $e->getMessage());
            }
        }

        public function fukami_lens_get_posts_count_callback() {
            if (!current_user_can('manage_options')) {
                wp_send_json_error('Permission denied');
            }
            check_ajax_referer('fukami_lens_chunk_posts_nonce');

            $start_date = sanitize_text_field($_POST['start_date'] ?? '');
            $end_date = sanitize_text_field($_POST['end_date'] ?? '');
            
            $query_args = [];
            if (!empty($start_date)) {
                $query_args['start_date'] = $start_date;
            }
            if (!empty($end_date)) {
                $query_args['end_date'] = $end_date;
            }

            try {
                $chunking_service = new FUKAMI_LENS_Chunking_Service();
                $result = $chunking_service->get_posts_count($query_args);
                
                if ($result['success']) {
                    wp_send_json_success($result['data']);
                } else {
                    wp_send_json_error($result['data']);
                }
            } catch (Exception $e) {
                wp_send_json_error('Failed to get posts count: ' . $e->getMessage());
            }
        }

        public function fukami_lens_check_embeddings_batch_callback() {
            if (!current_user_can('manage_options')) {
                wp_send_json_error('Permission denied');
            }
            check_ajax_referer('fukami_lens_chunk_posts_nonce');

            $start_date = sanitize_text_field($_POST['start_date'] ?? '');
            $end_date = sanitize_text_field($_POST['end_date'] ?? '');
            
            try {
                // Set memory limit and timeout
                ini_set('memory_limit', '256M');
                set_time_limit(120); // 2 minutes timeout
                
                // Get posts in the date range with proper date query handling
                $query_args = [
                    'post_status' => 'publish',
                    'orderby' => 'date',
                    'order' => 'DESC',
                    'numberposts' => -1
                ];
                
                // Handle date range filtering properly
                if (!empty($start_date) || !empty($end_date)) {
                    $date_query = [];
                    
                    if (!empty($start_date)) {
                        $date_query['after'] = $start_date;
                    }
                    
                    if (!empty($end_date)) {
                        $date_query['before'] = $end_date;
                    }
                    
                    if (!empty($date_query)) {
                        $date_query['inclusive'] = true;
                        $query_args['date_query'] = $date_query;
                    }
                }
                
                // Get posts with error handling
                $wordpress_posts = get_posts($query_args);
                
                if (is_wp_error($wordpress_posts)) {
                    wp_send_json_error('Failed to retrieve posts: ' . $wordpress_posts->get_error_message());
                }
                
                if (!is_array($wordpress_posts)) {
                    wp_send_json_error('Invalid posts data returned');
                }
                
                // Extract post IDs with validation
                $post_ids = [];
                foreach ($wordpress_posts as $post) {
                    if (is_object($post) && isset($post->ID) && is_numeric($post->ID)) {
                        $post_ids[] = intval($post->ID);
                    }
                }
                
                if (empty($post_ids)) {
                    wp_send_json_success([
                        'existing_count' => 0,
                        'missing_count' => 0,
                        'missing_posts' => []
                    ]);
                }
                
                // Limit the number of posts to check to prevent memory issues
                if (count($post_ids) > 1000) {
                    $post_ids = array_slice($post_ids, 0, 1000);
                    $wordpress_posts = array_slice($wordpress_posts, 0, 1000);
                }
                
                // Check which posts already have embeddings
                $lancedb_service = new FUKAMI_LENS_LanceDB_Service();
                
                if (!$lancedb_service) {
                    wp_send_json_error('Failed to initialize LanceDB service');
                }
                
                $check_result = $lancedb_service->check_existing_embeddings($post_ids);
                
                if (!$check_result['success']) {
                    wp_send_json_error('Failed to check existing embeddings: ' . $check_result['data']);
                }
                
                if (!isset($check_result['data']['existing_ids']) || !isset($check_result['data']['missing_ids'])) {
                    wp_send_json_error('Invalid response from embedding check');
                }
                
                $existing_ids = $check_result['data']['existing_ids'];
                $missing_ids = $check_result['data']['missing_ids'];
                
                // Validate that existing_ids and missing_ids are arrays
                if (!is_array($existing_ids) || !is_array($missing_ids)) {
                    wp_send_json_error('Invalid data structure returned from embedding check');
                }
                
                // Get missing posts data with error handling
                $missing_posts = [];
                foreach ($wordpress_posts as $post) {
                    if (!is_object($post) || !isset($post->ID)) {
                        continue;
                    }
                    
                    if (in_array($post->ID, $missing_ids)) {
                        try {
                            $categories = get_the_category($post->ID);
                            $category_names = is_array($categories) ? array_map(function($cat) { 
                                return is_object($cat) && isset($cat->name) ? $cat->name : ''; 
                            }, $categories) : [];
                            
                            $tags = get_the_tags($post->ID);
                            $tag_names = [];
                            if (is_array($tags)) {
                                $tag_names = array_map(function($tag) { 
                                    return is_object($tag) && isset($tag->name) ? $tag->name : ''; 
                                }, $tags);
                            }
                            
                            $missing_posts[] = [
                                'id' => intval($post->ID),
                                'title' => sanitize_text_field($post->post_title ?? ''),
                                'content' => wp_strip_all_tags($post->post_content ?? ''),
                                'date' => sanitize_text_field($post->post_date ?? ''),
                                'permalink' => esc_url_raw(get_permalink($post->ID) ?: ''),
                                'categories' => array_filter($category_names),
                                'tags' => array_filter($tag_names)
                            ];
                        } catch (Exception $e) {
                            // Skip this post if there's an error processing it
                            continue;
                        }
                    }
                }
                
                wp_send_json_success([
                    'existing_count' => count($existing_ids),
                    'missing_count' => count($missing_ids),
                    'missing_posts' => $missing_posts
                ]);
                
            } catch (Exception $e) {
                wp_send_json_error('Failed to check embeddings batch: ' . $e->getMessage());
            }
        }

        public function fukami_lens_store_embeddings_batch_callback() {
            if (!current_user_can('manage_options')) {
                wp_send_json_error('Permission denied');
            }
            check_ajax_referer('fukami_lens_chunk_posts_nonce');

            $posts_data = $_POST['posts'] ?? [];
            
            if (empty($posts_data)) {
                wp_send_json_error('No posts data provided');
            }
            
            try {
                $lancedb_service = new FUKAMI_LENS_LanceDB_Service();
                
                // Store embeddings for the batch
                $result = $lancedb_service->store_embeddings_with_check($posts_data);
                
                if ($result['success']) {
                    // Extract the number of stored embeddings from the result message
                    $stored_count = 0;
                    if (preg_match('/stored (\d+) new embeddings/', $result['data'], $matches)) {
                        $stored_count = intval($matches[1]);
                    }
                    
                    wp_send_json_success([
                        'stored_count' => $stored_count,
                        'message' => $result['data']
                    ]);
                } else {
                    wp_send_json_error($result['data']);
                }
                
            } catch (Exception $e) {
                wp_send_json_error('Failed to store embeddings batch: ' . $e->getMessage());
            }
        }
    }
}

// Instantiate only once
if ( ! isset( $GLOBALS['fukami_lens_core_instance'] ) ) {
    $GLOBALS['fukami_lens_core_instance'] = new FUKAMI_LENS_Core();
}
