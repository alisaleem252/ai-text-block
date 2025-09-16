jQuery(document).ready(function($) {
    // Initialize all chatbots on the page
    $('.rapidtextai-chatbot-widget').each(function() {
        initializeChatbot($(this));
    });

    function initializeChatbot($widget) {
        const chatbotId = $widget.data('chatbot-id');
        const settings = $widget.data('settings');
        const $toggle = $widget.find('.chatbot-toggle');
        const $container = $widget.find('.chatbot-container');
        const $messages = $widget.find('.chatbot-messages');
        const $input = $widget.find('.chatbot-input-field');
        const $sendBtn = $widget.find('.chatbot-send');
        const $closeBtn = $widget.find('.chatbot-close');

        let conversationHistory = [];
        let isOpen = false;

        // Auto-open if enabled
        if (settings.auto_open) {
            setTimeout(function() {
                openChatbot();
            }, settings.auto_open_delay || 3000);
        }

        // Toggle chatbot
        $toggle.on('click', function() {
            if (isOpen) {
                closeChatbot();
            } else {
                openChatbot();
            }
        });

        // Close chatbot
        $closeBtn.on('click', function() {
            closeChatbot();
        });

        // Send message on button click
        $sendBtn.on('click', function() {
            sendMessage();
        });

        // Send message on Enter key
        $input.on('keypress', function(e) {
            if (e.which === 13 && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });

        function openChatbot() {
            $container.addClass('open');
            $toggle.addClass('open');
            isOpen = true;

            // Add welcome message if no messages exist
            if ($messages.find('.message').length === 0 && settings.welcome_message) {
                addMessage('assistant', settings.welcome_message);
            }

            // Focus input
            setTimeout(function() {
                $input.focus();
            }, 300);
        }

        function closeChatbot() {
            $container.removeClass('open');
            $toggle.removeClass('open');
            isOpen = false;
        }

        function sendMessage() {
            const message = $input.val().trim();
            if (!message) return;

            // Add user message to chat
            addMessage('user', message);
            
            // Clear input
            $input.val('');

            // Add message to history
            conversationHistory.push({
                role: 'user',
                content: message
            });

            // Show typing indicator
            showTypingIndicator();

            // Send to server
            $.ajax({
                url: rapidtextai_chatbots_frontend.ajax_url,
                type: 'POST',
                data: {
                    action: 'rapidtextai_chatbot_message',
                    nonce: rapidtextai_chatbots_frontend.nonce,
                    chatbot_id: chatbotId,
                    message: message,
                    history: conversationHistory.slice(-10) // Keep last 10 messages
                },
                success: function(response) {
                    hideTypingIndicator();
                    
                    if (response.success) {
                        const botResponse = response.data.response;
                        addMessage('assistant', botResponse);
                        
                        // Add to history
                        conversationHistory.push({
                            role: 'assistant',
                            content: botResponse
                        });
                    } else {
                        addMessage('assistant', 'Sorry, I encountered an error. Please try again.');
                    }
                },
                error: function() {
                    hideTypingIndicator();
                    addMessage('assistant', 'Sorry, I encountered an error. Please try again.');
                }
            });
        }

        function addMessage(role, content) {
            const timestamp = new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
            const avatarHtml = settings.show_avatar && settings.avatar_url ? 
                `<img src="${settings.avatar_url}" alt="Avatar" class="message-avatar">` : '';
            
            const messageHtml = `
                <div class="chatbot-message ${role}">
                    <div class="chatbot-avatar">
                        ${role === 'assistant' && avatarHtml ? avatarHtml : (role === 'user' ? 'U' : 'AI')}
                    </div>
                    <div class="chatbot-message-content">
                        <div class="chatbot-message-text">${formatMessage(content)}</div>
                        <div class="chatbot-message-time">${timestamp}</div>
                    </div>
                </div>
            `;

            $messages.append(messageHtml);
            scrollToBottom();
        }

        function formatMessage(content) {
            // Convert URLs to links
            content = content.replace(/(https?:\/\/[^\s]+)/g, '<a href="$1" target="_blank" rel="noopener">$1</a>');
            
            // Convert line breaks to <br>
            content = content.replace(/\n/g, '<br>');
            
            return content;
        }

        function showTypingIndicator() {
            const typingHtml = `
                <div class="message message-assistant typing-indicator">
                    ${settings.show_avatar && settings.avatar_url ? 
                        `<img src="${settings.avatar_url}" alt="Avatar" class="message-avatar">` : ''}
                    <div class="message-content">
                        <div class="typing-dots">
                            <span></span>
                            <span></span>
                            <span></span>
                        </div>
                    </div>
                </div>
            `;
            $messages.append(typingHtml);
            scrollToBottom();
        }

        function hideTypingIndicator() {
            $messages.find('.typing-indicator').remove();
        }

        function scrollToBottom() {
            $messages.scrollTop($messages[0].scrollHeight);
        }

        // Apply custom styles
        applyCustomStyles();

        function applyCustomStyles() {
            const css = `
                .rapidtextai-chatbot-widget[data-chatbot-id="${chatbotId}"] {
                    --primary-color: ${settings.primary_color};
                    --text-color: ${settings.text_color};
                    --background-color: ${settings.background_color};
                }
            `;
            
            if (!$('#rapidtextai-chatbot-custom-styles-' + chatbotId).length) {
                $('<style id="rapidtextai-chatbot-custom-styles-' + chatbotId + '">' + css + '</style>').appendTo('head');
            }
        }
    }

    // Resize handler for responsive design
    $(window).on('resize', function() {
        $('.rapidtextai-chatbot-widget .chatbot-container.open').each(function() {
            const $container = $(this);
            const $widget = $container.closest('.rapidtextai-chatbot-widget');
            const settings = $widget.data('settings');
            
            // Adjust size on mobile
            if ($(window).width() <= 768) {
                $container.addClass('mobile');
            } else {
                $container.removeClass('mobile');
            }
        });
    });

    // Close chatbot when clicking outside
    $(document).on('click', function(e) {
        $('.rapidtextai-chatbot-widget .chatbot-container.open').each(function() {
            const $container = $(this);
            const $widget = $container.closest('.rapidtextai-chatbot-widget');
            
            if (!$widget.is(e.target) && $widget.has(e.target).length === 0) {
                $container.removeClass('open');
                $widget.find('.chatbot-toggle').removeClass('open');
            }
        });
    });

    // Prevent chatbot from closing when clicking inside
    $('.rapidtextai-chatbot-widget .chatbot-container').on('click', function(e) {
        e.stopPropagation();
    });

    // Handle file uploads (if implemented in future)
    $('.rapidtextai-chatbot-widget').on('change', '.chatbot-file-input', function() {
        const file = this.files[0];
        if (file) {
            // Handle file upload
            console.log('File selected:', file.name);
        }
    });

    // Handle quick reply buttons (if implemented)
    $('.rapidtextai-chatbot-widget').on('click', '.quick-reply-btn', function() {
        const $btn = $(this);
        const message = $btn.text();
        const $widget = $btn.closest('.rapidtextai-chatbot-widget');
        const $input = $widget.find('.chatbot-input');
        
        $input.val(message);
        $widget.find('.chatbot-send').click();
        
        // Remove quick reply buttons after use
        $btn.closest('.quick-replies').remove();
    });

    // Accessibility improvements
    $('.rapidtextai-chatbot-widget .chatbot-toggle').on('keydown', function(e) {
        if (e.which === 13 || e.which === 32) { // Enter or Space
            e.preventDefault();
            $(this).click();
        }
    });

    $('.rapidtextai-chatbot-widget .chatbot-send').on('keydown', function(e) {
        if (e.which === 13 || e.which === 32) { // Enter or Space
            e.preventDefault();
            $(this).click();
        }
    });

    // Handle connection errors
    $(document).ajaxError(function(event, xhr, settings, error) {
        if (settings.url.indexOf('rapidtextai_chatbot_message') !== -1) {
            $('.rapidtextai-chatbot-widget .typing-indicator').remove();
            $('.rapidtextai-chatbot-widget .chatbot-messages').append(`
                <div class="message message-assistant error">
                    <div class="message-content">
                        <div class="message-text">Connection error. Please check your internet connection and try again.</div>
                    </div>
                </div>
            `);
        }
    });
});