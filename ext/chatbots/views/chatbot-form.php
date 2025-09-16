<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly
?>

<div class="wrap">
    <h1><?php echo $is_edit ? 'Edit Chatbot' : 'Add New Chatbot'; ?></h1>
    
    <form method="post" action="" id="chatbot-form">
        <?php wp_nonce_field('rapidtextai_chatbots_save', 'rapidtextai_chatbots_nonce'); ?>
        
        <div id="chatbot-tabs">
            <ul>
                <li><a href="#tab-general">General</a></li>
                <li><a href="#tab-appearance">Appearance</a></li>
                <li><a href="#tab-behavior">Behavior</a></li>
                <li><a href="#tab-knowledge">Knowledge Base</a></li>
                <li><a href="#tab-tools">Tools</a></li>
            </ul>
            
            <!-- General Tab -->
            <div id="tab-general">
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="name">Chatbot Name</label></th>
                        <td>
                            <input type="text" id="name" name="name" value="<?php echo esc_attr($chatbot['name']); ?>" class="regular-text" required>
                            <p class="description">Enter a name for your chatbot.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="description">Description</label></th>
                        <td>
                            <textarea id="description" name="description" rows="3" class="large-text"><?php echo esc_textarea($chatbot['description']); ?></textarea>
                            <p class="description">Optional description for your reference.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="model">AI Model</label></th>
                        <td>
                            <select id="model" name="model" class="regular-text">
                                <option value="gpt-3.5-turbo" <?php selected($chatbot['model'], 'gpt-3.5-turbo'); ?>>GPT-3.5 Turbo</option>
                                <option value="gpt-4" <?php selected($chatbot['model'], 'gpt-4'); ?>>GPT-4</option>
                                <option value="gpt-4-turbo" <?php selected($chatbot['model'], 'gpt-4-turbo'); ?>>GPT-4 Turbo</option>
                            </select>
                            <button type="button" id="refresh-models" class="button">Refresh Models</button>
                            <p class="description">Select the AI model for your chatbot.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="status">Status</label></th>
                        <td>
                            <select id="status" name="status">
                                <option value="active" <?php selected($chatbot['status'], 'active'); ?>>Active</option>
                                <option value="inactive" <?php selected($chatbot['status'], 'inactive'); ?>>Inactive</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="system_message">System Message</label></th>
                        <td>
                            <textarea id="system_message" name="system_message" rows="5" class="large-text"><?php echo esc_textarea($chatbot['system_message']); ?></textarea>
                            <p class="description">Instructions that define how the chatbot should behave.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="welcome_message">Welcome Message</label></th>
                        <td>
                            <textarea id="welcome_message" name="welcome_message" rows="3" class="large-text"><?php echo esc_textarea($chatbot['welcome_message']); ?></textarea>
                            <p class="description">The first message users will see when they open the chatbot.</p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <!-- Appearance Tab -->
            <div id="tab-appearance">
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="theme">Theme</label></th>
                        <td>
                            <select id="theme" name="theme">
                                <option value="modern" <?php selected($chatbot['theme'], 'modern'); ?>>Modern</option>
                                <option value="classic" <?php selected($chatbot['theme'], 'classic'); ?>>Classic</option>
                                <option value="minimal" <?php selected($chatbot['theme'], 'minimal'); ?>>Minimal</option>
                                <option value="dark" <?php selected($chatbot['theme'], 'dark'); ?>>Dark</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="primary_color">Primary Color</label></th>
                        <td>
                            <input type="text" id="primary_color" name="primary_color" value="<?php echo esc_attr($chatbot['settings']['primary_color']); ?>" class="color-picker">
                        </td>
                    </tr>
                    <!-- <tr>
                        <th scope="row"><label for="secondary_color">Secondary Color</label></th>
                        <td>
                            <input type="text" id="secondary_color" name="secondary_color" value="<?php echo esc_attr($chatbot['settings']['secondary_color']); ?>" class="color-picker">
                        </td>
                    </tr> -->
                    <tr>
                        <th scope="row"><label for="text_color">Text Color</label></th>
                        <td>
                            <input type="text" id="text_color" name="text_color" value="<?php echo esc_attr($chatbot['settings']['text_color']); ?>" class="color-picker">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="background_color">Background Color</label></th>
                        <td>
                            <input type="text" id="background_color" name="background_color" value="<?php echo esc_attr($chatbot['settings']['background_color']); ?>" class="color-picker">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="position">Position</label></th>
                        <td>
                            <select id="position" name="position">
                                <option value="bottom-right" <?php selected($chatbot['settings']['position'], 'bottom-right'); ?>>Bottom Right</option>
                                <option value="bottom-left" <?php selected($chatbot['settings']['position'], 'bottom-left'); ?>>Bottom Left</option>
                                <option value="top-right" <?php selected($chatbot['settings']['position'], 'top-right'); ?>>Top Right</option>
                                <option value="top-left" <?php selected($chatbot['settings']['position'], 'top-left'); ?>>Top Left</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="size">Size</label></th>
                        <td>
                            <select id="size" name="size">
                                <option value="small" <?php selected($chatbot['settings']['size'], 'small'); ?>>Small</option>
                                <option value="medium" <?php selected($chatbot['settings']['size'], 'medium'); ?>>Medium</option>
                                <option value="large" <?php selected($chatbot['settings']['size'], 'large'); ?>>Large</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Avatar</th>
                        <td>
                            <label>
                                <input type="checkbox" name="show_avatar" value="1" <?php checked($chatbot['settings']['show_avatar'], 1); ?>>
                                Show Avatar
                            </label>
                            <br><br>
                            <input type="url" id="avatar_url" name="avatar_url" value="<?php echo esc_attr($chatbot['settings']['avatar_url']); ?>" class="regular-text" placeholder="Avatar URL">
                            <p class="description">URL of the avatar image to display.</p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <!-- Behavior Tab -->
            <div id="tab-behavior">
                <table class="form-table">
                    <tr>
                        <th scope="row">Auto Open</th>
                        <td>
                            <label>
                                <input type="checkbox" name="auto_open" value="1" <?php checked($chatbot['settings']['auto_open'], 1); ?>>
                                Automatically open chatbot
                            </label>
                            <br><br>
                            <label for="auto_open_delay">Delay (milliseconds):</label>
                            <input type="number" id="auto_open_delay" name="auto_open_delay" value="<?php echo esc_attr($chatbot['settings']['auto_open_delay']); ?>" min="0" step="500" class="small-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="max_tokens">Max Tokens</label></th>
                        <td>
                            <input type="number" id="max_tokens" name="max_tokens" value="<?php echo esc_attr($chatbot['settings']['max_tokens']); ?>" min="1" max="4000" class="small-text">
                            <p class="description">Maximum number of tokens in the response.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="temperature">Temperature</label></th>
                        <td>
                            <input type="number" id="temperature" name="temperature" value="<?php echo esc_attr($chatbot['settings']['temperature']); ?>" min="0" max="2" step="0.1" class="small-text">
                            <p class="description">Controls randomness. 0 = focused, 1 = balanced, 2 = creative.</p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <!-- Knowledge Base Tab -->
            <div id="tab-knowledge">
                <h3>Knowledge Base Documents</h3>
                <p>Add documents to provide context and information to your chatbot.</p>
                
                <div id="knowledge-base-container">
                    <?php if (!empty($chatbot['knowledge_base'])): ?>
                        <?php foreach ($chatbot['knowledge_base'] as $index => $doc): ?>
                            <div class="knowledge-item" data-index="<?php echo $index; ?>">
                                <div class="knowledge-item-header">
                                    <span class="knowledge-item-title"><?php echo esc_html($doc['title']); ?></span>
                                    <button type="button" class="button remove-knowledge">Remove</button>
                                </div>
                                <div class="knowledge-item-content">
                                    <input type="text" name="knowledge_base[<?php echo $index; ?>][title]" value="<?php echo esc_attr($doc['title']); ?>" placeholder="Document Title" class="regular-text">
                                    <textarea name="knowledge_base[<?php echo $index; ?>][content]" rows="5" class="large-text" placeholder="Document Content"><?php echo esc_textarea($doc['content']); ?></textarea>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <button type="button" id="add-knowledge" class="button">Add Document</button>
                
                <!-- Knowledge Base Template -->
                <script type="text/template" id="knowledge-template">
                    <div class="knowledge-item" data-index="{{index}}">
                        <div class="knowledge-item-header">
                            <span class="knowledge-item-title">New Document</span>
                            <button type="button" class="button remove-knowledge">Remove</button>
                        </div>
                        <div class="knowledge-item-content">
                            <input type="text" name="knowledge_base[{{index}}][title]" placeholder="Document Title" class="regular-text">
                            <textarea name="knowledge_base[{{index}}][content]" rows="5" class="large-text" placeholder="Document Content"></textarea>
                        </div>
                    </div>
                </script>
            </div>
            
            <!-- Tools Tab -->
            <div id="tab-tools">
                <h3>External Tools</h3>
                <p>Configure external API tools and services for your chatbot.</p>
                
                <div id="tools-container">
                    <?php if (!empty($chatbot['tools'])): ?>
                        <?php foreach ($chatbot['tools'] as $index => $tool): ?>
                            <div class="tool-item" data-index="<?php echo $index; ?>">
                                <div class="tool-item-header">
                                    <span class="tool-item-title"><?php echo esc_html($tool['name']); ?></span>
                                    <button type="button" class="button remove-tool">Remove</button>
                                </div>
                                <div class="tool-item-content">
                                    <table class="form-table">
                                        <tr>
                                            <th><label>Tool Name</label></th>
                                            <td><input type="text" name="tools[<?php echo $index; ?>][name]" value="<?php echo esc_attr($tool['name']); ?>" class="regular-text"></td>
                                        </tr>
                                        <tr>
                                            <th><label>Description</label></th>
                                            <td><textarea name="tools[<?php echo $index; ?>][description]" rows="3" class="large-text"><?php echo esc_textarea($tool['description']); ?></textarea></td>
                                        </tr>
                                        <tr>
                                            <th><label>API URL</label></th>
                                            <td><input type="url" name="tools[<?php echo $index; ?>][api_url]" value="<?php echo esc_attr($tool['api_url'] ?? ''); ?>" class="regular-text" placeholder="https://api.example.com/endpoint"></td>
                                        </tr>
                                        <tr>
                                            <th><label>HTTP Method</label></th>
                                            <td>
                                                <select name="tools[<?php echo $index; ?>][method]" class="regular-text">
                                                    <option value="GET" <?php selected($tool['method'] ?? 'GET', 'GET'); ?>>GET</option>
                                                    <option value="POST" <?php selected($tool['method'] ?? 'POST', 'POST'); ?>>POST</option>
                                                    <option value="PUT" <?php selected($tool['method'] ?? 'PUT', 'PUT'); ?>>PUT</option>
                                                    <option value="PATCH" <?php selected($tool['method'] ?? 'PATCH', 'PATCH'); ?>>PATCH</option>
                                                    <option value="DELETE" <?php selected($tool['method'] ?? 'DELETE', 'DELETE'); ?>>DELETE</option>
                                                </select>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th><label>Headers (JSON)</label></th>
                                            <td>
                                                <textarea name="tools[<?php echo $index; ?>][headers]" rows="3" class="large-text" placeholder='[{"key": "Authorization", "value": "Bearer your-token"}, {"key": "Content-Type", "value": "application/json"}]'><?php echo esc_textarea(is_array($tool['headers'] ?? '') ? json_encode($tool['headers'], JSON_PRETTY_PRINT) : ($tool['headers'] ?? '')); ?></textarea>
                                                <p class="description">Optional HTTP headers as JSON array</p>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th><label>Response Field</label></th>
                                            <td>
                                                <input type="text" name="tools[<?php echo $index; ?>][response_field]" value="<?php echo esc_attr($tool['response_field'] ?? ''); ?>" class="regular-text" placeholder="data.result">
                                                <p class="description">Optional: Specific field to extract from JSON response</p>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th><label>Parameters (JSON)</label></th>
                                            <td><textarea name="tools[<?php echo $index; ?>][parameters]" rows="5" class="large-text"><?php echo esc_textarea(is_array($tool['parameters'] ?? '') ? json_encode($tool['parameters'], JSON_PRETTY_PRINT) : ($tool['parameters'] ?? '')); ?></textarea></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <button type="button" id="add-tool" class="button">Add Tool</button>
            </div>
            
            
        </div>
        
        <p class="submit">
            <input type="submit" name="submit_chatbot" class="button-primary" value="<?php echo $is_edit ? 'Update Chatbot' : 'Create Chatbot'; ?>">
            <a href="<?php echo admin_url('admin.php?page=rapidtextai-chatbots'); ?>" class="button">Cancel</a>
        </p>
        
        <?php if ($is_edit): ?>
            <div class="postbox">
                <div class="postbox-header">
                    <h2>Shortcode</h2>
                </div>
                <div class="inside">
                    <p>Use this shortcode to display the chatbot:</p>
                    <code>[rapidtextai_chatbot id="<?php echo $chatbot_id; ?>"]</code>
                </div>
            </div>
        <?php endif; ?>
    </form>
</div>