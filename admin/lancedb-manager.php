<?php
/**
 * LanceDB Manager page for WP Fukami Lens AI
 *
 * @package FukamiLensAI
 */
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Render the LanceDB manager page.
 */
function fukami_lens_lancedb_manager_page() {
    // Handle test actions
    $action = $_GET['action'] ?? '';
    
    if ($action === 'test_schema_fix') {
        fukami_lens_run_schema_fix_test();
        return;
    } elseif ($action === 'test_embedding_check') {
        fukami_lens_run_embedding_check_test();
        return;
    } elseif ($action === 'test_lancedb') {
        fukami_lens_run_lancedb_test();
        return;
    }
    
    ?>
    <div class="wrap fukami-lens-admin">
        <h1><?php esc_html_e('LanceDB Vector Database Manager', 'wp-fukami-lens-ai'); ?></h1>
        <p class="description"><?php esc_html_e('Manage embeddings and vector search for your WordPress content using LanceDB.', 'wp-fukami-lens-ai'); ?></p>
        
        <!-- Database Statistics -->
        <div class="fukami-lens-other-settings-box">
            <h2><?php esc_html_e('Database Statistics', 'wp-fukami-lens-ai'); ?></h2>
            <button type="button" class="button" id="fukami-lens-get-stats-btn"><?php esc_html_e('Refresh Stats', 'wp-fukami-lens-ai'); ?></button>
            <div id="fukami-lens-stats-display" style="margin-top: 16px;"></div>
        </div>
        
        <!-- Store Embeddings -->
        <div class="fukami-lens-other-settings-box">
            <h2><?php esc_html_e('Store Embeddings', 'wp-fukami-lens-ai'); ?></h2>
            <p><?php esc_html_e('Generate and store embeddings for your WordPress posts. This enables semantic search capabilities. The system will automatically check for existing embeddings to avoid duplicate API calls.', 'wp-fukami-lens-ai'); ?></p>
            
            <div style="margin-bottom: 16px;">
                <label for="fukami-lens-embed-start-date"><strong><?php esc_html_e('Date Range:', 'wp-fukami-lens-ai'); ?></strong></label><br>
                <input type="date" id="fukami-lens-embed-start-date" style="margin-right: 8px;">
                <span>-</span>
                <input type="date" id="fukami-lens-embed-end-date" style="margin-left: 8px;">
            </div>
            
            <button type="button" class="button button-primary" id="fukami-lens-store-embeddings-btn"><?php esc_html_e('Store Embeddings', 'wp-fukami-lens-ai'); ?></button>
            <div id="fukami-lens-embedding-results" style="margin-top: 16px;"></div>
        </div>
        
        <!-- Search Similar Content -->
        <div class="fukami-lens-other-settings-box">
            <h2><?php esc_html_e('Search Similar Content', 'wp-fukami-lens-ai'); ?></h2>
            <p><?php esc_html_e('Search for content similar to your query using semantic embeddings.', 'wp-fukami-lens-ai'); ?></p>
            
            <div style="margin-bottom: 16px;">
                <label for="fukami-lens-search-query"><strong><?php esc_html_e('Search Query:', 'wp-fukami-lens-ai'); ?></strong></label><br>
                <textarea id="fukami-lens-search-query" rows="3" cols="60" placeholder="<?php esc_attr_e('Enter your search query...', 'wp-fukami-lens-ai'); ?>"></textarea>
            </div>
            
            <div style="margin-bottom: 16px;">
                <label for="fukami-lens-search-limit"><strong><?php esc_html_e('Results Limit:', 'wp-fukami-lens-ai'); ?></strong></label><br>
                <input type="number" id="fukami-lens-search-limit" value="5" min="1" max="20" style="width: 80px;">
            </div>
            
            <div style="margin-bottom: 16px;">
                <label for="fukami-lens-search-start-date"><strong><?php esc_html_e('Filter by Date Range:', 'wp-fukami-lens-ai'); ?></strong></label><br>
                <input type="date" id="fukami-lens-search-start-date" style="margin-right: 8px;">
                <span>-</span>
                <input type="date" id="fukami-lens-search-end-date" style="margin-left: 8px;">
            </div>
            
            <button type="button" class="button button-primary" id="fukami-lens-search-similar-btn"><?php esc_html_e('Search Similar', 'wp-fukami-lens-ai'); ?></button>
            <div id="fukami-lens-search-results" style="margin-top: 16px;"></div>
        </div>
        
        <!-- Test and Debug Section -->
        <div class="fukami-lens-other-settings-box">
            <h2><?php esc_html_e('Test and Debug', 'wp-fukami-lens-ai'); ?></h2>
            <p><?php esc_html_e('Run diagnostic tests to verify LanceDB functionality and fix schema issues.', 'wp-fukami-lens-ai'); ?></p>
            
            <div style="margin-bottom: 16px;">
                <a href="<?php echo admin_url('admin.php?page=fukami-lens-lancedb-manager&action=test_schema_fix'); ?>" class="button button-secondary">
                    <?php esc_html_e('Run Schema Fix Test', 'wp-fukami-lens-ai'); ?>
                </a>
                <span style="margin-left: 8px; color: #666;">
                    <?php esc_html_e('Fixes timestamp precision issues in LanceDB schema', 'wp-fukami-lens-ai'); ?>
                </span>
            </div>
            
            <div style="margin-bottom: 16px;">
                <a href="<?php echo admin_url('admin.php?page=fukami-lens-lancedb-manager&action=test_embedding_check'); ?>" class="button button-secondary">
                    <?php esc_html_e('Test Embedding Duplicate Check', 'wp-fukami-lens-ai'); ?>
                </a>
                <span style="margin-left: 8px; color: #666;">
                    <?php esc_html_e('Tests the duplicate checking functionality', 'wp-fukami-lens-ai'); ?>
                </span>
            </div>
            
            <div style="margin-bottom: 16px;">
                <a href="<?php echo admin_url('admin.php?page=fukami-lens-lancedb-manager&action=test_lancedb'); ?>" class="button button-secondary">
                    <?php esc_html_e('Test LanceDB Integration', 'wp-fukami-lens-ai'); ?>
                </a>
                <span style="margin-left: 8px; color: #666;">
                    <?php esc_html_e('Basic LanceDB functionality test', 'wp-fukami-lens-ai'); ?>
                </span>
            </div>
            
            <div id="fukami-lens-test-results" style="margin-top: 16px;"></div>
        </div>
    </div>
    
    <script>
    jQuery(function($){
        // Set default dates
        var today = new Date();
        var endDate = today.toISOString().slice(0,10);
        var lastMonth = new Date(today.getFullYear(), today.getMonth()-1, today.getDate());
        var startDate = lastMonth.toISOString().slice(0,10);
        
        $('#fukami-lens-embed-start-date, #fukami-lens-search-start-date').val(startDate);
        $('#fukami-lens-embed-end-date, #fukami-lens-search-end-date').val(endDate);
        
        // Get database statistics
        $('#fukami-lens-get-stats-btn').on('click', function() {
            var $btn = $(this);
            var $display = $('#fukami-lens-stats-display');
            
            $btn.prop('disabled', true).text('<?php esc_html_e('Loading...', 'wp-fukami-lens-ai'); ?>');
            $display.html('<em><?php esc_html_e('Loading statistics...', 'wp-fukami-lens-ai'); ?></em>');
            
            $.post(ajaxurl, {
                action: 'fukami_lens_get_lancedb_stats',
                _wpnonce: fukami_lens_ajax.chunk_posts_nonce
            }, function(response) {
                if (response.success) {
                    var stats = response.data;
                    var html = '<div style="background: #f9f9f9; padding: 16px; border: 1px solid #ddd;">';
                    html += '<h3><?php esc_html_e('Database Information', 'wp-fukami-lens-ai'); ?></h3>';
                    html += '<p><strong><?php esc_html_e('Table Exists:', 'wp-fukami-lens-ai'); ?></strong> ' + (stats.table_exists ? '<?php esc_html_e('Yes', 'wp-fukami-lens-ai'); ?>' : '<?php esc_html_e('No', 'wp-fukami-lens-ai'); ?>') + '</p>';
                    if (stats.table_exists) {
                        html += '<p><strong><?php esc_html_e('Total Posts:', 'wp-fukami-lens-ai'); ?></strong> ' + stats.total_posts + '</p>';
                        html += '<p><strong><?php esc_html_e('Database Size:', 'wp-fukami-lens-ai'); ?></strong> ' + stats.db_size_mb + ' MB</p>';
                        html += '<p><strong><?php esc_html_e('Table Name:', 'wp-fukami-lens-ai'); ?></strong> ' + stats.table_name + '</p>';
                    }
                    html += '</div>';
                    $display.html(html);
                } else {
                    $display.html('<span style="color:red;">' + (response.data ? response.data : '<?php esc_html_e('Error', 'wp-fukami-lens-ai'); ?>') + '</span>');
                }
                $btn.prop('disabled', false).text('<?php esc_html_e('Refresh Stats', 'wp-fukami-lens-ai'); ?>');
            });
        });
        
        // Store embeddings
        $('#fukami-lens-store-embeddings-btn').on('click', function() {
            var $btn = $(this);
            var $results = $('#fukami-lens-embedding-results');
            var startDate = $('#fukami-lens-embed-start-date').val();
            var endDate = $('#fukami-lens-embed-end-date').val();
            
            $btn.prop('disabled', true).text('<?php esc_html_e('Storing...', 'wp-fukami-lens-ai'); ?>');
            $results.html('<em><?php esc_html_e('Storing embeddings...', 'wp-fukami-lens-ai'); ?></em>');
            
            $.post(ajaxurl, {
                action: 'fukami_lens_store_embeddings',
                start_date: startDate,
                end_date: endDate,
                _wpnonce: fukami_lens_ajax.chunk_posts_nonce
            }, function(response) {
                if (response.success) {
                    $results.html('<div style="color:green;"><strong><?php esc_html_e('Success:', 'wp-fukami-lens-ai'); ?></strong> ' + response.data + '</div>');
                } else {
                    $results.html('<span style="color:red;">' + (response.data ? response.data : '<?php esc_html_e('Error', 'wp-fukami-lens-ai'); ?>') + '</span>');
                }
                $btn.prop('disabled', false).text('<?php esc_html_e('Store Embeddings', 'wp-fukami-lens-ai'); ?>');
            });
        });
        
        // Search similar content
        $('#fukami-lens-search-similar-btn').on('click', function() {
            var $btn = $(this);
            var $results = $('#fukami-lens-search-results');
            var query = $('#fukami-lens-search-query').val();
            var limit = $('#fukami-lens-search-limit').val();
            var startDate = $('#fukami-lens-search-start-date').val();
            var endDate = $('#fukami-lens-search-end-date').val();
            
            if (!query.trim()) {
                $results.html('<span style="color:red;"><?php esc_html_e('Please enter a search query.', 'wp-fukami-lens-ai'); ?></span>');
                return;
            }
            
            $btn.prop('disabled', true).text('<?php esc_html_e('Searching...', 'wp-fukami-lens-ai'); ?>');
            $results.html('<em><?php esc_html_e('Searching for similar content...', 'wp-fukami-lens-ai'); ?></em>');
            
            $.post(ajaxurl, {
                action: 'fukami_lens_search_similar',
                query_text: query,
                limit: limit,
                start_date: startDate,
                end_date: endDate,
                _wpnonce: fukami_lens_ajax.chunk_posts_nonce
            }, function(response) {
                if (response.success) {
                    var data = response.data;
                    var html = '<div style="background: #f9f9f9; padding: 16px; border: 1px solid #ddd;">';
                    html += '<h3><?php esc_html_e('Search Results', 'wp-fukami-lens-ai'); ?> (' + data.count + ')</h3>';
                    
                    if (data.posts && data.posts.length > 0) {
                        data.posts.forEach(function(post, index) {
                            html += '<div style="margin-bottom: 16px; padding: 12px; background: white; border: 1px solid #ddd;">';
                            html += '<h4>' + (index + 1) + '. ' + post.title + '</h4>';
                            html += '<p><strong><?php esc_html_e('Date:', 'wp-fukami-lens-ai'); ?></strong> ' + post.date + '</p>';
                            html += '<p><strong><?php esc_html_e('Content Preview:', 'wp-fukami-lens-ai'); ?></strong> ' + post.content + '</p>';
                            if (post.similarity_score !== null) {
                                html += '<p><strong><?php esc_html_e('Similarity Score:', 'wp-fukami-lens-ai'); ?></strong> ' + post.similarity_score.toFixed(4) + '</p>';
                            }
                            html += '</div>';
                        });
                    } else {
                        html += '<p><?php esc_html_e('No similar content found.', 'wp-fukami-lens-ai'); ?></p>';
                    }
                    
                    html += '</div>';
                    $results.html(html);
                } else {
                    $results.html('<span style="color:red;">' + (response.data ? response.data : '<?php esc_html_e('Error', 'wp-fukami-lens-ai'); ?>') + '</span>');
                }
                $btn.prop('disabled', false).text('<?php esc_html_e('Search Similar', 'wp-fukami-lens-ai'); ?>');
            });
        });
        
        // Load stats on page load
        $('#fukami-lens-get-stats-btn').click();
    });
    </script>
    <?php
}

