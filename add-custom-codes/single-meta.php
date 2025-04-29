<?php 
// If this file was called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<?php
function accodes_is_gutenberg_active() {
    // Check if we're on a post edit screen and Gutenberg is loaded
    $current_screen = get_current_screen();
    return (function_exists('is_gutenberg_page') && is_gutenberg_page())
        || (isset($current_screen->is_block_editor) && $current_screen->is_block_editor)
        || (defined('REST_REQUEST') && REST_REQUEST); 
}
?>
<div class="acc-ind-col-1 ">
	<?php wp_nonce_field('accodes_save_meta', 'accodes_meta_nonce'); ?>

		<fieldset class="accodes_ind_field">
			<div>
				<p class="acc_info">
				<label for="_accodes_header_metabox" class="first-label accodes-label green-label">
					<?php
						// escape
						esc_html_e( 'Header Codes', 'add-custom-codes' );
					?>
				</label>
					Codes & scripts to add before <em>&lt;/head&gt;</em> section of this page. Use  <em>&lt;script&gt; &lt;/script&gt;</em>, <em>&lt;style&gt; &lt;/style&gt;</em> tags when necessary.
	</p>
				<textarea
					type="text"
					name="_accodes_header_metabox"
					id="_accodes_header_metabox" class="<?php if (!accodes_is_gutenberg_active()) { echo "codemirror small-codemirror";} ?>"
						  ><?php echo esc_textarea($header_script) ; ?></textarea>
			</div>
			<p>
                <label for="accodes_hide_header" class="accodes-checkbox-label ">
                            <input type="checkbox" <?php echo checked( $hide_header, 'on', false ) ?>
                                   name="accodes_hide_header" id="accodes_hide_header"/>
							<?php esc_html_e( "Hide Global Header Codes on this page", 'add-custom-codes' ); ?>
				</label>
				Go to <em>Add Custom Codes -> Global Codes</em> to see your Global Header Codes.
			</p>
		</fieldset>
	
		<fieldset class="accodes_ind_field">
			<div>
				<p class="acc_info">
				<label for="_accodes_footer_metabox" class="accodes-label green-label">
					<?php
						esc_html_e( 'Footer Codes', 'add-custom-codes' );
					?>
				</label>
					Codes & scripts to add before <em>&lt;/body&gt;</em> section of this page. Use <em>&lt;script&gt; &lt;/script&gt;</em>, <em>&lt;style&gt; &lt;/style&gt;</em> tags when necessary.
	</p>
				<textarea
					type="text"
					name="_accodes_footer_metabox"
					id="_accodes_footer_metabox" class="<?php if (!accodes_is_gutenberg_active()) { echo "codemirror small-codemirror";} ?>"
						  ><?php echo esc_textarea($footer_script) ; ?></textarea>
				<p>
                <label for="accodes_hide_footer" class="accodes-checkbox-label">
                            <input type="checkbox" <?php echo checked( $hide_footer, 'on', false ) ?>
                                   name="accodes_hide_footer" id="accodes_hide_footer"/>
							<?php esc_html_e( "Hide Global Footer Codes on this page", 'add-custom-codes' ); ?>
                        </label>
				Go to <em>Add Custom Codes -> Global Codes</em> to see your Global Footer Codes.
				</p>
			</div>
		</fieldset>
	
	</div>
	<div class="acc-ind-col-2">
		<p>
			<em>Add Custom Codes by Mak</em> plugin is <span>Free Forever</span> to use! <a class="acc-link1" href="https://donate.stripe.com/9AQdRz5xJ87c9i0bIS" target="_blank">Donate</a> or <a class="acc-link1" href="https://maktalseo.com/" target="_blank">Hire us for your next project</a> to support!
		</p>
	</div>
	<div style="clear:both;"></div>