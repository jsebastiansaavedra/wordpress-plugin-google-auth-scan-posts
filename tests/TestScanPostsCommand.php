<?php

namespace Tests;

use WP_UnitTestCase;
use WPMUDEV\PluginTest\CLI\ScanPostsCommand;

class TestScanPostsCommand extends WP_UnitTestCase {

    public function testHandleScanPosts() {
        // Create an instance of the ScanPostsCommand class
        $scan_posts_command = new ScanPostsCommand();

        // Create test posts
        $post_id_1 = wp_insert_post([
            'post_title' => 'Test Post 1',
            'post_status' => 'publish',
            'post_type' => 'post',
        ]);

        $post_id_2 = wp_insert_post([
            'post_title' => 'Test Post 2',
            'post_status' => 'publish',
            'post_type' => 'post',
        ]);

        // Run the handle_scan_posts method
        $scan_posts_command->__invoke([], []);

        // Check if the post meta was updated correctly
        $last_scan_1 = get_post_meta($post_id_1, 'wpmudev_test_last_scan', true);
        $last_scan_2 = get_post_meta($post_id_2, 'wpmudev_test_last_scan', true);
        
        $this->assertNotEmpty($last_scan_1, 'Post 1 scan timestamp should not be empty');
        $this->assertNotEmpty($last_scan_2, 'Post 2 scan timestamp should not be empty');
        
        // Allow a margin of error for the timestamp comparison
        $time_now = current_time('timestamp');
        $this->assertLessThanOrEqual(10, abs($time_now - $last_scan_1), 'Post 1 scan timestamp should be close to current time');
        $this->assertLessThanOrEqual(10, abs($time_now - $last_scan_2), 'Post 2 scan timestamp should be close to current time');
    }
}
