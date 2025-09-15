jQuery(document).ready(function($){
    $('.rapidtextai-chatbot').each(function(){
        var container = $(this);
        container.on('click', '.rapidtextai-chatbot-send', function(){
            var message = container.find('.rapidtextai-chatbot-input').val();
            if(!message){ return; }
            var messagesDiv = container.find('.rapidtextai-chatbot-messages');
            messagesDiv.append('<div class="rapidtextai-user">'+ $('<div/>').text(message).html() +'</div>');
            container.find('.rapidtextai-chatbot-input').val('');
            $.post(rapidtextaiChatbot.ajax_url, {
                action: 'rapidtextai_chatbot_message',
                nonce: rapidtextaiChatbot.nonce,
                chatbot_id: container.data('chatbot'),
                message: message
            }, function(res){
                if(res.success && res.data.reply){
                    messagesDiv.append('<div class="rapidtextai-assistant">'+ $('<div/>').text(res.data.reply).html() +'</div>');
                } else {
                    messagesDiv.append('<div class="rapidtextai-error">'+ rapidtextaiChatbot.error +'</div>');
                }
            });
        });
    });
});
