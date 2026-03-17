<?php

// If this file was called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
//Register post type
function accodes_register_snippets_cpt() {
    register_post_type('accodes_snippets', array(
        'labels' => array(
            'name' => __('Snippets', 'add-custom-codes'),
            'singular_name' => __('Snippet', 'add-custom-codes'),
            'add_new' => __('Add New Snippet', 'add-custom-codes'),
            'add_new_item' => __('Add New Snippet', 'add-custom-codes'),
            'edit_item' => __('Edit Snippet', 'add-custom-codes'),
            'new_item' => __('New Snippet', 'add-custom-codes'),
            'view_item' => __('View Snippet', 'add-custom-codes'),
            'search_items' => __('Search Snippets', 'add-custom-codes'),
            'not_found' => __('No snippets found', 'add-custom-codes'),
            'not_found_in_trash' => __('No snippets found in trash', 'add-custom-codes'),
            'all_items' => __('All Snippets', 'add-custom-codes'),
            'menu_name' => __('Add Custom Codes', 'add-custom-codes'),
            'name_admin_bar' => __('Snippet', 'add-custom-codes'),
        ),
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        // Restrict management of snippets to administrators (or roles with manage_options)
        'capability_type' => 'post',
        'capabilities' => array(
            'edit_post'              => 'manage_options',
            'read_post'              => 'manage_options',
            'delete_post'            => 'manage_options',
            'edit_posts'             => 'manage_options',
            'edit_others_posts'      => 'manage_options',
            'publish_posts'          => 'manage_options',
            'read_private_posts'     => 'manage_options',
            'create_posts'           => 'manage_options',
            'delete_posts'           => 'manage_options',
            'delete_private_posts'   => 'manage_options',
            'delete_published_posts' => 'manage_options',
            'delete_others_posts'    => 'manage_options',
            'edit_private_posts'     => 'manage_options',
            'edit_published_posts'   => 'manage_options',
        ),
        'supports' => array('title'),
        'menu_icon' => plugins_url('add-custom-codes/assets/images/accodes-menu-icon.svg'), 
        'menu_position' => 60,
		'taxonomies' => array('accodes_tag'),
    ));
}
add_action('init', 'accodes_register_snippets_cpt');

//register tags
function accodes_register_snippet_tags_taxonomy() {
    register_taxonomy('accodes_tag', 'accodes_snippets', array(
        'label' => __('Snippet Tags', 'add-custom-codes'),
        'labels' => array(
            'name' => __('Snippet Tags', 'add-custom-codes'),
            'singular_name' => __('Snippet Tag', 'add-custom-codes'),
            'search_items' => __('Search Tags', 'add-custom-codes'),
            'all_items' => __('All Tags', 'add-custom-codes'),
            'edit_item' => __('Edit Tag', 'add-custom-codes'),
            'update_item' => __('Update Tag', 'add-custom-codes'),
            'add_new_item' => __('Add New Tag', 'add-custom-codes'),
            'new_item_name' => __('New Tag Name', 'add-custom-codes'),
            'menu_name' => __('Snippet Tags', 'add-custom-codes'),
        ),
        'hierarchical' => false, // set to true if you want category-style nesting
        'show_ui' => true,
        'show_admin_column' => true,
        'show_in_rest' => true,
        'rewrite' => array('slug' => 'snippet-tag'),
		'show_in_quick_edit' => false,
   		 'meta_box_cb' => false,
    ));
}
add_action('init', 'accodes_register_snippet_tags_taxonomy');

//remove default tags box on edit screen
add_action('admin_menu', function () {
    remove_meta_box('tagsdiv-accodes_tag', 'accodes_snippets', 'side');
});