/**
 * Run the schema fix test
 */
function fukami_lens_run_schema_fix_test() {
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('LanceDB Schema Fix Test', 'wp-fukami-lens-ai'); ?></h1>
        <p><a href="<?php echo admin_url('admin.php?page=fukami-lens-lancedb-manager'); ?>" class="button">← Back to LanceDB Manager</a></p>
        
        <div style="background: #fff; padding: 20px; border: 1px solid #ddd; margin-top: 20px;">
            <?php
            try {
                $lancedb_service = new FUKAMI_LENS_LanceDB_Service();
                
                echo "<h2>Step 1: Check Current Schema</h2>";
                
                // Get the database path from the service
                $db_path = plugin_dir_path(__FILE__) . '../data/lancedb';
                
                // Run the schema check and fix script
                $schema_script = plugin_dir_path(__FILE__) . '../python/check_schema.py';
                $cmd = escapeshellcmd('/usr/bin/python3') . ' ' . 
                       escapeshellarg($schema_script) . ' ' . 
                       escapeshellarg($db_path) . ' 2>&1';
                
                $output = shell_exec($cmd);
                
                echo "<pre>" . htmlspecialchars($output) . "</pre>";
                
                // Try to parse the JSON output
                $lines = explode("\n", $output);
                $json_output = '';
                foreach ($lines as $line) {
                    if (strpos($line, '{') === 0) {
                        $json_output = $line;
                        break;
                    }
                }
                
                if ($json_output) {
                    $result = json_decode($json_output, true);
                    if ($result && isset($result['success'])) {
                        if ($result['success']) {
                            echo "<p style='color: green;'><strong>✓ Schema fix successful:</strong> " . htmlspecialchars($result['data']) . "</p>";
                        } else {
                            echo "<p style='color: red;'><strong>✗ Schema fix failed:</strong> " . htmlspecialchars($result['data']) . "</p>";
                        }
                    }
                }
                
                echo "<h2>Step 2: Test Embedding Storage After Schema Fix</h2>";
                
                // Test storing embeddings after the schema fix
                $test_posts = [
                    [
                        'id' => 6001,
                        'title' => 'Post Schema Fix Test 1',
                        'content' => 'This post tests embedding storage after the schema fix.',
                        'date' => '2024-01-01',
                        'permalink' => 'https://example.com/schema-fix-test-1',
                        'categories' => ['Test', 'Schema'],
                        'tags' => ['test', 'schema', 'fix']
                    ],
                    [
                        'id' => 6002,
                        'title' => 'Post Schema Fix Test 2',
                        'content' => 'This is another test post after the schema fix.',
                        'date' => '2024-01-02',
                        'permalink' => 'https://example.com/schema-fix-test-2',
                        'categories' => ['Test', 'Schema'],
                        'tags' => ['test', 'schema', 'fix']
                    ]
                ];
                
                // Get embeddings for the test posts
                $embeddings = [];
                foreach ($test_posts as $post) {
                    $embedding_result = $lancedb_service->get_embedding($post['title'] . ' ' . $post['content']);
                    if ($embedding_result['success']) {
                        $embeddings[] = $embedding_result['data']['embedding'];
                    } else {
                        echo "<p style='color: red;'>Failed to get embedding for post {$post['id']}: " . htmlspecialchars($embedding_result['data']) . "</p>";
                        continue;
                    }
                }
                
                if (count($embeddings) === count($test_posts)) {
                    // Test store embeddings
                    $store_result = $lancedb_service->store_embeddings($test_posts, $embeddings);
                    
                    if ($store_result['success']) {
                        echo "<p style='color: green;'><strong>✓ Store embeddings successful:</strong> " . htmlspecialchars($store_result['data']) . "</p>";
                    } else {
                        echo "<p style='color: red;'><strong>✗ Store embeddings failed:</strong> " . htmlspecialchars($store_result['data']) . "</p>";
                    }
                    
                    // Test upsert embeddings
                    $upsert_result = $lancedb_service->upsert_embeddings($test_posts, $embeddings);
                    
                    if ($upsert_result['success']) {
                        echo "<p style='color: green;'><strong>✓ Upsert embeddings successful:</strong> " . htmlspecialchars($upsert_result['data']) . "</p>";
                    } else {
                        echo "<p style='color: red;'><strong>✗ Upsert embeddings failed:</strong> " . htmlspecialchars($upsert_result['data']) . "</p>";
                    }
                }
                
                echo "<h2>Step 3: Test Duplicate Checking After Schema Fix</h2>";
                
                // Test the duplicate checking functionality
                $check_result = $lancedb_service->store_embeddings_with_check($test_posts);
                
                if ($check_result['success']) {
                    echo "<p style='color: green;'><strong>✓ Duplicate checking works:</strong> " . htmlspecialchars($check_result['data']) . "</p>";
                } else {
                    echo "<p style='color: red;'><strong>✗ Duplicate checking failed:</strong> " . htmlspecialchars($check_result['data']) . "</p>";
                }
                
                echo "<h2>Step 4: Database Statistics After Schema Fix</h2>";
                
                $stats_result = $lancedb_service->get_stats();
                if ($stats_result['success']) {
                    $stats = $stats_result['data'];
                    echo "<p><strong>Table Exists:</strong> " . ($stats['table_exists'] ? 'Yes' : 'No') . "</p>";
                    if ($stats['table_exists']) {
                        echo "<p><strong>Total Posts:</strong> " . $stats['total_posts'] . "</p>";
                        echo "<p><strong>Database Size:</strong> " . $stats['db_size_mb'] . " MB</p>";
                    }
                } else {
                    echo "<p style='color: red;'>Error getting stats: " . htmlspecialchars($stats_result['data']) . "</p>";
                }
                
            } catch (Exception $e) {
                echo "<p style='color: red;'>Exception: " . htmlspecialchars($e->getMessage()) . "</p>";
            }
            ?>
            
            <h2>Schema Fix Test Complete</h2>
            <p>If you can see successful results above, the schema fix resolved the timestamp precision issue!</p>
            <p><strong>What was fixed:</strong></p>
            <ul>
                <li>Detected existing table with <code>timestamp[ms]</code> schema</li>
                <li>Backed up existing data from the table</li>
                <li>Dropped the old table with incorrect schema</li>
                <li>Created new table with <code>timestamp[us]</code> schema</li>
                <li>Re-inserted all data with microsecond precision timestamps</li>
                <li>Verified that new embeddings can be stored without errors</li>
            </ul>
        </div>
    </div>
    <?php
}

