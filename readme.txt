=== AI Content Writer & Auto Post Generator for WordPress by RapidTextAI ===
Contributors: zinger252  
Tags: ai content generator, gpt-4, article writer, content automation, wordpress ai
Requires at least: 6.0  
Tested up to: 6.8.2  
Requires PHP: 7.0  
Stable tag: trunk  
License: GPLv2 or later  
License URI: https://www.gnu.org/licenses/gpl-2.0.html  

Generate AI-powered articles using GPT-4, GPT-5, Claude, DeepSeek & Grok with automatic images for WordPress.

== Description ==

**RapidTextAI** is a powerful AI article generation plugin for WordPress that transforms how you create content. Leveraging cutting-edge models including **GPT-5**, **Gemini 2.5**, **DeepSeek 3.1**, and **Grok**, this plugin enables you to generate complete, publication-ready articles with just a few clicks.

With **RapidTextAI**, you can:
- Generate full-length, SEO-optimized articles using multiple AI models
- Automatically include relevant images in your generated content
- Create content through an intuitive meta box in your post editor
- Insert AI content blocks in **Gutenberg**, **WP Bakery**, and **Elementor**
- Customize generation parameters for tone, length, and style
- Auto Blogging System Schedule and automate content creation on any topic with customizable frequency

**Key Features**:
- **Multi-Model AI Article Generation**: Choose between GPT5, GPT4, Claude 3.7, Gemini 2.5, DeepSeek V1, Grok 3.
- **Integrated Image Generation**: Automatically add relevant images to your AI-written articles
- **Post Editor Meta Box**: Generate complete articles directly in your WordPress post editor
- **AI Content Blocks**: Insert smaller AI-generated content sections using blocks in your favorite page builder
- **Advanced Customization**: Control article structure, headings, paragraphs, and SEO elements
- **User-Friendly**: Simple interface requiring no technical knowledge
- **Auto Blogging**: Let RapidTextAI Auto Blog For you.


**Models**:

- GPT 3.5
- GPT 4
- GPT 5
- Gemini 2.0
- Gemini 2.5
- Deepseek v 3
- Deepseek R 3
- Grok 2
- Grok 3
- Claude 3.7




= Usage =

Full Demo & Tutorial
[youtube https://www.youtube.com/watch?v=g7tuYLgH5U8]



For detailed terms and privacy, visit the following links:
- [RapidTextAI Terms of Service](https://app.rapidtextai.com/terms)
- [RapidTextAI Privacy Policy](https://app.rapidtextai.com/privacy-policy)

== Installation ==

### Automatic Installation:
1. Go to the **Plugins** menu in WordPress.
2. Click **Add New** and search for "RapidTextAI Blocks".
3. Click **Install Now** and then **Activate** the plugin.
  
### Manual Installation:
1. Download the plugin zip file from the WordPress plugin repository or [RapidTextAI.com](https://app.rapidtextai.com).
2. Unzip the downloaded file.
3. Upload the unzipped folder to the `/wp-content/plugins/` directory of your WordPress installation.
4. Activate the plugin from the 'Plugins' menu in WordPress.

### Getting Started ###

1. Once the plugin is activated, navigate to your page or post editor using either **Elementor** or **WP Bakery**.
2. Insert the "RapidTextAI Block" from the widget list into your page.
3. Enter your prompt in the block to generate AI content instantly.
4. Customize the output text as needed using the editor.

== Chatbot Tools ==

Expose custom PHP callbacks to the RapidTextAI chatbot using the tool registry.

```php
rapidtextai_register_tool_callback('book_room', function( $args ) {
    $name = sanitize_text_field( $args['name'] );
    $date = sanitize_text_field( $args['date'] );

    // your booking logic here
    return [ 'status' => 'success', 'message' => "Booked $name on $date" ];
});

// Save a snippet for later reuse
rapidtextai_save_tool_snippet('greet_user', 'return "Hello " . sanitize_text_field($args["name"]);');
```

See `tool-example.json` for an OpenAI tool definition describing the `book_room` callback.

== Screenshots ==

1. **Screenshot 7** - RapidTextAI Article Advance Generate
![Screenshot 7](screenshot-7.png)

2. **Screenshot 5** - RapidTextAI Article Generate
![Screenshot 5](screenshot-5.png)

3. **Screenshot 6** - RapidTextAI Article Advance Generate
![Screenshot 6](screenshot-6.png)

4. **Screenshot 8** - RapidTextAI Auto Blogging
![Screenshot 8](screenshot-8.png)


== Frequently Asked Questions ==

**Q: What are the system requirements for RapidTextAI Blocks?**  
A: RapidTextAI Blocks requires WordPress version 6.0 or higher and PHP version 7.0 or higher.

**Q: How do I generate AI content using this plugin?**  
A: After activating the plugin, insert the RapidTextAI Block into your Elementor or WP Bakery editor. Enter your content prompt, and the AI will generate relevant text based on your input.

**Q: Is there a limit to the amount of content I can generate?**  
A: The plugin leverages the RapidTextAI platform. Please check the RapidTextAI service for any usage limits or subscription plans.

**Q: Can I customize the generated AI text?**  
A: Yes! Once the AI generates the content, you can fully edit and format it within your WP Bakery or Elementor editor.

== Changelog ==

= 3.5.0 =
* Streaming Content
* Generate Images Again

= 3.4.0 =
* Set Maximum images
* Improved Prompt

= 3.3.0 =
* Added Auto Blogging Logs
* Added Weekly Schedule

= 3.2.0 =
* Added Deepseek 3.1
* Disabled Max Tokens, Enjoy Unlimited tokens

= 3.1.0 =
* Fixed broken images

= 3.0.0 =
* Added Claude and more models
* Improved Admin UI

= 2.5 =
* Introduced Auto Blogging

= 2.1 =
* Major update with advanced AI article generation capabilities
* Added support for multiple AI models: GPT-4, Gemini 2, DeepSeek, and Grok
* Integrated automatic image generation and insertion with articles
* New AI-powered metadata generator for tags, categories, and excerpts
* Enhanced article structure with customizable headings and sections
* Improved user interface for better content generation workflow
* Added bulk article generation capability
* Expanded customization options for tone, style, and content length
* Optimized performance for faster content generation
* Added new templates for different content types
* Enhanced SEO optimization features for generated content

= 1.6 =
* Added Authentication Button

= 1.5 =
* Powerful Article Generate Added as Meta Box

= 1.4 =
* Added Support for Gutenberg

= 1.3 =
* Fixed bugs

= 1.2 =
* Added support for WP Bakery Editor.
* Improved AI prompt accuracy.
* Updated documentation and links.

= 1.1 =
* Added full Elementor integration.
* Fixed bugs related to AI content generation.

= 1.0 =
* Initial release with basic AI block functionality.

== Upgrade Notice ==

= 1.2 =
Upgrade to get support for WP Bakery and improved AI content generation.