//add submeanu
add_action('admin_menu', function () {
	//global codes page
    add_submenu_page(
        'edit.php?post_type=accodes_snippets', 
        'Global Codes',
        'Global Codes',
        'manage_options',
        'accodes-global',
        'accodes_global_page_callback'
    );
	// Import / Export 
    add_submenu_page(
        'edit.php?post_type=accodes_snippets',
        'Import / Export Snippets',
        'Import / Export',
        'manage_options',
        'accodes-import-export',
        'accodes_import_export_page_callback'
    );
	//about page
	add_submenu_page(
    'edit.php?post_type=accodes_snippets', 
    'About Add Custom Codes',            
    'About',                             
    'manage_options',                 
    'accodes-about',             
    'accodes_about_page_callback'        
	);
});


// Disable block editor and hide content editor
add_filter('use_block_editor_for_post_type', function($use, $type){
    return $type === 'accodes_snippets' ? false : $use;
}, 10, 2);

add_action('admin_head', function () {
    $screen = get_current_screen();
    if ($screen && $screen->post_type === 'accodes_snippets') {
        remove_post_type_support('accodes_snippets', 'editor');
    }
});

// place Snippet UI right below the title
add_action('edit_form_after_title', function () {
    $screen = get_current_screen();
    if ($screen->post_type === 'accodes_snippets') {
        global $post;
        accodes_render_snippet_editor($post);
    }
});

