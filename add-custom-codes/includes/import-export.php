<?php
// Export Snippets Handler
add_action('admin_post_accodes_export_snippets', function () {
    if (!current_user_can('manage_options')) {
        wp_die('Permission denied');
    }

    $export_scope = $_POST['export_scope'] ?? 'all';
    $exclude_drafts = isset($_POST['exclude_drafts']);

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

    if (!isset($_FILES['accodes_import_file']) || $_FILES['accodes_import_file']['error'] !== UPLOAD_ERR_OK) {
        wp_redirect(admin_url('admin.php?page=accodes-import-export&import=failed'));
        exit;
    }

    $json = file_get_contents($_FILES['accodes_import_file']['tmp_name']);
    $snippets = json_decode($json, true);

    if (!is_array($snippets)) {
        wp_redirect(admin_url('admin.php?page=accodes-import-export&import=invalid'));
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

    wp_redirect(admin_url('admin.php?page=accodes-import-export&import=success&count=' . $imported_count));
    exit;
});

function accodes_import_export_page_callback() {
    $status = $_GET['import'] ?? '';
    $count = intval($_GET['count'] ?? 0);

    if ($status === 'success') {
        echo '<div class="notice notice-success is-dismissible"><p>Import completed! (' . $count . ' snippets)</p></div>';
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
        <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
            <input type="hidden" name="action" value="accodes_export_snippets">
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
        <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" enctype="multipart/form-data">
            <input type="hidden" name="action" value="accodes_import_snippets">
            <input type="file" name="accodes_import_file" accept=".json" required>
            <button class="button">Import Now</button>
        </form>
    </div>
    <?php
}
