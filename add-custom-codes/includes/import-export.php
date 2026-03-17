<?php

// If this file was called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Export Snippets Handler
add_action('admin_post_accodes_export_snippets', function () {
    if (!current_user_can('manage_options')) {
        wp_die('Permission denied');
    }

    check_admin_referer('accodes_export_snippets', 'accodes_export_nonce');

    $export_scope = isset( $_POST['export_scope'] ) ? sanitize_text_field( wp_unslash( $_POST['export_scope'] ) ) : 'all';
    $exclude_drafts = isset( $_POST['exclude_drafts'] );

    $args = [
        'post_type' => 'accodes_snippets',
        'post_status' => 'any',
        'posts_per_page' => -1,
    ];

    $snippets = get_posts($args);
    $export_data = [];

    foreach ($snippets as $snippet) {
        $active = get_post_meta($snippet->ID, '_accodes_snippet_active', true);

        if ($export_scope === 'active' && $active !== '1') {
            continue;
        }

        if ($exclude_drafts && $snippet->post_status === 'draft') {
            continue;
        }

        $export_data[] = [
            'title' => $snippet->post_title,
            'status' => $snippet->post_status,
            'content' => get_post_meta($snippet->ID, '_accodes_code', true),
            'language' => get_post_meta($snippet->ID, '_accodes_snippet_language', true),
            'location' => get_post_meta($snippet->ID, '_accodes_snippet_location', true),
            'active' => $active,
            'notes' => get_post_meta($snippet->ID, '_accodes_snippet_notes', true),
            'tags' => wp_get_post_terms($snippet->ID, 'accodes_tag', ['fields' => 'names']),
        ];
    }

    if (empty($export_data)) {
        echo "<script>alert('No snippets to export that meet selected conditions!');window.history.back();</script>";
        exit;
    }

    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="accodes-snippets-export.json"');
    echo json_encode($export_data, JSON_PRETTY_PRINT);
    exit;
});

// Import Snippets Handler
add_action('admin_post_accodes_import_snippets', function () {
    if (!current_user_can('manage_options')) {
        wp_die('Permission denied');
    }

    check_admin_referer('accodes_import_snippets', 'accodes_import_nonce');

    // Validate uploaded file array indexes before use. Rely on the upload error index and
    // `wp_handle_upload()` to safely move and validate the uploaded file.
    if ( empty( $_FILES['accodes_import_file'] ) || ! isset( $_FILES['accodes_import_file']['error'] ) || $_FILES['accodes_import_file']['error'] !== UPLOAD_ERR_OK ) {
        wp_safe_redirect( esc_url_raw( admin_url('admin.php?page=accodes-import-export&import=failed') ) );
        exit;
    }

    // Move uploaded file into WordPress uploads directory for safe handling.
    require_once ABSPATH . 'wp-admin/includes/file.php';
    $overrides = [
        'test_form' => false,
        'mimes' => [ 'json' => 'application/json', 'txt' => 'text/plain' ],
    ];
    $move = wp_handle_upload( $_FILES['accodes_import_file'], $overrides );
    if ( isset( $move['error'] ) || empty( $move['file'] ) ) {
        wp_safe_redirect( esc_url_raw( admin_url('admin.php?page=accodes-import-export&import=failed') ) );
        exit;
    }

    $file_path = $move['file'];
    $json = file_get_contents( $file_path );
    // Clean up the moved file after reading.
    if ( function_exists( 'wp_delete_file' ) ) {
        wp_delete_file( $file_path );
    } else {
        @unlink( $file_path );
    }
    $snippets = json_decode($json, true);

    if (!is_array($snippets)) {
        wp_safe_redirect( esc_url_raw( admin_url('admin.php?page=accodes-import-export&import=invalid') ) );
        exit;
    }

    $imported_count = 0;

    foreach ($snippets as $snippet) {
        $post_id = wp_insert_post([
            'post_type' => 'accodes_snippets',
            'post_title' => sanitize_text_field($snippet['title']),
            'post_status' => sanitize_text_field($snippet['status'] ?? 'draft'),
        ]);

        if ($post_id) {
            update_post_meta($post_id, '_accodes_code', $snippet['content']);
            update_post_meta($post_id, '_accodes_snippet_language', $snippet['language']);
            update_post_meta($post_id, '_accodes_snippet_location', $snippet['location']);
            update_post_meta($post_id, '_accodes_snippet_active', $snippet['active']);
            update_post_meta($post_id, '_accodes_snippet_notes', $snippet['notes'] ?? '');
            wp_set_post_terms($post_id, $snippet['tags'], 'accodes_tag');
            $imported_count++;
        }
    }

    wp_safe_redirect( esc_url_raw( admin_url('admin.php?page=accodes-import-export&import=success&count=' . (int) $imported_count) ) );
    exit;
});

function accodes_import_export_page_callback() {
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- values come from redirect parameters after verified handlers
    $status = isset( $_GET['import'] ) ? sanitize_text_field( wp_unslash( $_GET['import'] ) ) : '';
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $count = isset( $_GET['count'] ) ? intval( wp_unslash( $_GET['count'] ) ) : 0;

    if ($status === 'success') {
        echo '<div class="notice notice-success is-dismissible"><p>Import completed! (' . esc_html( (string) $count ) . ' snippets)</p></div>';
    } elseif ($status === 'failed') {
        echo '<div class="notice notice-error is-dismissible"><p>File upload failed. Please try again.</p></div>';
    } elseif ($status === 'invalid') {
        echo '<div class="notice notice-error is-dismissible"><p>Invalid JSON format. Please upload a valid export file.</p></div>';
    }

    accodes_render_import_export_buttons();
}

function accodes_render_import_export_buttons() {
    ?>
    <div class="accodes-export-box white-import-export">
        <h2>Export Snippets</h2>
        <form method="post" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>">
            <input type="hidden" name="action" value="accodes_export_snippets">
            <?php wp_nonce_field('accodes_export_snippets', 'accodes_export_nonce'); ?>
            <p><strong>Which snippets to export?</strong></p>
            <label><input type="radio" name="export_scope" value="all" checked> Export All Snippets</label><br>
            <label><input type="radio" name="export_scope" value="active"> Export only Active Snippets</label>
            <p><label><input type="checkbox" name="exclude_drafts"> Do not include Draft Snippets</label></p>
            <button class="button button-primary">Download Export File</button>
        </form>
    </div>
    <hr/>
    <div class="accodes-import-box white-import-export">
		<h2>Import Snippets</h2>
        <form method="post" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" enctype="multipart/form-data">
            <input type="hidden" name="action" value="accodes_import_snippets">
            <?php wp_nonce_field('accodes_import_snippets', 'accodes_import_nonce'); ?>
            <input type="file" name="accodes_import_file" accept=".json" required>
            <button class="button">Import Now</button>
        </form>
    </div>
    <?php
}
