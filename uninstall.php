<?php

/**
 * Fired when the plugin is uninstalled.
 *
 * When populating this file, consider the following flow
 * of control:
 *
 * - This method should be static
 * - Check if the $_REQUEST content actually is the plugin name
 * - Run an admin referrer check to make sure it goes through authentication
 * - Verify the output of $_GET makes sense
 * - Repeat with other user roles. Best directly by using the links/query string parameters.
 * - Repeat things for multisite. Once for a single site in the network, once sitewide.
 *
 * This file may be updated more in future version of the Boilerplate; however, this is the
 * general skeleton and outline for how the file should work.
 *
 * For more information, see the following discussion:
 * https://github.com/tommcfarlin/WordPress-Plugin-Boilerplate/pull/123#issuecomment-28541913
 *
 * @link       https://codeben.fr
 * @since      1.0.0
 *
 * @package    All_Images_Ai
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

if (defined('WP_CONTENT_DIR')) {
    $log_path = WP_CONTENT_DIR . '/all-images-ai-logs/';
    if (is_dir($log_path)) {
        @unlink($log_path . 'all-images-ai.log');
        @rmdir($log_path);
    }
}

$api_key = get_option('all-images-api-key');

global $wpdb;
$tablename = $wpdb->prefix."allimages_generations";

if (!empty($api_key)) {
    $result = $wpdb->get_col(
        <<<SQL
SELECT DISTINCT generation_id
FROM {$tablename}
WHERE generation_id IS NOT NULL;
SQL
    );

    if (!empty($result)) {
        $options['headers'] = [
            'Content-Type' => 'application/json',
            'api-key' => $api_key
        ];

        $options['body'] = json_encode([
            'printIds' => $result
        ]);

        $args = wp_parse_args(
            $options,
            [
                'method' => 'DELETE',
                'timeout' => '5',
                'redirection' => '5',
                'httpversion' => '1.0',
                'blocking' => false,
                'cookies' => [],
            ]
        );

        $url = 'https://api.all-images.ai/v1/image-generations';
        $response = wp_remote_post($url, $args);
    }
}

$wpdb->query( "DROP TABLE IF EXISTS ".$tablename );

delete_option('all-images-ai-settings');
delete_option('all-images-api-key');
