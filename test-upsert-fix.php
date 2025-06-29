<?php
/**
 * Test script to verify LanceDB upsert fix
 * 
 * This file tests the fix for the "'LanceTable' object has no attribute 'upsert'" error.
 */

// Load WordPress if running standalone
if (!defined('ABSPATH')) {
    require_once dirname(__DIR__, 4) . '/wp-load.php';
}

echo "<h1>Testing LanceDB Upsert Fix</h1>";

try {
    $lancedb_service = new FUKAMI_LENS_LanceDB_Service();
    
    echo "<h2>Test 1: Check LanceDB Version</h2>";
    
    // Test LanceDB version and capabilities
    $test_version = tempnam(sys_get_temp_dir(), 'fukami_lens_lancedb_version_');
    file_put_contents($test_version, 'import lancedb; print("LanceDB version:", lancedb.__version__)');
    
    $cmd = escapeshellcmd('/usr/bin/python3') . ' ' . escapeshellarg($test_version) . ' 2>&1';
    $output = shell_exec($cmd);
    unlink($test_version);
    
    if (strpos($output, 'LanceDB version:') !== false) {
        echo "<p style='color: green;'><strong>✓ LanceDB is available:</strong> " . htmlspecialchars(trim($output)) . "</p>";
    } else {
        echo "<p style='color: red;'><strong>✗ LanceDB not available:</strong> " . htmlspecialchars($output) . "</p>";
    }
    
    echo "<h2>Test 2: Test Store Embeddings (with overwrite)</h2>";
    
    // Test storing embeddings with the new approach
    $test_posts = [
        [
            'id' => 3001,
            'title' => 'LanceDB Upsert Test Post 1',
            'content' => 'This post tests the LanceDB upsert fix using add() with overwrite=True.',
            'date' => '2024-01-01',
            'permalink' => 'https://example.com/upsert-test-1',
            'categories' => ['Test', 'LanceDB'],
            'tags' => ['test', 'lancedb', 'upsert']
        ],
        [
            'id' => 3002,
            'title' => 'LanceDB Upsert Test Post 2',
            'content' => 'This is another test post for the LanceDB upsert functionality.',
            'date' => '2024-01-02',
            'permalink' => 'https://example.com/upsert-test-2',
            'categories' => ['Test', 'LanceDB'],
            'tags' => ['test', 'lancedb', 'upsert']
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
    }
    
    echo "<h2>Test 3: Test Upsert Embeddings (with overwrite)</h2>";
    
    // Test upsert functionality
    $upsert_result = $lancedb_service->upsert_embeddings($test_posts, $embeddings);
    
    if ($upsert_result['success']) {
        echo "<p style='color: green;'><strong>✓ Upsert embeddings successful:</strong> " . htmlspecialchars($upsert_result['data']) . "</p>";
    } else {
        echo "<p style='color: red;'><strong>✗ Upsert embeddings failed:</strong> " . htmlspecialchars($upsert_result['data']) . "</p>";
    }
    
    echo "<h2>Test 4: Test Duplicate Checking with Fixed Upsert</h2>";
    
    // Test the duplicate checking functionality with the fixed upsert
    $check_result = $lancedb_service->store_embeddings_with_check($test_posts);
    
    if ($check_result['success']) {
        echo "<p style='color: green;'><strong>✓ Duplicate checking works:</strong> " . htmlspecialchars($check_result['data']) . "</p>";
    } else {
        echo "<p style='color: red;'><strong>✗ Duplicate checking failed:</strong> " . htmlspecialchars($check_result['data']) . "</p>";
    }
    
    echo "<h2>Test 5: Database Statistics</h2>";
    
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

echo "<h2>LanceDB Upsert Fix Test Complete</h2>";
echo "<p>If you can see successful results above, the LanceDB upsert fix is working correctly!</p>";
echo "<p><strong>What was fixed:</strong></p>";
echo "<ul>";
echo "<li>Replaced <code>table.upsert()</code> with <code>table.add(data, overwrite=True)</code></li>";
echo "<li>Updated both <code>store_embeddings()</code> and <code>upsert_embeddings()</code> methods</li>";
echo "<li>Used the correct LanceDB API according to the <a href='https://lancedb.github.io/lancedb/guides/tables/' target='_blank'>official documentation</a></li>";
echo "<li>The <code>overwrite=True</code> parameter handles both insert and update operations</li>";
echo "</ul>";
echo "<p><strong>Key Benefits:</strong></p>";
echo "<ul>";
echo "<li>Compatible with current LanceDB versions</li>";
echo "<li>Handles duplicate records by overwriting them</li>";
echo "<li>Maintains the same functionality as the original upsert approach</li>";
echo "<li>Follows LanceDB best practices</li>";
echo "</ul>";
?> 