// Render snippet UI
function accodes_render_snippet_editor($post) {
	
    $content  = get_post_meta($post->ID, '_accodes_code', true);
    $language = get_post_meta($post->ID, '_accodes_snippet_language', true) ?: 'php';
    $location = get_post_meta($post->ID, '_accodes_snippet_location', true) ?: 'site';
    $active   = get_post_meta($post->ID, '_accodes_snippet_active', true);
	if ($active === '') {
   		 $active = '1'; // default checked for new snippets
	}
	// If it's a new draft (not published yet), default to active
	if ($active === '0' && $post->post_status !== 'publish') {
   		 $active = '1';
	}
    $notes = get_post_meta($post->ID, '_accodes_snippet_notes', true);
	$tags_raw = get_post_meta($post->ID, '_accodes_snippet_tags', true);
	$tags_array = $tags_raw ? explode(',', $tags_raw) : [];
	
	$error = get_post_meta($post->ID, '_accodes_snippet_error', true);
	if ($error) {
		echo '<div class="accodes-error-msg">';
		echo esc_html($error);
		echo '</div>';
		// Echo JS to uncheck the status toggle
		echo "<script>
			document.addEventListener('DOMContentLoaded', function () {
				const statusToggle = document.getElementById('accodes_snippet_active');
				if (statusToggle) {
					statusToggle.checked = false;
				}
			});
		</script>";
		}

    // Provide safe default templates only when empty
    if (trim((string) $content) === '') {
        switch ($language) {
            case 'javascript':
                $content = '/* Add your scripts here. You can remove example code */' . "\n" . 'console.log("code loaded.");' . "\n";
                break;
            case 'css':
                $content = '/* Your CSS here */' . "\n" . '.selector {' . "\n\t\n}" . "\n";
                break;
            case 'htmlmixed':
                $content = '<!-- Add your HTML here. You can remove example code-->' . "\n" . '<div></div>' . "\n";
                break;
            case 'php':
            default:
                // No explicit PHP open/close tags; runner evaluates raw PHP body.
                $content = '// Your PHP here' . "\n";
                break;
        }
    }

    ?>
    <div class="postbox accodes-meta-wrapper">
        <?php wp_nonce_field('accodes_save_snippet_meta', 'accodes_snippet_meta_nonce'); ?>
        <div class="accodes-group above-accodes-tab">
            <div class="accodes-group accodes-theme-toggle" style="display: flex; justify-content: flex-end; align-items: center;">
				<label class="accodes-switch">
       			<input type="checkbox" id="accodes_dark_mode_toggle">
       			<span class="slider"></span>
   				</label>
    			<span class="accodes-toggle-label"> Use Dark Mode Editor</span>
			</div>


            <div class="accodes-radio-group accodes-tab-style">
                <label><input type="radio" name="accodes_snippet_language" value="php" <?php checked($language, 'php'); ?>><div class="tab-label"><span class="label-php">PHP</span> Functions</div></label>
				<label><input type="radio" name="accodes_snippet_language" value="htmlmixed" <?php checked($language, 'htmlmixed'); ?>><div class="tab-label"><span class="label-html">HTML</span> Content</div></label>
				<label><input type="radio" name="accodes_snippet_language" value="javascript" <?php checked($language, 'javascript'); ?>><div class="tab-label"><span class="label-js">JS</span> Scripts</div></label>
				<label><input type="radio" name="accodes_snippet_language" value="css" <?php checked($language, 'css'); ?>><div class="tab-label"><span class="label-css">CSS</span> Styles</div></label>
            </div>
        </div>
        <textarea id="accodes_code_editor" name="accodes_code_editor"
                  class="codemirror-dynamic"
                  data-lang="<?php echo esc_attr($language); ?>"
                  style="width:100%; height:300px; "><?php echo esc_textarea($content); ?></textarea>
        <hr>
        <div class="accodes-group">
			<p class="accodes-snippet-label"><strong>How do you want to use the snippet?</strong></p>
			<div id="location-options-wrapper">
				<!-- PHP -->
				
				<div class="location-options" data-type="php" style="display:none;">
					<label><input type="radio" name="accodes_snippet_location" value="site" <?php checked($location, 'site'); ?>><span>Run site-wide</span></label>
					<label><input type="radio" name="accodes_snippet_location" value="admin" <?php checked($location, 'admin'); ?>><span>Run only in Admin Panel</span></label>
					<label><input type="radio" name="accodes_snippet_location" value="frontend" <?php checked($location, 'frontend'); ?>><span>Run only on Site Front-end</span></label>
				</div>

				<!-- HTML -->
				<div class="location-options" data-type="htmlmixed" style="display:none;">
					<label><input type="radio" name="accodes_snippet_location" value="shortcode" <?php checked($location, 'shortcode'); ?>><span>Create Shortcode for this</span></label>
					<label><input type="radio" name="accodes_snippet_location" value="head" <?php checked($location, 'head'); ?>><span>Insert inside &lt;head&gt;</span></label>
					<label><input type="radio" name="accodes_snippet_location" value="footer" <?php checked($location, 'footer'); ?>><span>Insert before &lt;/body&gt;</span></label>
				</div>

				<!-- JS -->
				<div class="location-options" data-type="javascript" style="display:none;">
					<label><input type="radio" name="accodes_snippet_location" value="head" <?php checked($location, 'head'); ?>><span>Insert inside &lt;head&gt;</span></label>
					<label><input type="radio" name="accodes_snippet_location" value="footer" <?php checked($location, 'footer'); ?>><span>Insert before &lt;/body&gt;</span></label>
				</div>

				<!-- CSS -->
				<div class="location-options" data-type="css" style="display:none;">
					<label><input type="radio" name="accodes_snippet_location" value="footer" <?php checked($location, 'footer'); ?>><span>Insert before &lt;/body&gt;</span></label>
					<label><input type="radio" name="accodes_snippet_location" value="head" <?php checked($location, 'head'); ?>><span>Insert inside &lt;head&gt; </span></label>
				</div>
    		</div>
		</div>
		
		<!-- display shortcode -->
		<div class="accodes-shortcode-box" id="accodes_shortcode_box" style="<?php echo ($location === 'shortcode') ? '' : 'display:none;'; ?>">
			<div class="accodes-shortcode-content" <?php echo ($post->post_status === 'publish') ? '' : 'style="display:none;"'; ?>>
				<p><strong>Shortcode (Click to copy)</strong></p>
				<code id="accodes_shortcode" style="cursor:pointer;">[accodes_snippet id="<?php echo esc_attr($post->ID); ?>"]</code>
				<span id="accodes_copy_msg" style="display:none; margin-left:10px; color:green;">Shortcode Copied!</span>
			</div>

			<div class="accodes-shortcode-pending" <?php echo ($post->post_status === 'publish') ? 'style="display:none;"' : ''; ?>>
				<p><em>Shortcode will be displayed here after you Publish.</em></p>
			</div>
		</div>

			
            <div class="accodes-toggle-wrapper">
    			<label class="accodes-switch">
				<input type="checkbox" name="accodes_snippet_active" id="accodes_snippet_active" value="1" <?php checked($active, '1'); ?>>
				<span class="slider"></span>
				</label>
			<label for="accodes_snippet_active" class="accodes-toggle-label"><strong>Status</strong></label>
			</div>
		
			<div class="accodes-group accodes-notes-group">
				<label for="accodes_snippet_notes"><strong>Notes (optional)</strong></label><br/>
				<p class="description">Use this field to describe what this snippet does. This won't be shown anywhere publicly.</p>								
				<textarea name="accodes_snippet_notes" id="accodes_snippet_notes" rows="3" style="width:100%;"><?php echo esc_textarea($notes); ?></textarea>			
			</div>
		
			<div class="accodes-group">
				<label for="accodes_tags_inpu"><strong>Tags (Optional)</strong></label><br/>
				<p class="description">Classify your snippets using tags. For example, if you are using multiple snippets for Woocommerce, use "Woocommerce" as a tag.</p>	
				<div class="accodes-tags-wrapper">
					<input type="text" id="accodes_tag_input" class="tag-input" placeholder="Type to add tags..." autocomplete="off">
					<div id="accodes_tags_list">
						<?php
						$terms = get_the_terms($post->ID, 'accodes_tag');
						if ($terms && !is_wp_error($terms)) {
							foreach ($terms as $term) {
								echo '<span class="accodes-tag-chip">' . esc_html($term->name) . '<input type="hidden" name="accodes_tags[]" value="' . esc_attr($term->name) . '"><span class="remove-tag">&times;</span></span>';
							}
						}
						?>
					</div>
				</div>
			</div>


    </div>
    <?php
}


