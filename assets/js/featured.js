(function($) {
    'use strict';

    // Wait for WordPress to be ready
    $(document).ready(function() {
        // Wait for Gutenberg editor to be fully loaded
        const waitForGutenberg = () => {
            if (typeof wp !== 'undefined' && wp.data && wp.data.select('core/editor')) {
                initFeaturedImageAI();
            } else {
                setTimeout(waitForGutenberg, 500);
            }
        };
        waitForGutenberg();
    });

    function initFeaturedImageAI() {
        const { select, dispatch } = wp.data;
        const { __ } = wp.i18n;
        
        // Add AI Featured Image button to the featured image panel
        const addAIFeaturedImageButton = () => {
            // Find the featured image panel
            const featuredImagePanel = $('.editor-post-featured-image, .components-panel__body:has(.editor-post-featured-image)');
            
            if (featuredImagePanel.length && !featuredImagePanel.find('.rapidtextai-featured-image-btn').length) {
                const aiButton = $(`
                    <div class="rapidtextai-featured-image-container" style="margin-top: 10px;">
                        <button type="button" class="rapidtextai-featured-image-btn components-button is-secondary" style="width: 100%;">
                            <span class="dashicons dashicons-admin-media" style="margin-right: 5px;"></span>
                            ${__('Grab Featured Image with AI', 'rapidtextai')}
                        </button>
                        <div class="rapidtextai-featured-loading" style="display: none; text-align: center; margin-top: 10px;">
                            <span class="spinner is-active" style="float: none; margin: 0;"></span>
                            <p style="margin: 5px 0 0 0; font-size: 12px;">${__('Grabbing featured image...', 'rapidtextai')}</p>
                        </div>
                    </div>
                `);
                
                featuredImagePanel.append(aiButton);
            }
        };

        // Add observer to watch for DOM changes (Gutenberg updates)
        const observer = new MutationObserver(() => {
            addAIFeaturedImageButton();
        });

        // Start observing
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });

        // Initial button addition
        setTimeout(addAIFeaturedImageButton, 1000);

        // Handle AI featured image generation
        $(document).on('click', '.rapidtextai-featured-image-btn', function(e) {
            e.preventDefault();
            
            const $button = $(this);
            const $loading = $button.siblings('.rapidtextai-featured-loading');
            
            // Get post content for context
            const postContent = select('core/editor').getEditedPostContent();
            const postTitle = select('core/editor').getEditedPostAttribute('title');
            
            if (!postContent && !postTitle) {
                alert(__('Please add some content to your post before generating a featured image.', 'rapidtextai'));
                return;
            }

            // Extract topic from headings or use title/content
            let topic = extractTopicFromContent(postContent, postTitle);
            
            if (!topic) {
                alert(__('Unable to determine topic for image generation. Please add a title or headings to your post.', 'rapidtextai'));
                return;
            }

            // Show loading state
            $button.prop('disabled', true);
            $loading.show();

            // Make AJAX request to get featured image
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'rapidtextai_get_featured_image',
                    topic: topic,
                    nonce: rapidtextai_ajax.nonce
                },
                success: function(response) {
                    console.log(response);
                    if (response.success && response.data && response.data.link) {
                        // Set the featured image
                        setFeaturedImageFromUrl(response.data.link, postTitle || topic);
                    } else {
                        alert(__('Failed to grab featured image: ', 'rapidtextai') + (response.data && response.data.message ? response.data.message : 'Unknown error'));
                    }
                },
                error: function(xhr, status, error) {
                    console.error('RapidTextAI Featured Image Error:', error);
                    alert(__('Error grabbing featured image. Please try again.', 'rapidtextai'));
                },
                complete: function() {
                    // Hide loading state
                    $button.prop('disabled', false);
                    $loading.hide();
                }
            });
        });
    }

    // Extract topic from post content
    function extractTopicFromContent(content, title) {
        // First, try to get topic from title if available
        if (title && title.trim()) {
            return title.trim();
        }

        // Extract headings from content
        const headings = [];
        const tempDiv = $('<div>').html(content);
        
        // Get all headings
        tempDiv.find('h1, h2, h3, h4, h5, h6').each(function() {
            const headingText = $(this).text().trim();
            if (headingText && headingText.length > 3) {
                headings.push(headingText);
            }
        });

        // Use first meaningful heading
        if (headings.length > 0) {
            return headings[0];
        }

        // Fallback: extract first sentence or meaningful text from content
        const plainText = tempDiv.text().replace(/\s+/g, ' ').trim();
        if (plainText) {
            // Get first sentence or first 100 characters
            const firstSentence = plainText.split('.')[0];
            if (firstSentence && firstSentence.length > 10) {
                return firstSentence.trim();
            }
            
            // Fallback to first 100 characters
            return plainText.substring(0, 100).trim();
        }

        return null;
    }

    // Set featured image from URL
    function setFeaturedImageFromUrl(imageUrl, altText) {
        if (!imageUrl) return;

        const { select, dispatch } = wp.data;
        
        
        // Download image and convert to blob
        // Use WordPress AJAX endpoint to upload image from URL
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
            action: 'rapidtextai_upload_image_from_url',
            image_url: imageUrl,
            filename: (altText || 'featured-image').replace(/[^a-z0-9]/gi, '-').toLowerCase() + '.jpg',
            post_id: select('core/editor').getCurrentPostId(),
            nonce: rapidtextai_ajax.nonce
            },
            success: function(response) {
            if (response.success && response.data && response.data.attachment_id) {
                // Set as featured image
                dispatch('core/editor').editPost({
                featured_media: parseInt(response.data.attachment_id)
                });
                
                // Show success message
                dispatch('core/notices').createSuccessNotice(
                wp.i18n.__('Featured image grabbed and set successfully!', 'rapidtextai'),
                { type: 'snackbar' }
                );
            } else {
                throw new Error(response.data && response.data.message ? response.data.message : 'Upload failed');
            }
            },
            error: function(xhr, status, error) {
            console.error('Error uploading featured image:', error);
            dispatch('core/notices').createErrorNotice(
                wp.i18n.__('Failed to upload featured image. Please try again.', 'rapidtextai'),
                { type: 'snackbar' }
            );
            }
        });
    }

})(jQuery);