<?php
// If this file was called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
add_action('admin_enqueue_scripts', 'accodes_enqueue_deactivation_feedback');
function accodes_enqueue_deactivation_feedback($hook) {
    if ($hook !== 'plugins.php') {
        return;
    }

    $plugin_data = get_file_data(plugin_dir_path(__FILE__) . '../add-custom-codes.php', array('Version' => 'Version'), 'plugin');

    wp_enqueue_script(
        'accodes-deactivate-feedback',
        plugins_url('deactivate-feedback.js', __FILE__),
        array('jquery'),
        $plugin_data['Version'],
        true
    );

    wp_localize_script('accodes-deactivate-feedback', 'accodes_feedback', array(
        'token'           => 'rY9b@F9xM3hPzLw82dKq!xT4',
        'plugin_version'  => $plugin_data['Version'],
        'wp_version'      => get_bloginfo('version'),
        'php_version'     => phpversion()
    ));
}