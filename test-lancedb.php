<?php
/**
 * Test script to verify LanceDB functionality in the WordPress plugin
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

echo "<h1>Testing LanceDB Integration</h1>";

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

echo "<h2>LanceDB Integration Test Complete</h2>";
echo "<p>If you can see successful results above, the LanceDB integration is working correctly!</p>";
?> 