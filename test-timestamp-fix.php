<?php
/**
 * Test script to verify LanceDB timestamp precision fix
 * 
 * This file tests the fix for the timestamp precision mismatch error.
 */

// Load WordPress if running standalone
if (!defined('ABSPATH')) {
    require_once dirname(__DIR__, 4) . '/wp-load.php';
}

echo "<h1>Testing LanceDB Timestamp Precision Fix</h1>";

try {
    $lancedb_service = new FUKAMI_LENS_LanceDB_Service();
    
    echo "<h2>Test 1: Check Timestamp Precision</h2>";
    
    // Test timestamp precision in Python
    $test_timestamp = tempnam(sys_get_temp_dir(), 'fukami_lens_timestamp_test_');
    file_put_contents($test_timestamp, '
import pyarrow as pa
from datetime import datetime

# Test microsecond precision
timestamp_us = pa.timestamp("us")
print("Microsecond timestamp type:", timestamp_us)

# Test current datetime with microsecond precision
now = datetime.now()
print("Current datetime with microseconds:", now)
print("Microseconds:", now.microsecond)
');
    
    $cmd = escapeshellcmd('/usr/bin/python3') . ' ' . escapeshellarg($test_timestamp) . ' 2>&1';
    $output = shell_exec($cmd);
    unlink($test_timestamp);
    
    echo "<pre>" . htmlspecialchars($output) . "</pre>";
    
    echo "<h2>Test 2: Test Store Embeddings with Microsecond Precision</h2>";
    
    // Test storing embeddings with the fixed timestamp precision
    $test_posts = [
        [
            'id' => 5001,
            'title' => 'Timestamp Fix Test Post 1',
            'content' => 'This post tests the timestamp precision fix for LanceDB.',
            'date' => '2024-01-01',
            'permalink' => 'https://example.com/timestamp-test-1',
            'categories' => ['Test', 'Timestamp'],
            'tags' => ['test', 'timestamp', 'precision']
        ],
        [
            'id' => 5002,
            'title' => 'Timestamp Fix Test Post 2',
            'content' => 'This is another test post for timestamp precision.',
            'date' => '2024-01-02',
            'permalink' => 'https://example.com/timestamp-test-2',
            'categories' => ['Test', 'Timestamp'],
            'tags' => ['test', 'timestamp', 'precision']
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
    
    echo "<h2>Test 3: Test Upsert Embeddings with Microsecond Precision</h2>";
    
    // Test upsert functionality with the fixed timestamp precision
    $upsert_result = $lancedb_service->upsert_embeddings($test_posts, $embeddings);
    
    if ($upsert_result['success']) {
        echo "<p style='color: green;'><strong>✓ Upsert embeddings successful:</strong> " . htmlspecialchars($upsert_result['data']) . "</p>";
    } else {
        echo "<p style='color: red;'><strong>✗ Upsert embeddings failed:</strong> " . htmlspecialchars($upsert_result['data']) . "</p>";
    }
    
    echo "<h2>Test 4: Test Duplicate Checking with Fixed Timestamps</h2>";
    
    // Test the duplicate checking functionality with the fixed timestamps
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

echo "<h2>LanceDB Timestamp Precision Fix Test Complete</h2>";
echo "<p>If you can see successful results above, the timestamp precision fix is working correctly!</p>";
echo "<p><strong>What was fixed:</strong></p>";
echo "<ul>";
echo "<li>Changed timestamp precision from <code>timestamp[ms]</code> to <code>timestamp[us]</code></li>";
echo "<li>Updated schema to use microsecond precision to match existing data</li>";
echo "<li>Ensured data insertion uses microsecond precision timestamps</li>";
echo "<li>Prevented data loss when casting between timestamp formats</li>";
echo "</ul>";
echo "<p><strong>Key Benefits:</strong></p>";
echo "<ul>";
echo "<li>Compatible with existing LanceDB data that uses microsecond precision</li>";
echo "<li>Prevents data loss errors when inserting new records</li>";
echo "<li>Maintains consistent timestamp precision across all operations</li>";
echo "<li>Follows PyArrow best practices for timestamp handling</li>";
echo "</ul>";
echo "<p><strong>Note:</strong> Microsecond precision provides more accurate timestamps and is the standard for high-precision time tracking in databases.</p>";
?> 