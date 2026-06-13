/**
 * RapidTextAI Deactivation Feedback
 * Intercepts plugin deactivation to ask for feedback before proceeding.
 */
(function ($) {
    'use strict';

    var pluginSlug = 'ai-text-block/rapidtext-ai-text-block.php';
    var deactivateUrl = '';
    var $overlay, $modal, $thanks, $form, $submitBtn, $customTextarea, $footer;

    function createModal() {
        var html = '' +
            '<div class="rapidtextai-deactivate-modal-overlay" id="rapidtextai-deactivate-overlay">' +
                '<div class="rapidtextai-deactivate-modal">' +
                    '<div class="rapidtextai-deactivate-modal-header">' +
                        '<h2><span>👋</span> Quick feedback?</h2>' +
                        '<p>If you have a moment, please let us know why you\'re deactivating RapidTextAI.</p>' +
                    '</div>' +
                    '<div class="rapidtextai-deactivate-modal-body" id="rapidtextai-deactivate-form">' +
                        '<ul class="rapidtextai-deactivate-reasons">' +
                            '<li><label><input type="radio" name="rapidtextai_reason" value="no_longer_need"> <span>I no longer need the plugin</span></label></li>' +
                            '<li><label><input type="radio" name="rapidtextai_reason" value="found_better_alternative"> <span>I found a better alternative</span></label></li>' +
                            '<li><label><input type="radio" name="rapidtextai_reason" value="temporary_deactivation"> <span>This is a temporary deactivation</span></label></li>' +
                            '<li><label><input type="radio" name="rapidtextai_reason" value="not_working"> <span>The plugin didn\'t work as expected</span></label></li>' +
                            '<li><label><input type="radio" name="rapidtextai_reason" value="too_complex"> <span>Too complex / hard to use</span></label></li>' +
                            '<li><label><input type="radio" name="rapidtextai_reason" value="other"> <span>Other reason</span></label></li>' +
                        '</ul>' +
                        '<div class="rapidtextai-deactivate-custom-reason">' +
                            '<textarea id="rapidtextai-custom-reason" placeholder="Tell us more (optional)..." rows="2"></textarea>' +
                        '</div>' +
                    '</div>' +
                    '<div class="rapidtextai-deactivate-modal-footer" id="rapidtextai-deactivate-footer">' +
                        '<button type="button" class="rapidtextai-btn-skip" id="rapidtextai-btn-skip">Skip &amp; Deactivate</button>' +
                        '<button type="button" class="rapidtextai-btn-submit" id="rapidtextai-btn-submit" disabled>Submit &amp; Deactivate</button>' +
                    '</div>' +
                    '<div class="rapidtextai-deactivate-thanks" id="rapidtextai-deactivate-thanks">' +
                        '<h3>🙏 Thank you for your feedback!</h3>' +
                        '<p>Deactivating now...</p>' +
                    '</div>' +
                '</div>' +
            '</div>';

        $('body').append(html);

        $overlay      = $('#rapidtextai-deactivate-overlay');
        $modal        = $overlay.find('.rapidtextai-deactivate-modal');
        $thanks       = $('#rapidtextai-deactivate-thanks');
        $form         = $('#rapidtextai-deactivate-form');
        $submitBtn    = $('#rapidtextai-btn-submit');
        $customTextarea = $('#rapidtextai-custom-reason');
        $footer = $('#rapidtextai-deactivate-footer');
    }

    function bindEvents() {
        // Enable submit button when a reason is selected
        $(document).on('change', 'input[name="rapidtextai_reason"]', function () {
            $submitBtn.prop('disabled', false);
        });

        // Skip button: deactivate without sending feedback
        $(document).on('click', '#rapidtextai-btn-skip', function () {
            window.location.href = deactivateUrl;
        });

        // Submit button: send feedback then deactivate
        $(document).on('click', '#rapidtextai-btn-submit', function () {
            var selectedReason = $('input[name="rapidtextai_reason"]:checked').val();
            var customText     = $.trim($customTextarea.val());

            if (!selectedReason) return;

            $submitBtn.prop('disabled', true).text('Sending...');

            $.ajax({
                url: rapidtextai_deactivate.ajaxurl,
                type: 'POST',
                data: {
                    action: 'rapidtextai_deactivate_feedback',
                    nonce: rapidtextai_deactivate.nonce,
                    reason: selectedReason,
                    user_message: customText || selectedReason
                },
                success: function () {
                    // Show thank you briefly then redirect
                    $form.hide();
                    $footer.hide();
                    $thanks.addClass('active');
                    setTimeout(function () {
                        window.location.href = deactivateUrl;
                    }, 800);
                },
                error: function () {
                    // Even if AJAX fails, proceed with deactivation
                    window.location.href = deactivateUrl;
                }
            });
        });

        // Close modal when clicking outside
        $(document).on('click', '#rapidtextai-deactivate-overlay', function (e) {
            if ($(e.target).is('#rapidtextai-deactivate-overlay')) {
                // Don't close on outside click — user must choose
            }
        });
    }

    function interceptDeactivation() {
        // Find the deactivation link for our plugin
        var $deactivateLink = $('tr[data-plugin="' + pluginSlug + '"] .deactivate a, ' +
                                  'tr[data-slug="ai-text-block"] .deactivate a');

        if (!$deactivateLink.length) {
            // Fallback: find by href
            $deactivateLink = $('a[href*="action=deactivate"][href*="' + pluginSlug + '"]');
        }

        if (!$deactivateLink.length) return;

        $deactivateLink.on('click', function (e) {
            e.preventDefault();
            deactivateUrl = $(this).attr('href');
            $overlay.addClass('active');
        });
    }

    // Init on plugins page
    $(function () {
        if (typeof rapidtextai_deactivate === 'undefined') return;
        createModal();
        bindEvents();
        interceptDeactivation();
    });

})(jQuery);
