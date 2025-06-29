<?php
/**
 * Test script to verify embedding duplicate checking functionality
 * 
 * This file tests the new functionality that checks for existing embeddings
 * before making API calls to avoid unnecessary costs.
 */

// Load WordPress if running standalone
if (!defined('ABSPATH')) {
    // Try multiple possible paths for wp-load.php
    $possible_paths = [
        dirname(__DIR__, 4) . '/wp-load.php',  // Standard WordPress structure
        dirname(__DIR__, 3) . '/wp-load.php',  // Alternative structure
        dirname(__DIR__, 5) . '/wp-load.php',  // Another alternative
        '/var/www/wp-load.php',               // Docker container path
        '/var/www/html/wp-load.php',          // Alternative Docker path
    ];
    
    $wp_loaded = false;
    foreach ($possible_paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            $wp_loaded = true;
            break;
        }
    }
    
    if (!$wp_loaded) {
        die('Could not find wp-load.php. Please ensure this script is run from within WordPress.');
    }
}

echo "<h1>Testing Embedding Duplicate Checking</h1>";

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

echo "<h2>Embedding Duplicate Check Test Complete</h2>";
echo "<p>If you can see successful results above, the duplicate checking is working correctly!</p>";
echo "<p><strong>Key Benefits:</strong></p>";
echo "<ul>";
echo "<li>Checks for existing embeddings before making API calls</li>";
echo "<li>Saves money by avoiding duplicate OpenAI API requests</li>";
echo "<li>Uses upsert operations for efficient database updates</li>";
echo "<li>Provides detailed feedback on what was processed</li>";
echo "</ul>";
?> 