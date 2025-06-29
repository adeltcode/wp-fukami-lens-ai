<?php
/**
 * Test script to verify PyArrow schema fix for LanceDB
 * 
 * This file tests the fix for the "Schema must be an instance of pyarrow.Schema" error.
 */

// Load WordPress if running standalone
if (!defined('ABSPATH')) {
    require_once dirname(__DIR__, 4) . '/wp-load.php';
}

echo "<h1>Testing PyArrow Schema Fix for LanceDB</h1>";

try {
    $lancedb_service = new FUKAMI_LENS_LanceDB_Service();
    
    echo "<h2>Test 1: Check if PyArrow is available</h2>";
    
    // Test if we can import pyarrow in Python
    $test_import = tempnam(sys_get_temp_dir(), 'fukami_lens_pyarrow_test_');
    file_put_contents($test_import, 'import pyarrow as pa; print("PyArrow version:", pa.__version__)');
    
    $cmd = escapeshellcmd('/usr/bin/python3') . ' ' . escapeshellarg($test_import) . ' 2>&1';
    $output = shell_exec($cmd);
    unlink($test_import);
    
    if (strpos($output, 'PyArrow version:') !== false) {
        echo "<p style='color: green;'><strong>✓ PyArrow is available:</strong> " . htmlspecialchars(trim($output)) . "</p>";
    } else {
        echo "<p style='color: red;'><strong>✗ PyArrow not available:</strong> " . htmlspecialchars($output) . "</p>";
        echo "<p>Please install pyarrow: <code>pip3 install pyarrow</code></p>";
    }
    
    echo "<h2>Test 2: Test Table Creation with PyArrow Schema</h2>";
    
    // Test creating a table with the new schema
    $test_posts = [
        [
            'id' => 2001,
            'title' => 'PyArrow Schema Test Post',
            'content' => 'This post tests the PyArrow schema fix for LanceDB.',
            'date' => '2024-01-01',
            'permalink' => 'https://example.com/pyarrow-test',
            'categories' => ['Test', 'PyArrow'],
            'tags' => ['test', 'pyarrow', 'schema']
        ]
    ];
    
    // Get embedding for the test post
    $embedding_result = $lancedb_service->get_embedding($test_posts[0]['title'] . ' ' . $test_posts[0]['content']);
    
    if ($embedding_result['success']) {
        $test_embeddings = [$embedding_result['data']['embedding']];
        
        // Test upsert with new schema
        $upsert_result = $lancedb_service->upsert_embeddings($test_posts, $test_embeddings);
        
        if ($upsert_result['success']) {
            echo "<p style='color: green;'><strong>✓ Schema fix successful:</strong> " . htmlspecialchars($upsert_result['data']) . "</p>";
        } else {
            echo "<p style='color: red;'><strong>✗ Schema fix failed:</strong> " . htmlspecialchars($upsert_result['data']) . "</p>";
        }
    } else {
        echo "<p style='color: red;'><strong>✗ Failed to get embedding:</strong> " . htmlspecialchars($embedding_result['data']) . "</p>";
    }
    
    echo "<h2>Test 3: Test Duplicate Checking with New Schema</h2>";
    
    // Test the duplicate checking functionality
    $check_result = $lancedb_service->store_embeddings_with_check($test_posts);
    
    if ($check_result['success']) {
        echo "<p style='color: green;'><strong>✓ Duplicate checking works:</strong> " . htmlspecialchars($check_result['data']) . "</p>";
    } else {
        echo "<p style='color: red;'><strong>✗ Duplicate checking failed:</strong> " . htmlspecialchars($check_result['data']) . "</p>";
    }
    
    echo "<h2>Test 4: Database Statistics</h2>";
    
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

echo "<h2>PyArrow Schema Fix Test Complete</h2>";
echo "<p>If you can see successful results above, the PyArrow schema fix is working correctly!</p>";
echo "<p><strong>What was fixed:</strong></p>";
echo "<ul>";
echo "<li>Added PyArrow import to LanceDB operations</li>";
echo "<li>Updated schema definition to use <code>pa.schema()</code> instead of dictionary</li>";
echo "<li>Added PyArrow to requirements.txt and Dockerfile</li>";
echo "<li>Fixed embedding field definition to use <code>pa.list_(pa.float32(), 1536)</code></li>";
echo "</ul>";
?> 