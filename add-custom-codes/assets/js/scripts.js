(function ($) {
    $(document).ready(function () {
		
		
        const $editor = $('#accodes_code_editor');
        const lang = $editor.data('lang') || 'php';
        let cmInstance;

        if ($editor.length) {
            let cm_settings = wp.codeEditor.defaultSettings ? _.clone(wp.codeEditor.defaultSettings) : {};
			
            cm_settings.codemirror = _.extend({}, cm_settings.codemirror, {
                mode: lang,
                lineNumbers: true,
                indentUnit: 4,
				theme: 'default' // or 'dark' if saved preference
            });

            // Store instance so we can change it later
            cmInstance = wp.codeEditor.initialize($editor[0], cm_settings).codemirror;
			
			// Handle theme toggle
            $('#accodes_dark_mode_toggle').on('change', function () {
                const isDark = $(this).is(':checked');
                cmInstance.setOption('theme', isDark ? 'material' : 'default');
				
				// Add/remove "darkmode" class to tab div
				const $tabs = $('.accodes-radio-group.accodes-tab-style');
				if (isDark) {
					$tabs.addClass('darkmode');
				} else {
					$tabs.removeClass('darkmode');
				}
				
            });
        }

        // Auto switch mode on code type change
        $('input[name="accodes_snippet_language"]').on('change', function () {
            const selectedMode = $(this).val();
            if (cmInstance) {
                cmInstance.setOption('mode', selectedMode);
            }
        });

        // For any CSS-specific codemirror blocks
        $('.codemirror-accodes-css').each(function (index, elem) {
            let cm_settings = wp.codeEditor.defaultSettings ? _.clone(wp.codeEditor.defaultSettings) : {};
            cm_settings.codemirror = _.extend({}, cm_settings.codemirror, {
                mode: 'css'
            });
            wp.codeEditor.initialize($(elem), cm_settings);
        });
		
		// For any htmlmixed codemirror blocks
        $('.accodes_cols .codemirror').each(function (index, elem) {
            let cm_settings = wp.codeEditor.defaultSettings ? _.clone(wp.codeEditor.defaultSettings) : {};
            cm_settings.codemirror = _.extend({}, cm_settings.codemirror, {
                mode: 'htmlmixed'
            });
            wp.codeEditor.initialize($(elem), cm_settings);
        });
		
		
		//change status of snippet on clicking Status slider on listing page
		$('.accodes-toggle-snippet').on('change', function () {
			const postId = $(this).data('id');
			const active = $(this).is(':checked') ? 1 : 0;
			
			$.post(ajaxurl, {
				action: 'accodes_toggle_snippet_status',
				post_id: postId,
				active: active,
				_ajax_nonce: accodes_toggle_snippet_nonce
			}, function (response) {
				if (response.success) {
					console.log('Snippet status updated.');
				} else {
					alert('Failed to update snippet status.');
				}
			});
		});
		
		function toggleLocationOptions(type) {
			$('.location-options').hide();

			const $target = $(`.location-options[data-type="${type}"]`);
			$target.show();

			const currentSelected = $('input[name="accodes_snippet_location"]:checked').val();
			const $existingRadioInGroup = $target.find(`input[value="${currentSelected}"]`);

			if ($existingRadioInGroup.length > 0) {
				// Make sure it's visibly selected
				$existingRadioInGroup.prop('checked', true);
			} else {
				// If not present, select the first
				$target.find('input[type="radio"]').first().prop('checked', true);
			}

			toggleShortcodeBox();
		}

		
		function toggleShortcodeBox() {
            const selectedLoc = $('input[name="accodes_snippet_location"]:checked').val();
            const selectedType = $('input[name="accodes_snippet_language"]:checked').val();

            if (selectedLoc === 'shortcode' && selectedType === 'htmlmixed') {
                $('#accodes_shortcode_box').slideDown();
            } else {
                $('#accodes_shortcode_box').slideUp();
            }
        }

        // Init
        toggleShortcodeBox();


        // Get selected on load
        setTimeout(function () {
    		const initialType = $('input[name="accodes_snippet_language"]:checked').val();
    		toggleLocationOptions(initialType);
		}, 50);

        // On radio change — use event delegation
        $(document).on('change', 'input[name="accodes_snippet_language"]', function () {
           toggleLocationOptions($(this).val());
        });
		
		//show or hide shortcodebox
		$(document).on('change', 'input[name="accodes_snippet_location"]', function () {
            toggleShortcodeBox();
        });
		
		// Copy shortcode on click
        $('#accodes_shortcode').on('click', function () {
            const shortcode = $(this).text();

            // Create temp input and copy
            const $temp = $('<input>');
            $('body').append($temp);
            $temp.val(shortcode).select();
            document.execCommand('copy');
            $temp.remove();

            // Show message
            $('#accodes_copy_msg').fadeIn(200).delay(1500).fadeOut(300);
        });
		
		// tags suggestions while typing
		if (typeof accodes_data.accodes_tag_suggestions !== 'undefined') {
			$('#accodes_tag_input').autocomplete({
				source: accodes_data.accodes_tag_suggestions,
				minLength: 1,
				select: function (event, ui) {
					const val = ui.item.value;
					const exists = $(`#accodes_tags_list input[value="${val}"]`).length > 0;

					if (!exists) {
						const chip = `
							<span class="accodes-tag-chip">${val}
								<input type="hidden" name="accodes_tags[]" value="${val}">
								<span class="remove-tag">&times;</span>
							</span>`;
						$('#accodes_tags_list').append(chip);
					}
					$(this).val('');
					return false;
				}
			});
		}

    // Enter key as fallback
    $(document).on('keypress', '#accodes_tag_input', function (e) {
      if (e.which === 13) {
        e.preventDefault();
        const val = $(this).val().trim();
        if (val !== '') {
          const chip = `
            <span class="accodes-tag-chip">${val}
              <input type="hidden" name="accodes_tags[]" value="${val}">
              <span class="remove-tag">&times;</span>
            </span>`;
          $('#accodes_tags_list').append(chip);
          $(this).val('');
        }
      }
    });

    // Remove tag on x click
    $(document).on('click', '.remove-tag', function () {
      $(this).closest('.accodes-tag-chip').remove();
    });

		
		
    });
})(jQuery);
