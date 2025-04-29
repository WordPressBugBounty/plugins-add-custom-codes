jQuery(document).ready(function ($) {
    const pluginSlug = 'add-custom-codes/add-custom-codes.php';
    const feedbackToken = accodes_feedback.token;

    $('tr[data-slug="add-custom-codes"] .deactivate a').on('click', function (e) {
        e.preventDefault();
        const deactivateUrl = $(this).attr('href');

        const formHtml = `
            <div id="accodes-deactivate-overlay">
                <div class="accodes-popup">
                    <h2>Quick feedback before you go?</h2>
                    <p>Would you mind telling why you are disabling the plugin? This could help me improve it to serve you better later.</p>
                    <form id="accodes-deactivate-form">
                        <label><input type="radio" name="reason" value="Technical issue"> Technical issue</label><br>
                        <label><input type="radio" name="reason" value="I don't know how to use"> I don’t know how to use</label><br>
                        <label><input type="radio" name="reason" value="Missing features"> Missing features</label><br>
                        <label><input type="radio" name="reason" value="Other"> Other</label><br><br>
                        <textarea name="details" rows="4" placeholder="More information or suggestions (optional)" style="width: 100%;"></textarea>
                        <p style="font-size: 12px; color: #666; margin-top: 10px;">
                            By submitting this form, you agree to share your feedback anonymously for improvement purposes only.
                        </p>
                        <div style="display: flex; gap: 10px; flex-wrap: wrap; margin-top: 15px;">
                            <button type="submit" class="button button-primary">Submit & Deactivate</button>
                            <button type="button" class="button accodes-cancel">Cancel</button>
                            <button type="button" class="button button-link accodes-skip-deactivate">Skip & Deactivate</button>
                        </div>
                    </form>
                </div>
            </div>
            <style>
                #accodes-deactivate-overlay {
                    position: fixed;
                    top: 0; left: 0;
                    width: 100%;
                    height: 100%;
                    background: rgba(0,0,0,0.5);
                    z-index: 9999;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }
                .accodes-popup {
                    background: #fff;
                    padding: 30px;
                    max-width: 500px;
                    border-radius: 8px;
                    box-shadow: 0 0 15px rgba(0,0,0,0.2);
                    width: 90%;
                }
            </style>
        `;

        $('body').append(formHtml);

        $('.accodes-cancel').on('click', function () {
            $('#accodes-deactivate-overlay').remove();
        });

        $('.accodes-skip-deactivate').on('click', function () {
            window.location.href = deactivateUrl;
        });

        $('#accodes-deactivate-form').on('submit', function (ev) {
            ev.preventDefault();

            const reason = $('input[name="reason"]:checked').val();
            const details = $('textarea[name="details"]').val();

            if (!reason) {
                alert('Please select a reason');
                return;
            }

            $.post('https://pluginfeedback.maktalseo.com/feedback/submit.php', {
                plugin: 'Add Custom Codes',
                reason: reason,
                details: details,
                token: feedbackToken,
                plugin_version: accodes_feedback.plugin_version,
                wp_version: accodes_feedback.wp_version,
                php_version: accodes_feedback.php_version
            }, function () {
                window.location.href = deactivateUrl;
            }).fail(function () {
                window.location.href = deactivateUrl;
            });
        });
    });
});