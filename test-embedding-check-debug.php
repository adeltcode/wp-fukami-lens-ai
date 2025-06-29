<?php
/**
 * Debug test script for embedding check functionality
 * 
 * This script tests the embedding check functionality step by step
 * to identify where the Internal Server Error is occurring.
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

echo "=== WP Fukami Lens AI - Embedding Check Debug Test ===\n\n";

try {
    echo "Step 1: Testing LanceDB Service Constructor\n";
    echo "----------------------------------------\n";
    
    $lancedb_service = new FUKAMI_LENS_LanceDB_Service();
    echo "✓ LanceDB Service created successfully\n";
    
    echo "\nStep 2: Testing Database Path Validation\n";
    echo "--------------------------------------\n";
    
    $db_path = plugin_dir_path(__FILE__) . 'data/lancedb';
    echo "Database path: " . $db_path . "\n";
    echo "Path exists: " . (file_exists($db_path) ? 'Yes' : 'No') . "\n";
    echo "Path is writable: " . (is_writable($db_path) ? 'Yes' : 'No') . "\n";
    
    echo "\nStep 3: Testing Python Script Path\n";
    echo "--------------------------------\n";
    
    $python_script = plugin_dir_path(__FILE__) . 'python/lancedb_operations.py';
    echo "Python script path: " . $python_script . "\n";
    echo "Script exists: " . (file_exists($python_script) ? 'Yes' : 'No') . "\n";
    echo "Script is readable: " . (is_readable($python_script) ? 'Yes' : 'No') . "\n";
    
    echo "\nStep 4: Testing Python Availability\n";
    echo "--------------------------------\n";
    
    $python_check = shell_exec('which python3 2>/dev/null');
    echo "Python3 path: " . trim($python_check) . "\n";
    
    $python_version = shell_exec('python3 --version 2>&1');
    echo "Python3 version: " . trim($python_version) . "\n";
    
    echo "\nStep 5: Testing Temporary File Creation\n";
    echo "-------------------------------------\n";
    
    $tmpfile = tempnam(sys_get_temp_dir(), 'fukami_lens_test_');
    if ($tmpfile) {
        echo "✓ Temporary file created: " . $tmpfile . "\n";
        
        $test_data = ['test' => 'data'];
        $json_data = json_encode($test_data);
        $written = file_put_contents($tmpfile, $json_data);
        
        if ($written !== false) {
            echo "✓ JSON data written successfully\n";
        } else {
            echo "✗ Failed to write JSON data\n";
        }
        
        unlink($tmpfile);
        echo "✓ Temporary file cleaned up\n";
    } else {
        echo "✗ Failed to create temporary file\n";
    }
    
    echo "\nStep 6: Testing Post Retrieval\n";
    echo "----------------------------\n";
    
    $query_args = [
        'post_status' => 'publish',
        'orderby' => 'date',
        'order' => 'DESC',
        'numberposts' => 5 // Limit to 5 posts for testing
    ];
    
    $posts = get_posts($query_args);
    echo "Posts found: " . count($posts) . "\n";
    
    if (!empty($posts)) {
        $post_ids = array_map(function($post) { return $post->ID; }, $posts);
        echo "Post IDs: " . implode(', ', $post_ids) . "\n";
        
        echo "\nStep 7: Testing Embedding Check with Sample Data\n";
        echo "----------------------------------------------\n";
        
        // Test with just the first post ID
        $test_post_ids = [$post_ids[0]];
        echo "Testing with post ID: " . $test_post_ids[0] . "\n";
        
        $check_result = $lancedb_service->check_existing_embeddings($test_post_ids);
        
        if ($check_result['success']) {
            echo "✓ Embedding check successful\n";
            echo "Existing IDs: " . implode(', ', $check_result['data']['existing_ids']) . "\n";
            echo "Missing IDs: " . implode(', ', $check_result['data']['missing_ids']) . "\n";
        } else {
            echo "✗ Embedding check failed: " . $check_result['data'] . "\n";
        }
        
    } else {
        echo "✗ No posts found to test with\n";
    }
    
    echo "\nStep 8: Testing Python Script Directly\n";
    echo "-----------------------------------\n";
    
    $test_data = [
        'post_ids' => [1, 2, 3],
        'db_path' => $db_path,
        'table_name' => 'wordpress_posts'
    ];
    
    $tmpfile = tempnam(sys_get_temp_dir(), 'fukami_lens_python_test_');
    file_put_contents($tmpfile, json_encode($test_data));
    
    $cmd = escapeshellcmd('/usr/bin/python3') . ' ' . 
           escapeshellarg($python_script) . ' ' . 
           escapeshellarg($tmpfile) . ' check_existing_embeddings 2>&1';
    
    echo "Running command: " . $cmd . "\n";
    
    $output = shell_exec($cmd);
    echo "Python output:\n" . $output . "\n";
    
    unlink($tmpfile);
    
    echo "\nStep 9: Testing Memory and Time Limits\n";
    echo "------------------------------------\n";
    
    echo "Current memory limit: " . ini_get('memory_limit') . "\n";
    echo "Current max execution time: " . ini_get('max_execution_time') . "\n";
    echo "Current time limit: " . get_cfg_var('max_execution_time') . "\n";
    
    echo "\n=== Debug Test Complete ===\n";
    
} catch (Exception $e) {
    echo "✗ Exception occurred: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
} 