(function ($) {
    'use strict';

    var iconUrl = rapidtextai_modal.icon_url;

    /* ── Modal helpers ── */
    function openModal() {
        $('#rapidtextai-modal-overlay').addClass('rtai-open');
        $('body').css('overflow', 'hidden');
    }

    function closeModal() {
        $('#rapidtextai-modal-overlay').removeClass('rtai-open');
        $('body').css('overflow', '');
    }

    /* ── Button injection ── */
    function addGenerateButton() {
        if ($('.rapidtextai-generate-article-btn').length) return;

        var $btn = $('<button>', {
            type: 'button',
            class: 'rapidtextai-generate-article-btn components-button is-primary'
        }).html(
            '<img src="' + iconUrl + '" width="16" height="16" alt=""> Generate Article'
        ).css({
            width: '100%',
            marginTop: '10px',
            justifyContent: 'center'
        }).on('click', openModal);

        // Gutenberg: featured image panel in the sidebar
        var $gbPanel = $('.editor-post-featured-image, .components-panel__body:has(.editor-post-featured-image)');
        if ($gbPanel.length) {
            $gbPanel.last().append($btn);
            return;
        }

        // Classic editor: inside the Featured Image meta box
        var $classic = $('#postimagediv .inside');
        if ($classic.length) {
            $classic.append($btn);
            return;
        }

        // Last resort: after the publish box
        var $publish = $('#submitdiv');
        if ($publish.length) {
            $publish.after($btn);
        }
    }

    /* ── Boot ── */
    $(document).ready(function () {

        /* Modal close handlers */
        $(document).on('click', '#rapidtextai-modal-close', closeModal);
        $(document).on('keydown', function (e) {
            if (e.key === 'Escape') closeModal();
        });
        $(document).on('click', '#rapidtextai-modal-overlay', function (e) {
            if (e.target === this) closeModal();
        });

        /* Wait for Gutenberg or detect Classic Editor */
        function tryInit() {
            if (typeof wp !== 'undefined' && wp.data && wp.data.select('core/editor')) {
                // Gutenberg is live — use MutationObserver for dynamic sidebar
                var observer = new MutationObserver(addGenerateButton);
                observer.observe(document.body, { childList: true, subtree: true });
                setTimeout(addGenerateButton, 800);
            } else if ($('#postimagediv').length || $('#submitdiv').length) {
                // Classic editor — DOM is already static
                addGenerateButton();
            } else {
                setTimeout(tryInit, 400);
            }
        }

        tryInit();
    });

})(jQuery);