// Save meta
add_action('save_post', function ($post_id) {
    if (get_post_type($post_id) !== 'accodes_snippets') return;

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (wp_is_post_revision($post_id)) return;

    $nonce = isset($_POST['accodes_snippet_meta_nonce']) ? sanitize_text_field( wp_unslash($_POST['accodes_snippet_meta_nonce']) ) : '';
    if (!$nonce || !wp_verify_nonce( $nonce, 'accodes_save_snippet_meta')) return;
    if (!current_user_can('edit_post', $post_id)) return;

    // Raw code is stored by design; validated on execution and escaped on output where applicable.
    // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
    $code     = isset($_POST['accodes_code_editor']) ? wp_unslash($_POST['accodes_code_editor']) : '';
    $language = isset($_POST['accodes_snippet_language']) ? sanitize_text_field( wp_unslash($_POST['accodes_snippet_language']) ) : '';
    $active   = isset($_POST['accodes_snippet_active']) ? '1' : '0';

    // Save core meta
    update_post_meta($post_id, '_accodes_code', $code);
    update_post_meta($post_id, '_accodes_snippet_language', $language);
    $location = isset($_POST['accodes_snippet_location']) ? sanitize_text_field( wp_unslash($_POST['accodes_snippet_location']) ) : '';
    $notes    = isset($_POST['accodes_snippet_notes']) ? sanitize_textarea_field( wp_unslash($_POST['accodes_snippet_notes']) ) : '';
    update_post_meta($post_id, '_accodes_snippet_location', $location);
    update_post_meta($post_id, '_accodes_snippet_notes', $notes);
    update_post_meta($post_id, '_accodes_snippet_active', $active);

    // Save tags
    // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Collected then sanitized per-item below
    $raw_tags_input = isset($_POST['accodes_tags']) ? wp_unslash($_POST['accodes_tags']) : null;
    if (is_array($raw_tags_input)) {
        $raw_tags = array_map('strval', $raw_tags_input);
        $tags = array_map('sanitize_text_field', $raw_tags);
        wp_set_post_terms($post_id, $tags, 'accodes_tag');
    }
	if ($language === 'php') {
		delete_post_meta($post_id, '_accodes_snippet_error'); // clear old errors
	}
    // Clear cached snippet lists for all locations when a snippet is saved.
    if ( function_exists( 'set_transient' ) ) {
        $locations = [ 'site', 'admin', 'frontend', 'shortcode', 'head', 'footer' ];
        foreach ( $locations as $loc ) {
            delete_transient( 'accodes_snippet_ids_' . md5( (string) $loc ) );
        }
    }
	
});

