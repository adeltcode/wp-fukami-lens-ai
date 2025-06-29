<?php
/**
 * Test script to verify LanceDB fix for overwrite parameter issue
 * 
 * This file tests the fix for the "add() got an unexpected keyword argument 'overwrite'" error.
 */

// Load WordPress if running standalone
if (!defined('ABSPATH')) {
    require_once dirname(__DIR__, 4) . '/wp-load.php';
}

echo "<h1>Testing LanceDB Fix for Overwrite Parameter</h1>";

try {
    $lancedb_service = new FUKAMI_LENS_LanceDB_Service();
    
    echo "<h2>Test 1: Check LanceDB Version and Capabilities</h2>";
    
    // Test LanceDB version and check if delete method is available
    $test_capabilities = tempnam(sys_get_temp_dir(), 'fukami_lens_lancedb_capabilities_');
    file_put_contents($test_capabilities, '
import lancedb
import inspect

print("LanceDB version:", lancedb.__version__)

# Check if table.delete method exists
table_methods = [method for method in dir(lancedb.LanceTable) if not method.startswith("_")]
print("Available table methods:", ", ".join(table_methods))

# Check if delete is available
if "delete" in table_methods:
    print("✓ delete method is available")
else:
    print("✗ delete method is not available")
');
    
    $cmd = escapeshellcmd('/usr/bin/python3') . ' ' . escapeshellarg($test_capabilities) . ' 2>&1';
    $output = shell_exec($cmd);
    unlink($test_capabilities);
    
    echo "<pre>" . htmlspecialchars($output) . "</pre>";
    
    echo "<h2>Test 2: Test Store Embeddings (without overwrite)</h2>";
    
    // Test storing embeddings with the fixed approach
    $test_posts = [
        [
            'id' => 4001,
            'title' => 'LanceDB Fix Test Post 1',
            'content' => 'This post tests the LanceDB fix for the overwrite parameter issue.',
            'date' => '2024-01-01',
            'permalink' => 'https://example.com/fix-test-1',
            'categories' => ['Test', 'LanceDB'],
            'tags' => ['test', 'lancedb', 'fix']
        ],
        [
            'id' => 4002,
            'title' => 'LanceDB Fix Test Post 2',
            'content' => 'This is another test post for the LanceDB fix.',
            'date' => '2024-01-02',
            'permalink' => 'https://example.com/fix-test-2',
            'categories' => ['Test', 'LanceDB'],
            'tags' => ['test', 'lancedb', 'fix']
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
    
    echo "<h2>Test 3: Test Upsert Embeddings (with delete + add)</h2>";
    
    // Test upsert functionality with the new approach
    $upsert_result = $lancedb_service->upsert_embeddings($test_posts, $embeddings);
    
    if ($upsert_result['success']) {
        echo "<p style='color: green;'><strong>✓ Upsert embeddings successful:</strong> " . htmlspecialchars($upsert_result['data']) . "</p>";
    } else {
        echo "<p style='color: red;'><strong>✗ Upsert embeddings failed:</strong> " . htmlspecialchars($upsert_result['data']) . "</p>";
    }
    
    echo "<h2>Test 4: Test Duplicate Checking with Fixed Methods</h2>";
    
    // Test the duplicate checking functionality with the fixed methods
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

echo "<h2>LanceDB Overwrite Parameter Fix Test Complete</h2>";
echo "<p>If you can see successful results above, the LanceDB overwrite parameter fix is working correctly!</p>";
echo "<p><strong>What was fixed:</strong></p>";
echo "<ul>";
echo "<li>Removed <code>overwrite=True</code> parameter from <code>table.add()</code> calls</li>";
echo "<li>Implemented proper upsert logic using <code>table.delete()</code> followed by <code>table.add()</code></li>";
echo "<li>Updated both <code>store_embeddings()</code> and <code>upsert_embeddings()</code> methods</li>";
echo "<li>Maintained the same functionality while using compatible LanceDB API</li>";
echo "</ul>";
echo "<p><strong>Key Benefits:</strong></p>";
echo "<ul>";
echo "<li>Compatible with current LanceDB versions that don't support overwrite parameter</li>";
echo "<li>Properly handles duplicate records by deleting old ones before adding new ones</li>";
echo "<li>Maintains the same upsert functionality as intended</li>";
echo "<li>Follows LanceDB best practices for data management</li>";
echo "</ul>";
echo "<p><strong>Note:</strong> This approach ensures that duplicate records are properly handled by first removing existing records with the same IDs, then adding the new data. This is the standard way to implement upsert functionality in LanceDB.</p>";
?> 