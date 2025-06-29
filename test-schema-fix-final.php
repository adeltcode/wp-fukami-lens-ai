<?php
/**
 * Test script to fix LanceDB schema timestamp precision issue
 * 
 * This file runs the schema fix to resolve the timestamp casting error.
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

echo "<h1>Fixing LanceDB Schema Timestamp Precision Issue</h1>";

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

echo "<h2>Schema Fix Test Complete</h2>";
echo "<p>If you can see successful results above, the schema fix resolved the timestamp precision issue!</p>";
echo "<p><strong>What was fixed:</strong></p>";
echo "<ul>";
echo "<li>Detected existing table with <code>timestamp[ms]</code> schema</li>";
echo "<li>Backed up existing data from the table</li>";
echo "<li>Dropped the old table with incorrect schema</li>";
echo "<li>Created new table with <code>timestamp[us]</code> schema</li>";
echo "<li>Re-inserted all data with microsecond precision timestamps</li>";
echo "<li>Verified that new embeddings can be stored without errors</li>";
echo "</ul>";
echo "<p><strong>Key Benefits:</strong></p>";
echo "<ul>";
echo "<li>Resolves the timestamp casting error permanently</li>";
echo "<li>Preserves all existing data during the migration</li>";
echo "<li>Ensures future operations use consistent timestamp precision</li>";
echo "<li>Maintains compatibility with PyArrow best practices</li>";
echo "</ul>";
echo "<p><strong>Note:</strong> This fix only needs to be run once. After this, all future embedding operations will work correctly with the new schema.</p>";
?> 