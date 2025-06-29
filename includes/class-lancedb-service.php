<?php
/**
 * LanceDB Service for WP Fukami Lens AI
 *
 * @package FukamiLensAI
 */
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'FUKAMI_LENS_LanceDB_Service' ) ) {
    /**
     * Class FUKAMI_LENS_LanceDB_Service
     * Handles LanceDB vector database operations for storing and retrieving embeddings.
     */
    class FUKAMI_LENS_LanceDB_Service {
        
        private $db_path;
        private $table_name;
        private $python_script_path;
        
        /**
         * Constructor
         */
        public function __construct() {
            try {
                $this->db_path = plugin_dir_path(__FILE__) . '../data/lancedb';
                $this->table_name = 'wordpress_posts';
                $this->python_script_path = plugin_dir_path(__FILE__) . '../python/lancedb_operations.py';
                
                // Validate paths
                if (!is_dir(dirname($this->db_path))) {
                    throw new Exception('Database directory does not exist: ' . dirname($this->db_path));
                }
                
                if (!file_exists($this->python_script_path)) {
                    throw new Exception('Python script not found: ' . $this->python_script_path);
                }
                
                // Ensure data directory exists
                if (!file_exists($this->db_path)) {
                    $created = wp_mkdir_p($this->db_path);
                    if (!$created) {
                        throw new Exception('Failed to create database directory: ' . $this->db_path);
                    }
                }
                
                // Check if Python is available
                $python_check = shell_exec('which python3 2>/dev/null');
                if (empty($python_check)) {
                    throw new Exception('Python3 is not available on the system');
                }
                
            } catch (Exception $e) {
                error_log('FUKAMI_LENS_LanceDB_Service constructor error: ' . $e->getMessage());
                throw $e;
            }
        }
        
        /**
         * Store post embeddings in LanceDB
         *
         * @param array $posts Array of post data with content and metadata
         * @param array $embeddings Array of embeddings corresponding to posts
         * @return array Response with success status and data
         */
        public function store_embeddings($posts, $embeddings) {
            try {
                // Prepare data for Python processing
                $data = [
                    'posts' => $posts,
                    'embeddings' => $embeddings,
                    'db_path' => $this->db_path,
                    'table_name' => $this->table_name
                ];
                
                // Create temporary JSON file
                $tmpfile = tempnam(sys_get_temp_dir(), 'fukami_lens_lancedb_');
                file_put_contents($tmpfile, json_encode($data));
                
                // Run Python script to store embeddings
                $cmd = escapeshellcmd('/usr/bin/python3') . ' ' . 
                       escapeshellarg($this->python_script_path) . ' ' . 
                       escapeshellarg($tmpfile) . ' store 2>&1';
                
                $output = shell_exec($cmd);
                
                // Clean up
                unlink($tmpfile);
                
                // Parse output
                $result = json_decode($output, true);
                
                if ($result && isset($result['success'])) {
                    return $result;
                } else {
                    return [
                        'success' => false,
                        'data' => 'Failed to store embeddings: ' . $output
                    ];
                }
                
            } catch (Exception $e) {
                return [
                    'success' => false,
                    'data' => 'Exception: ' . $e->getMessage()
                ];
            }
        }
        
        /**
         * Search for similar content using embeddings
         *
         * @param array $query_embedding Query embedding vector
         * @param int $limit Number of results to return
         * @param array $filters Optional filters (date range, categories, etc.)
         * @return array Response with success status and similar posts
         */
        public function search_similar($query_embedding, $limit = 5, $filters = []) {
            try {
                // Prepare search data
                $search_data = [
                    'query_embedding' => $query_embedding,
                    'limit' => $limit,
                    'filters' => $filters,
                    'db_path' => $this->db_path,
                    'table_name' => $this->table_name
                ];
                
                // Create temporary JSON file
                $tmpfile = tempnam(sys_get_temp_dir(), 'fukami_lens_lancedb_search_');
                file_put_contents($tmpfile, json_encode($search_data));
                
                // Run Python script to search
                $cmd = escapeshellcmd('/usr/bin/python3') . ' ' . 
                       escapeshellarg($this->python_script_path) . ' ' . 
                       escapeshellarg($tmpfile) . ' search 2>&1';
                
                $output = shell_exec($cmd);
                
                // Clean up
                unlink($tmpfile);
                
                // Parse output
                $result = json_decode($output, true);
                
                if ($result && isset($result['success'])) {
                    return $result;
                } else {
                    return [
                        'success' => false,
                        'data' => 'Failed to search embeddings: ' . $output
                    ];
                }
                
            } catch (Exception $e) {
                return [
                    'success' => false,
                    'data' => 'Exception: ' . $e->getMessage()
                ];
            }
        }
        
        /**
         * Get embedding for text using configured model
         *
         * @param string $text Text to embed
         * @return array Response with success status and embedding
         */
        public function get_embedding($text) {
            try {
                // Get embedding model from settings
                $embedding_model = get_option('fukami_lens_rag_embeddings_model', 'text-embedding-3-small');
                $openai_key = get_option('fukami_lens_openai_api_key', '');
                
                if (!$openai_key) {
                    return [
                        'success' => false,
                        'data' => 'OpenAI API key not configured for embeddings'
                    ];
                }
                
                // Prepare embedding request
                $embedding_data = [
                    'text' => $text,
                    'model' => $embedding_model,
                    'api_key' => $openai_key
                ];
                
                // Create temporary JSON file
                $tmpfile = tempnam(sys_get_temp_dir(), 'fukami_lens_embedding_');
                file_put_contents($tmpfile, json_encode($embedding_data));
                
                // Run Python script to get embedding
                $embedding_script = plugin_dir_path(__FILE__) . '../python/get_embedding.py';
                $cmd = escapeshellcmd('/usr/bin/python3') . ' ' . 
                       escapeshellarg($embedding_script) . ' ' . 
                       escapeshellarg($tmpfile) . ' 2>&1';
                
                $output = shell_exec($cmd);
                
                // Clean up
                unlink($tmpfile);
                
                // Parse output
                $result = json_decode($output, true);
                
                if ($result && isset($result['success'])) {
                    return $result;
                } else {
                    return [
                        'success' => false,
                        'data' => 'Failed to get embedding: ' . $output
                    ];
                }
                
            } catch (Exception $e) {
                return [
                    'success' => false,
                    'data' => 'Exception: ' . $e->getMessage()
                ];
            }
        }
        
        /**
         * Update embeddings for existing posts
         *
         * @param array $post_ids Array of post IDs to update
         * @return array Response with success status and data
         */
        public function update_embeddings($post_ids) {
            try {
                // Get posts data
                $posts = [];
                foreach ($post_ids as $post_id) {
                    $post = get_post($post_id);
                    if ($post && $post->post_status === 'publish') {
                        $posts[] = [
                            'id' => $post->ID,
                            'title' => $post->post_title,
                            'content' => wp_strip_all_tags($post->post_content),
                            'date' => $post->post_date,
                            'permalink' => get_permalink($post->ID),
                            'categories' => wp_get_post_categories($post->ID, ['fields' => 'names']),
                            'tags' => wp_get_post_tags($post->ID, ['fields' => 'names'])
                        ];
                    }
                }
                
                if (empty($posts)) {
                    return [
                        'success' => false,
                        'data' => 'No valid posts found to update'
                    ];
                }
                
                // Use the new method that checks for existing embeddings
                return $this->store_embeddings_with_check($posts);
                
            } catch (Exception $e) {
                return [
                    'success' => false,
                    'data' => 'Exception: ' . $e->getMessage()
                ];
            }
        }
        
        /**
         * Get database statistics
         *
         * @return array Response with success status and database info
         */
        public function get_stats() {
            try {
                $stats_data = [
                    'db_path' => $this->db_path,
                    'table_name' => $this->table_name
                ];
                
                // Create temporary JSON file
                $tmpfile = tempnam(sys_get_temp_dir(), 'fukami_lens_lancedb_stats_');
                file_put_contents($tmpfile, json_encode($stats_data));
                
                // Run Python script to get stats
                $cmd = escapeshellcmd('/usr/bin/python3') . ' ' . 
                       escapeshellarg($this->python_script_path) . ' ' . 
                       escapeshellarg($tmpfile) . ' stats 2>&1';
                
                $output = shell_exec($cmd);
                
                // Clean up
                unlink($tmpfile);
                
                // Parse output
                $result = json_decode($output, true);
                
                if ($result && isset($result['success'])) {
                    return $result;
                } else {
                    return [
                        'success' => false,
                        'data' => 'Failed to get stats: ' . $output
                    ];
                }
                
            } catch (Exception $e) {
                return [
                    'success' => false,
                    'data' => 'Exception: ' . $e->getMessage()
                ];
            }
        }
        
        /**
         * Check which post IDs already have embeddings in the database
         *
         * @param array $post_ids Array of post IDs to check
         * @return array Response with existing and missing post IDs
         */
        public function check_existing_embeddings($post_ids) {
            try {
                // Validate input
                if (!is_array($post_ids)) {
                    return [
                        'success' => false,
                        'data' => 'Post IDs must be an array'
                    ];
                }
                
                if (empty($post_ids)) {
                    return [
                        'success' => true,
                        'data' => [
                            'existing_ids' => [],
                            'missing_ids' => []
                        ]
                    ];
                }
                
                // Validate post IDs are integers
                $valid_post_ids = [];
                foreach ($post_ids as $id) {
                    if (is_numeric($id) && $id > 0) {
                        $valid_post_ids[] = intval($id);
                    }
                }
                
                if (empty($valid_post_ids)) {
                    return [
                        'success' => false,
                        'data' => 'No valid post IDs provided'
                    ];
                }
                
                // Limit the number of IDs to prevent memory issues
                if (count($valid_post_ids) > 1000) {
                    $valid_post_ids = array_slice($valid_post_ids, 0, 1000);
                }
                
                $check_data = [
                    'post_ids' => $valid_post_ids,
                    'db_path' => $this->db_path,
                    'table_name' => $this->table_name
                ];
                
                // Create temporary JSON file
                $tmpfile = tempnam(sys_get_temp_dir(), 'fukami_lens_lancedb_check_');
                if (!$tmpfile) {
                    return [
                        'success' => false,
                        'data' => 'Failed to create temporary file'
                    ];
                }
                
                $json_data = json_encode($check_data);
                if ($json_data === false) {
                    unlink($tmpfile);
                    return [
                        'success' => false,
                        'data' => 'Failed to encode data to JSON'
                    ];
                }
                
                $written = file_put_contents($tmpfile, $json_data);
                if ($written === false) {
                    unlink($tmpfile);
                    return [
                        'success' => false,
                        'data' => 'Failed to write temporary file'
                    ];
                }
                
                // Run Python script to check existing embeddings with timeout
                $cmd = escapeshellcmd('/usr/bin/python3') . ' ' . 
                       escapeshellarg($this->python_script_path) . ' ' . 
                       escapeshellarg($tmpfile) . ' check_existing_embeddings 2>&1';
                
                // Use timeout command to prevent hanging
                $cmd = 'timeout 60 ' . $cmd;
                
                $output = shell_exec($cmd);
                
                // Clean up
                if (file_exists($tmpfile)) {
                    unlink($tmpfile);
                }
                
                // Check if timeout occurred
                if ($output === null) {
                    return [
                        'success' => false,
                        'data' => 'Operation timed out after 60 seconds'
                    ];
                }
                
                // Check for shell execution errors
                if (empty($output)) {
                    return [
                        'success' => false,
                        'data' => 'No output from Python script'
                    ];
                }
                
                // Parse output
                $result = json_decode($output, true);
                
                if ($result === null) {
                    return [
                        'success' => false,
                        'data' => 'Failed to parse JSON output: ' . substr($output, 0, 200)
                    ];
                }
                
                if (!isset($result['success'])) {
                    return [
                        'success' => false,
                        'data' => 'Invalid response format from Python script'
                    ];
                }
                
                if (!$result['success']) {
                    return [
                        'success' => false,
                        'data' => 'Python script error: ' . ($result['data'] ?? 'Unknown error')
                    ];
                }
                
                // Validate response data structure
                if (!isset($result['data']['existing_ids']) || !isset($result['data']['missing_ids'])) {
                    return [
                        'success' => false,
                        'data' => 'Invalid data structure in Python script response'
                    ];
                }
                
                return $result;
                
            } catch (Exception $e) {
                // Clean up temp file if it exists
                if (isset($tmpfile) && file_exists($tmpfile)) {
                    unlink($tmpfile);
                }
                
                return [
                    'success' => false,
                    'data' => 'Exception: ' . $e->getMessage()
                ];
            }
        }
        
        /**
         * Get embeddings for specific post IDs
         *
         * @param array $post_ids Array of post IDs to retrieve
         * @return array Response with embeddings for the specified posts
         */
        public function get_embeddings_by_ids($post_ids) {
            try {
                $get_data = [
                    'post_ids' => $post_ids,
                    'db_path' => $this->db_path,
                    'table_name' => $this->table_name
                ];
                
                // Create temporary JSON file
                $tmpfile = tempnam(sys_get_temp_dir(), 'fukami_lens_lancedb_get_');
                file_put_contents($tmpfile, json_encode($get_data));
                
                // Run Python script to get embeddings by IDs
                $cmd = escapeshellcmd('/usr/bin/python3') . ' ' . 
                       escapeshellarg($this->python_script_path) . ' ' . 
                       escapeshellarg($tmpfile) . ' get_embeddings_by_ids 2>&1';
                
                $output = shell_exec($cmd);
                
                // Clean up
                unlink($tmpfile);
                
                // Parse output
                $result = json_decode($output, true);
                
                if ($result && isset($result['success'])) {
                    return $result;
                } else {
                    return [
                        'success' => false,
                        'data' => 'Failed to get embeddings by IDs: ' . $output
                    ];
                }
                
            } catch (Exception $e) {
                return [
                    'success' => false,
                    'data' => 'Exception: ' . $e->getMessage()
                ];
            }
        }
        
        /**
         * Upsert embeddings (insert new, update existing)
         *
         * @param array $posts Array of post data
         * @param array $embeddings Array of embeddings
         * @return array Response with success status and data
         */
        public function upsert_embeddings($posts, $embeddings) {
            try {
                $upsert_data = [
                    'posts' => $posts,
                    'embeddings' => $embeddings,
                    'db_path' => $this->db_path,
                    'table_name' => $this->table_name
                ];
                
                // Create temporary JSON file
                $tmpfile = tempnam(sys_get_temp_dir(), 'fukami_lens_lancedb_upsert_');
                file_put_contents($tmpfile, json_encode($upsert_data));
                
                // Run Python script to upsert embeddings
                $cmd = escapeshellcmd('/usr/bin/python3') . ' ' . 
                       escapeshellarg($this->python_script_path) . ' ' . 
                       escapeshellarg($tmpfile) . ' upsert_embeddings 2>&1';
                
                $output = shell_exec($cmd);
                
                // Clean up
                unlink($tmpfile);
                
                // Parse output
                $result = json_decode($output, true);
                
                if ($result && isset($result['success'])) {
                    return $result;
                } else {
                    return [
                        'success' => false,
                        'data' => 'Failed to upsert embeddings: ' . $output
                    ];
                }
                
            } catch (Exception $e) {
                return [
                    'success' => false,
                    'data' => 'Exception: ' . $e->getMessage()
                ];
            }
        }
        
        /**
         * Store embeddings with duplicate checking to avoid unnecessary API calls
         *
         * @param array $posts Array of post data
         * @return array Response with success status and data
         */
        public function store_embeddings_with_check($posts) {
            try {
                // Extract post IDs
                $post_ids = array_column($posts, 'id');
                
                // Check which posts already have embeddings
                $check_result = $this->check_existing_embeddings($post_ids);
                
                if (!$check_result['success']) {
                    return $check_result;
                }
                
                $existing_ids = $check_result['data']['existing_ids'];
                $missing_ids = $check_result['data']['missing_ids'];
                
                // Filter posts that need new embeddings
                $posts_to_embed = array_filter($posts, function($post) use ($missing_ids) {
                    return in_array($post['id'], $missing_ids);
                });
                
                $posts_to_embed = array_values($posts_to_embed); // Re-index array
                
                $result_message = "Found {$existing_ids} existing embeddings, ";
                
                if (empty($posts_to_embed)) {
                    $result_message .= "no new embeddings needed.";
                    return [
                        'success' => true,
                        'data' => $result_message
                    ];
                }
                
                // Get embeddings for posts that need them
                $embeddings = [];
                foreach ($posts_to_embed as $post) {
                    $embedding_result = $this->get_embedding($post['title'] . ' ' . $post['content']);
                    if ($embedding_result['success']) {
                        $embeddings[] = $embedding_result['data']['embedding'];
                    } else {
                        return $embedding_result; // Return error if embedding fails
                    }
                }
                
                // Store new embeddings
                $store_result = $this->upsert_embeddings($posts_to_embed, $embeddings);
                
                if ($store_result['success']) {
                    $result_message .= "stored " . count($posts_to_embed) . " new embeddings.";
                    return [
                        'success' => true,
                        'data' => $result_message
                    ];
                } else {
                    return $store_result;
                }
                
            } catch (Exception $e) {
                return [
                    'success' => false,
                    'data' => 'Exception: ' . $e->getMessage()
                ];
            }
        }
    }
} 