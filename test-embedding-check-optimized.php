<?php
/**
 * Test script for optimized embedding check
 * 
 * This script tests the optimized check_existing_embeddings method
 * to ensure it doesn't hang and works correctly.
 */

// Find WordPress
$wp_load_paths = [
    __DIR__ . '/wp-load.php',
    __DIR__ . '/../../../../wp-load.php',
    __DIR__ . '/../../../wp-load.php',
    __DIR__ . '/../../wp-load.php'
];

$wp_loaded = false;
foreach ($wp_load_paths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $wp_loaded = true;
        break;
    }
}

if (!$wp_loaded) {
    die("Could not find wp-load.php. Please run this script from the plugin directory.\n");
}

echo "=== WP Fukami Lens AI - Optimized Embedding Check Test ===\n\n";

try {
    // Test 1: Check if LanceDB service can be instantiated
    echo "Test 1: Instantiating LanceDB service...\n";
    $lancedb_service = new FUKAMI_LENS_LanceDB_Service();
    echo "✓ LanceDB service instantiated successfully\n\n";
    
    // Test 2: Get database stats
    echo "Test 2: Getting database statistics...\n";
    $stats_result = $lancedb_service->get_stats();
    if ($stats_result['success']) {
        $stats = $stats_result['data'];
        echo "✓ Database stats retrieved successfully\n";
        echo "  - Table exists: " . ($stats['table_exists'] ? 'Yes' : 'No') . "\n";
        if ($stats['table_exists']) {
            echo "  - Total posts: " . $stats['total_posts'] . "\n";
            echo "  - Database size: " . $stats['db_size_mb'] . " MB\n";
        }
    } else {
        echo "✗ Failed to get stats: " . $stats_result['data'] . "\n";
    }
    echo "\n";
    
    // Test 3: Check existing embeddings with sample post IDs
    echo "Test 3: Testing optimized embedding check...\n";
    $test_post_ids = [1, 2, 3, 999, 1000, 1001]; // Mix of existing and non-existing IDs
    
    echo "  Checking post IDs: " . implode(', ', $test_post_ids) . "\n";
    
    // Set a timeout for this test
    set_time_limit(60);
    $start_time = microtime(true);
    
    $check_result = $lancedb_service->check_existing_embeddings($test_post_ids);
    
    $end_time = microtime(true);
    $duration = $end_time - $start_time;
    
    if ($check_result['success']) {
        $data = $check_result['data'];
        echo "✓ Embedding check completed in " . round($duration, 2) . " seconds\n";
        echo "  - Existing IDs: " . implode(', ', $data['existing_ids']) . "\n";
        echo "  - Missing IDs: " . implode(', ', $data['missing_ids']) . "\n";
        echo "  - Performance: " . (count($test_post_ids) / $duration) . " IDs/second\n";
    } else {
        echo "✗ Embedding check failed: " . $check_result['data'] . "\n";
    }
    echo "\n";
    
    // Test 4: Test with a larger set of IDs (if table exists)
    if ($stats_result['success'] && $stats['table_exists'] && $stats['total_posts'] > 0) {
        echo "Test 4: Testing with larger set of IDs...\n";
        
        // Get some real post IDs from the database
        $real_post_ids = [];
        $posts = get_posts([
            'post_status' => 'publish',
            'numberposts' => 50,
            'orderby' => 'date',
            'order' => 'DESC'
        ]);
        
        foreach ($posts as $post) {
            $real_post_ids[] = $post->ID;
        }
        
        echo "  Checking " . count($real_post_ids) . " real post IDs...\n";
        
        $start_time = microtime(true);
        $check_result = $lancedb_service->check_existing_embeddings($real_post_ids);
        $end_time = microtime(true);
        $duration = $end_time - $start_time;
        
        if ($check_result['success']) {
            $data = $check_result['data'];
            echo "✓ Large embedding check completed in " . round($duration, 2) . " seconds\n";
            echo "  - Existing IDs: " . count($data['existing_ids']) . "\n";
            echo "  - Missing IDs: " . count($data['missing_ids']) . "\n";
            echo "  - Performance: " . (count($real_post_ids) / $duration) . " IDs/second\n";
        } else {
            echo "✗ Large embedding check failed: " . $check_result['data'] . "\n";
        }
    }
    
    echo "\n=== Test completed successfully ===\n";
    echo "The optimized embedding check should now work without hanging.\n";
    
} catch (Exception $e) {
    echo "✗ Exception occurred: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
} 