function accodes_inject_snippets($location) {
    // Use a short-lived transient to cache snippet IDs per location and avoid expensive meta_query on every request.
    $cache_key = 'accodes_snippet_ids_' . md5( (string) $location );
    $ids = get_transient( $cache_key );
    if ( false === $ids ) {
        // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Results are cached in a transient to avoid running this meta_query on every request.
        $query_args = [
            'post_type'      => 'accodes_snippets',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids', // only IDs to keep the query light
            'no_found_rows'  => true,
            'meta_query'     => [
                [
                    'key'   => '_accodes_snippet_active',
                    'value' => '1',
                ],
                [
                    'key'   => '_accodes_snippet_location',
                    'value' => $location,
                ],
            ],
        ];

        $ids = get_posts( $query_args );
        if ( ! is_array( $ids ) ) {
            $ids = [];
        }
        // Cache for 5 minutes
        set_transient( $cache_key, $ids, 5 * MINUTE_IN_SECONDS );
    }

    if ( empty( $ids ) ) {
        return;
    }

    // Fetch posts by IDs preserving order
    $snippets = get_posts([
        'post_type' => 'accodes_snippets',
        'post_status' => 'publish',
        'posts_per_page' => count( $ids ),
        'post__in' => $ids,
        'orderby' => 'post__in',
        'no_found_rows' => true,
    ]);

    foreach ($snippets as $snippet) {
    // Only run snippets created by users with administrative capability
    if (!user_can((int) $snippet->post_author, 'manage_options')) {
        continue;
    }
    $language = get_post_meta($snippet->ID, '_accodes_snippet_language', true);
    $code     = get_post_meta($snippet->ID, '_accodes_code', true);

    if (empty($code)) continue;

    switch ($language) {
        case 'php':
                if (!is_admin()) {
                    $cleaned = preg_replace('/^\s*<\?(php)?/', '', trim($code));
                    try {
                        // phpcs:ignore WordPress.Security.EvalDetected -- Intentionally executing admin-authored PHP snippets; access is restricted to administrators (snippet authors must have 'manage_options').
                        eval( $cleaned );
                        delete_post_meta( $snippet->ID, '_accodes_snippet_error' ); // clear old errors
                    } catch ( Throwable $e ) {
                        // Auto-deactivate the snippet
                        update_post_meta($snippet->ID, '_accodes_snippet_active', '0');
                        // Log error
                        update_post_meta(
                            $snippet->ID,
                            '_accodes_snippet_error',
                            'PHP Error on line ' . $e->getLine() . ': ' . $e->getMessage()
                        );
                    }
                }
        break;

        case 'css':
            // Intentionally output admin-authored CSS without HTML-encoding so quotes stay intact.
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Intentionally outputting admin-authored CSS; restricted to manage_options.
            echo "<style>\n" . trim( (string) $code ) . "\n</style>\n";
            break;

        case 'javascript':
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Intentionally outputting admin-authored JS for a code injection plugin; restricted to manage_options.
            echo "<script>\n" . trim( (string) $code ) . "\n</script>\n";
            break;

        case 'htmlmixed':
        default:
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Intentionally outputting admin-authored HTML; restricted to manage_options and sanitized for non-privileged.
            echo $code;
            break;
    }
}

}



// Declare Columns on snippets listing page
function accodes_snippet_columns($columns) {
    $columns['accodes_type'] = __('Type', 'add-custom-codes');
	 $columns['accodes_health'] = __('Snippet Health', 'add-custom-codes');
    $columns['accodes_status'] = __('Status', 'add-custom-codes');
    return $columns;
}
add_filter('manage_accodes_snippets_posts_columns', 'accodes_snippet_columns');

