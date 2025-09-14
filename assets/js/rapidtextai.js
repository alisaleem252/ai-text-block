(function($){
    // Function to build form data
    function build_data(prompt, streaming = false){
        var formData = new FormData();
        formData.append('type', 'custom_prompt');
        formData.append('toneOfVoice', $('#articleTone').val()); // Adjusted to match your form fields
        formData.append('language', $('#language').val() || 'en'); // Assuming a language field or default 'en'
        formData.append('text', $('#text').val() || ''); // Assuming a text field if needed
        formData.append('temperature', $('#temperature').val() || '0.7'); // Assuming a default temperature
        formData.append('custom_prompt', prompt);
        formData.append('model', $('#modelSelection').val());
        formData.append('stream', streaming ? 'true' : 'false');

        return formData;
    }
     // Function to handle streaming response
    function handleStreamingGeneration(prompt) {
        $('#generateArticleButton').text('Generating...');
        $('#generateArticleButton').prop('disabled', true);
        
        // Create a container to show streaming content
        let streamingContainer = $('<div id="streaming-container" style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 80%; max-width: 600px; height: 400px; background: white; border: 1px solid #ccc; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.3); z-index: 9999; padding: 20px; overflow-y: auto;"><div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; border-bottom: 1px solid #eee; padding-bottom: 10px;"><h4 style="margin: 0;">Generating Article...</h4><button id="close-streaming" style="background: none; border: none; font-size: 18px; cursor: pointer; color: #666;">&times;</button></div><div id="streaming-content"></div></div>');
        
        $('#articleForm').after(streamingContainer);
        
        let accumulatedContent = '';
        
        // Build form data for streaming
        let formData = build_data(prompt, true);
        formData.append('action', 'rapidtextai_generate_article_stream');
        formData.append('nonce', rapidtextai_ajax.nonce);
        
        // Create EventSource for SSE
        // We need to convert FormData to URL params for GET request
        let params = new URLSearchParams();
        for (let [key, value] of formData) {
            params.append(key, value);
        }
        
        // Use fetch with streaming instead of EventSource for POST
        fetch(rapidtextai_ajax.ajax_url, {
            method: 'POST',
            body: formData
        }).then(response => {
            const reader = response.body.getReader();
            const decoder = new TextDecoder();
            
            function readStream() {
                return reader.read().then(({ done, value }) => {
                    if (done) {
                        // Streaming complete
                        completeGeneration(accumulatedContent);
                        return;
                    }
                    
                    const chunk = decoder.decode(value, { stream: true });
                    const lines = chunk.split('\n');
                    
                    for (let line of lines) {
                        if (line.startsWith('data: ')) {
                            const data = line.slice(6);
                            if (data === '[DONE]') {
                                completeGeneration(accumulatedContent);
                                return;
                            }
                            
                            try {
                                const parsed = JSON.parse(data);
                                if (parsed.choices && parsed.choices[0].delta && parsed.choices[0].delta.content) {
                                    const content = parsed.choices[0].delta.content;
                                    accumulatedContent += content;
                                    
                                    // Update streaming display
                                    $('#streaming-content').html(marked.parse(accumulatedContent));
                                    // Auto-scroll to bottom
                                    $('#streaming-container')[0].scrollTop = $('#streaming-container')[0].scrollHeight;
                                    

                                }
                            } catch (e) {
                                // Skip invalid JSON
                            }
                        }
                    }
                    
                    return readStream();
                });
            }
            
            return readStream();
        }).catch(error => {
            console.error('Streaming error:', error);
            $('#generateArticleButton').text('Generate');
            $('#generateArticleButton').prop('disabled', false);
            $('#streaming-container').remove();
            alert('Streaming error: ' + error.message);
        });
    }
    
    // Function to complete generation and insert content
    async function completeGeneration(content) {
        $('#generateArticleButton').text('Processing...');
        
        try {
            // Parse markdown and insert images
            let htmlContent = marked.parse(content);
            htmlContent = await insertImagesForHeadings(htmlContent);
            tempDiv = $('<div>').html(htmlContent);
            // Insert content into editor
            if (typeof tinymce !== 'undefined' && tinymce.activeEditor) {
                tinymce.activeEditor.setContent(htmlContent);
            } else if (wp.data) {
                wp.data.dispatch('core/editor').editPost({ content: htmlContent });
            } else {
                $('#content').val(htmlContent);
            }
            
            $('#streaming-container').remove();
            $('#generateArticleButton').text('Generate');
            $('#generateArticleButton').prop('disabled', false);
            
            alert('Article generated and inserted into the editor.');

        } catch (error) {
            console.error('Error processing content:', error);
            $('#generateArticleButton').text('Generate');
            $('#generateArticleButton').prop('disabled', false);
            alert('Error processing generated content.');
        }
    }
    // Function to toggle advanced options
    function toggleAdvancedOptions(event) {
        event.preventDefault();
        $('#advancedOptions').toggle();
    }

    // Function to generate the article
    function generate_article() {
        // Validate required fields
        if ($('#articleTopic').val() === '' || $('#articleKeywords').val() === '') {
            alert('Article topic and keywords cannot be empty.');
            return;
        }

        // Build the prompt
        var topic = $('#articleTopic').val();
        var keywords = $('#articleKeywords').val();
        
        // Build the prompt using available form data
        var prompt = `Generate a detailed article on the topic of ${topic} with the following keywords: ${keywords}`;
        
        // Add optional parameters to the prompt
        var callToAction = $('#callToAction').val();
        if (callToAction) prompt += ` with a call to action: ${callToAction}`;
        
        var references = $('#references').val();
        if (references) prompt += ` with references: ${references}`;
        
        var articleTone = $('#articleTone').val();
        if (articleTone) prompt += ` with the tone of voice: ${articleTone}`;
        
        var targetAudience = $('#targetAudience').val();
        if (targetAudience) prompt += ` for the target audience: ${targetAudience}`;
        
        var articleLength = $('#articleLength').val();
        if (articleLength) prompt += ` with the article length: ${articleLength}`;
        
        var writingStyle = $('#writingStyle').val();
        if (writingStyle) prompt += ` with the writing style: ${writingStyle}`;
        
        var articleStructure = $('#articleStructure').val();
        if (articleStructure) prompt += ` with the article structure: ${articleStructure}`;
        
        var internalLinks = $('#internalLinks').val();
        if (internalLinks) prompt += ` with internal links: ${internalLinks}`;
        
        var externalLinks = $('#externalLinks').val();
        if (externalLinks) prompt += ` with external links: ${externalLinks}`;


        // Use streaming generation
        handleStreamingGeneration(prompt);

        return;

    }

    async function insertImagesForHeadings(content) {
        // Create a temporary div to hold the content
        let tempDiv = $('<div></div>').html(content);
        
        // Find all headings
        let headings = tempDiv.find("h2, h3, h4");
        let promises = [];
        
        // Process each heading
        headings.each(function(index) {
            let headingText = $(this).text();
            let headingElement = $(this);
            // ignore if the heading is Conclusion
            if (headingText.toLowerCase().includes("conclusion") || 
                headingText.toLowerCase().includes("conclude") || 
                headingText.toLowerCase().includes("introduction") || 
                headingText.toLowerCase().includes("summary")) {
                return;
            }
            // Create a promise for each API call
            let promise = new Promise((resolve) => {
                // Call Google PSE API
                $.ajax({
                    url: "https://app.rapidtextai.com/openai/customsearch?gigsixkey="+rapidtextai_ajax.api_key,
                    data: {
                        searchType: "image",
                        q: headingText,
                        num: Math.floor(Math.random() * 5) + 1
                    },
                    method: "GET",
                    success: function(response) {
                        if (response.items && response.items.length > 0) {
                            let imageInserted = false;
                            let itemIndex = Math.floor(Math.random() * response.items.length);

                            let image = response.items[itemIndex];
                            
                            let imageHtml = `
                                <div class="inserted-image" style="margin: 10px 0; text-align: center;">
                                    <img src="${image.link}" alt="${headingText}" style="max-width:100%; height:auto; border-radius:5px; box-shadow:0 2px 4px rgba(0,0,0,0.2);">
                                    <p style="font-size: 12px; color: #666;">Source: <a href="${image.image.contextLink}" target="_blank">${image.displayLink}</a></p>
                                </div>
                            `;
                                
                                // Insert image before heading
                                headingElement.before(imageHtml);
                                imageInserted = true;
                                resolve();
                                 
                        }
                        resolve();
                    },
                    error: function() {
                        console.log("Error fetching image for:", headingText);
                        resolve(); // Resolve even on error to continue
                    }
                });
            });
            
            promises.push(promise);
        });
        
        // Wait for all API calls to complete
        await Promise.all(promises);
        
        // Return the modified content
        return tempDiv.html();
    }


    // Attach event handlers
    $(document).ready(function(){
        $('#showAdvancedOptions').on('click', function(event) {
            event.preventDefault();
            $('#advancedOptions').toggle();
        });
        
        $('#generateArticleButton').on('click', function(event){
            event.preventDefault();
            generate_article();
        });
        $('#regenerateImagesButton').on('click', function(event){
            event.preventDefault();
            var button = $(this);
            // disable button
            button.prop('disabled', true);
            // Get content from WordPress editor
            let currentContent = '';

            if (typeof tinymce !== 'undefined' && tinymce.activeEditor) {
                // Classic Editor (TinyMCE)
                currentContent = tinymce.activeEditor.getContent();
            } else if (wp.data && wp.data.select('core/editor')) {
                // Block Editor (Gutenberg)
                currentContent = wp.data.select('core/editor').getEditedPostContent();
            } else {
                // Fallback for other cases
                currentContent = $('#content').val() || '';
            }

            if (!currentContent) {
                alert('No content found in the editor to regenerate images for.');
                return;
            }

            // Process the content to regenerate images
            insertImagesForHeadings(currentContent).then(function(updatedContent) {
                // Insert the updated content back into the editor
                if (typeof tinymce !== 'undefined' && tinymce.activeEditor) {
                    tinymce.activeEditor.setContent(updatedContent);
                } else if (wp.data) {
                    wp.data.dispatch('core/editor').editPost({ content: updatedContent });
                } else {
                    $('#content').val(updatedContent);
                }
                
                alert('Images regenerated successfully!');
                button.prop('disabled', false);
            }).catch(function(error) {
                console.error('Error regenerating images:', error);
                alert('Error regenerating images.');
                button.prop('disabled', false);
            });

        });
    });
})(jQuery);
