<div class="wrap rapidtextai-auto-blogging">
        <div class="rapidtextai-header">
            <h1><?php esc_html_e('Auto Blogging', 'rapidtextai'); ?></h1>
            <p class="description"><?php esc_html_e('Configure automatic blog post generation with AI-powered content creation.', 'rapidtextai'); ?></p>
        </div>

        <div class="rapidtextai-content">
            <div class="rapidtextai-card">
                <div class="rapidtextai-card-header">
                    <h2><?php esc_html_e('Auto Blogging Settings', 'rapidtextai'); ?></h2>
                </div>
                <div class="rapidtextai-card-body">
                    <form method="post" action="" id="rapidtextai_auto_blogging_form">
                        <?php wp_nonce_field('rapidtextai_auto_blogging', 'rapidtextai_auto_blogging_nonce'); ?>
                        
                        <div class="rapidtextai-settings-grid">
                            <!-- Enable Auto Blogging -->
                            <div class="rapidtextai-form-group">
                                <label class="rapidtextai-toggle">
                                    <input type="checkbox" name="rapidtextai_auto_blogging_enabled" value="1" <?php checked($settings['enabled']); ?>>
                                    <span class="rapidtextai-toggle-slider"></span>
                                    <span class="rapidtextai-toggle-label"><?php esc_html_e('Enable Auto Blogging', 'rapidtextai'); ?></span>
                                </label>
                                <p class="rapidtextai-field-description"><?php esc_html_e('Automatically generate and publish blog posts based on your schedule', 'rapidtextai'); ?></p>
                            </div>

                            <!-- Schedule -->
                            <div class="rapidtextai-form-group">
                                <label class="rapidtextai-label"><?php esc_html_e('Publishing Schedule', 'rapidtextai'); ?></label>
                                <select name="rapidtextai_schedule" class="rapidtextai-select">
                                    <option value="hourly" <?php selected($settings['schedule'], 'hourly'); ?>><?php esc_html_e('Every Hour', 'rapidtextai'); ?></option>
                                    <option value="twicedaily" <?php selected($settings['schedule'], 'twicedaily'); ?>><?php esc_html_e('Twice Daily', 'rapidtextai'); ?></option>
                                    <option value="daily" <?php selected($settings['schedule'], 'daily'); ?>><?php esc_html_e('Daily', 'rapidtextai'); ?></option>
                                    <option value="weekly" <?php selected($settings['schedule'], 'weekly'); ?>><?php esc_html_e('Weekly', 'rapidtextai'); ?></option>
                                </select>
                            </div>

                            <!-- Post Status -->
                            <div class="rapidtextai-form-group">
                                <label class="rapidtextai-label"><?php esc_html_e('Post Status', 'rapidtextai'); ?></label>
                                <select name="rapidtextai_post_status" class="rapidtextai-select">
                                    <option value="publish" <?php selected($settings['post_status'], 'publish'); ?>><?php esc_html_e('Published', 'rapidtextai'); ?></option>
                                    <option value="draft" <?php selected($settings['post_status'], 'draft'); ?>><?php esc_html_e('Draft', 'rapidtextai'); ?></option>
                                    <option value="pending" <?php selected($settings['post_status'], 'pending'); ?>><?php esc_html_e('Pending Review', 'rapidtextai'); ?></option>
                                </select>
                            </div>

                            <!-- Post Author -->
                            <div class="rapidtextai-form-group">
                                <label class="rapidtextai-label"><?php esc_html_e('Post Author', 'rapidtextai'); ?></label>
                                <select name="rapidtextai_post_author" class="rapidtextai-select">
                                    <?php foreach ($authors as $author) : ?>
                                        <option value="<?php echo esc_attr($author->ID); ?>" <?php selected($settings['post_author'], $author->ID); ?>>
                                            <?php echo esc_html($author->display_name); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- AI Model -->
                            <div class="rapidtextai-form-group">
                                <label class="rapidtextai-label"><?php esc_html_e('AI Model', 'rapidtextai'); ?></label>
                                <select name="rapidtextai_model" class="rapidtextai-select">
                                    <option value="gemini-2.0-flash" <?php selected($settings['model'], 'gemini-2.0-flash'); ?>><?php esc_html_e('Gemini 2.0 (Google)', 'rapidtextai'); ?></option>
                                    <option value="gemini-2.5-flash-preview-05-20" <?php selected($settings['model'], 'gemini-2.5-flash-preview-05-20'); ?>><?php esc_html_e('Gemini 2.5 (Google)', 'rapidtextai'); ?></option>
                                    <option value="deepseek-chat" <?php selected($settings['model'], 'deepseek-chat'); ?>><?php esc_html_e('DeepSeek 3.1 (DeepSeek)', 'rapidtextai'); ?></option>
                                    <option value="claude-3-7-sonnet-latest" <?php selected($settings['model'], 'claude-3-7-sonnet-latest'); ?>><?php esc_html_e('Claude 3.7 (Anthropic)', 'rapidtextai'); ?></option>
                                    <option value="gpt-5" <?php selected($settings['model'], 'gpt-5'); ?>><?php esc_html_e('GPT-5 (OpenAI)', 'rapidtextai'); ?></option>
                                    <option value="gpt-4" <?php selected($settings['model'], 'gpt-4'); ?>><?php esc_html_e('GPT-4 (OpenAI)', 'rapidtextai'); ?></option>
                                    <option value="gpt-3.5-turbo" <?php selected($settings['model'], 'gpt-3.5-turbo'); ?>><?php esc_html_e('GPT-3.5 Turbo (OpenAI)', 'rapidtextai'); ?></option>
                                    <option value="grok-3" <?php selected($settings['model'], 'grok-3'); ?>><?php esc_html_e('Grok 3 (xAI)', 'rapidtextai'); ?></option>
                                    <option value="glm-4.6v-flash" <?php selected($settings['model'], 'glm-4.6v-flash'); ?>><?php esc_html_e('GLM-4.5 Flash (Zhipu AI)', 'rapidtextai'); ?></option>
                                </select>
                            </div>

                            <!-- Tone of Voice -->
                            <div class="rapidtextai-form-group">
                                <label class="rapidtextai-label"><?php esc_html_e('Tone of Voice', 'rapidtextai'); ?></label>
                                <select name="rapidtextai_tone" class="rapidtextai-select">
                                    <option value="informative" <?php selected($settings['tone'], 'informative'); ?>><?php esc_html_e('Informative', 'rapidtextai'); ?></option>
                                    <option value="conversational" <?php selected($settings['tone'], 'conversational'); ?>><?php esc_html_e('Conversational', 'rapidtextai'); ?></option>
                                    <option value="formal" <?php selected($settings['tone'], 'formal'); ?>><?php esc_html_e('Formal', 'rapidtextai'); ?></option>
                                    <option value="friendly" <?php selected($settings['tone'], 'friendly'); ?>><?php esc_html_e('Friendly', 'rapidtextai'); ?></option>
                                    <option value="persuasive" <?php selected($settings['tone'], 'persuasive'); ?>><?php esc_html_e('Persuasive', 'rapidtextai'); ?></option>
                                </select>
                            </div>
                        </div>

                        <!-- Topics Section -->
                        <div class="rapidtextai-form-group rapidtextai-full-width">
                            <label class="rapidtextai-label"><?php esc_html_e('Content Topics & Prompts', 'rapidtextai'); ?></label>
                            <textarea name="rapidtextai_topics" rows="8" class="rapidtextai-textarea" placeholder="<?php esc_attr_e('Enter your topics, one per line...', 'rapidtextai'); ?>"><?php echo esc_textarea($settings['topics']); ?></textarea>
                            <div class="rapidtextai-field-help">
                                <div class="rapidtextai-help-content">
                                    <h4><?php esc_html_e('Advanced Format:', 'rapidtextai'); ?></h4>
                                    <p><code>Topic: [topic]; Keywords: [keywords]; Tone: [tone]; Audience: [audience]; CTA: [call to action]; Length: [length] : Language: [language]</code></p>
                                    <p><strong><?php esc_html_e('Example:', 'rapidtextai'); ?></strong> <em>Topic: Complete Guide to Sustainable Gardening for Beginners; Keywords: sustainable gardening, eco-friendly plants, organic fertilizer, water conservation, composting methods; Tone: friendly and informative; Audience: homeowners and gardening beginners; Length: 2500-3000 words; CTA: Download our free sustainable gardening checklist; Language: English</em></p>
                                </div>
                                <div class="rapidtextai-topic-actions">
                                    <button type="button" id="rapidtextai_improve_topics" class="rapidtextai-btn rapidtextai-btn-secondary">
                                        <span class="dashicons dashicons-admin-tools"></span>
                                        <?php esc_html_e('Improve with AI', 'rapidtextai'); ?>
                                    </button>
                                    <div class="rapidtextai-loading" id="rapidtextai_improve_loading" style="display:none;">
                                        <div class="rapidtextai-spinner"></div>
                                        <span><?php esc_html_e('Improving topics...', 'rapidtextai'); ?></span>
                                    </div>
                                </div>
                            </div>
                            <div id="rapidtextai_improved_topics_result"></div>
                        </div>

                        <!-- Advanced Settings -->
                        <div class="rapidtextai-collapsible-section">
                            <button type="button" class="rapidtextai-collapsible-trigger" data-target="advanced-settings">
                                <span class="dashicons dashicons-admin-generic"></span>
                                <?php esc_html_e('Advanced Settings', 'rapidtextai'); ?>
                                <span class="rapidtextai-chevron dashicons dashicons-arrow-down-alt2"></span>
                            </button>
                            <div class="rapidtextai-collapsible-content" id="advanced-settings">
                                <div class="rapidtextai-settings-grid">
                                    <!-- Categories -->
                                    <div class="rapidtextai-form-group rapidtextai-full-width">
                                        <label class="rapidtextai-label"><?php esc_html_e('Post Categories', 'rapidtextai'); ?></label>
                                        <div class="rapidtextai-checkbox-group">
                                            <?php foreach ($categories as $category) : ?>
                                                <label class="rapidtextai-checkbox">
                                                    <input type="checkbox" name="rapidtextai_post_category[]" value="<?php echo esc_attr($category->term_id); ?>" 
                                                           <?php checked(in_array($category->term_id, $settings['post_category'])); ?>>
                                                    <span class="rapidtextai-checkbox-mark"></span>
                                                    <span class="rapidtextai-checkbox-label"><?php echo esc_html($category->name); ?></span>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>

                                    <!-- Generate Tags -->
                                    <div class="rapidtextai-form-group">
                                        <label class="rapidtextai-toggle">
                                            <input type="checkbox" name="rapidtextai_generate_tags" value="1" <?php checked($settings['generate_tags']); ?>>
                                            <span class="rapidtextai-toggle-slider"></span>
                                            <span class="rapidtextai-toggle-label"><?php esc_html_e('Auto-generate Tags', 'rapidtextai'); ?></span>
                                        </label>
                                    </div>

                                    <!-- Number of Tags -->
                                    <div class="rapidtextai-form-group">
                                        <label class="rapidtextai-label"><?php esc_html_e('Number of Tags', 'rapidtextai'); ?></label>
                                        <input type="number" name="rapidtextai_tags_count" value="<?php echo esc_attr($settings['tags_count']); ?>" 
                                               min="1" max="10" class="rapidtextai-input">
                                    </div>

                                    <!-- Excerpt Length -->
                                    <div class="rapidtextai-form-group">
                                        <label class="rapidtextai-label"><?php esc_html_e('Excerpt Length (words)', 'rapidtextai'); ?></label>
                                        <input type="number" name="rapidtextai_excerpt_length" value="<?php echo esc_attr($settings['excerpt_length']); ?>" 
                                               min="10" max="100" class="rapidtextai-input">
                                    </div>

                                    <!-- Taxonomy Limit -->
                                    <div class="rapidtextai-form-group">
                                        <label class="rapidtextai-label"><?php esc_html_e('Taxonomy Limit', 'rapidtextai'); ?></label>
                                        <input type="number" name="rapidtextai_taxonomy_limit" value="<?php echo esc_attr($settings['taxonomy_limit']); ?>" 
                                               min="1" max="10" class="rapidtextai-input">
                                    </div>

                                    <!-- Include Images -->
                                    <div class="rapidtextai-form-group">
                                        <label class="rapidtextai-toggle">
                                            <input type="checkbox" name="rapidtextai_include_images" value="1" <?php checked($settings['include_images']); ?>>
                                            <span class="rapidtextai-toggle-slider"></span>
                                            <span class="rapidtextai-toggle-label"><?php esc_html_e('Include Images', 'rapidtextai'); ?></span>
                                        </label>
                                        <p class="rapidtextai-field-description"><?php esc_html_e('Add relevant images to generated posts', 'rapidtextai'); ?></p>
                                    </div>
                                    <!-- Featured Image -->
                                    <div class="rapidtextai-form-group">
                                        <label class="rapidtextai-toggle">
                                            <input type="checkbox" name="rapidtextai_include_featured_image" value="1" <?php checked($settings['include_featured_image']); ?>>
                                            <span class="rapidtextai-toggle-slider"></span>
                                            <span class="rapidtextai-toggle-label"><?php esc_html_e('Set Featured Image', 'rapidtextai'); ?></span>
                                        </label>
                                        <p class="rapidtextai-field-description"><?php esc_html_e('Automatically set a featured image for generated posts', 'rapidtextai'); ?></p>
                                    </div>
                                    <!-- maximum number of images -->
                                    <div class="rapidtextai-form-group">
                                        <label class="rapidtextai-label"><?php esc_html_e('Maximum Images', 'rapidtextai'); ?></label>
                                        <input type="number" name="rapidtextai_max_images" value="<?php  echo esc_attr($settings['max_images']); ?>" 
                                               min="1" max="10" class="rapidtextai-input">
                                        <p class="rapidtextai-field-description"><?php esc_html_e('Maximum number of images to include per post', 'rapidtextai'); ?></p>
                                    </div>
                                    <!-- enable logging  -->
                                    <div class="rapidtextai-form-group">
                                        <label class="rapidtextai-toggle">
                                            <input type="checkbox" name="rapidtextai_enable_logging" value="1" <?php checked($settings['enable_logging']); ?>>
                                            <span class="rapidtextai-toggle-slider"></span>
                                            <span class="rapidtextai-toggle-label"><?php esc_html_e('Enable Logging', 'rapidtextai'); ?></span>
                                        </label>
                                        <p class="rapidtextai-field-description"><?php esc_html_e('Keep a log of all auto blogging activities', 'rapidtextai'); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="rapidtextai-form-actions">
                            <button type="submit" name="rapidtextai_save_auto_blogging" class="rapidtextai-btn rapidtextai-btn-primary">
                                <span class="dashicons dashicons-saved"></span>
                                <?php esc_html_e('Save Settings', 'rapidtextai'); ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Quick Actions Card -->
            <div class="rapidtextai-card">
                <div class="rapidtextai-card-header">
                    <h2><?php esc_html_e('Quick Actions', 'rapidtextai'); ?></h2>
                </div>
                <div class="rapidtextai-card-body">
                    <div class="rapidtextai-quick-actions">
                        <form method="post" action="" class="rapidtextai-quick-action">
                            <?php wp_nonce_field('rapidtextai_generate_now', 'rapidtextai_generate_now_nonce'); ?>
                            <div class="rapidtextai-quick-action-content">
                                <div class="rapidtextai-quick-action-icon">
                                    <span class="dashicons dashicons-edit-large"></span>
                                </div>
                                <div class="rapidtextai-quick-action-text">
                                    <h3><?php esc_html_e('Generate Post Now', 'rapidtextai'); ?></h3>
                                    <p><?php esc_html_e('Create a new blog post immediately using your current settings', 'rapidtextai'); ?></p>
                                </div>
                                <button type="submit" name="rapidtextai_generate_post_now" class="rapidtextai-btn rapidtextai-btn-secondary">
                                    <?php esc_html_e('Generate Now', 'rapidtextai'); ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <?php if ($settings['enable_logging']) : ?>
            <!-- Logs Card -->
            <div class="rapidtextai-card">
                <div class="rapidtextai-card-header">
                    <h2><?php esc_html_e('Auto Blogging Logs', 'rapidtextai'); ?></h2>
                </div>
                <div class="rapidtextai-card-body">
                    <div class="rapidtextai-logs-section">
                        <div class="rapidtextai-logs-controls">
                            <button type="button" id="rapidtextai_load_logs" class="rapidtextai-btn rapidtextai-btn-secondary">
                                <span class="dashicons dashicons-list-view"></span>
                                <?php esc_html_e('Load Logs', 'rapidtextai'); ?>
                            </button>
                            <button type="button" id="rapidtextai_clear_logs" class="rapidtextai-btn rapidtextai-btn-secondary" style="display:none;">
                                <span class="dashicons dashicons-trash"></span>
                                <?php esc_html_e('Clear Logs', 'rapidtextai'); ?>
                            </button>
                            <div class="rapidtextai-loading" id="rapidtextai_logs_loading" style="display:none;">
                                <div class="rapidtextai-spinner"></div>
                                <span><?php esc_html_e('Loading logs...', 'rapidtextai'); ?></span>
                            </div>
                        </div>
                        <div class="rapidtextai-logs-container" id="rapidtextai_logs_container" style="display:none;">
                            <div class="rapidtextai-logs-content" id="rapidtextai_logs_content">
                                <!-- Logs will be populated here -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

                                                
                                                
        </div><!-- rapidtextai-content -->
    </div><!-- wrap -->

    <style>
        .rapidtextai-auto-blogging {
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

        .rapidtextai-settings-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 24px;
        }

        .rapidtextai-form-group {
            display: flex;
            flex-direction: column;
        }

        .rapidtextai-form-group.rapidtextai-full-width {
            grid-column: 1 / -1;
        }

        .rapidtextai-label {
            font-weight: 600;
            color: #1d2327;
            margin-bottom: 8px;
            display: block;
        }

        .rapidtextai-input,
        .rapidtextai-select,
        .rapidtextai-textarea {
            padding: 12px 16px;
            border: 2px solid #e2e4e7;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.2s ease;
            background: #fff;
        }

        .rapidtextai-input:focus,
        .rapidtextai-select:focus,
        .rapidtextai-textarea:focus {
            border-color: #2271b1;
            outline: none;
            box-shadow: 0 0 0 1px #2271b1;
        }

        .rapidtextai-textarea {
            resize: vertical;
            min-height: 120px;
            font-family: monospace;
        }

        .rapidtextai-field-description {
            color: #646970;
            font-size: 13px;
            margin-top: 6px;
        }

        .rapidtextai-field-help {
            margin-top: 12px;
            padding: 16px;
            background: #f8f9fa;
            border-radius: 6px;
            border-left: 4px solid #2271b1;
        }

        .rapidtextai-help-content h4 {
            margin: 0 0 8px 0;
            color: #1d2327;
            font-size: 14px;
        }

        .rapidtextai-help-content p {
            margin: 4px 0;
            font-size: 13px;
            color: #646970;
        }

        .rapidtextai-help-content code {
            background: #fff;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
            border: 1px solid #e2e4e7;
        }

        .rapidtextai-topic-actions {
            margin-top: 12px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .rapidtextai-loading {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #646970;
            font-size: 14px;
        }

        /* Toggle Switch */
        .rapidtextai-toggle {
            display: flex;
            align-items: center;
            gap: 12px;
            cursor: pointer;
        }

        .rapidtextai-toggle input[type="checkbox"] {
            display: none;
        }

        .rapidtextai-toggle-slider {
            position: relative;
            width: 44px;
            height: 24px;
            background: #ccd0d4;
            border-radius: 12px;
            transition: background-color 0.2s ease;
        }

        .rapidtextai-toggle-slider:before {
            content: '';
            position: absolute;
            top: 2px;
            left: 2px;
            width: 20px;
            height: 20px;
            background: #fff;
            border-radius: 50%;
            transition: transform 0.2s ease;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
        }

        .rapidtextai-toggle input:checked + .rapidtextai-toggle-slider {
            background: #2271b1;
        }

        .rapidtextai-toggle input:checked + .rapidtextai-toggle-slider:before {
            transform: translateX(20px);
        }

        .rapidtextai-toggle-label {
            font-weight: 600;
            color: #1d2327;
        }

        /* Checkbox Group */
        .rapidtextai-checkbox-group {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 12px;
            margin-top: 8px;
        }

        .rapidtextai-checkbox {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            padding: 8px 12px;
            border-radius: 6px;
            transition: background-color 0.2s ease;
        }

        .rapidtextai-checkbox:hover {
            background: #f8f9fa;
        }

        .rapidtextai-checkbox input[type="checkbox"] {
            display: none;
        }

        .rapidtextai-checkbox-mark {
            width: 16px;
            height: 16px;
            border: 2px solid #ccd0d4;
            border-radius: 3px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }

        .rapidtextai-checkbox input:checked + .rapidtextai-checkbox-mark {
            background: #2271b1;
            border-color: #2271b1;
        }

        .rapidtextai-checkbox input:checked + .rapidtextai-checkbox-mark:after {
            content: '\2713';
            color: #fff;
            font-size: 12px;
            font-weight: bold;
        }

        .rapidtextai-checkbox-label {
            font-size: 14px;
            color: #1d2327;
        }

        /* Collapsible Section */
        .rapidtextai-collapsible-section {
            margin: 24px 0;
        }

        .rapidtextai-collapsible-trigger {
            width: 100%;
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 16px;
            background: #f8f9fa;
            border: 1px solid #e2e4e7;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            color: #1d2327;
            transition: background-color 0.2s ease;
        }

        .rapidtextai-collapsible-trigger:hover {
            background: #f0f0f1;
        }

        .rapidtextai-chevron {
            margin-left: auto;
            transition: transform 0.2s ease;
        }

        .rapidtextai-collapsible-trigger.active .rapidtextai-chevron {
            transform: rotate(180deg);
        }

        .rapidtextai-collapsible-content {
            display: none;
            padding: 24px 16px;
            border: 1px solid #e2e4e7;
            border-top: none;
            border-radius: 0 0 6px 6px;
            background: #fff;
        }

        .rapidtextai-collapsible-content.active {
            display: block;
        }

        /* Buttons */
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
            white-space: nowrap;
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

        .rapidtextai-btn-secondary {
            background: #f6f7f7;
            color: #2c3338;
            border: 1px solid #c3c4c7;
        }

        .rapidtextai-btn-secondary:hover {
            background: #f0f0f1;
            border-color: #8c8f94;
        }

        .rapidtextai-form-actions {
            margin-top: 32px;
            padding-top: 24px;
            border-top: 1px solid #e2e4e7;
            display: flex;
            justify-content: flex-end;
        }

        /* Quick Actions */
        .rapidtextai-quick-actions {
            display: grid;
            gap: 16px;
        }

        .rapidtextai-quick-action-content {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 20px;
            border: 1px solid #e2e4e7;
            border-radius: 8px;
            background: #f8f9fa;
        }

        .rapidtextai-quick-action-icon {
            width: 48px;
            height: 48px;
            background: #2271b1;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 20px;
        }

        .rapidtextai-quick-action-text {
            flex: 1;
        }

        .rapidtextai-quick-action-text h3 {
            margin: 0 0 4px 0;
            font-size: 16px;
            color: #1d2327;
        }

        .rapidtextai-quick-action-text p {
            margin: 0;
            color: #646970;
            font-size: 14px;
        }

        /* Spinner */
        .rapidtextai-spinner {
            width: 16px;
            height: 16px;
            border: 2px solid #e2e4e7;
            border-top: 2px solid #2271b1;
            border-radius: 50%;
            animation: rapidtextai-spin 1s linear infinite;
        }

        @keyframes rapidtextai-spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Message Styles */
        #rapidtextai_improved_topics_result .notice {
            margin: 16px 0 0 0;
            padding: 12px 16px;
            border-radius: 6px;
            border-left-width: 4px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .rapidtextai-settings-grid {
                grid-template-columns: 1fr;
            }
            
            .rapidtextai-checkbox-group {
                grid-template-columns: 1fr;
            }
            
            .rapidtextai-quick-action-content {
                flex-direction: column;
                text-align: center;
            }
            
            .rapidtextai-form-actions {
                justify-content: stretch;
            }
            
            .rapidtextai-btn {
                justify-content: center;
                width: 100%;
            }
        }


        /* Logs Styles */
        .rapidtextai-logs-section {
            min-height: 60px;
        }

        .rapidtextai-logs-controls {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
        }

        .rapidtextai-logs-container {
            border: 1px solid #e2e4e7;
            border-radius: 6px;
            background: #f8f9fa;
            max-height: 400px;
            overflow-y: auto;
        }

        .rapidtextai-logs-content {
            padding: 16px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            line-height: 1.6;
            white-space: pre-wrap;
            word-wrap: break-word;
        }

        .rapidtextai-log-entry {
            padding: 8px 12px;
            margin-bottom: 4px;
            border-radius: 4px;
            border-left: 3px solid #ccd0d4;
            background: #fff;
        }

        .rapidtextai-log-entry.log-info {
            border-left-color: #2271b1;
            background: #f0f6fc;
        }

        .rapidtextai-log-entry.log-success {
            border-left-color: #00a32a;
            background: #f0f6fc;
        }

        .rapidtextai-log-entry.log-warning {
            border-left-color: #dba617;
            background: #fcf9e8;
        }

        .rapidtextai-log-entry.log-error {
            border-left-color: #d63638;
            background: #fcf0f1;
        }

        .rapidtextai-log-timestamp {
            color: #646970;
            font-weight: 600;
            margin-right: 8px;
        }

        .rapidtextai-log-message {
            color: #1d2327;
        }

        .rapidtextai-logs-empty {
            text-align: center;
            color: #646970;
            font-style: italic;
            padding: 32px;
        }
    </style>

    <script type="text/javascript">
    jQuery(document).ready(function($) {
        // Collapsible sections
        $('.rapidtextai-collapsible-trigger').on('click', function() {
            $(this).toggleClass('active');
            $(this).next('.rapidtextai-collapsible-content').toggleClass('active');
        });

        // Topic improvement
        $('#rapidtextai_improve_topics').on('click', function() {
            const topics = $('textarea[name="rapidtextai_topics"]').val().trim();
            if (!topics) {
                alert('<?php esc_html_e('Please enter at least one topic to improve.', 'rapidtextai'); ?>');
                return;
            }
            
            const $button = $(this);
            const $loading = $('#rapidtextai_improve_loading');
            const $result = $('#rapidtextai_improved_topics_result');
            
            $button.prop('disabled', true);
            $loading.show();
            $result.html('');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'rapidtextai_improve_topics',
                    topics: topics,
                    nonce: '<?php echo wp_create_nonce('rapidtextai_improve_topics_nonce'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        $('textarea[name="rapidtextai_topics"]').val(response.data.improved_topics);
                        $result.html('<div class="notice notice-success inline"><p><span class="dashicons dashicons-yes-alt"></span> ' + 
                            response.data.message + '</p></div>');
                    } else {
                        $result.html('<div class="notice notice-error inline"><p><span class="dashicons dashicons-warning"></span> ' + 
                            response.data.message + '</p></div>');
                    }
                },
                error: function() {
                    $result.html('<div class="notice notice-error inline"><p><span class="dashicons dashicons-warning"></span> <?php esc_html_e('Error connecting to server.', 'rapidtextai'); ?></p></div>');
                },
                complete: function() {
                    $button.prop('disabled', false);
                    $loading.hide();
                }
            });
        });

        // Form validation
        $('#rapidtextai_auto_blogging_form').on('submit', function(e) {
            const enabled = $('input[name="rapidtextai_auto_blogging_enabled"]').is(':checked');
            const topics = $('textarea[name="rapidtextai_topics"]').val().trim();
            
            if (enabled && !topics) {
                e.preventDefault();
                alert('<?php esc_html_e('Please add at least one topic when auto blogging is enabled.', 'rapidtextai'); ?>');
                $('textarea[name="rapidtextai_topics"]').focus();
            }
        });
    });
    </script>

    <script type="text/javascript">
    jQuery(document).ready(function($) {
        // Load logs
        $('#rapidtextai_load_logs').on('click', function() {
            const $button = $(this);
            const $loading = $('#rapidtextai_logs_loading');
            const $container = $('#rapidtextai_logs_container');
            const $content = $('#rapidtextai_logs_content');
            const $clearBtn = $('#rapidtextai_clear_logs');
            
            $button.prop('disabled', true);
            $loading.show();
            $container.hide();
            
            $.ajax({
                url: ajaxurl,
                type: 'GET',
                data: {
                    action: 'rapidtextai_get_logs',
                    nonce: '<?php echo wp_create_nonce('rapidtextai_get_logs_nonce'); ?>'
                },
                success: function(response) {
                    if (response.success && response.data.logs) {
                        let logsHtml = '';
                        if (response.data.logs.length > 0) {
                            response.data.logs.forEach(function(log) {
                                const logClass = 'log-' + (log.level || 'info');
                                logsHtml += '<div class="rapidtextai-log-entry ' + logClass + '">';
                                logsHtml += '<span class="rapidtextai-log-timestamp">[' + log.timestamp + ']</span>';
                                logsHtml += '<span class="rapidtextai-log-message">' + $('<div>').text(log.message).html() + '</span>';
                                logsHtml += '</div>';
                            });
                        } else {
                            logsHtml = '<div class="rapidtextai-logs-empty"><?php esc_html_e('No logs found.', 'rapidtextai'); ?></div>';
                        }
                        
                        $content.html(logsHtml);
                        $container.show();
                        $clearBtn.show();
                    } else {
                        $content.html('<div class="rapidtextai-logs-empty"><?php esc_html_e('Failed to load logs.', 'rapidtextai'); ?></div>');
                        $container.show();
                    }
                },
                error: function() {
                    $content.html('<div class="rapidtextai-logs-empty"><?php esc_html_e('Error loading logs.', 'rapidtextai'); ?></div>');
                    $container.show();
                },
                complete: function() {
                    $button.prop('disabled', false);
                    $loading.hide();
                }
            });
        });
        
        // Clear logs
        $('#rapidtextai_clear_logs').on('click', function() {
            if (!confirm('<?php esc_html_e('Are you sure you want to clear all logs?', 'rapidtextai'); ?>')) {
                return;
            }
            
            const $button = $(this);
            const $content = $('#rapidtextai_logs_content');
            
            $button.prop('disabled', true);
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'rapidtextai_clear_logs',
                    nonce: '<?php echo wp_create_nonce('rapidtextai_clear_logs_nonce'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        $content.html('<div class="rapidtextai-logs-empty"><?php esc_html_e('Logs cleared successfully.', 'rapidtextai'); ?></div>');
                    } else {
                        alert('<?php esc_html_e('Failed to clear logs.', 'rapidtextai'); ?>');
                    }
                },
                error: function() {
                    alert('<?php esc_html_e('Error clearing logs.', 'rapidtextai'); ?>');
                },
                complete: function() {
                    $button.prop('disabled', false);
                }
            });
        });
    });
    </script>