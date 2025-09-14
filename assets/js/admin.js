// File: my-excerpt-ai.js
// Make sure this file is enqueued properly in the Gutenberg editor (see step 2).

( function() {
	const { createElement, useState } = wp.element;
	const { createRoot } = wp.element;
    const { subscribe, select } = wp.data;

	/**
	 * A simple example component that displays a slider, current value,
	 * and two buttons (Generate, Discard).
	 */
	const RapidTextExcerptAi = () => {
		const [sliderVal, setSliderVal] = useState(50);

        const [tempExcerpt, setTempExcerpt] = useState('');
		const [showButtons, setShowButtons] = useState(false); // Add this new state

        const handleGenerate = () => {
            const { select } = wp.data;
            const postId = select('core/editor').getCurrentPostId();
            jQuery.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
            action: 'rapidtextai_get_excerpt',
			nonce: rapidtextai_ajax.nonce,
            post_id: postId,
            excerpt_length: sliderVal
            },
            success: function(response) {
            if (response.success) {
                setTempExcerpt(response.data.excerpt);
                // can also put the response in the textarea if the id of text area is inspector-textarea-control-0
                 jQuery('#inspector-textarea-control-0').val(response.data.excerpt);
				 setShowButtons(true); // Show buttons after successful generation
                // Don't update excerpt immediately, just show in temp area
            } else {
                alert('Error: ' + response.data.message);
            }
            },
            error: function() {
			// Use wp.i18n for internationalization
			alert(wp.i18n.__('Network error occurred while generating excerpt', 'wp-eazyai-chatbot'));
            }
            });
			// Use wp.i18n for internationalization
			alert(wp.i18n.__('Generating with approx. ' + sliderVal + ' words, please wait...', 'wp-eazyai-chatbot'));
        };

        const handleAccept = () => {
            if (tempExcerpt) {
            // Update the excerpt only when accepting
            wp.data.dispatch('core/editor').editPost({ excerpt: tempExcerpt });
            wp.data.dispatch('core/edit-post').toggleEditorPanelOpened('post-excerpt');
            // Click back into title/content area to ensure excerpt gets saved
            document.querySelector('.editor-post-title__input, .block-editor-default-block-appender__content').click();
            setTempExcerpt('');
			setShowButtons(false);
            }
        };

        const handleDiscard = () => {
            setTempExcerpt('');
			setShowButtons(false);
			// Use wp.i18n for internationalization
			alert(wp.i18n.__('Generated excerpt discarded', 'wp-eazyai-chatbot'));
        };

		// We’re using raw createElement calls for a quick example,
		// but you can also use JSX if you build with a bundler.
		return createElement(
			'div',
			{ className: 'my-excerpt-ai-panel' },
			[
				createElement(
					'label',
					{ key: 'label', style: { display: 'block', marginBottom: '0.5em' } },
					wp.i18n.__('Desired Length (in words):', 'wp-eazyai-chatbot')
				),
				createElement(
					'input',
					{
						key: 'slider',
						type: 'range',
						min: '10',
						max: '300',
						value: sliderVal,
						onChange: (e) => setSliderVal(e.target.value),
						style: { marginRight: '1em' }
					}
				),
				createElement(
					'span',
					{ key: 'val' },
					sliderVal + wp.i18n.__(' words', 'wp-eazyai-chatbot')
				),
				createElement(
					'p',
					{ key: 'desc', className: 'description' },
					wp.i18n.__('Adjust how many words to generate for the excerpt.', 'wp-eazyai-chatbot')
				),
				createElement(
					'button',
					{ key: 'generateBtn', className: 'button button-primary', onClick: handleGenerate, style: { marginRight: '1em' } },
					wp.i18n.__('Generate', 'wp-eazyai-chatbot')
				),
                
				showButtons && createElement(
					'button',
					{ key: 'acceptBtn', className: 'button button-primary', onClick: handleAccept, style: { marginRight: '1em' } },
					wp.i18n.__('Accept', 'wp-eazyai-chatbot')
				),
				showButtons && createElement(
					'button',
					{ key: 'discardBtn', className: 'button button-secondary', onClick: handleDiscard },
					wp.i18n.__('Discard', 'wp-eazyai-chatbot')
				),
                tempExcerpt && createElement(
                    'p',
                    { key: 'tempExcerpt', style: { marginTop: '1em' } },
                    wp.i18n.__('Generated Excerpt:', 'wp-eazyai-chatbot') + tempExcerpt
                )
			]
		);
	};
    // ------------------------------------------
	// 2) Insert the React component if the excerpt panel is present
	//    and hasn't already been injected.
	// ------------------------------------------
	const insertAiIfNeeded = () => {
		// Change this selector if needed to match your WordPress version or configuration.
		const excerptPanel = document.querySelector('.editor-post-excerpt');
		if (!excerptPanel) return;

		// Avoid duplicate insertion
		if (!excerptPanel.querySelector('.my-excerpt-ai-panel-container')) {
			const container = document.createElement('div');
			container.classList.add('my-excerpt-ai-panel-container');
			container.style.marginTop = '1em';

			excerptPanel.appendChild(container);

			// Mount our React component
			const root = createRoot(container);
			root.render(createElement(RapidTextExcerptAi));
		}
	};
	/**
	 * 2. Wait for the Block Editor to be “ready,”
	 *    then look for the excerpt panel DOM element.
	 */
	wp.domReady(() => {
		insertAiIfNeeded();

		// ------------------------------------------
		// 4) Subscribe to WP data changes:
		//    If the excerpt panel becomes enabled (or re-enabled),
		//    we attempt insertion again.
		// ------------------------------------------
		subscribe(() => {
			// Checks if the excerpt panel is currently enabled.
			const isEnabled = select('core/edit-post')?.isEditorPanelEnabled('post-excerpt');
			if (isEnabled) {
				insertAiIfNeeded();
			}
		});
	});
} )();