// Render the columns in snippets listing page
function accodes_snippet_column_content($column, $post_id) {
    if ($column === 'accodes_type') {
        $lang = get_post_meta($post_id, '_accodes_snippet_language', true);
        $badge = '';

        switch ($lang) {
            case 'php':
                $badge = '<span class="accodes-badge php">PHP</span>';
                break;
            case 'css':
                $badge = '<span class="accodes-badge css">CSS</span>';
                break;
            case 'javascript':
                $badge = '<span class="accodes-badge js">JS</span>';
                break;
            case 'htmlmixed':
            default:
                $badge = '<span class="accodes-badge html">HTML</span>';
                break;
        }

        echo wp_kses( $badge, [ 'span' => [ 'class' => [] ] ] );
    }
	if ($column === 'accodes_health') {
        $error = get_post_meta($post_id, '_accodes_snippet_error', true);
        if (!empty($error)) {
            echo '<span class="accodes-health-warning" title="Snippet was deactivated due to an error in code">i</span>';
        } else {
            echo '<span title="Good!" style="display:inline-block;width:15px;height:15px;background:#46b450;border-radius:50%;"></span>';
        }
    }
    if ($column === 'accodes_status') {
        $active = get_post_meta($post_id, '_accodes_snippet_active', true);
        $is_checked = ($active === '1');
        echo '
            <label class="accodes-switch">
                <input type="checkbox" data-id="' . esc_attr( (string) $post_id ) . '" class="accodes-toggle-snippet"' . ( $is_checked ? ' checked="checked"' : '' ) . '>
                <span class="slider"></span>
            </label>
        ';
    }
	

}


add_action('manage_accodes_snippets_posts_custom_column', 'accodes_snippet_column_content', 10, 2);

//change Snippet status on Status toggle click (List snippets page)
add_action('wp_ajax_accodes_toggle_snippet_status', function () {
    check_ajax_referer('accodes_toggle_snippet');

    $post_id    = isset($_POST['post_id']) ? intval( wp_unslash($_POST['post_id']) ) : 0;
    $active_raw = isset($_POST['active']) ? sanitize_text_field( wp_unslash($_POST['active']) ) : null;
    $active     = ($active_raw === '1') ? '1' : '0';

    if ($post_id && get_post_type($post_id) === 'accodes_snippets') {
        if (!current_user_can('edit_post', $post_id)) {
            wp_send_json_error(['message' => 'Permission denied'], 403);
        }
        update_post_meta($post_id, '_accodes_snippet_active', $active);
        wp_send_json_success(['status' => $active]);
    }

    wp_send_json_error(['message' => 'Invalid post ID or type']);
});

//create shortcode option for html
add_action('init', function () {
    add_shortcode('accodes_snippet', function ($atts) {
        $atts = shortcode_atts(['id' => 0], $atts);
        $post_id = intval($atts['id']);

        if (!$post_id || get_post_type($post_id) !== 'accodes_snippets') return '';

        $snippet = get_post($post_id);
        if (!$snippet || !user_can((int) $snippet->post_author, 'manage_options')) return '';

        $location = get_post_meta($post_id, '_accodes_snippet_location', true);
        $active   = get_post_meta($post_id, '_accodes_snippet_active', true);
        $content  = get_post_meta($post_id, '_accodes_code', true);
        $language = get_post_meta($post_id, '_accodes_snippet_language', true);

        if ($location === 'shortcode' && $active === '1' && $language === 'htmlmixed') {
            return $content;
        }

        return ''; // fallback: do nothing
    });
});


//put snippets on site
add_action('admin_footer', function () {
    if (is_admin()) accodes_inject_snippets('admin');
});

add_action('wp_body_open', function () {
    if (!is_admin()) accodes_inject_snippets('site');
});

add_action('wp_footer', function () {
    if (!is_admin()) accodes_inject_snippets('footer');
});

add_action('wp_head', function () {
    accodes_inject_snippets('head');
});