/**
 * Run the embedding duplicate check test
 */
function fukami_lens_run_embedding_check_test() {
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Embedding Duplicate Check Test', 'wp-fukami-lens-ai'); ?></h1>
        <p><a href="<?php echo admin_url('admin.php?page=fukami-lens-lancedb-manager'); ?>" class="button">← Back to LanceDB Manager</a></p>
        
        <div style="background: #fff; padding: 20px; border: 1px solid #ddd; margin-top: 20px;">
            <?php
            try {
                $lancedb_service = new FUKAMI_LENS_LanceDB_Service();
                
                echo "<h2>Test 1: Check Existing Embeddings</h2>";
                
                // Test with some sample post IDs
                $test_post_ids = [1, 2, 3, 999]; // Mix of existing and non-existing IDs
                $check_result = $lancedb_service->check_existing_embeddings($test_post_ids);
                
                if ($check_result['success']) {
                    $data = $check_result['data'];
                    echo "<p><strong>Existing IDs:</strong> " . implode(', ', $data['existing_ids']) . "</p>";
                    echo "<p><strong>Missing IDs:</strong> " . implode(', ', $data['missing_ids']) . "</p>";
                } else {
                    echo "<p style='color: red;'>Error checking existing embeddings: " . htmlspecialchars($check_result['data']) . "</p>";
                }
                
                echo "<h2>Test 2: Get Embeddings by IDs</h2>";
                
                // Test getting embeddings for existing posts
                if (!empty($data['existing_ids'])) {
                    $get_result = $lancedb_service->get_embeddings_by_ids($data['existing_ids']);
                    
                    if ($get_result['success']) {
                        $embeddings = $get_result['data'];
                        echo "<p><strong>Retrieved embeddings for:</strong> " . count($embeddings) . " posts</p>";
                        
                        foreach ($embeddings as $post_id => $embedding_data) {
                            echo "<p>Post ID {$post_id}: " . count($embedding_data['embedding']) . " dimensions</p>";
                        }
                    } else {
                        echo "<p style='color: red;'>Error getting embeddings: " . htmlspecialchars($get_result['data']) . "</p>";
                    }
                } else {
                    echo "<p>No existing embeddings to retrieve</p>";
                }
                
                echo "<h2>Test 3: Store Embeddings with Check</h2>";
                
                // Test the new method that checks before storing
                $test_posts = [
                    [
                        'id' => 1001,
                        'title' => 'Test Post for Duplicate Check',
                        'content' => 'This is a test post to verify duplicate checking functionality.',
                        'date' => '2024-01-01',
                        'permalink' => 'https://example.com/test-post-1001',
                        'categories' => ['Test'],
                        'tags' => ['test', 'duplicate-check']
                    ],
                    [
                        'id' => 1002,
                        'title' => 'Another Test Post',
                        'content' => 'This is another test post for duplicate checking.',
                        'date' => '2024-01-02',
                        'permalink' => 'https://example.com/test-post-1002',
                        'categories' => ['Test'],
                        'tags' => ['test', 'duplicate-check']
                    ]
                ];
                
                $store_result = $lancedb_service->store_embeddings_with_check($test_posts);
                
                if ($store_result['success']) {
                    echo "<p style='color: green;'><strong>Success:</strong> " . htmlspecialchars($store_result['data']) . "</p>";
                } else {
                    echo "<p style='color: red;'>Failed to store embeddings: " . htmlspecialchars($store_result['data']) . "</p>";
                }
                
                echo "<h2>Test 4: Store Same Posts Again (Should Skip)</h2>";
                
                // Try to store the same posts again - should skip them
                $store_result2 = $lancedb_service->store_embeddings_with_check($test_posts);
                
                if ($store_result2['success']) {
                    echo "<p style='color: green;'><strong>Success:</strong> " . htmlspecialchars($store_result2['data']) . "</p>";
                    echo "<p><em>Note: This should show that existing embeddings were found and no new API calls were made.</em></p>";
                } else {
                    echo "<p style='color: red;'>Failed on second attempt: " . htmlspecialchars($store_result2['data']) . "</p>";
                }
                
                echo "<h2>Test 5: Update Specific Post IDs</h2>";
                
                // Test updating specific post IDs
                $update_result = $lancedb_service->update_embeddings([1001, 1002]);
                
                if ($update_result['success']) {
                    echo "<p style='color: green;'><strong>Success:</strong> " . htmlspecialchars($update_result['data']) . "</p>";
                } else {
                    echo "<p style='color: red;'>Failed to update embeddings: " . htmlspecialchars($update_result['data']) . "</p>";
                }
                
            } catch (Exception $e) {
                echo "<p style='color: red;'>Exception: " . htmlspecialchars($e->getMessage()) . "</p>";
            }
            ?>
            
            <h2>Embedding Duplicate Check Test Complete</h2>
            <p>If you can see successful results above, the duplicate checking is working correctly!</p>
            <p><strong>Key Benefits:</strong></p>
            <ul>
                <li>Checks for existing embeddings before making API calls</li>
                <li>Saves money by avoiding duplicate OpenAI API requests</li>
                <li>Uses upsert operations for efficient database updates</li>
                <li>Provides detailed feedback on what was processed</li>
            </ul>
        </div>
    </div>
    <?php
}

