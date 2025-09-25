jQuery(document).ready(function($) {
    // Initialize tabs
    $('#chatbot-tabs').tabs();
    
    // Initialize color pickers
    $('.color-picker').wpColorPicker();
    
    // Make knowledge base items sortable
    $('#knowledge-base-container').sortable({
        handle: '.knowledge-item-header',
        placeholder: 'knowledge-item-placeholder'
    });
    
    // Knowledge Base functionality
    let knowledgeIndex = $('#knowledge-base-container .knowledge-item').length;
    
    $('#add-knowledge').on('click', function() {
        const template = $('#knowledge-template').html();
        const html = template.replace(/{{index}}/g, knowledgeIndex);
        $('#knowledge-base-container').append(html);
        knowledgeIndex++;
    });
    
    $(document).on('click', '.remove-knowledge', function() {
        $(this).closest('.knowledge-item').remove();
    });
    
    $(document).on('input', '.knowledge-item input[type="text"]', function() {
        const title = $(this).val() || 'New Document';
        $(this).closest('.knowledge-item').find('.knowledge-item-title').text(title);
    });
    
    // Tools functionality
    let toolIndex = $('#tools-container .tool-item').length;
    // start with 0 if no tools exist
    if (toolIndex === 0) {
        toolIndex = 0;
    } else {
        toolIndex = Math.max(...$('#tools-container .tool-item').map(function() {
            return parseInt($(this).data('index'));
        }).get()) + 1;
    }

    $('#add-tool').on('click', function() {
        const html = `
            <div class="tool-item" data-index="${toolIndex}">
                <div class="tool-item-header">
                    <span class="tool-item-title">${toolIndex} - New Tool</span>
                    <button type="button" class="button test-tool">Test</button>
                    <button type="button" class="button remove-tool">Remove</button>
                </div>
                <div class="tool-item-content">
                    <table class="form-table">
                        <tr>
                            <th><label>Tool Name</label></th>
                            <td><input type="text" name="tools[${toolIndex}][name]" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th><label>Description</label></th>
                            <td><textarea name="tools[${toolIndex}][description]" rows="3" class="large-text"></textarea></td>
                        </tr>
                        <tr>
                            <th><label>API URL</label></th>
                            <td><input type="url" name="tools[${toolIndex}][api_url]" class="regular-text" placeholder="https://api.example.com/endpoint"></td>
                        </tr>
                        <tr>
                            <th><label>HTTP Method</label></th>
                            <td>
                                <select name="tools[${toolIndex}][method]" class="regular-text">
                                    <option value="GET">GET</option>
                                    <option value="POST" selected>POST</option>
                                    <option value="PUT">PUT</option>
                                    <option value="PATCH">PATCH</option>
                                    <option value="DELETE">DELETE</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label>Headers (JSON)</label></th>
                            <td>
                                <textarea name="tools[${toolIndex}][headers]" rows="3" class="large-text" placeholder='[{"key": "Authorization", "value": "Bearer your-token"}, {"key": "Content-Type", "value": "application/json"}]'></textarea>
                                <p class="description">Optional HTTP headers as JSON array</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label>Response Field</label></th>
                            <td>
                                <input type="text" name="tools[${toolIndex}][response_field]" class="regular-text" placeholder="data.result">
                                <p class="description">Optional: Specific field to extract from JSON response</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label>Parameters (JSON)</label></th>
                            <td><textarea name="tools[${toolIndex}][parameters]" rows="5" class="large-text"></textarea></td>
                        </tr>
                    </table>
                </div>
            </div>
        `;
        $('#tools-container').append(html);
        // Add sample weather API button after the new tool is added
        const $newTool = $('#tools-container .tool-item').last();
        $newTool.find('.tool-item-content').append(`
            <div style="margin-top: 10px; border-top: 1px solid #ddd; padding-top: 10px;">
                <button type="button" class="button insert-weather-sample">Insert Weather API Sample</button>
            </div>
        `);
        toolIndex++;
    });
    $(document).on('click', '.insert-weather-sample', function() {
        const $toolItem = $(this).closest('.tool-item');
        const index = $toolItem.data('index');
        
        // Fill in the weather API sample data
        $toolItem.find(`input[name="tools[${index}][name]"]`).val('get_weather');
        $toolItem.find(`textarea[name="tools[${index}][description]"]`).val('Get current weather information for a city');
        $toolItem.find(`input[name="tools[${index}][api_url]"]`).val('https://api.openweathermap.org/data/2.5/weather');
        $toolItem.find(`select[name="tools[${index}][method]"]`).val('GET');
        $toolItem.find(`textarea[name="tools[${index}][headers]"]`).val('[\n    {"key": "Content-Type", "value": "application/json"}\n]');
        $toolItem.find(`input[name="tools[${index}][response_field]"]`).val('weather.0.description');
        $toolItem.find(`textarea[name="tools[${index}][parameters]"]`).val('{\n    "type": "object",\n    "properties": {\n        "q": {\n            "type": "string",\n            "description": "City name, state code and country code divided by comma"\n        },\n        "appid": {\n            "type": "string",\n            "description": "Your API key"\n        },\n        "units": {\n            "type": "string",\n            "enum": ["standard", "metric", "imperial"],\n            "description": "Temperature units"\n        }\n    },\n    "required": ["q", "appid"]\n}');
        
        // Update the title
        $toolItem.find('.tool-item-title').text('get_weather');
        
        // Remove the sample button after use
        $(this).parent().remove();
    });
    $(document).on('click', '.remove-tool', function() {
        $(this).closest('.tool-item').remove();
    });
    
    $(document).on('input', '.tool-item input[name*="[name]"]', function() {
        const title = $(this).val() || 'New Tool';
        $(this).closest('.tool-item').find('.tool-item-title').text(title);
    });
    
    
    $(document).on('click', '.remove-function', function() {
        $(this).closest('.php-function-item').remove();
    });
    
    $(document).on('input', '.php-function-item input[name*="[name]"]', function() {
        const title = $(this).val() || 'New Function';
        $(this).closest('.php-function-item').find('.php-function-item-title').text(title);
    });
    
    // Test PHP function
    $(document).on('click', '.test-function', function() {
        const $item = $(this).closest('.php-function-item');
        const code = $item.find('textarea[name*="[code]"]').val();
        const parametersJson = $item.find('textarea[name*="[parameters]"]').val();
        
        let testArgs = {};
        try {
            const parameters = JSON.parse(parametersJson || '{}');
            if (parameters.properties) {
                // Generate test arguments based on parameter schema
                Object.keys(parameters.properties).forEach(key => {
                    const prop = parameters.properties[key];
                    switch (prop.type) {
                        case 'string':
                            testArgs[key] = prop.example || 'test_string';
                            break;
                        case 'number':
                        case 'integer':
                            testArgs[key] = prop.example || 123;
                            break;
                        case 'boolean':
                            testArgs[key] = prop.example || true;
                            break;
                        default:
                            testArgs[key] = prop.example || null;
                    }
                });
            }
        } catch (e) {
            alert('Invalid parameters JSON format');
            return;
        }
        
    });
    
    // Refresh models
    $('#refresh-models').on('click', function() {
        const $button = $(this);
        const $select = $('#model');
        const currentValue = $select.val();
        
        $button.prop('disabled', true).text('Loading...');
        
        $.post(rapidtextai_chatbots_ajax.ajax_url, {
            action: 'rapidtextai_get_models',
            nonce: rapidtextai_chatbots_ajax.nonce
        }, function(response) {
            if (response.success) {
                $select.empty();
                response.data.forEach(function(model) {
                    const selected = model.id === currentValue ? 'selected' : '';
                    $select.append(`<option value="${model.id}" ${selected}>${model.id}</option>`);
                });
            } else {
                alert('Failed to load models: ' + response.data);
            }
        }).always(function() {
            $button.prop('disabled', false).text('Refresh Models');
        });
    });
    
    // Form validation
    $('#chatbot-form').on('submit', function(e) {
        let isValid = true;
        const errors = [];
        
        // Validate required fields
        if (!$('#name').val().trim()) {
            errors.push('Chatbot name is required');
            isValid = false;
        }
        
        // Validate JSON fields
        $('textarea[name*="[parameters]"]').each(function() {
            const $textarea = $(this);
            const value = $textarea.val().trim();
            if (value) {
                try {
                    JSON.parse(value);
                } catch (e) {
                    errors.push('Invalid JSON in parameters field');
                    $textarea.focus();
                    isValid = false;
                    return false;
                }
            }
        });
        
        // Validate temperature range
        const temperature = parseFloat($('#temperature').val());
        if (temperature < 0 || temperature > 2) {
            errors.push('Temperature must be between 0 and 2');
            isValid = false;
        }
        
        // Validate max tokens
        const maxTokens = parseInt($('#max_tokens').val());
        if (maxTokens < 1 || maxTokens > 4000) {
            errors.push('Max tokens must be between 1 and 4000');
            isValid = false;
        }
        
        if (!isValid) {
            e.preventDefault();
            alert('Please fix the following errors:\n' + errors.join('\n'));
        }
    });
    
    // Auto-save draft functionality
    let autoSaveTimer;
    function autoSave() {
        clearTimeout(autoSaveTimer);
        autoSaveTimer = setTimeout(function() {
            const formData = $('#chatbot-form').serialize();
            localStorage.setItem('rapidtextai_chatbot_draft', formData);
        }, 2000);
    }
    
    // Trigger auto-save on form changes
    $('#chatbot-form').on('input change', 'input, textarea, select', autoSave);
    
    // Load draft on page load
    $(document).ready(function() {
        const draft = localStorage.getItem('rapidtextai_chatbot_draft');
        if (draft && confirm('A draft was found. Would you like to restore it?')) {
            // Parse and restore form data
            const params = new URLSearchParams(draft);
            params.forEach((value, key) => {
                const $field = $(`[name="${key}"]`);
                if ($field.length) {
                    if ($field.is(':checkbox')) {
                        $field.prop('checked', value === '1');
                    } else {
                        $field.val(value);
                    }
                }
            });
        }
        // if cancel create draft
        else {
            localStorage.removeItem('rapidtextai_chatbot_draft');
        }
    });
    
    // Clear draft on successful save
    $('#chatbot-form').on('submit', function() {
        localStorage.removeItem('rapidtextai_chatbot_draft');
    });
    
    // Collapsible sections
    $(document).on('click', '.knowledge-item-header, .tool-item-header, .php-function-item-header', function() {
        $(this).next('.knowledge-item-content, .tool-item-content, .php-function-item-content').slideToggle();
    });
    
    // Syntax highlighting for code editor (basic)
    $(document).on('focus', '.code-editor', function() {
        $(this).addClass('code-focused');
    });
    
    $(document).on('blur', '.code-editor', function() {
        $(this).removeClass('code-focused');
    });
    
    // Add line numbers to code editors
    $(document).on('scroll input', '.code-editor', function() {
        const $textarea = $(this);
        const lines = $textarea.val().split('\n').length;
        let lineNumbers = '';
        for (let i = 1; i <= lines; i++) {
            lineNumbers += i + '\n';
        }
        
        let $lineNumbers = $textarea.siblings('.line-numbers');
        if ($lineNumbers.length === 0) {
            $lineNumbers = $('<div class="line-numbers"></div>');
            $textarea.before($lineNumbers);
        }
        $lineNumbers.text(lineNumbers);
    });
    
    // Initialize existing code editors
    $('.code-editor').trigger('input');
    
    // Copy shortcode functionality
    $(document).on('click', '.postbox code', function() {
        const text = $(this).text();
        if (navigator.clipboard) {
            navigator.clipboard.writeText(text).then(function() {
                alert('Shortcode copied to clipboard!');
            });
        } else {
            // Fallback for older browsers
            const $temp = $('<input>');
            $('body').append($temp);
            $temp.val(text).select();
            document.execCommand('copy');
            $temp.remove();
            alert('Shortcode copied to clipboard!');
        }
    });
    
    // Help tooltips
    $('.form-table th label').each(function() {
        const $label = $(this);
        const $description = $label.closest('tr').find('.description');
        if ($description.length) {
            $label.attr('title', $description.text());
        }
    });
    
    // Real-time preview for colors
    $('.color-picker').on('change', function() {
        updatePreview();
    });
    
    function updatePreview() {
        const primaryColor = $('#primary_color').val();
       // const secondaryColor = $('#secondary_color').val();
        const textColor = $('#text_color').val();
        const backgroundColor = $('#background_color').val();
        
        // Create or update preview styles
        let $previewStyle = $('#chatbot-preview-style');
        if ($previewStyle.length === 0) {
            $previewStyle = $('<style id="chatbot-preview-style"></style>');
            $('head').append($previewStyle);
        }
        
        const css = `
            .chatbot-preview {
                background-color: ${backgroundColor};
                color: ${textColor};
                border: 2px solid ${primaryColor};
                padding: 10px;
                margin: 10px 0;
                border-radius: 5px;
            }
            .chatbot-preview .header {
                background-color: ${primaryColor};
                color: ${backgroundColor};
                padding: 5px 10px;
                margin: -10px -10px 10px -10px;
                border-radius: 3px 3px 0 0;
            }
            .chatbot-preview .button {
                background-color: ${primaryColor};
                color: ${backgroundColor};
                border: none;
                padding: 5px 10px;
                width:30px;
                height:30px;
                border-radius: 50%;
                cursor: pointer;
            }
        `;
        
        $previewStyle.text(css);
        
        // Add preview if it doesn't exist
        if ($('#chatbot-color-preview').length === 0) {
            const preview = `
                <div id="chatbot-color-preview" class="chatbot-preview">
                    <div class="header">Chatbot Preview</div>
                    <p>This is how your chatbot will look with the selected colors.</p>
                    <button class="button"><svg class="chatbot-toggle-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M20 2H4C2.9 2 2 2.9 2 4V16C2 17.1 2.9 18 4 18H6L9 21L12 18H20C21.1 18 22 17.1 22 16V4C22 2.9 21.1 2 20 2ZM20 16H11.17L9 18.17L6.83 16H4V4H20V16Z" fill="currentColor"></path>
          <circle cx="7" cy="10" r="1" fill="currentColor"></circle>
          <circle cx="12" cy="10" r="1" fill="currentColor"></circle>
          <circle cx="17" cy="10" r="1" fill="currentColor"></circle>
       </svg></button>
                </div>
            `;
            $('#tab-appearance .form-table').after(preview);
        }
    }
    
    // Initialize preview
    updatePreview();
});