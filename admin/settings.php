<div class="wrap rapidtextai-settings">
        <div class="rapidtextai-header">
            <h1><?php esc_html_e('RapidTextAI Settings', 'rapidtextai'); ?></h1>
            <p class="description"><?php esc_html_e('Configure your RapidTextAI integration to start generating AI-powered content.', 'rapidtextai'); ?></p>
        </div>

        <div class="rapidtextai-content">
            <div class="rapidtextai-card">
                <div class="rapidtextai-card-header">
                    <h2><?php esc_html_e('Authentication', 'rapidtextai'); ?></h2>
                </div>
                <div class="rapidtextai-card-body">
                    <form method="post" id="rapidtextai_auth_form">
                        <?php wp_nonce_field('rapidtextai_api_key_nonce', 'rapidtextai_api_key_nonce'); ?>
                        
                        <div class="rapidtextai-auth-section">
                            <div class="rapidtextai-auth-button-wrapper">
                                <button type="button" id="rapidtextai_auth_button" class="rapidtextai-btn rapidtextai-btn-primary">
                                    <span class="dashicons dashicons-admin-network"></span>
                                    <?php esc_html_e('Authenticate with RapidTextAI', 'rapidtextai'); ?>
                                </button>
                            </div>
                            
                            <div id="rapidtextai_status_message" class="rapidtextai-status-message"></div>
                            
                            <?php if (!empty($current_api_key)) { ?>
                                <div class="rapidtextai-notice rapidtextai-notice-success">
                                    <span class="dashicons dashicons-yes-alt"></span>
                                    <span><?php esc_html_e('API Key is already configured. You can re-authenticate to refresh your connection.', 'rapidtextai'); ?></span>
                                </div>
                                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-top: 16px;">
                                    <div class="rapidtextai-card rapidtextai-chatbots-card">
                                        <div class="rapidtextai-card-header">
                                            <h2><?php esc_html_e('AI Chatbots', 'rapidtextai'); ?></h2>
                                        </div>
                                        <div class="rapidtextai-card-body">
                                            <div class="rapidtextai-chatbots-preview">
                                                <img src="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjEyMCIgdmlld0JveD0iMCAwIDIwMCAxMjAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxyZWN0IHdpZHRoPSIyMDAiIGhlaWdodD0iMTIwIiBmaWxsPSIjRjhGOUZBIi8+CjxyZWN0IHg9IjIwIiB5PSIyMCIgd2lkdGg9IjE2MCIgaGVpZ2h0PSI4MCIgcng9IjgiIGZpbGw9IiNGRkYiIHN0cm9rZT0iI0UyRTRFNyIgc3Ryb2tlLXdpZHRoPSIxIi8+CjxyZWN0IHg9IjMwIiB5PSIzMCIgd2lkdGg9IjgwIiBoZWlnaHQ9IjEyIiByeD0iNiIgZmlsbD0iIzIyNzFCMSIvPgo8cmVjdCB4PSIzMCIgeT0iNDgiIHdpZHRoPSI2MCIgaGVpZ2h0PSI4IiByeD0iNCIgZmlsbD0iI0U5RUNFRiIvPgo8cmVjdCB4PSIxMDAiIHk9IjYwIiB3aWR0aD0iNzAiIGhlaWdodD0iMTIiIHJ4PSI2IiBmaWxsPSIjNkY0N0VCIi8+CjxyZWN0IHg9IjEwMCIgeT0iNzgiIHdpZHRoPSI1MCIgaGVpZ2h0PSI4IiByeD0iNCIgZmlsbD0iI0U5RUNFRiIvPgo8Y2lyY2xlIGN4PSIxNjAiIGN5PSI0MCIgcj0iMTAiIGZpbGw9IiMwMEE0OEEiLz4KPHBhdGggZD0iTTE1NSA0MEgxNjVNMTYwIDM1VjQ1IiBzdHJva2U9IndoaXRlIiBzdHJva2Utd2lkdGg9IjIiIHN0cm9rZS1saW5lY2FwPSJyb3VuZCIvPgo8Y2lyY2xlIGN4PSIxNzAiIGN5PSI3MCIgcj0iOCIgZmlsbD0iI0ZGNjUwMCIvPgo8cGF0aCBkPSJNMTY2IDcwTDE3NCA3MCIgc3Ryb2tlPSJ3aGl0ZSIgc3Ryb2tlLXdpZHRoPSIxLjUiIHN0cm9rZS1saW5lY2FwPSJyb3VuZCIvPgo8L3N2Zz4=" alt="AI Chatbots Preview" class="rapidtextai-chatbots-image" />
                                            </div>
                                            <p class="rapidtextai-chatbots-description">
                                                <?php esc_html_e('Create and manage multiple AI chatbots with custom tools and knowledge bases for your specific needs.', 'rapidtextai'); ?>
                                            </p>
                                            <a href="<?php echo admin_url('admin.php?page=rapidtextai-chatbots'); ?>" class="rapidtextai-btn rapidtextai-btn-primary rapidtextai-chatbots-btn">
                                                <span class="dashicons dashicons-admin-users"></span>
                                                <?php esc_html_e('Manage Chatbots', 'rapidtextai'); ?>
                                                <span class="dashicons dashicons-arrow-right-alt"></span>
                                            </a>
                                        </div>
                                    </div>
                                    <div class="rapidtextai-card rapidtextai-autoblog-card">
                                        <div class="rapidtextai-card-header">
                                            <h2><?php esc_html_e('Auto Blogging', 'rapidtextai'); ?></h2>
                                        </div>
                                        <div class="rapidtextai-card-body">
                                            <div class="rapidtextai-autoblog-preview">
                                                <img src="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjEyMCIgdmlld0JveD0iMCAwIDIwMCAxMjAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxyZWN0IHdpZHRoPSIyMDAiIGhlaWdodD0iMTIwIiBmaWxsPSIjRjhGOUZBIi8+CjxyZWN0IHg9IjE0IiB5PSIxNCIgd2lkdGg9IjE3MiIgaGVpZ2h0PSI4IiByeD0iNCIgZmlsbD0iIzJCQjJGRiIvPgo8cmVjdCB4PSIxNCIgeT0iMjgiIHdpZHRoPSIxMzAiIGhlaWdodD0iNiIgcng9IjMiIGZpbGw9IiNFOUVDRUYiLz4KPHJlY3QgeD0iMTQiIHk9IjQwIiB3aWR0aD0iMTU2IiBoZWlnaHQ9IjYiIHJ4PSIzIiBmaWxsPSIjRTlFQ0VGIi8+CjxyZWN0IHg9IjE0IiB5PSI1MiIgd2lkdGg9IjE0NCIgaGVpZ2h0PSI2IiByeD0iMyIgZmlsbD0iI0U5RUNFRiIvPgo8cmVjdCB4PSIxNCIgeT0iNjgiIHdpZHRoPSIxNzIiIGhlaWdodD0iOCIgcng9IjQiIGZpbGw9IiMyQkIyRkYiLz4KPHJlY3QgeD0iMTQiIHk9IjgyIiB3aWR0aD0iMTIwIiBoZWlnaHQ9IjYiIHJ4PSIzIiBmaWxsPSIjRTlFQ0VGIi8+CjxyZWN0IHg9IjE0IiB5PSI5NCIgd2lkdGg9IjE2MCIgaGVpZ2h0PSI2IiByeD0iMyIgZmlsbD0iI0U5RUNFRiIvPgo8Y2lyY2xlIGN4PSIxNzUiIGN5PSIyMCIgcj0iMTAiIGZpbGw9IiMxNEE4NTEiLz4KPHBhdGggZD0ibTE3MC41IDIwbDQuNS00bC00LjUgNCIgZmlsbD0id2hpdGUiLz4KPC9zdmc+" alt="Auto Blogging Preview" class="rapidtextai-autoblog-image" />
                                            </div>
                                            <p class="rapidtextai-autoblog-description">
                                                <?php esc_html_e('Set up automated content generation to create blog posts on schedule with AI-powered content.', 'rapidtextai'); ?>
                                            </p>
                                            <a href="<?php echo admin_url('admin.php?page=rapidtextai-auto-blogging'); ?>" class="rapidtextai-btn rapidtextai-btn-primary rapidtextai-autoblog-btn">
                                                <span class="dashicons dashicons-clock"></span>
                                                <?php esc_html_e('Configure', 'rapidtextai'); ?>
                                                <span class="dashicons dashicons-arrow-right-alt"></span>
                                            </a>
                                        </div>
                                    </div>
                                    
                                    <div class="rapidtextai-card rapidtextai-third-card">
                                        <div class="rapidtextai-card-header">
                                            <h2><?php esc_html_e('Add New Post', 'rapidtextai'); ?></h2>
                                        </div>
                                        <div class="rapidtextai-card-body">
                                            <div class="rapidtextai-third-preview">
                                                <img src="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjEyMCIgdmlld0JveD0iMCAwIDIwMCAxMjAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxyZWN0IHdpZHRoPSIyMDAiIGhlaWdodD0iMTIwIiBmaWxsPSIjRjhGOUZBIi8+CjxyZWN0IHg9IjE1IiB5PSIxNSIgd2lkdGg9IjE3MCIgaGVpZ2h0PSI5MCIgcng9IjgiIGZpbGw9IiNGRkYiIHN0cm9rZT0iI0UyRTRFNyIgc3Ryb2tlLXdpZHRoPSIxLjUiLz4KPHJlY3QgeD0iMjQiIHk9IjI0IiB3aWR0aD0iMTUyIiBoZWlnaHQ9IjEyIiByeD0iNiIgZmlsbD0iIzIyNzFCMSIvPgo8cmVjdCB4PSIyNCIgeT0iNDIiIHdpZHRoPSIxMjAiIGhlaWdodD0iNiIgcng9IjMiIGZpbGw9IiNFOUVDRUYiLz4KPHJlY3QgeD0iMjQiIHk9IjUyIiB3aWR0aD0iMTQwIiBoZWlnaHQ9IjYiIHJ4PSIzIiBmaWxsPSIjRTlFQ0VGIi8+CjxyZWN0IHg9IjI0IiB5PSI2MiIgd2lkdGg9IjEwMCIgaGVpZ2h0PSI2IiByeD0iMyIgZmlsbD0iI0U5RUNFRiIvPgo8cmVjdCB4PSIyNCIgeT0iNzQiIHdpZHRoPSIxMjAiIGhlaWdodD0iNiIgcng9IjMiIGZpbGw9IiNFOUVDRUYiLz4KPHJlY3QgeD0iMjQiIHk9Ijg0IiB3aWR0aD0iMTYwIiBoZWlnaHQ9IjYiIHJ4PSIzIiBmaWxsPSIjRTlFQ0VGIi8+CjxjaXJjbGUgY3g9IjE3MCIgY3k9IjM2IiByPSIxMiIgZmlsbD0iIzAwQTMyQSIvPgo8cGF0aCBkPSJNMTY1IDM2SDE3NU0xNzAgMzFWNDEiIHN0cm9rZT0id2hpdGUiIHN0cm9rZS13aWR0aD0iMi41IiBzdHJva2UtbGluZWNhcD0icm91bmQiLz4KPC9zdmc+" alt="Add New Post Preview" class="rapidtextai-third-image" />
                                            </div>
                                            <p class="rapidtextai-third-description">
                                                <?php esc_html_e('Create new posts effortlessly with RapidTextAI, Provide Topic, Keywords and Choose your favorite model.', 'rapidtextai'); ?>
                                            </p>
                                            <a href="<?php echo admin_url('post-new.php'); ?>" class="rapidtextai-btn rapidtextai-btn-primary rapidtextai-third-btn">
                                                <span class="dashicons dashicons-plus"></span>
                                                <?php esc_html_e('Create Post', 'rapidtextai'); ?>
                                                <span class="dashicons dashicons-arrow-right-alt"></span>
                                            </a>
                                        </div>
                                    </div>
                                    <div class="rapidtextai-card rapidtextai-extension-card">
                                        <div class="rapidtextai-card-header">
                                            <h2><?php esc_html_e('Chrome Extension', 'rapidtextai'); ?></h2>
                                        </div>
                                        <div class="rapidtextai-card-body">
                                            <div class="rapidtextai-extension-preview">
                                                <img src="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjEyMCIgdmlld0JveD0iMCAwIDIwMCAxMjAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxyZWN0IHdpZHRoPSIyMDAiIGhlaWdodD0iMTIwIiBmaWxsPSIjRjhGOUZBIi8+CjxjaXJjbGUgY3g9IjEwMCIgY3k9IjQ1IiByPSIyNSIgZmlsbD0iIzRDQUY1MCIvPgo8cGF0aCBkPSJNODUgNDVMOTUgNTVMMTE1IDM1IiBzdHJva2U9IndoaXRlIiBzdHJva2Utd2lkdGg9IjMiIHN0cm9rZS1saW5lY2FwPSJyb3VuZCIgc3Ryb2tlLWxpbmVqb2luPSJyb3VuZCIvPgo8cmVjdCB4PSI0MCIgeT0iNzUiIHdpZHRoPSIxMjAiIGhlaWdodD0iOCIgcng9IjQiIGZpbGw9IiM5Q0EzQUYiLz4KPHJlY3QgeD0iNjAiIHk9Ijg4IiB3aWR0aD0iODAiIGhlaWdodD0iNiIgcng9IjMiIGZpbGw9IiNFOUVDRUYiLz4KPHJlY3QgeD0iNzAiIHk9Ijk5IiB3aWR0aD0iNjAiIGhlaWdodD0iNCIgcng9IjIiIGZpbGw9IiNFOUVDRUYiLz4KPC9zdmc+" alt="Chrome Extension Preview" class="rapidtextai-extension-image" />
                                            </div>
                                            <p class="rapidtextai-extension-description">
                                                <?php esc_html_e('Install the Chrome extension to access RapidTextAI directly from your browser on any website.', 'rapidtextai'); ?>
                                            </p>
                                            <a href="https://chromewebstore.google.com/detail/rapidtextai-ai-writing-as/amkcloakmokikphcbimboccpbbjehkml" target="_blank" class="rapidtextai-btn rapidtextai-btn-primary rapidtextai-extension-btn">
                                                <span class="dashicons dashicons-admin-plugins"></span>
                                                <?php esc_html_e('Install Extension', 'rapidtextai'); ?>
                                                <span class="dashicons dashicons-external"></span>
                                            </a>
                                        </div>
                                    </div>

                                    <div class="rapidtextai-card rapidtextai-mobile-card">
                                        <div class="rapidtextai-card-header">
                                            <h2><?php esc_html_e('Mobile App', 'rapidtextai'); ?></h2>
                                        </div>
                                        <div class="rapidtextai-card-body">
                                            <div class="rapidtextai-mobile-preview">
                                                <img src="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjEyMCIgdmlld0JveD0iMCAwIDIwMCAxMjAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxyZWN0IHdpZHRoPSIyMDAiIGhlaWdodD0iMTIwIiBmaWxsPSIjRjhGOUZBIi8+CjxyZWN0IHg9Ijc1IiB5PSIxNSIgd2lkdGg9IjUwIiBoZWlnaHQ9IjkwIiByeD0iOCIgZmlsbD0iIzFEMjMyNyIvPgo8cmVjdCB4PSI3OSIgeT0iMjIiIHdpZHRoPSI0MiIgaGVpZ2h0PSI2MCIgcng9IjQiIGZpbGw9IiM0Mjg1RjQiLz4KPGNpcmNsZSBjeD0iMTAwIiBjeT0iOTQiIHI9IjQiIGZpbGw9IiM5Q0EzQUYiLz4KPHJlY3QgeD0iODMiIHk9IjI4IiB3aWR0aD0iMzQiIGhlaWdodD0iNCIgcng9IjIiIGZpbGw9IndoaXRlIi8+CjxyZWN0IHg9Ijg1IiB5PSIzNSIgd2lkdGg9IjMwIiBoZWlnaHQ9IjMiIHJ4PSIxLjUiIGZpbGw9InJnYmEoMjU1LDI1NSwyNTUsMC44KSIvPgo8cmVjdCB4PSI4NSIgeT0iNDAiIHdpZHRoPSIyNiIgaGVpZ2h0PSIzIiByeD0iMS41IiBmaWxsPSJyZ2JhKDI1NSwyNTUsMjU1LDAuOCkiLz4KPHJlY3QgeD0iODUiIHk9IjQ2IiB3aWR0aD0iMjgiIGhlaWdodD0iMyIgcng9IjEuNSIgZmlsbD0icmdiYSgyNTUsMjU1LDI1NSwwLjgpIi8+CjxyZWN0IHg9Ijg1IiB5PSI1NiIgd2lkdGg9IjIwIiBoZWlnaHQ9IjEwIiByeD0iNSIgZmlsbD0iIzAwQTMyQSIvPgo8L3N2Zz4=" alt="Mobile App Preview" class="rapidtextai-mobile-image" />
                                            </div>
                                            <p class="rapidtextai-mobile-description">
                                                <?php esc_html_e('Download the mobile app to access RapidTextAI on-the-go and generate content from anywhere.', 'rapidtextai'); ?>
                                            </p>
                                            <a href="https://play.google.com/store/apps/details?id=com.rapidtextai.mobile" target="_blank" class="rapidtextai-btn rapidtextai-btn-primary rapidtextai-mobile-btn">
                                                <span class="dashicons dashicons-smartphone"></span>
                                                <?php esc_html_e('Download App', 'rapidtextai'); ?>
                                                <span class="dashicons dashicons-external"></span>
                                            </a>
                                        </div>
                                    </div>

                                    <div class="rapidtextai-card rapidtextai-chat-card">
                                        <div class="rapidtextai-card-header">
                                            <h2><?php esc_html_e('AI Chat Interface', 'rapidtextai'); ?></h2>
                                        </div>
                                        <div class="rapidtextai-card-body">
                                            <div class="rapidtextai-chat-preview">
                                                <img src="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjEyMCIgdmlld0JveD0iMCAwIDIwMCAxMjAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxyZWN0IHdpZHRoPSIyMDAiIGhlaWdodD0iMTIwIiBmaWxsPSIjRjhGOUZBIi8+CjxyZWN0IHg9IjEwIiB5PSIxMCIgd2lkdGg9IjEwMCIgaGVpZ2h0PSIxNSIgcng9IjciIGZpbGw9IiMyMjcxQjEiLz4KPHJlY3QgeD0iMTAiIHk9IjMwIiB3aWR0aD0iMTQwIiBoZWlnaHQ9IjEwIiByeD0iNSIgZmlsbD0iI0U5RUNFRiIvPgo8cmVjdCB4PSI3MCIgeT0iNTAiIHdpZHRoPSIxMjAiIGhlaWdodD0iMTUiIHJ4PSI3IiBmaWxsPSIjNjQ2OTcwIi8+CjxyZWN0IHg9IjcwIiB5PSI3MCIgd2lkdGg9IjEwMCIgaGVpZ2h0PSIxMCIgcng9IjUiIGZpbGw9IiNFOUVDRUYiLz4KPGNpcmNsZSBjeD0iMTc1IiBjeT0iOTUiIHI9IjE1IiBmaWxsPSIjMjI3MUIxIi8+CjxwYXRoIGQ9Ik0xNzEgOTFIMTc5VjEwMCIgc3Ryb2tlPSJ3aGl0ZSIgc3Ryb2tlLXdpZHRoPSIyIiBzdHJva2UtbGluZWNhcD0icm91bmQiLz4KPC9zdmc+" alt="Chat Interface Preview" class="rapidtextai-chat-image" />
                                            </div>
                                            <p class="rapidtextai-chat-description">
                                                <?php esc_html_e('Access the AI chat interface to have conversations and get instant responses from RapidTextAI.', 'rapidtextai'); ?>
                                            </p>
                                            <a href="https://app.rapidtextai.com/api?action=mobile-login&gigsixkey=<?php echo $current_api_key ?>" target="_blank" class="rapidtextai-btn rapidtextai-btn-primary rapidtextai-chat-btn">
                                                <span class="dashicons dashicons-format-chat"></span>
                                                <?php esc_html_e('Ask RapidTextAI', 'rapidtextai'); ?>
                                                <span class="dashicons dashicons-external"></span>
                                            </a>
                                        </div>
                                    </div>

                                </div>

                                
                            <?php } ?>
                        </div>
                    </form>
                </div>
            </div>

            <div class="rapidtextai-card">
                <div class="rapidtextai-card-header">
                    <h2><?php esc_html_e('Account Status (Monthly)', 'rapidtextai'); ?></h2>
                </div>
                <div class="rapidtextai-card-body">
                    <div id="rapidtextai_status" class="rapidtextai-status-loading">
                        <div class="rapidtextai-spinner"></div>
                        <span><?php esc_html_e('Loading account information...', 'rapidtextai'); ?></span>
                    </div>
                </div>
            </div>

            <div class="rapidtextai-card">
                <div class="rapidtextai-card-header">
                    <h2><?php esc_html_e('Getting Started', 'rapidtextai'); ?></h2>
                </div>
                <div class="rapidtextai-card-body">
                    <div class="rapidtextai-video-wrapper">
                        <iframe src="https://www.youtube.com/embed/0zAP0qCnk4w" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                    </div>
                    <p class="rapidtextai-help-text">
                        <?php esc_html_e('Watch this tutorial to learn how to generate articles using RapidTextAI.', 'rapidtextai'); ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <style>
        .rapidtextai-settings {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        .rapidtextai-header {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #e2e4e7;
        }

        .rapidtextai-header h1 {
            color: #1d2327;
            font-size: 28px;
            font-weight: 600;
            margin: 0 0 8px 0;
        }

        .rapidtextai-header .description {
            color: #646970;
            font-size: 16px;
            margin: 0;
        }

        .rapidtextai-content {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 24px;
            max-width: 1200px;
        }

        .rapidtextai-card {
            background: #fff;
            border: 1px solid #e2e4e7;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
            overflow: hidden;
        }

        .rapidtextai-card-header {
            background: #f8f9fa;
            padding: 20px 24px;
            border-bottom: 1px solid #e2e4e7;
        }

        .rapidtextai-card-header h2 {
            color: #1d2327;
            font-size: 18px;
            font-weight: 600;
            margin: 0;
        }

        .rapidtextai-card-body {
            padding: 24px;
        }

        .rapidtextai-auth-section {
            text-align: center;
        }

        .rapidtextai-auth-button-wrapper {
            margin-bottom: 20px;
        }

        .rapidtextai-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .rapidtextai-btn-primary {
            background: #2271b1;
            color: #fff;
        }

        .rapidtextai-btn-primary:hover {
            background: #135e96;
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(34, 113, 177, 0.3);
        }

        .rapidtextai-notice {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 12px 16px;
            border-radius: 6px;
            font-size: 14px;
            margin-top: 16px;
        }

        .rapidtextai-notice-success {
            background: #d1e7dd;
            color: #0f5132;
            border: 1px solid #badbcc;
        }

        .rapidtextai-status-message {
            min-height: 20px;
            margin-top: 16px;
        }

        .rapidtextai-status-loading {
            display: grid;
            align-items: center;
            gap: 12px;
            color: #646970;
            font-size: 14px;
        }

        .rapidtextai-spinner {
            width: 20px;
            height: 20px;
            border: 2px solid #e2e4e7;
            border-top: 2px solid #2271b1;
            border-radius: 50%;
            animation: rapidtextai-spin 1s linear infinite;
        }

        @keyframes rapidtextai-spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .rapidtextai-video-wrapper {
            position: relative;
            width: 100%;
            height: 0;
            padding-bottom: 56.25%;
            margin-bottom: 16px;
            border-radius: 8px;
            overflow: hidden;
        }

        .rapidtextai-video-wrapper iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border: none;
        }

        .rapidtextai-help-text {
            color: #646970;
            font-size: 14px;
            margin: 0;
            text-align: center;
        }

        #rapidtextai_status table {
            width: 100%;
            border-collapse: collapse;
        }

        #rapidtextai_status th,
        #rapidtextai_status td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e2e4e7;
        }

        #rapidtextai_status th {
            background: #f8f9fa;
            font-weight: 600;
            color: #1d2327;
            width: 30%;
        }

        #rapidtextai_status td {
            color: #646970;
        }

        @media (max-width: 768px) {
            .rapidtextai-content {
                padding: 0 20px;
            }
            
            .rapidtextai-card-body {
                padding: 16px;
            }
            
            .rapidtextai-btn {
                padding: 10px 20px;
                font-size: 13px;
            }
        }

        

        .rapidtextai-chat-preview {
            text-align: center;
            margin-bottom: 16px;
        }

        .rapidtextai-chat-image {
            width: 100%;
            max-width: 200px;
            height: auto;
            border-radius: 8px;
            border: 1px solid #e2e4e7;
        }

        .rapidtextai-chat-description {
            color: #646970;
            font-size: 14px;
            text-align: center;
            margin: 0 0 20px 0;
            line-height: 1.5;
        }

        .rapidtextai-chat-btn {
            width: auto;
            justify-content: center;
            gap: 8px;
        }

        .rapidtextai-chat-btn .dashicons-external {
            font-size: 12px;
            opacity: 0.8;
        }
    </style>
    
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('#rapidtextai_auth_button').on('click', function(e) {
                e.preventDefault();
                var authWindow = window.open('https://app.rapidtextai.com/log-in?action=popup', 'RapidTextAIAuth', 'width=500,height=600');
            });

            window.addEventListener('message', function(event) {
                // Only accept messages from the trusted RapidTextAI origin
                
                if (event.origin === 'https://app.rapidtextai.com') {
                    var apiKey = event.data.api_key;
                    if (apiKey) {
                        $('#rapidtextai_status_message').html('Authentication successful! Saving API key...');

                        $.post(ajaxurl, {
                            action: 'rapidtextai_save_api_key',
                            api_key: apiKey,
                            _wpnonce: '<?php echo wp_create_nonce('rapidtextai_save_api_key_nonce'); ?>'
                        }, function(response) {
                            $('#rapidtextai_status_message').html(response.message);
                            // append a button to reload the page
                            $('#rapidtextai_status_message').append('<br><button id="rapidtextai_reload_button" class="button button-primary">Reload</button>');

                        });
                    }
                }
            });

            /** Get Response using API */
        });

        jQuery(document).ready(function($) {
            // Get the connect key from the input field
            var connectKey = '<?php echo $current_api_key; ?>';

            // Make the AJAX request using jQuery
            $.ajax({
                url: 'https://app.rapidtextai.com/api.php',
                type: 'GET',
                data: {
                    gigsixkey: connectKey
                },
                dataType: 'json',
                success: function(response_data) {
                    var output = '';

                    if (response_data.response_code) {
                        var code = response_data.response_code;

                        if (code == 1 || code == 2) {
                            output += '<table class="form-table">';
                            output += '<tr><th>Created</th><td>' + (code == 1 ? response_data.create_at : response_data.subscription_cycle) + '</td></tr>';
                            output += '<tr><th>Status</th><td>' + (code == 1 ? response_data.subscription_status : 'Free Trial') + '</td></tr>';
                            output += '<tr><th>Plan</th><td>' + (code == 1 ? 'Premium' : 'Free') + '</td></tr>';
                            output += '<tr><th>Interval</th><td>' + (code == 1 ? response_data.subscription_interval : 'N/A') + '</td></tr>';
                            output += '<tr><th>Start</th><td>' + (code == 1 ? response_data.current_period_start : 'N/A') + '</td></tr>';
                            output += '<tr><th>End</th><td>' + (code == 1 ? response_data.current_period_end : 'N/A') + '</td></tr>';
                            output += '<tr><th>Requests Used</th><td>' + response_data.requests_used + '</td></tr>';
                            output += '<tr><th>Request Limit</th><td>' + response_data.request_limit + '</td></tr>';
                            output += '<tr><th>Remaining</th><td>' + (response_data.request_limit - response_data.requests_used) + '</td></tr>';
                            output += '<tr><th>Models</th><td>' + response_data.models + '</td></tr>';
                            output += '</table>';
                            
                            if (code == 2) {
                                output += '<div style="margin-top: 20px;">';
                                output += '<h3 style="color: #2271b1; margin-bottom: 16px;">Upgrade Your Plan</h3>';
                                output += '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px;">';
                                
                                // Basic Plan
                                output += '<div style="border: 1px solid #e2e4e7; border-radius: 8px; padding: 20px; background: #fff;">';
                                output += '<h4 style="color: #1d2327; margin-bottom: 8px;">Basic Plan</h4>';
                                output += '<div style="font-size: 24px; font-weight: 600; color: #2271b1; margin-bottom: 16px;">$10/month</div>';
                                output += '<ul style="list-style: none; padding: 0; margin: 0 0 20px 0;">';
                                output += '<li style="padding: 4px 0;"><span style="color: #00a32a;">✓</span> 10,000 Requests/month</li>';
                                output += '<li style="padding: 4px 0;"><span style="color: #00a32a;">✓</span> All AI Models</li>';
                                output += '<li style="padding: 4px 0;"><span style="color: #00a32a;">✓</span> Advanced Features</li>';
                                output += '<li style="padding: 4px 0;"><span style="color: #00a32a;">✓</span> Priority Support</li>';
                                output += '</ul>';
                                output += '<a href="https://app.rapidtextai.com/pricing" target="_blank" style="display: inline-block; background: #2271b1; color: white; padding: 10px 20px; text-decoration: none; border-radius: 6px;">Upgrade Now</a>';
                                output += '</div>';
                                
                                // Pro Plan
                                output += '<div style="border: 1px solid #e2e4e7; border-radius: 8px; padding: 20px; background: #fff;">';
                                output += '<h4 style="color: #1d2327; margin-bottom: 8px;">Pro Plan</h4>';
                                output += '<div style="font-size: 24px; font-weight: 600; color: #2271b1; margin-bottom: 16px;">$30/month</div>';
                                output += '<ul style="list-style: none; padding: 0; margin: 0 0 20px 0;">';
                                output += '<li style="padding: 4px 0;"><span style="color: #00a32a;">✓</span> 30,000 Requests/month</li>';
                                output += '<li style="padding: 4px 0;"><span style="color: #00a32a;">✓</span> All AI Models</li>';
                                output += '<li style="padding: 4px 0;"><span style="color: #00a32a;">✓</span> Advanced Features</li>';
                                output += '<li style="padding: 4px 0;"><span style="color: #00a32a;">✓</span> Priority Support</li>';
                                output += '<li style="padding: 4px 0;"><span style="color: #00a32a;">✓</span> API Access</li>';
                                output += '</ul>';
                                output += '<a href="https://app.rapidtextai.com/pricing" target="_blank" style="display: inline-block; background: #2271b1; color: white; padding: 10px 20px; text-decoration: none; border-radius: 6px;">Upgrade Now</a>';
                                output += '</div>';
                                
                                output += '</div>';
                                output += '</div>';
                            }
                        } else {
                            output = response_data.message;
                        }
                    } else {
                        output = 'Error retrieving data';
                    }

                    // Place the response in the div with id rapidtextai_status
                    $('#rapidtextai_status').html(output);
                },
                error: function() {
                    $('#rapidtextai_status').html('Error connecting to the server');
                }
            });
        });

    </script>