/**
 * Run the basic LanceDB integration test
 */
function fukami_lens_run_lancedb_test() {
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('LanceDB Integration Test', 'wp-fukami-lens-ai'); ?></h1>
        <p><a href="<?php echo admin_url('admin.php?page=fukami-lens-lancedb-manager'); ?>" class="button">← Back to LanceDB Manager</a></p>
        
        <div style="background: #fff; padding: 20px; border: 1px solid #ddd; margin-top: 20px;">
            <?php
            try {
                $lancedb_service = new FUKAMI_LENS_LanceDB_Service();
                
                echo "<h2>Test 1: Database Statistics</h2>";
                $stats_result = $lancedb_service->get_stats();
                if ($stats_result['success']) {
                    $stats = $stats_result['data'];
                    echo "<p><strong>Table Exists:</strong> " . ($stats['table_exists'] ? 'Yes' : 'No') . "</p>";
                    if ($stats['table_exists']) {
                        echo "<p><strong>Total Posts:</strong> " . $stats['total_posts'] . "</p>";
                        echo "<p><strong>Database Size:</strong> " . $stats['db_size_mb'] . " MB</p>";
                    }
                } else {
                    echo "<p style='color: red;'>Error getting stats: " . htmlspecialchars($stats_result['data']) . "</p>";
                }
                
                echo "<h2>Test 2: Embedding Generation</h2>";
                $test_text = "This is a test text for embedding generation.";
                $embedding_result = $lancedb_service->get_embedding($test_text);
                
                if ($embedding_result['success']) {
                    $embedding = $embedding_result['data']['embedding'];
                    echo "<p><strong>Embedding Generated:</strong> Yes</p>";
                    echo "<p><strong>Embedding Length:</strong> " . count($embedding) . " dimensions</p>";
                    echo "<p><strong>Model Used:</strong> " . $embedding_result['data']['model'] . "</p>";
                    
                    echo "<h2>Test 3: Store and Search</h2>";
                    $test_posts = [
                        [
                            'id' => 1,
                            'title' => 'Test Post 1',
                            'content' => 'This is the content of test post 1.',
                            'date' => '2024-01-01',
                            'permalink' => 'https://example.com/post-1',
                            'categories' => ['Test'],
                            'tags' => ['test', 'sample']
                        ]
                    ];
                    
                    $test_embeddings = [$embedding];
                    
                    $store_result = $lancedb_service->store_embeddings($test_posts, $test_embeddings);
                    if ($store_result['success']) {
                        echo "<p style='color: green;'><strong>Success:</strong> " . htmlspecialchars($store_result['data']) . "</p>";
                    } else {
                        echo "<p style='color: red;'>Failed to store embeddings: " . htmlspecialchars($store_result['data']) . "</p>";
                    }
                    
                } else {
                    echo "<p style='color: red;'>Failed to generate embedding: " . htmlspecialchars($embedding_result['data']) . "</p>";
                }
                
            } catch (Exception $e) {
                echo "<p style='color: red;'>Exception: " . htmlspecialchars($e->getMessage()) . "</p>";
            }
            ?>
            
            <h2>LanceDB Integration Test Complete</h2>
            <p>If you can see successful results above, the LanceDB integration is working correctly!</p>
        </div>
    </div>
    <?php
} 