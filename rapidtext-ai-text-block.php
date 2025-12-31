<?php
/*
* Plugin Name: AI Content Writer & Auto Post Generator for WordPress by RapidTextAI
* Description: Add an AI-powered tool to your wordpress to generate articles using advanced options and models for using meta box using Gemini, GPT4, Deepseek and Grok.
* Version: 3.7.1
* Author: Rapidtextai.com
* Text Domain: rapidtextai
* License: GPL-2.0-or-later
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly 
define('RAPIDTEXTAI_PLUGIN_DIR', plugin_dir_path( __FILE__ ));
define('RAPIDTEXTAI_PLUGIN_URL', plugin_dir_url( __FILE__ ));
require_once RAPIDTEXTAI_PLUGIN_DIR . 'rapidtext-ai-meta-box.php';
require_once RAPIDTEXTAI_PLUGIN_DIR . 'rapidtext-ai-check.php';
require_once RAPIDTEXTAI_PLUGIN_DIR . 'rapidtextai-openaihandler.php';
require_once RAPIDTEXTAI_PLUGIN_DIR . 'ext/chatbots/chatbots.php';

add_action('admin_notices', 'rapidtextai_admin_notice');

function rapidtextai_admin_notice() {
    if (empty(get_option('rapidtextai_api_key'))) {
        ?>
        <div class="notice notice-warning is-dismissible">
            <p><?php _e('Authenticate with RapidTextAI. <a href="admin.php?page=rapidtextai-settings">Go to settings</a>', 'rapidtextai'); ?></p>
        </div>
        <?php
    }
}

function rapidtextai_register_gutenberg_block() {
    wp_register_script(
        'rapidtextai-block-editor',
        plugins_url('block/rapidtextai-block.js', __FILE__),
        array('wp-blocks', 'wp-element', 'wp-editor'),
        filemtime(plugin_dir_path(__FILE__) . 'block/rapidtextai-block.js')
    );
    
    register_block_type('rapidtextai/ai-text-block', array(
        'editor_script' => 'rapidtextai-block-editor',
    ));
}
add_action('init', 'rapidtextai_register_gutenberg_block');



// AJAX handler for generating AI content
add_action('wp_ajax_rapidtextai_generate_content', 'rapidtextai_generate_content_callback');
add_action('wp_ajax_nopriv_rapidtextai_generate_content', 'rapidtextai_generate_content_callback');

function rapidtextai_generate_content_callback() {
    if (isset($_POST['prompt']) && isset($_POST['post_id']) && isset($_POST['instance_id'])) {
        $prompt = sanitize_text_field($_POST['prompt']);
        $postid = intval($_POST['post_id']);
        $instance_id = sanitize_text_field($_POST['instance_id']);

        // Call the existing function to generate text
        $generated_text = rapidtextai_generate_text($prompt, $postid, $instance_id);

        // Send the generated content as a response
        if ($generated_text) {
            wp_send_json_success(array('generated_text' => $generated_text));
        } else {
            wp_send_json_error('Error generating content');
        }
    } else {
        wp_send_json_error('Invalid request');
    }
}


add_action('wp_ajax_rapidtextai_generate_content_block', 'rapidtextai_generate_content_callback_block');
add_action('wp_ajax_nopriv_rapidtextai_generate_content_block', 'rapidtextai_generate_content_callback_block');

function rapidtextai_generate_content_callback_block() {
    if (isset($_POST['prompt'])) {
        $prompt = sanitize_text_field($_POST['prompt']);
        // Call the AI text generation function
        $generated_text = rapidtextai_generate_text($prompt, 0, '');

        // Return the generated content
        if ($generated_text) {
            wp_send_json_success(array('generated_text' => $generated_text));
        } else {
            wp_send_json_error('Error generating content');
        }
    } else {
        wp_send_json_error('Invalid request');
    }
}


function rapidtextai_is_wp_bakery_active() {
    return class_exists('Vc_Manager');
}



function rapidtextai_settings_menu() {
    add_menu_page(
        'Rapidtextai Settings',
        'RapidTextAI',
        'manage_options',
        'rapidtextai-settings',
        'rapidtextai_settings_page',
        'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHhtbG5zOnhsaW5rPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5L3hsaW5rIiB3aWR0aD0iMTAyNCIgem9vbUFuZFBhbj0ibWFnbmlmeSIgdmlld0JveD0iMCAwIDc2OCA3NjcuOTk5OTk0IiBoZWlnaHQ9IjEwMjQiIHByZXNlcnZlQXNwZWN0UmF0aW89InhNaWRZTWlkIG1lZXQiIHZlcnNpb249IjEuMCI+PGRlZnM+PGcvPjxjbGlwUGF0aCBpZD0iZWM4NTZjNWVlZSI+PHBhdGggZD0iTSAxMjUgNzYuODAwNzgxIEwgNjUyLjk0OTIxOSA3Ni44MDA3ODEgTCA2NTIuOTQ5MjE5IDQyOSBMIDEyNSA0MjkgWiBNIDEyNSA3Ni44MDA3ODEgIiBjbGlwLXJ1bGU9Im5vbnplcm8iLz48L2NsaXBQYXRoPjxjbGlwUGF0aCBpZD0iZWMzNTNkNGRjMCI+PHBhdGggZD0iTSAxMTUuMTk5MjE5IDM1MyBMIDY0MyAzNTMgTCA2NDMgNTY3IEwgMTE1LjE5OTIxOSA1NjcgWiBNIDExNS4xOTkyMTkgMzUzICIgY2xpcC1ydWxlPSJub256ZXJvIi8+PC9jbGlwUGF0aD48Y2xpcFBhdGggaWQ9ImM2MDFlY2Q5M2QiPjxwYXRoIGQ9Ik0gMzkyIDU3MiBMIDQ4MCA1NzIgTCA0ODAgNjkxLjA1MDc4MSBMIDM5MiA2OTEuMDUwNzgxIFogTSAzOTIgNTcyICIgY2xpcC1ydWxlPSJub256ZXJvIi8+PC9jbGlwUGF0aD48Y2xpcFBhdGggaWQ9IjIzOThiMDkzZDYiPjxwYXRoIGQ9Ik0gMjg4IDU3MiBMIDM3NiA1NzIgTCAzNzYgNjkxLjA1MDc4MSBMIDI4OCA2OTEuMDUwNzgxIFogTSAyODggNTcyICIgY2xpcC1ydWxlPSJub256ZXJvIi8+PC9jbGlwUGF0aD48L2RlZnM+PGcgZmlsbD0iI2ZmZmZmZiIgZmlsbC1vcGFjaXR5PSIxIj48ZyB0cmFuc2Zvcm09InRyYW5zbGF0ZSgzNzYuNzE2NjM1LCAyNDguMzIwMDA5KSI+PGc+PHBhdGggZD0iTSA1LjM3NSAwLjI2NTYyNSBDIDQuNDg4MjgxIDAuMjY1NjI1IDMuNzE4NzUgMC4xMDE1NjIgMy4wNjI1IC0wLjIxODc1IEMgMi40MTQwNjIgLTAuNTUwNzgxIDEuOTE0MDYyIC0xLjAzMTI1IDEuNTYyNSAtMS42NTYyNSBDIDEuMjE4NzUgLTIuMjgxMjUgMS4wNDY4NzUgLTMuMDM1MTU2IDEuMDQ2ODc1IC0zLjkyMTg3NSBDIDEuMDQ2ODc1IC00Ljc0MjE4OCAxLjIxODc1IC01LjQyOTY4OCAxLjU2MjUgLTUuOTg0Mzc1IEMgMS45MTQwNjIgLTYuNTM1MTU2IDIuNDU3MDMxIC02Ljk4ODI4MSAzLjE4NzUgLTcuMzQzNzUgQyAzLjkyNTc4MSAtNy42OTUzMTIgNC44OTA2MjUgLTcuOTY4NzUgNi4wNzgxMjUgLTguMTU2MjUgQyA2Ljg5ODQzOCAtOC4yODkwNjIgNy41MjM0MzggLTguNDM3NSA3Ljk1MzEyNSAtOC41OTM3NSBDIDguMzc4OTA2IC04Ljc1IDguNjY0MDYyIC04LjkyNTc4MSA4LjgxMjUgLTkuMTI1IEMgOC45Njg3NSAtOS4zMzIwMzEgOS4wNDY4NzUgLTkuNTg1OTM4IDkuMDQ2ODc1IC05Ljg5MDYyNSBDIDkuMDQ2ODc1IC0xMC4zMzU5MzggOC44OTA2MjUgLTEwLjY3NTc4MSA4LjU3ODEyNSAtMTAuOTA2MjUgQyA4LjI3MzQzOCAtMTEuMTQ0NTMxIDcuNzY1NjI1IC0xMS4yNjU2MjUgNy4wNDY4NzUgLTExLjI2NTYyNSBDIDYuMjg1MTU2IC0xMS4yNjU2MjUgNS41NTQ2ODggLTExLjA5Mzc1IDQuODU5Mzc1IC0xMC43NSBDIDQuMTcxODc1IC0xMC40MDYyNSAzLjU3MDMxMiAtOS45NTMxMjUgMy4wNjI1IC05LjM5MDYyNSBMIDIuODEyNSAtOS4zOTA2MjUgTCAxLjM1OTM3NSAtMTEuNTc4MTI1IEMgMi4wNTQ2ODggLTEyLjM1OTM3NSAyLjkxMDE1NiAtMTIuOTYwOTM4IDMuOTIxODc1IC0xMy4zOTA2MjUgQyA0LjkyOTY4OCAtMTMuODI4MTI1IDYuMDMxMjUgLTE0LjA0Njg3NSA3LjIxODc1IC0xNC4wNDY4NzUgQyA5LjAzOTA2MiAtMTQuMDQ2ODc1IDEwLjM1OTM3NSAtMTMuNjU2MjUgMTEuMTcxODc1IC0xMi44NzUgQyAxMS45OTIxODggLTEyLjEwMTU2MiAxMi40MDYyNSAtMTEuMDAzOTA2IDEyLjQwNjI1IC05LjU3ODEyNSBMIDEyLjQwNjI1IC0zLjU5Mzc1IEMgMTIuNDA2MjUgLTIuOTc2NTYyIDEyLjY3NTc4MSAtMi42NzE4NzUgMTMuMjE4NzUgLTIuNjcxODc1IEMgMTMuNDI1NzgxIC0yLjY3MTg3NSAxMy42Mjg5MDYgLTIuNzA3MDMxIDEzLjgyODEyNSAtMi43ODEyNSBMIDE0LjAxNTYyNSAtMi43MzQzNzUgTCAxNC4yNjU2MjUgLTAuMzI4MTI1IEMgMTQuMDY2NDA2IC0wLjIwMzEyNSAxMy43OTY4NzUgLTAuMDk3NjU2MiAxMy40NTMxMjUgLTAuMDE1NjI1IEMgMTMuMTE3MTg4IDAuMDU0Njg3NSAxMi43NDIxODggMC4wOTM3NSAxMi4zMjgxMjUgMC4wOTM3NSBDIDExLjUwMzkwNiAwLjA5Mzc1IDEwLjg1OTM3NSAtMC4wNjI1IDEwLjM5MDYyNSAtMC4zNzUgQyA5LjkyOTY4OCAtMC42ODc1IDkuNjAxNTYyIC0xLjE3OTY4OCA5LjQwNjI1IC0xLjg1OTM3NSBMIDkuMTQwNjI1IC0xLjg5MDYyNSBDIDguNDcyNjU2IC0wLjQ1MzEyNSA3LjIxODc1IDAuMjY1NjI1IDUuMzc1IDAuMjY1NjI1IFogTSA2LjQ2ODc1IC0yLjMyODEyNSBDIDcuMjY5NTMxIC0yLjMyODEyNSA3LjkxMDE1NiAtMi41OTM3NSA4LjM5MDYyNSAtMy4xMjUgQyA4Ljg2NzE4OCAtMy42NjQwNjIgOS4xMDkzNzUgLTQuNDE0MDYyIDkuMTA5Mzc1IC01LjM3NSBMIDkuMTA5Mzc1IC02Ljc2NTYyNSBMIDguODc1IC02LjgyODEyNSBDIDguNjY0MDYyIC02LjY2MDE1NiA4LjM5MDYyNSAtNi41MTk1MzEgOC4wNDY4NzUgLTYuNDA2MjUgQyA3LjcxMDkzOCAtNi4yODkwNjIgNy4yMjY1NjIgLTYuMTc5Njg4IDYuNTkzNzUgLTYuMDc4MTI1IEMgNS44NDM3NSAtNS45NTMxMjUgNS4zMDA3ODEgLTUuNzM0Mzc1IDQuOTY4NzUgLTUuNDIxODc1IEMgNC42NDQ1MzEgLTUuMTE3MTg4IDQuNDg0Mzc1IC00LjY5MTQwNiA0LjQ4NDM3NSAtNC4xNDA2MjUgQyA0LjQ4NDM3NSAtMy41NDY4NzUgNC42NTYyNSAtMy4wOTM3NSA1IC0yLjc4MTI1IEMgNS4zNTE1NjIgLTIuNDc2NTYyIDUuODQzNzUgLTIuMzI4MTI1IDYuNDY4NzUgLTIuMzI4MTI1IFogTSA2LjQ2ODc1IC0yLjMyODEyNSAiLz48L2c+PC9nPjwvZz48cGF0aCBmaWxsPSIjNzM3MzczIiBkPSJNIDUxMy43NzM0MzggMzkxLjQ4NDM3NSBDIDUxMy43NzM0MzggNDEwLjg5MDYyNSA0OTguMDM1MTU2IDQyNi42NjQwNjIgNDc4LjU4OTg0NCA0MjYuNjY0MDYyIEMgNDU5LjE0NDUzMSA0MjYuNjY0MDYyIDQ0My40MDYyNSA0MTAuOTI5Njg4IDQ0My40MDYyNSAzOTEuNDg0Mzc1IEMgNDQzLjQwNjI1IDM3Mi4wMzkwNjIgNDU5LjE0NDUzMSAzNTYuMzAwNzgxIDQ3OC41ODk4NDQgMzU2LjMwMDc4MSBDIDQ5OC4wMzUxNTYgMzU2LjMwMDc4MSA1MTMuNzczNDM4IDM3Mi4wMzkwNjIgNTEzLjc3MzQzOCAzOTEuNDg0Mzc1IFogTSA1MTMuNzczNDM4IDM5MS40ODQzNzUgIiBmaWxsLW9wYWNpdHk9IjEiIGZpbGwtcnVsZT0ibm9uemVybyIvPjxwYXRoIGZpbGw9IiM3MzczNzMiIGQ9Ik0gMjg5LjQ0NTMxMiAzNTYuMzAwNzgxIEMgMzA4Ljg1MTU2MiAzNTYuMzAwNzgxIDMyNC42Mjg5MDYgMzcyLjAzOTA2MiAzMjQuNjI4OTA2IDM5MS40ODQzNzUgQyAzMjQuNjI4OTA2IDQxMC44OTA2MjUgMzA4Ljg5MDYyNSA0MjYuNjY0MDYyIDI4OS40NDUzMTIgNDI2LjY2NDA2MiBDIDI3MCA0MjYuNjY0MDYyIDI1NC4yNjE3MTkgNDEwLjg5MDYyNSAyNTQuMjYxNzE5IDM5MS40ODQzNzUgQyAyNTQuMjYxNzE5IDM3Mi4wMzkwNjIgMjcwIDM1Ni4zMDA3ODEgMjg5LjQ0NTMxMiAzNTYuMzAwNzgxIFogTSAyODkuNDQ1MzEyIDM1Ni4zMDA3ODEgIiBmaWxsLW9wYWNpdHk9IjEiIGZpbGwtcnVsZT0ibm9uemVybyIvPjxnIGNsaXAtcGF0aD0idXJsKCNlYzg1NmM1ZWVlKSI+PHBhdGggZmlsbD0iIzczNzM3MyIgZD0iTSA1OTYuODk4NDM4IDQyOC45NTcwMzEgTCA2NTIuNjE3MTg4IDQyOC45NTcwMzEgQyA2NTIuNjUyMzQ0IDQyNy43MTg3NSA2NTIuNzk2ODc1IDQyNi41MTk1MzEgNjUyLjc5Njg3NSA0MjUuMzIwMzEyIEwgNjUyLjc5Njg3NSAzNTcuNjA5Mzc1IEMgNjUyLjc5Njg3NSAyNzkuNjQ0NTMxIDU4OS4zNzUgMjE2LjIyMjY1NiA1MTEuNDEwMTU2IDIxNi4yMjI2NTYgTCAzOTYuNTkzNzUgMjE2LjIyMjY1NiBMIDM5Ni41OTM3NSAxNTUuNjMyODEyIEMgNDEyLjczMDQ2OSAxNTAuMjg5MDYyIDQyNC40Njg3NSAxMzUuMjc3MzQ0IDQyNC40Njg3NSAxMTcuMzU5Mzc1IEMgNDI0LjQ2ODc1IDk1LjAwNzgxMiA0MDYuMzMyMDMxIDc2Ljg3MTA5NCAzODMuOTgwNDY5IDc2Ljg3MTA5NCBDIDM2MS42Mjg5MDYgNzYuODcxMDk0IDM0My40OTIxODggOTUuMDA3ODEyIDM0My40OTIxODggMTE3LjM1OTM3NSBDIDM0My40OTIxODggMTM1LjI3NzM0NCAzNTUuMjMwNDY5IDE1MC4zMjQyMTkgMzcxLjM2NzE4OCAxNTUuNjMyODEyIEwgMzcxLjM2NzE4OCAyMTYuMjU3ODEyIEwgMjU2LjU4NTkzOCAyMTYuMjU3ODEyIEMgMTk3LjY2Nzk2OSAyMTYuMjU3ODEyIDE0Ny4xMTMyODEgMjUyLjUzMTI1IDEyNS44ODY3MTkgMzAzLjg1MTU2MiBMIDE5MC4wMzUxNTYgMzAzLjg1MTU2MiBDIDIwNS43MzgyODEgMjg0LjQ0MTQwNiAyMjkuNzI2NTYyIDI3MS45NzY1NjIgMjU2LjU4NTkzOCAyNzEuOTc2NTYyIEwgNTExLjQ0NTMxMiAyNzEuOTc2NTYyIEMgNTU4LjY2MDE1NiAyNzEuOTc2NTYyIDU5Ny4wNzgxMjUgMzEwLjM5NDUzMSA1OTcuMDc4MTI1IDM1Ny42MDkzNzUgTCA1OTcuMDc4MTI1IDQyNS4zMjAzMTIgQyA1OTcuMDc4MTI1IDQyNi41NTg1OTQgNTk2LjkzMzU5NCA0MjcuNzU3ODEyIDU5Ni44OTg0MzggNDI4Ljk1NzAzMSBaIE0gMzgwLjUyNzM0NCA5OS45MTQwNjIgQyAzODAuNTI3MzQ0IDEwNi4yMzgyODEgMzc1LjQwMjM0NCAxMTEuMzYzMjgxIDM2OS4wNzgxMjUgMTExLjM2MzI4MSBDIDM2Mi43NTM5MDYgMTExLjM2MzI4MSAzNTcuNjI4OTA2IDEwNi4yMzgyODEgMzU3LjYyODkwNiA5OS45MTQwNjIgQyAzNTcuNjI4OTA2IDkzLjU4OTg0NCAzNjIuNzUzOTA2IDg4LjQ2NDg0NCAzNjkuMDc4MTI1IDg4LjQ2NDg0NCBDIDM3NS40MDIzNDQgODguNDY0ODQ0IDM4MC41MjczNDQgOTMuNjI1IDM4MC41MjczNDQgOTkuOTE0MDYyIFogTSAzODAuNTI3MzQ0IDk5LjkxNDA2MiAiIGZpbGwtb3BhY2l0eT0iMSIgZmlsbC1ydWxlPSJub256ZXJvIi8+PC9nPjxnIGNsaXAtcGF0aD0idXJsKCNlYzM1M2Q0ZGMwKSI+PHBhdGggZmlsbD0iIzczNzM3MyIgZD0iTSAxMTUuNDE3OTY5IDM1My45NzI2NTYgTCAxNzEuMTcxODc1IDM1My45NzI2NTYgQyAxNzEuMTM2NzE5IDM1NS4yMTA5MzggMTcwLjk5MjE4OCAzNTYuNDEwMTU2IDE3MC45OTIxODggMzU3LjYwOTM3NSBMIDE3MC45OTIxODggNDI1LjMyMDMxMiBDIDE3MC45OTIxODggNDcyLjUzNTE1NiAyMDkuNDEwMTU2IDUxMC45NTMxMjUgMjU2LjYyNSA1MTAuOTUzMTI1IEwgNTExLjQ4NDM3NSA1MTAuOTUzMTI1IEMgNTM4LjM0Mzc1IDUxMC45NTMxMjUgNTYyLjMzMjAzMSA0OTguNDg0Mzc1IDU3OC4wMzUxNTYgNDc5LjA3ODEyNSBMIDY0Mi4xODM1OTQgNDc5LjA3ODEyNSBDIDYyMC45OTYwOTQgNTMwLjQzMzU5NCA1NzAuNDAyMzQ0IDU2Ni42NzE4NzUgNTExLjQ4NDM3NSA1NjYuNjcxODc1IEwgMjU2LjU4NTkzOCA1NjYuNjcxODc1IEMgMTc4LjYyNSA1NjYuNjcxODc1IDExNS4xOTkyMTkgNTAzLjI0NjA5NCAxMTUuMTk5MjE5IDQyNS4yODUxNTYgTCAxMTUuMTk5MjE5IDM1Ny42MDkzNzUgQyAxMTUuMTk5MjE5IDM1Ni4zNzEwOTQgMTE1LjM4MjgxMiAzNTUuMTcxODc1IDExNS40MTc5NjkgMzUzLjk3MjY1NiBaIE0gMTE1LjQxNzk2OSAzNTMuOTcyNjU2ICIgZmlsbC1vcGFjaXR5PSIxIiBmaWxsLXJ1bGU9Im5vbnplcm8iLz48L2c+PGcgY2xpcC1wYXRoPSJ1cmwoI2M2MDFlY2Q5M2QpIj48cGF0aCBmaWxsPSIjNzM3MzczIiBkPSJNIDQ3OS40MjU3ODEgNTg2Ljc2OTUzMSBMIDQ3OS40MjU3ODEgNjc2LjY5MTQwNiBMIDQ2Mi45MjU3ODEgNjkxLjEyMTA5NCBMIDM5Mi4xOTUzMTIgNjQ2LjMwNDY4OCBMIDM5Mi4xOTUzMTIgNjE3LjEyMTA5NCBMIDQ2Mi45MjU3ODEgNTcyLjMwNDY4OCBaIE0gNDc5LjQyNTc4MSA1ODYuNzY5NTMxICIgZmlsbC1vcGFjaXR5PSIxIiBmaWxsLXJ1bGU9Im5vbnplcm8iLz48L2c+PGcgY2xpcC1wYXRoPSJ1cmwoIzIzOThiMDkzZDYpIj48cGF0aCBmaWxsPSIjNzM3MzczIiBkPSJNIDM3NS44Mzk4NDQgNjE3LjEyMTA5NCBMIDM3NS44Mzk4NDQgNjQ2LjMwNDY4OCBMIDMwNS4xMDkzNzUgNjkxLjEyMTA5NCBMIDI4OC42MDkzNzUgNjc2LjY5MTQwNiBMIDI4OC42MDkzNzUgNTg2Ljc2OTUzMSBMIDMwNS4xMDkzNzUgNTcyLjMwNDY4OCBaIE0gMzc1LjgzOTg0NCA2MTcuMTIxMDk0ICIgZmlsbC1vcGFjaXR5PSIxIiBmaWxsLXJ1bGU9Im5vbnplcm8iLz48L2c+PHBhdGggZmlsbD0iIzczNzM3MyIgZD0iTSAzNzguMjAzMTI1IDYxNy40NDkyMTkgTCAzODkuODMyMDMxIDYxNy40NDkyMTkgTCAzODkuODMyMDMxIDY0NS45ODA0NjkgTCAzNzguMjAzMTI1IDY0NS45ODA0NjkgWiBNIDM3OC4yMDMxMjUgNjE3LjQ0OTIxOSAiIGZpbGwtb3BhY2l0eT0iMSIgZmlsbC1ydWxlPSJub256ZXJvIi8+PC9zdmc+',
    );
}
add_action('admin_menu', 'rapidtextai_settings_menu');



function rapidtextai_settings_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    // Retrieve the current API key
    $current_api_key = get_option('rapidtextai_api_key', '');

    include(plugin_dir_path(__FILE__) . 'admin/settings.php');
}

add_action('wp_ajax_rapidtextai_save_api_key', 'rapidtextai_save_api_key');
function rapidtextai_save_api_key() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Permission denied.'));
    }

    if (!isset($_POST['_wpnonce']) || !wp_verify_nonce(sanitize_text_field($_POST['_wpnonce']), 'rapidtextai_save_api_key_nonce')) {
        wp_send_json_error(array('message' => 'Nonce verification failed.'));
    }

    $api_key = sanitize_text_field($_POST['api_key']);
    update_option('rapidtextai_api_key', $api_key);

    wp_send_json_success(array('message' => 'API Key saved successfully.'));
}



/**
 * WP Bakery
 */
if(rapidtextai_is_wp_bakery_active()){
    function rapidtextai_ai_text_block_vc_element() {
        vc_map(array(
            'name' => __('AI Text Block', 'rapidtextai'),
            'base' => 'rapidtextai_ai_text_block',
            'category' => __('Content', 'rapidtextai'),
            'params' => array(
                array(
                    'type' => 'textarea',
                    'heading' => esc_html__('Prompt', 'rapidtextai'),
                    'param_name' => 'wpb_input_text',
                    'description' => esc_html__('Enter the prompt to generate AI text, i.e Write an about use section for my company which manufacture light bulbs', 'rapidtextai')
                ),
                array(
                    "type" => "textarea",
                    "heading" => esc_html__( "Prompt Output", 'rapidtextai'),
                    "param_name" => "wpb_input_text_output", 
                    'description' => esc_html__('Prompt response will be here, edit here if needed', 'rapidtextai'),
                ),
            ),
            'shortcode' => 'rapidtextai_ai_text_block_shortcode',
        ));
    }
    add_action('vc_before_init', 'rapidtextai_ai_text_block_vc_element');
    
    

    function rapidtextai_ai_text_block_shortcode($atts, $sc_content = null,$instance_id) {
        extract(shortcode_atts(array(
            'wpb_input_text' => '',
            'wpb_input_text_output' => '',
        ), $atts));

        $postid = get_the_ID();

        global $post;
        $new_value = '';

        $shortcode = 'rapidtextai_ai_text_block';

        // Define the attribute you want to update
        $attribute_to_update = 'wpb_input_text_output';
        $content = $post->post_content;
        // Use a regular expression to find all instances of the shortcode
        $pattern = get_shortcode_regex([$shortcode]);
        preg_match_all('/' . $pattern . '/s', $content, $matches);
           
        if (isset($matches[0]) && isset($atts['wpb_input_text']) && trim($atts['wpb_input_text']) != '') {
            foreach ($matches[0] as $shortcode_instance) {

                $attribute_pattern = '/' . $attribute_to_update . '=["\'](.*?)["\']/';
                preg_match($attribute_pattern, $shortcode_instance, $attribute_match);

                // // Check if the attribute was found
                if (!isset($attribute_match[1])) {
                    $new_value = rapidtextai_generate_text($atts['wpb_input_text'],$postid,$instance_id);;                   
                    $updated_shortcode = str_replace('rapidtextai_ai_text_block','rapidtextai_ai_text_block wpb_input_text_output="'.$new_value.'"', $shortcode_instance);
                    $content = str_replace($shortcode_instance, $updated_shortcode, $content);
                }
            }
        }


        
        wp_update_post(array('ID'=>$postid,'post_content'=>$content));
        return isset($atts['wpb_input_text_output']) ? $atts['wpb_input_text_output'] : $new_value;
    } // func
    add_shortcode('rapidtextai_ai_text_block', 'rapidtextai_ai_text_block_shortcode');
}



/***
 * Elementor
 */

 // Hook to ensure Elementor is fully loaded before registering the widget
add_action('elementor/init', 'rapidtextai_is_elementor_active');


function rapidtextai_is_elementor_active(){
    // Register the custom widget
    add_action( 'elementor/widgets/widgets_registered', function() {
        class rapidtextai_AITextBlock_Elementor_Widget extends \Elementor\Widget_Base {

            public function get_name() {
                return 'rapidtextai-ai-text-block';
            }

            public function get_title() {
                return __('AI Text Block', 'rapidtextai');
            }

        

            protected function register_controls() {
                
                $this->start_controls_section(
                    'content_section',
                    [
                        'label' => esc_html__('Content', 'rapidtextai'),
                        'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
                    ]
                );

             
                $this->add_control(
                    'input_text',
                    [
                        'label' => esc_html__('Prompt', 'rapidtextai-ai-text-block-elementor'),
                        'type' => \Elementor\Controls_Manager::TEXTAREA,
                        'placeholder' => esc_html__('Write an about use section for my company which manufacture light bulbs.', 'rapidtextai-ai-text-block-elementor'),
                        'input_type' => 'text',
                        'label_block' => true,
                        'attributes' => [
                            'class' => 'rapidtextai-prompt-textarea',  // Custom class to identify the textarea
                        ],
                    ]
                );
                
             
                $this->add_control(
                    'input_text_output',
                    [
                        'label' => esc_html__( 'Prompt Output', 'rapidtextai-ai-text-block-elementor' ),
                        'description' => esc_html__('Prompt response will be here, edit here if needed', 'rapidtextai'),
                        'type' => \Elementor\Controls_Manager::TEXTAREA
                    ]
                );
           
           
                $this->end_controls_section();
            } // function


            public function render() {
                $postid = get_the_ID();
                $settings = $this->get_settings_for_display();

                $jsonelem_str = get_metadata('post',$postid, '_elementor_data', true );
                $jsonelem_arr = $jsonelem_str ? json_decode( $jsonelem_str, true ) : false;
                $instance_id = $this->get_id();

                if($jsonelem_arr){
                    $input_text = $settings['input_text'];
                    $input_text_output = $settings['input_text_output'];
                
                    $generated_text = '';

                


                    if($input_text_output && trim($input_text_output) != '')
                    $generated_text = $input_text_output;
                    else{
                        if($input_text && trim($input_text) != ''){
                            $generated_text = rapidtextai_generate_text($input_text,$postid,$instance_id);
                            foreach ($jsonelem_arr as $key => $value) {
                                if($value['elements'][0]['elements'][0]['id'] == $instance_id){
                                    $jsonelem_arr[$key]['elements'][0]['elements'][0]['settings']['input_text_output'] = $generated_text;
                                    $jsonvalue = wp_slash( wp_json_encode( $jsonelem_arr ) );
                                    update_metadata( 'post', $postid, '_elementor_data', $jsonvalue );
                                    break;
                                } // if($value['elements'][0
                            } // foreach

                        } // if($input_text && trim($input_text
                    
                    } // ELSE of  if($input_text_output &&
                


                    echo wp_kses_post($generated_text);
                } // $jsonelem_arr
            } // func

            // protected function content_template() {}

            // public function render_plain_content( $instance = [] ) {}

          
            

        }  // clASS
        // Register the widget in Elementor
        \Elementor\Plugin::instance()->widgets_manager->register_widget_type( new \rapidtextai_AITextBlock_Elementor_Widget() );

        });
    //add_action( 'elementor/widgets/register', 'rapidtextai_register_block_widget' );
}



function rapidtextai_generate_text($prompt,$postid,$instance_id){
    $apikey = get_option('rapidtextai_api_key','c52ec1-5c73cd-e411e2-d8dc2d-491514');
    // Define the URL with query parameters
    $url = "https://app.rapidtextai.com/openai/detailedarticle-v2?gigsixkey=" . $apikey;
    $request_data = array(
            'type' => 'custom_prompt',
            'toneOfVoice' => '', // Assuming tone is sent as POST data
            'language' => '', // Assuming language is sent as POST data
            'text' => '',
            'temperature' => '0.7', // Assuming temperature is sent as POST data
            'custom_prompt' => $prompt,
    );
    $json_data = wp_json_encode($request_data);
    
    $response = wp_remote_post($url, array(
        'body' => $json_data,
       'method' => 'POST',
        //'timeout' => 45,
        //'redirection' => 5,
        //'httpversion' => '1.0',
        //'blocking' => true,
        'sslverify' => false,
        'headers' => array('Content-Type' => 'multipart/form-data'),
    ));

    if (!is_wp_error($response)) {
        $http_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);

        if ($http_code === 200) {
            $content = rapidtextai_simple_markdown_to_html($body); 
            return $content;
        }
        else
        return 'Unauthorized Access, check your Rapidtextai.com Key';
    }
}
function rapidtextai_simple_markdown_to_html($markdown) {
    // Convert headers (we'll use a callback inside preg_replace_callback instead of preg_replace)
    $markdown = preg_replace_callback('/^(#{1,6})\s*(.*?)\s*#*\s*(?:\n+|$)/m', function($matches) {
        $level = strlen($matches[1]);
        return "<h{$level}>{$matches[2]}</h{$level}>";
    }, $markdown);

    // Convert bold (**text** or __text__)
    $markdown = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $markdown);
    $markdown = preg_replace('/__(.*?)__/', '<strong>$1</strong>', $markdown);

    // Convert italic (*text* or _text_)
    $markdown = preg_replace('/\*(.*?)\*/', '<em>$1</em>', $markdown);
    $markdown = preg_replace('/_(.*?)_/', '<em>$1</em>', $markdown);

    // Convert links [text](url)
    $markdown = preg_replace('/\[([^\[]+)\]\((.*?)\)/', '<a href="$2">$1</a>', $markdown);

    // Convert unordered lists
    $markdown = preg_replace('/^\s*[\*\+\-]\s+(.*)/m', '<li>$1</li>', $markdown);
    $markdown = preg_replace('/(<li>.*<\/li>)/s', '<ul>$1</ul>', $markdown);

    // Convert ordered lists
    $markdown = preg_replace('/^\d+\.\s+(.*)/m', '<li>$1</li>', $markdown);
    $markdown = preg_replace('/(<li>.*<\/li>)/s', '<ol>$1</ol>', $markdown);

    // Convert blockquotes
    $markdown = preg_replace('/^\s*>\s+(.*)/m', '<blockquote>$1</blockquote>', $markdown);

    // Convert code blocks
    $markdown = preg_replace('/```(.*?)```/s', '<pre><code>$1</code></pre>', $markdown);

    // Convert inline code
    $markdown = preg_replace('/`([^`]+)`/', '<code>$1</code>', $markdown);

    // Convert newlines to paragraphs
    $markdown = preg_replace('/\n\n/', '</p><p>', $markdown);
    $markdown = '<p>' . $markdown . '</p>';  // Wrap with paragraph tags

    // Cleanup multiple paragraph tags
    $markdown = str_replace('<p></p>', '', $markdown);

    return $markdown;
}
// Add Auto Blogging feature
function rapidtextai_add_auto_blogging_menu() {
    add_submenu_page(
        'rapidtextai-settings', // Parent menu slug
        'Auto Blogging', // Page title
        'Auto Blogging', // Menu title
        'manage_options', // Capability
        'rapidtextai-auto-blogging', // Menu slug
        'rapidtextai_auto_blogging_page' // Callback function
    );
}
add_action('admin_menu', 'rapidtextai_add_auto_blogging_menu');

// Auto Blogging settings page
function rapidtextai_auto_blogging_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    // Save settings
    if (isset($_POST['rapidtextai_save_auto_blogging']) && isset($_POST['rapidtextai_auto_blogging_nonce']) && 
        wp_verify_nonce($_POST['rapidtextai_auto_blogging_nonce'], 'rapidtextai_auto_blogging')) {
        
        $settings = array(
            'enabled' => isset($_POST['rapidtextai_auto_blogging_enabled']) ? 1 : 0,
            'schedule' => sanitize_text_field($_POST['rapidtextai_schedule']),
            'post_status' => sanitize_text_field($_POST['rapidtextai_post_status']),
            'post_author' => intval($_POST['rapidtextai_post_author']),
            'topics' => sanitize_textarea_field($_POST['rapidtextai_topics']),
            'model' => sanitize_text_field($_POST['rapidtextai_model']),
            'tone' => sanitize_text_field($_POST['rapidtextai_tone']),
            'post_category' => isset($_POST['rapidtextai_post_category']) ? array_map('intval', $_POST['rapidtextai_post_category']) : array(),
            'generate_tags' => isset($_POST['rapidtextai_generate_tags']) ? 1 : 0,
            'tags_count' => intval($_POST['rapidtextai_tags_count']),
            'excerpt_length' => intval($_POST['rapidtextai_excerpt_length']),
            'taxonomy_limit' => intval($_POST['rapidtextai_taxonomy_limit']),
            'include_images' => isset($_POST['rapidtextai_include_images']) ? 1 : 0,
            'include_featured_image' => isset($_POST['rapidtextai_include_featured_image']) ? 1 : 0,
            'max_images' => intval($_POST['rapidtextai_max_images']),
            'enable_logging' => isset($_POST['rapidtextai_enable_logging']) ? 1 : 0,
        );
        
        update_option('rapidtextai_auto_blogging', $settings);
        
        // Handle cron job scheduling
        if ($settings['enabled']) {
            if (!wp_next_scheduled('rapidtextai_auto_blogging_cron')) {
                if ($settings['schedule'] == 'hourly') {
                    wp_schedule_event(time(), 'hourly', 'rapidtextai_auto_blogging_cron');
                } elseif ($settings['schedule'] == 'twicedaily') {
                    wp_schedule_event(time(), 'twicedaily', 'rapidtextai_auto_blogging_cron');
                } elseif ($settings['schedule'] == 'daily') {
                    wp_schedule_event(time(), 'daily', 'rapidtextai_auto_blogging_cron');
                } elseif ($settings['schedule'] == 'weekly') {
                    wp_schedule_event(time(), 'weekly', 'rapidtextai_auto_blogging_cron');
                }

            }
        } else {
            wp_clear_scheduled_hook('rapidtextai_auto_blogging_cron');
        }
        
        echo '<div class="notice notice-success is-dismissible"><p>Settings saved successfully.</p></div>';
    }
    
    // Get current settings
    $settings = get_option('rapidtextai_auto_blogging', array(
        'enabled' => 0,
        'schedule' => 'daily',
        'post_status' => 'draft',
        'post_author' => get_current_user_id(),
        'topics' => '',
        'model' => 'gpt-3.5-turbo',
        'tone' => 'informative',
        'post_category' => array(1), // Default category
        'generate_tags' => 1,
        'tags_count' => 5,
        'excerpt_length' => 55,
        'taxonomy_limit' => 5,
        'include_images' => 1,
        'max_images' => 5
    ));
    
    // Get all authors
    $authors = get_users(array('role__in' => array('administrator', 'editor', 'author')));
    
    // Get all categories
    $categories = get_categories(array('hide_empty' => 0));
    include( RAPIDTEXTAI_PLUGIN_DIR .'/admin/auto_blogging_page.php'); // Include the template file for the settings page
    ?>
    
    
    <?php
    
    // Handle manual post generation
    if (isset($_POST['rapidtextai_generate_post_now']) && isset($_POST['rapidtextai_generate_now_nonce']) && 
        wp_verify_nonce($_POST['rapidtextai_generate_now_nonce'], 'rapidtextai_generate_now')) {
        
        rapidtextai_generate_auto_blog_post();
        echo '<div class="notice notice-success is-dismissible"><p>Post generation triggered. Check your posts.</p></div>';
    }
}

// rapidtextai_improve_topics
add_action('wp_ajax_rapidtextai_improve_topics', 'rapidtextai_improve_topics_callback');
add_action('wp_ajax_nopriv_rapidtextai_improve_topics', 'rapidtextai_improve_topics_callback');

function rapidtextai_improve_topics_callback() {
    // Check nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'rapidtextai_improve_topics_nonce')) {
        wp_send_json_error(array('message' => 'Security check failed.'));
        return;
    }
    
    // Check if topics were provided
    if (empty($_POST['topics'])) {
        wp_send_json_error(array('message' => 'No topics provided.'));
        return;
    }
    
    $topics = sanitize_textarea_field($_POST['topics']);
    $topics_array = explode("\n", $topics);
    $topics_array = array_map('trim', $topics_array);
    $topics_array = array_filter($topics_array);
    
    // Limit to prevent abuse
    if (count($topics_array) > 50) {
        $topics_array = array_slice($topics_array, 0, 50);
    }
    
    $api_key = get_option('rapidtextai_api_key', '');
    if (empty($api_key)) {
        wp_send_json_error(array('message' => 'API key not found. Please set up your RapidTextAI authentication.'));
        return;
    }
    
    // Create prompt for improving topics
    $prompt = "I have a list of blog post topics that need improvement to make them more specific, engaging, and SEO-friendly. Please improve each of these topics, topic keywords should be set one per line:\n\n";
    $prompt .= implode("\n", $topics_array);
    $prompt .= "\n\nFor each topic:\n1. Make it more specific\n2. Add relevant keywords\n3. Make it more engaging\n4. Format as a headline\n5. Return one improved version per topic, group each topic specific keywords headline in a single line\n\n 
    \n\nUse same language as the input topics
    \n\nexample response Example: Topic: Complete Guide to Sustainable Gardening for Beginners; Keywords: sustainable gardening, eco-friendly plants, organic fertilizer, water conservation, composting methods; Tone: friendly and informative; Audience: homeowners and gardening beginners; Length: 2500-3000 words; CTA: Download our free sustainable gardening checklist";
    
    // Call RapidTextAI API using the chat completions endpoint
    $chat_endpoint = 'https://app.rapidtextai.com/openai/v1/chat/completions?gigsixkey=' . $api_key;
    $chat_body = [
        'model' => 'gpt-3.5-turbo',
        'messages' => [
            [
                'role' => 'system',
                'content' => "You are a helpful assistant that improves blog post topics."
            ],
            [
                'role' => 'user', 
                'content' => $prompt
            ]
        ],
        'max_tokens' => 500,
        'temperature' => 0.7
    ];
    
    $response = wp_remote_post($chat_endpoint, [
        'headers' => [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $api_key,
        ],
        'body' => json_encode($chat_body),
        'timeout' => 30,
    ]);
    
    if (is_wp_error($response)) {
        wp_send_json_error(array('message' => 'Error connecting to RapidTextAI: ' . $response->get_error_message()));
        return;
    }
    
    $http_code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    
    if ($http_code !== 200) {
        wp_send_json_error(array('message' => 'API request failed with code ' . $http_code));
        return;
    }
    
    // Process the response
    $response_data = json_decode($body, true);
    if (!isset($response_data['choices'][0]['message']['content'])) {
        wp_send_json_error(array('message' => 'Invalid response from API'));
        return;
    }
    
    $improved_topics = trim($response_data['choices'][0]['message']['content']);
    
    // Clean up the response to ensure proper format
    $improved_topics = preg_replace('/^\d+\.\s+/m', '', $improved_topics); // Remove numbering
    $improved_topics = preg_replace('/^- /m', '', $improved_topics); // Remove bullet points
    wp_send_json_success(array(
        'message' => 'Topics improved successfully!',
        'improved_topics' => $improved_topics
    ));
}


// Schedule the cron job - Main entry point
add_action('rapidtextai_auto_blogging_cron', 'rapidtextai_generate_auto_blog_post');

// Additional cron hooks for the new workflow
add_action('rapidtextai_generate_title', 'rapidtextai_generate_title_handler', 10, 1);
add_action('rapidtextai_create_post', 'rapidtextai_create_post_handler', 10, 1);
add_action('rapidtextai_finalize_post', 'rapidtextai_finalize_post_handler', 10, 1);

/**
 * Streaming Logic Implementation:
 * 
 * This implementation uses a multi-stage approach with transient storage:
 * 
 * Stage 1: rapidtextai_generate_auto_blog_post()
 *   - Streams content from completionsarticle-stream endpoint
 *   - Saves raw content to WordPress transient (1 hour expiry)
 *   - Schedules title generation job
 * 
 * Stage 2: rapidtextai_generate_title_handler()
 *   - Retrieves content from transient
 *   - Uses completion API to generate SEO-optimized title
 *   - Updates transient with generated title
 *   - Schedules post creation job
 * 
 * Stage 3: rapidtextai_create_post_handler()
 *   - Retrieves title + content from transient
 *   - Converts markdown to HTML
 *   - Creates WordPress post with title and content
 *   - Deletes transient (no longer needed)
 *   - Schedules finalization job
 * 
 * Stage 4: rapidtextai_finalize_post_handler()
 *   - Generates tags using completion API (if enabled)
 *   - Adds images to content (if enabled)
 *   - Sets featured image (if enabled)
 *   - Updates post status to publish/draft
 *   - Cleanup metadata
 * 
 * Benefits of this approach:
 *   - Content streaming prevents timeout on long articles
 *   - Transient storage is cleaner than post meta for temporary data
 *   - Title generation uses AI with article context (better SEO)
 *   - No draft post created until title is ready
 *   - Each stage is independent and can be retried
 *   - 1-hour transient expiry prevents data buildup
 */

/**
 * Stream content from API using Server-Sent Events (SSE)
 * 
 * SSE Format Example:
 * data: {"id":"123","object":"chat.completion.chunk","choices":[{"delta":{"content":"Hello"}}]}
 * 
 * data: {"id":"123","object":"chat.completion.chunk","choices":[{"delta":{"content":" world"}}]}
 * 
 * data: [DONE]
 */
function rapidtextai_stream_content_via_sse($api_url, $post_data) {
    $accumulated_content = '';
    $buffer = '';
    $chunk_count = 0;
    $settings = get_option('rapidtextai_auto_blogging', array());
    if (!empty($settings['enable_logging'])) {
        error_log('RapidTextAI: Stream: Starting SSE stream');
    }

    // Initialize cURL for streaming
    $ch = curl_init($api_url);
    curl_setopt_array($ch, array(
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($post_data),
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'Accept: text/event-stream',
        ),
        CURLOPT_RETURNTRANSFER => false,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_TIMEOUT => 300, // 5 minutes max
        CURLOPT_CONNECTTIMEOUT => 30,
        CURLOPT_BUFFERSIZE => 128, // Small buffer for real-time processing
        CURLOPT_WRITEFUNCTION => function($curl, $data) use (&$accumulated_content, &$buffer, &$chunk_count) {
            $buffer .= $data;
            
            // Process complete SSE events (separated by \n\n)
            while (($double_newline_pos = strpos($buffer, "\n\n")) !== false) {
                $event_block = substr($buffer, 0, $double_newline_pos);
                $buffer = substr($buffer, $double_newline_pos + 2);
                
                // Parse the event block
                $lines = explode("\n", $event_block);
                foreach ($lines as $line) {
                    // SSE format: "data: {json}"
                    if (strpos($line, 'data: ') === 0) {
                        $json_data = trim(substr($line, 6));
                        
                        // Check for stream end signal
                        if ($json_data === '[DONE]') {
                            if(!empty($settings['enable_logging']))
                            error_log('RapidTextAI: Stream: Received [DONE] signal');

                            continue;
                        }
                        
                        // Decode JSON chunk
                        $chunk = json_decode($json_data, true);
                        
                        // Check for JSON decode errors
                        if (json_last_error() !== JSON_ERROR_NONE) {
                            if(!empty($settings['enable_logging']))
                            error_log('RapidTextAI: Stream: JSON decode error: ' . json_last_error_msg() . ' - Data: ' . substr($json_data, 0, 200));
                            continue;
                        }
                        
                        
                        // Extract content from delta (OpenAI streaming format)
                        if (isset($chunk['choices'][0]['delta']['content'])) {
                            $content_piece = $chunk['choices'][0]['delta']['content'];
                            $accumulated_content .= $content_piece;
                            $chunk_count++;
                            
                            // Log progress every 50 chunks
                            if ($chunk_count % 50 === 0 && !empty($settings['enable_logging'])) {
                                error_log("RapidTextAI Stream: Processed {$chunk_count} chunks, " . strlen($accumulated_content) . " chars");
                            }
                        } else {
                            // Log when expected structure is not found
                            if ($chunk_count < 5 && !empty($settings['enable_logging'])) { // Only log first few to avoid spam
                                error_log('RapidTextAI: Stream: Chunk missing delta.content - Structure: ' . json_encode($chunk));
                            }
                        }
                    }
                }
            }
            
            return strlen($data);
        }
    ));
    
    // Execute the streaming request
    $result = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    // Validate results
    if ($result === false || !empty($curl_error)) {
        if(!empty($settings['enable_logging']))
        error_log('RapidTextAI: Stream: cURL error - ' . $curl_error);
        return false;
    }
    
    if ($http_code !== 200) {
        if(!empty($settings['enable_logging']))
        error_log('RapidTextAI: Stream: HTTP error code ' . $http_code);
        return false;
    }
    
    if (empty($accumulated_content)) {
        if(!empty($settings['enable_logging']))
        error_log('RapidTextAI: Stream: No content received from stream. Chunks processed: ' . $chunk_count . ', Buffer remaining: ' . strlen($buffer));
        return false;
    }
    if(!empty($settings['enable_logging']))
    error_log("RapidTextAI Stream: SUCCESS! Received {$chunk_count} chunks, total " . strlen($accumulated_content) . " characters");
    
    return $accumulated_content;
}

// Stage 1: Stream article content and save to transient
function rapidtextai_generate_auto_blog_post() {
    // Get settings
    $settings = get_option('rapidtextai_auto_blogging', array());
    
    // Check if auto blogging is enabled
    if (empty($settings) || empty($settings['enabled'])) {
        return;
    }
    // Check user limits before proceeding
    // Check user limits before proceeding
    $user_limits_check = rapidtextai_check_user_limits();
    if (!$user_limits_check) {
        if(!empty($settings['enable_logging']))
        error_log('RapidTextAI: User limits exceeded, auto blogging skipped.');
        return false;
    }
    
    // Get a random topic
    $topics = explode("\n", $settings['topics']);
    $topics = array_map('trim', $topics);
    $topics = array_filter($topics);
    
    if (empty($topics)) {
        if(!empty($settings['enable_logging']))
        error_log('RapidTextAI: No topics available for auto blogging.');
        return;
    }
    
    $selected_topic = $topics[array_rand($topics)];
    
    // Get API key
    $api_key = get_option('rapidtextai_api_key', '');
    if (empty($api_key)) {
        if(!empty($settings['enable_logging']))
        error_log('RapidTextAI: API key not found. Please set up your authentication.');
        return;
    }
    if(!empty($settings['enable_logging']))
    error_log('RapidTextAI: Stage 1: Starting content streaming for topic: ' . $selected_topic);
    
    // Set up post data for streaming endpoint
    $post_data = array(
        'model' => $settings['model'],
        'messages' => array(
            array(
                'role' => 'user',
                'content' => "Write a comprehensive article about: " . $selected_topic. "\n\nThe tone should be " . $settings['tone'] . "."
            )
        ),
        'chatsession' => 'rapidtextai__'.$settings['model'].'_' . time() . '_' . wp_rand(),
        'stream' => true,
    );
    
    // Use max_completion_tokens for gpt-5, max_tokens for other models
    if (strpos($settings['model'], 'gpt-5') !== false) {
        $post_data['max_completion_tokens'] = 4000;
        $post_data['reasoning_effort'] = 'minimal';
    } else {
        // $user_limits_check
        $post_data['max_tokens'] = $user_limits_check['response_code'] == 1 ? null : 4000;
        $post_data['temperature'] = 0.7;
    }
    
    // Streaming API endpoint
    $api_url = "https://app.rapidtextai.com/openai/v1/chat/completionsarticle-stream?gigsixkey=" . urlencode($api_key);
    
    // Stream the content
    $content = rapidtextai_stream_content_via_sse($api_url, $post_data);
    
    if ($content === false) {
        if(!empty($settings['enable_logging']))
        error_log('RapidTextAI: Stage 1: Streaming failed for topic: ' . $selected_topic);
        return;
    }
    if(!empty($settings['enable_logging']))
    error_log('RapidTextAI: Stage 1: Successfully streamed ' . strlen($content) . ' characters');
    
    // Generate unique transient key
    $transient_key = 'rapidtextai_content_' . md5($selected_topic . time());
    
    // Save content to transient (expires in 1 hour)
    set_transient($transient_key, array(
        'content' => $content,
        'topic' => $selected_topic,
        'settings' => $settings,
        'created' => current_time('mysql'),
    ), HOUR_IN_SECONDS);
    if(!empty($settings['enable_logging']))
    error_log('RapidTextAI: Stage 1: Content saved to transient: ' . $transient_key);
    
    // Schedule Stage 2: Generate title (run immediately as single event)
    wp_schedule_single_event(time() + 2, 'rapidtextai_generate_title', array($transient_key));
    if(!empty($settings['enable_logging']))
    error_log('RapidTextAI: Stage 1: Scheduled title generation job');
}

// Stage 2: Generate title using completion API
function rapidtextai_generate_title_handler($transient_key) {
    $settings = get_option('rapidtextai_auto_blogging', array());
    if(!empty($settings['enable_logging']))
    error_log('RapidTextAI: Stage 2: Generating title from transient: ' . $transient_key);
    
    // Get content from transient
    $data = get_transient($transient_key);
    
    if ($data === false || empty($data['content'])) {
        if(!empty($settings['enable_logging']))
        error_log('RapidTextAI: Stage 2: Transient data not found or empty: ' . $transient_key);
        return;
    }
    
    $content = $data['content'];
    $topic = $data['topic'];
    $settings = $data['settings'];
    
    // Get API key
    $api_key = get_option('rapidtextai_api_key', '');
    if (empty($api_key)) {
        if(!empty($settings['enable_logging']))
        error_log('RapidTextAI: Stage 2: API key not found');
        delete_transient($transient_key);
        return;
    }
    if(!empty($settings['enable_logging']))
    error_log('RapidTextAI: Stage 2: Requesting title generation for content (' . strlen($content) . ' chars)');
    
    // Use completion API to generate title
    $title_data = array(
        'model' => 'gpt-3.5-turbo',
        'messages' => array(
            array(
                'role' => 'system',
                'content' => 'You are a title generator. Generate only an SEO-optimized, engaging title for the article in language same as content language. Return ONLY the title, nothing else.'
            ),
            array(
                'role' => 'user',
                'content' => "Generate a compelling title for this article in language same as content language:\n\n" . substr($content, 0, 1000)
            )
        ),
        'max_tokens' => 100,
        'temperature' => 0.8,
    );
    
    $api_url = "https://app.rapidtextai.com/openai/v1/chat/completions?gigsixkey=" . urlencode($api_key);
    
    $response = wp_remote_post($api_url, array(
        'timeout' => 60,
        'headers' => array('Content-Type' => 'application/json'),
        'body' => json_encode($title_data),
    ));
    
    if (is_wp_error($response)) {
        if(!empty($settings['enable_logging']))
        error_log('RapidTextAI: Stage 2: Title generation failed - ' . $response->get_error_message());
        delete_transient($transient_key);
        return;
    }
    
    $response_body = wp_remote_retrieve_body($response);
    $result = json_decode($response_body, true);
    
    if (empty($result['choices'][0]['message']['content'])) {
        if(!empty($settings['enable_logging']))
        error_log('RapidTextAI: Stage 2: No title returned from API');
        delete_transient($transient_key);
        return;
    }
    
    $title = trim($result['choices'][0]['message']['content']);
    
    // Remove quotes if present
    $title = trim($title, '"\'');
    if(!empty($settings['enable_logging']))
    error_log('RapidTextAI: Stage 2: Generated title: "' . $title . '"');
    
    // Update transient with title
    $data['title'] = $title;
    set_transient($transient_key, $data, HOUR_IN_SECONDS);
    
    // Schedule Stage 3: Create post
    wp_schedule_single_event(time() + 2, 'rapidtextai_create_post', array($transient_key));
    if(!empty($settings['enable_logging']))
    error_log('RapidTextAI: Stage 2: Scheduled post creation job');
}

// Stage 3: Create post with title and content
function rapidtextai_create_post_handler($transient_key) {
    $log_settings = get_option('rapidtextai_auto_blogging', array()); 
    if(!empty($log_settings['enable_logging']))
    error_log('RapidTextAI: Stage 3: Creating post from transient: ' . $transient_key);
    
    // Get data from transient
    $data = get_transient($transient_key);
    
    if ($data === false || empty($data['content']) || empty($data['title'])) {
        if(!empty($log_settings['enable_logging']))
        error_log('RapidTextAI: Stage 3: Missing data in transient: ' . $transient_key);
        return;
    }
    
    $content = $data['content'];
    $title = $data['title'];
    $topic = $data['topic'];
    $settings = $data['settings'];
    if(!empty($log_settings['enable_logging']))
    error_log('RapidTextAI: Stage 3: Creating post with title: "' . $title . '"');
    
    // Convert markdown to HTML
    $post_content = rapidtextai_simple_markdown_to_html($content);
    
    // Generate excerpt
    $excerpt_length = isset($settings['excerpt_length']) && $settings['excerpt_length'] > 0 ? $settings['excerpt_length'] : 55;
    $excerpt = wp_trim_words(wp_strip_all_tags($post_content), $excerpt_length, '...');
    
    // Create the post
    $post_id = wp_insert_post(array(
        'post_title'   => $title,
        'post_content' => $post_content,
        'post_excerpt' => $excerpt,
        'post_status'  => 'draft',
        'post_author'  => $settings['post_author'],
    ));
    
    if (is_wp_error($post_id)) {
        if(!empty($log_settings['enable_logging']))
        error_log('RapidTextAI: Stage 3: Failed to create post - ' . $post_id->get_error_message());
        delete_transient($transient_key);
        return;
    }
    if(!empty($log_settings['enable_logging']))
    error_log('RapidTextAI: Stage 3: Created post ID ' . $post_id);
    
    // Store metadata
    update_post_meta($post_id, '_rapidtextai_topic', $topic);
    update_post_meta($post_id, '_rapidtextai_settings', $settings);
    update_post_meta($post_id, '_rapidtextai_raw_content', $content);
    update_post_meta($post_id, '_rapidtextai_status', 'created');
    update_post_meta($post_id, '_rapidtextai_started', $data['created']);
    
    // Delete transient (no longer needed)
    delete_transient($transient_key);
    if(!empty($log_settings['enable_logging']))
    error_log('RapidTextAI: Stage 3: Post created successfully, scheduling finalization');
    
    // Schedule Stage 4: Finalize (add images, tags, publish)
    wp_schedule_single_event(time() + 2, 'rapidtextai_finalize_post', array($post_id));
    if(!empty($log_settings['enable_logging']))
    error_log('RapidTextAI: Stage 3: Scheduled finalization job for post ' . $post_id);
}

// Stage 4: Finalize post (tags, images, status)
function rapidtextai_finalize_post_handler($post_id) {
    $log_settings = get_option('rapidtextai_auto_blogging', array());
    if(!empty($log_settings['enable_logging']))
    error_log('RapidTextAI: Stage 4: Finalizing post ' . $post_id);
    
    $settings = get_post_meta($post_id, '_rapidtextai_settings', true);
    $raw_content = get_post_meta($post_id, '_rapidtextai_raw_content', true);
    $post = get_post($post_id);
    
    if (empty($settings) || !$post) {
        if(!empty($log_settings['enable_logging']))
        error_log('RapidTextAI: Stage 4: Missing data for post ' . $post_id);
        update_post_meta($post_id, '_rapidtextai_status', 'failed');
        return;
    }
    
    update_post_meta($post_id, '_rapidtextai_status', 'finalizing');
    
    // Generate tags if enabled
    if ($settings['generate_tags']) {
        if(!empty($log_settings['enable_logging']))
        error_log('RapidTextAI: Stage 4: Generating tags for post ' . $post_id);
        
        $api_key = get_option('rapidtextai_api_key', '');
        $tag_prompt = "Generate only {$settings['tags_count']} comma-separated tags for this content in content language: " . substr($raw_content, 0, 1000);
        
        $tag_data = array(
            'model' => 'gpt-3.5-turbo',
            'messages' => array(
                array(
                    'role' => 'system',
                    'content' => 'Generate only keywords as tags, keep them simple and relevant.'
                ),
                array(
                    'role' => 'user',
                    'content' => $tag_prompt
                )
            ),
            'temperature' => 0.5,
            'max_tokens' => 100
        );
        
        $api_url = "https://app.rapidtextai.com/openai/v1/chat/completions?gigsixkey=" . urlencode($api_key);
        
        $tag_response = wp_remote_post($api_url, array(
            'body' => json_encode($tag_data),
            'headers' => array('Content-Type' => 'application/json'),
            'timeout' => 30,
            'sslverify' => false
        ));
        
        if (!is_wp_error($tag_response)) {
            $tag_body = wp_remote_retrieve_body($tag_response);
            $tag_result = json_decode($tag_body, true);
            
            if (isset($tag_result['choices'][0]['message']['content'])) {
                $tags = explode(',', $tag_result['choices'][0]['message']['content']);
                $tags = array_map('trim', $tags);
                $tags = array_slice($tags, 0, $settings['tags_count']);
                wp_set_post_tags($post_id, $tags, false);
                if(!empty($log_settings['enable_logging']))
                error_log('RapidTextAI: Stage 4: Added ' . count($tags) . ' tags to post ' . $post_id);
            }
        }
    }
    
    // Add images if enabled
    if ($settings['include_images']) {
        if(!empty($log_settings['enable_logging']))
        error_log('RapidTextAI: Stage 4: Adding images to post ' . $post_id);
        
        $post_content = $post->post_content;
        preg_match_all('/<h[2-4][^>]*>(.*?)<\/h[2-4]>/i', $post_content, $headings);
        $images_added = 0;
        
        if (!empty($headings[1])) {
            foreach ($headings[1] as $heading) {
                if (stripos($heading, 'conclusion') !== false) {
                    continue;
                }
                
                $image_data = rapidtextai_get_image_for_heading($heading);
                
                if ($image_data) {
                    $images_added++;
                    $image_html = '<div class="wp-block-image"><figure class="aligncenter">';
                    $image_html .= '<img width="100%" src="' . esc_url($image_data['link']) . '" alt="' . esc_attr($heading) . '"/>';
                    $image_html .= '<figcaption class="wp-element-caption">Source: <a href="' . esc_url($image_data['context_link']) . '">' . esc_html($image_data['display_link']) . '</a></figcaption>';
                    $image_html .= '</figure></div>';
                    
                    $heading_pattern = '/<h[2-4][^>]*>' . preg_quote($heading, '/') . '<\/h[2-4]>/i';
                    $replacement = $image_html . '$0';
                    $post_content = preg_replace($heading_pattern, $replacement, $post_content, 1);
                    
                    if ($images_added >= $settings['max_images']) {
                        break;
                    }
                }
            }
            
            if ($images_added > 0) {
                wp_update_post(array(
                    'ID' => $post_id,
                    'post_content' => $post_content
                ));
                if(!empty($log_settings['enable_logging']))
                error_log('RapidTextAI: Stage 4: Added ' . $images_added . ' images to post ' . $post_id);
            }
        }
    }
    
    // Set featured image if enabled
    if (isset($settings['include_featured_image']) && $settings['include_featured_image']) {
        if(!empty($log_settings['enable_logging']))
        error_log('RapidTextAI: Stage 4: Setting featured image for post ' . $post_id);
        
        $topic = get_post_meta($post_id, '_rapidtextai_topic', true);
        $featured_image_data = rapidtextai_get_featured_image_for_topic($topic);
        
        if ($featured_image_data && !empty($featured_image_data['link'])) {
            $image_url = $featured_image_data['link'];
            $image_response = wp_remote_get($image_url, array(
                'timeout' => 30,
                'user-agent' => 'WordPress/' . get_bloginfo('version') . '; ' . get_bloginfo('url')
            ));
            
            if (!is_wp_error($image_response) && wp_remote_retrieve_response_code($image_response) === 200) {
                $image_data = wp_remote_retrieve_body($image_response);
                $image_type = wp_remote_retrieve_header($image_response, 'content-type');
                
                $extension = 'jpg';
                if (strpos($image_type, 'png') !== false) {
                    $extension = 'png';
                } elseif (strpos($image_type, 'gif') !== false) {
                    $extension = 'gif';
                } elseif (strpos($image_type, 'webp') !== false) {
                    $extension = 'webp';
                }
                
                $filename = sanitize_file_name(substr($post->post_title, 0, 50)) . '.' . $extension;
                $upload = wp_upload_bits($filename, null, $image_data);
                
                if (!$upload['error']) {
                    $wp_filetype = wp_check_filetype($upload['file']);
                    $attachment = array(
                        'post_mime_type' => $wp_filetype['type'],
                        'post_title' => sanitize_text_field($post->post_title),
                        'post_content' => '',
                        'post_status' => 'inherit'
                    );
                    
                    $attach_id = wp_insert_attachment($attachment, $upload['file'], $post_id);
                    
                    if (!is_wp_error($attach_id)) {
                        require_once(ABSPATH . 'wp-admin/includes/image.php');
                        $attach_data = wp_generate_attachment_metadata($attach_id, $upload['file']);
                        wp_update_attachment_metadata($attach_id, $attach_data);
                        set_post_thumbnail($post_id, $attach_id);
                        if(!empty($log_settings['enable_logging']))
                        error_log('RapidTextAI: Stage 4: Set featured image for post ' . $post_id);
                    }
                }
            }
        }
    }
    
    // Set categories
    if (!empty($settings['post_category'])) {
        wp_set_post_categories($post_id, $settings['post_category']);
    }
    
    // Update post status to final status
    wp_update_post(array(
        'ID' => $post_id,
        'post_status' => $settings['post_status']
    ));
    
    // Clean up temporary metadata
    delete_post_meta($post_id, '_rapidtextai_raw_content');
    delete_post_meta($post_id, '_rapidtextai_settings');
    
    // Mark as completed
    update_post_meta($post_id, '_rapidtextai_status', 'completed');
    update_post_meta($post_id, '_rapidtextai_completed', current_time('mysql'));

    if(!empty($log_settings['enable_logging'])) {
        error_log('RapidTextAI: Stage 4: Successfully completed post ' . $post_id . ' - "' . $post->post_title . '"');
    }
}

// Rest of the existing code continues below...

// Function to get image for heading
function rapidtextai_get_image_for_heading($heading) {
    $api_key = get_option('rapidtextai_api_key', '');
    if (empty($api_key)) {
        return false;
    }
    
    $response = wp_remote_get("https://app.rapidtextai.com/openai/customsearch?gigsixkey={$api_key}&searchType=image&q=" . urlencode($heading) . "&num=1");
    
    if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);   
        if (!empty($data['items']) && !empty($data['items'][0])) {
            return array(
                'link' => $data['items'][0]['link'],
                'context_link' => $data['items'][0]['image']['contextLink'],
                'display_link' => $data['items'][0]['displayLink']
            );
        }
    }
    
    return false;
}

// ajax callback for featured image generation based on topic
add_action('wp_ajax_rapidtextai_get_featured_image', 'rapidtextai_get_featured_image_for_topic');
add_action('wp_ajax_nopriv_rapidtextai_get_featured_image', 'rapidtextai_get_featured_image_for_topic');


// Function to get featured image for post topic
function rapidtextai_get_featured_image_for_topic($topic) { 
    $ajax = false;
    // Only verify nonce if topic is not provided as argument (i.e., when called via AJAX)
    if (!$topic) {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'rapidtextai_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed.'));
            return;
        }
        $topic = isset($_POST['topic']) ? sanitize_text_field($_POST['topic']) : '';
        $ajax = true;
    }
    
    $api_key = get_option('rapidtextai_api_key', '');
    if (empty($api_key)) {
        return false;
    }
    
    // Generate search query using AI
    $search_query = rapidtextai_generate_search_query_for_featured($topic);
    if (!$search_query) {
        return false;
    }
    // Use the existing function to get image
    $image_data = rapidtextai_get_image_for_heading($search_query);
    
    if ($ajax) {
        if ($image_data) {
            wp_send_json_success($image_data);
        } else {
            wp_send_json_error(array('message' => 'No image found for this topic.'));
        }
        return;
    }
    
    return $image_data;
}
// AJAX handler for uploading image from URL
add_action('wp_ajax_rapidtextai_upload_image_from_url', 'rapidtextai_upload_image_from_url_callback');
add_action('wp_ajax_nopriv_rapidtextai_upload_image_from_url', 'rapidtextai_upload_image_from_url_callback');

function rapidtextai_upload_image_from_url_callback() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'rapidtextai_nonce')) {
        wp_send_json_error(array('message' => 'Security check failed.'));
        return;
    }

    // Check user permissions
    if (!current_user_can('upload_files')) {
        wp_send_json_error(array('message' => 'Permission denied.'));
        return;
    }

    // Validate and sanitize URL
    $image_url = isset($_POST['image_url']) ? esc_url_raw($_POST['image_url']) : '';
    if (empty($image_url) || !filter_var($image_url, FILTER_VALIDATE_URL)) {
        wp_send_json_error(array('message' => 'Invalid image URL provided.'));
        return;
    }

    // Optional: Get post ID if provided
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;

    // Get image filename or generate one
    $filename = isset($_POST['filename']) ? sanitize_file_name($_POST['filename']) : '';
    if (empty($filename)) {
        $filename = basename(parse_url($image_url, PHP_URL_PATH));
        if (empty($filename)) {
            $filename = 'rapidtextai-image-' . time();
        }
    }

    // Download the image
    $response = wp_remote_get($image_url, array(
        'timeout' => 30,
        'user-agent' => 'WordPress/' . get_bloginfo('version') . '; ' . get_bloginfo('url')
    ));

    if (is_wp_error($response)) {
        wp_send_json_error(array('message' => 'Failed to download image: ' . $response->get_error_message()));
        return;
    }

    // Check response code
    if (wp_remote_retrieve_response_code($response) !== 200) {
        wp_send_json_error(array('message' => 'Failed to download image. HTTP status: ' . wp_remote_retrieve_response_code($response)));
        return;
    }

    // Get image data and content type
    $image_data = wp_remote_retrieve_body($response);
    $content_type = wp_remote_retrieve_header($response, 'content-type');
    
    // Validate that it's actually an image
    if (strpos($content_type, 'image/') !== 0) {
        wp_send_json_error(array('message' => 'URL does not point to a valid image.'));
        return;
    }

    // Determine file extension from content type
    $extension = '';
    switch ($content_type) {
        case 'image/jpeg':
        case 'image/jpg':
            $extension = 'jpg';
            break;
        case 'image/png':
            $extension = 'png';
            break;
        case 'image/gif':
            $extension = 'gif';
            break;
        case 'image/webp':
            $extension = 'webp';
            break;
        default:
            // Try to get extension from URL
            $path_info = pathinfo(parse_url($image_url, PHP_URL_PATH));
            $extension = isset($path_info['extension']) ? $path_info['extension'] : 'jpg';
    }

    // Ensure filename has proper extension
    if (!preg_match('/\.' . $extension . '$/i', $filename)) {
        $filename = pathinfo($filename, PATHINFO_FILENAME) . '.' . $extension;
    }

    // Upload the file to WordPress media library
    $upload = wp_upload_bits($filename, null, $image_data);

    if ($upload['error']) {
        wp_send_json_error(array('message' => 'Failed to upload image: ' . $upload['error']));
        return;
    }

    // Get file type
    $wp_filetype = wp_check_filetype($upload['file']);

    // Prepare attachment data
    $attachment = array(
        'post_mime_type' => $wp_filetype['type'],
        'post_title' => sanitize_text_field(pathinfo($filename, PATHINFO_FILENAME)),
        'post_content' => '',
        'post_status' => 'inherit'
    );

    // Insert attachment
    $attach_id = wp_insert_attachment($attachment, $upload['file'], $post_id);

    if (is_wp_error($attach_id)) {
        wp_send_json_error(array('message' => 'Failed to create attachment: ' . $attach_id->get_error_message()));
        return;
    }

    // Generate attachment metadata
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    $attach_data = wp_generate_attachment_metadata($attach_id, $upload['file']);
    wp_update_attachment_metadata($attach_id, $attach_data);

    // Get attachment URL
    $attachment_url = wp_get_attachment_url($attach_id);

    // Return success response
    wp_send_json_success(array(
        'message' => 'Image uploaded successfully.',
        'attachment_id' => $attach_id,
        'attachment_url' => $attachment_url,
        'attachment_title' => get_the_title($attach_id),
        'file_path' => $upload['file']
    ));
}
// Function to generate search query for featured image using AI
function rapidtextai_generate_search_query_for_featured($topic) {
    $api_key = get_option('rapidtextai_api_key', '');
    if (empty($api_key)) {
        return false;
    }
    $log_settings = get_option('rapidtextai_auto_blogging', array());
    // Create prompt for generating search query
    $prompt = "Based on this blog post topic, generate a short and specific search query (2-4 words) that would find the best featured image for this article. Focus on the main concept or subject matter.\n\nTopic: " . $topic . "\n\nReturn only the search query, nothing else.";
    
    // Set up request data
    $request_data = array(
        'model' => 'gpt-3.5-turbo',
        'messages' => [
            [
                'role' => 'system',
                'content' => 'You are a helpful assistant that generates concise image search queries. Return only the search terms, no explanations.'
            ],
            [
                'role' => 'user',
                'content' => $prompt
            ]
        ],
        'max_tokens' => 20,
        'temperature' => 0.3
    );
    
    // API endpoint
    $api_url = "https://app.rapidtextai.com/openai/v1/chat/completions?gigsixkey=" . urlencode($api_key);
    
    // Make API request
    $response = wp_remote_post($api_url, array(
        'body' => json_encode($request_data),
        'headers' => array(
            'Content-Type' => 'application/json',
        ),
        'timeout' => 30,
    ));
    
    if (is_wp_error($response)) {
        if(!empty($log_settings['enable_logging']))
        error_log('RapidTextAI: Failed to generate search query: ' . $response->get_error_message());
        return false;
    }
    
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    
    if (!isset($data['choices'][0]['message']['content'])) {
        if(!empty($log_settings['enable_logging']))
        error_log('RapidTextAI: Invalid response when generating search query');
        return false;
    }
    
    $search_query = trim($data['choices'][0]['message']['content']);
    
    // Clean up the search query (remove quotes, extra punctuation)
    $search_query = preg_replace('/["\']/', '', $search_query);
    $search_query = preg_replace('/[^\w\s-]/', '', $search_query);
    
    return $search_query;
}

// Add a dashboard widget to show auto blogging status
add_action('wp_dashboard_setup', 'rapidtextai_add_dashboard_widget');

function rapidtextai_add_dashboard_widget() {
    wp_add_dashboard_widget(
        'rapidtextai_dashboard_widget',
        'RapidTextAI Auto Blogging Status',
        'rapidtextai_dashboard_widget_content'
    );
}

function rapidtextai_dashboard_widget_content() {
    $settings = get_option('rapidtextai_auto_blogging', array());
    
    if (empty($settings) || empty($settings['enabled'])) {
        echo '<p>Auto blogging is currently disabled. <a href="' . admin_url('admin.php?page=rapidtextai-auto-blogging') . '">Enable it here</a>.</p>';
        return;
    }
    
    $next_run = wp_next_scheduled('rapidtextai_auto_blogging_cron');
    
    echo '<p><strong>Status:</strong> Enabled</p>';
    echo '<p><strong>Schedule:</strong> ' . ucfirst($settings['schedule']) . '</p>';
    echo '<p><strong>Post Status:</strong> ' . ucfirst($settings['post_status']) . '</p>';
    echo '<p><strong>Next scheduled run:</strong> ' . ($next_run ? date_i18n('F j, Y, g:i a', $next_run) : 'Not scheduled') . '</p>';
    echo '<p><a href="' . admin_url('admin.php?page=rapidtextai-auto-blogging') . '">Manage Settings</a></p>';
}

// Register activation and deactivation hooks
register_activation_hook(__FILE__, 'rapidtextai_activate_auto_blogging');
register_deactivation_hook(__FILE__, 'rapidtextai_deactivate_auto_blogging');

function rapidtextai_activate_auto_blogging() {
    $settings = get_option('rapidtextai_auto_blogging', array());
    
    if (!empty($settings) && !empty($settings['enabled'])) {
        if (!wp_next_scheduled('rapidtextai_auto_blogging_cron')) {
            wp_schedule_event(time(), $settings['schedule'], 'rapidtextai_auto_blogging_cron');
        }
    }
}

function rapidtextai_deactivate_auto_blogging() {
    wp_clear_scheduled_hook('rapidtextai_auto_blogging_cron');
}

// Add AJAX handlers for logs functionality
add_action('wp_ajax_rapidtextai_get_logs', 'rapidtextai_get_logs_callback');
add_action('wp_ajax_rapidtextai_clear_logs', 'rapidtextai_clear_logs_callback');

function rapidtextai_get_logs_callback() {
    ini_set('display_errors', 1);
    ini_set('log_errors', 1);
    error_reporting(E_ALL);


    // Verify nonce
    if (!isset($_GET['nonce']) || !wp_verify_nonce($_GET['nonce'], 'rapidtextai_get_logs_nonce')) {
        wp_send_json_error(array('message' => 'Security check failed.'));
        return;
    }
    
    // Check user permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Permission denied.'));
        return;
    }
    
    $logs = rapidtextai_read_error_logs();
    wp_send_json_success(array('logs' => $logs));
}

function rapidtextai_clear_logs_callback() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'rapidtextai_clear_logs_nonce')) {
        wp_send_json_error(array('message' => 'Security check failed.'));
        return;
    }
    
    // Check user permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Permission denied.'));
        return;
    }
    
    $cleared = rapidtextai_clear_error_logs();
    
    if ($cleared) {
        wp_send_json_success(array('message' => 'Logs cleared successfully.'));
    } else {
        wp_send_json_error(array('message' => 'Failed to clear logs.'));
    }
}

function rapidtextai_read_error_logs() {
    $logs = array();
    
    // Get PHP error log file path
    $log_file = ini_get('error_log');

    // If not set, try default locations
    if (empty($log_file) || !file_exists($log_file)) {
        $possible_paths = array(
            ABSPATH . 'wp-content/debug.log',
            ABSPATH . 'error_log',
            WP_CONTENT_DIR . '/debug.log',
            '/var/log/php_errors.log',
            '/var/log/apache2/error.log',
            $_SERVER['DOCUMENT_ROOT'] . '/logs/php_error.log'
        );
        
        foreach ($possible_paths as $path) {
            if (file_exists($path)) {
                $log_file = $path;
                break;
            }
        }
    }
    
    if (!empty($log_file) && file_exists($log_file) && is_readable($log_file)) {
        // Check file size first
        $file_size = filesize($log_file);
        $max_size = 50 * 1024 * 1024; // 50MB limit
        
        if ($file_size > $max_size) {
            // For large files, read from the end
            $handle = fopen($log_file, 'r');
            if ($handle) {
                // Seek to near the end of file
                fseek($handle, max(0, $file_size - $max_size));
                
                // Skip the first incomplete line
                fgets($handle);
                
                while (($line = fgets($handle)) !== false) {
                    if (strpos($line, 'RapidTextAI:') !== false) {
                        $log_entry = rapidtextai_parse_log_line($line);
                        if ($log_entry) {
                            $logs[] = $log_entry;
                        }
                    }
                }
                fclose($handle);
            }
        } else {
            // For smaller files, use the original method
            $file_content = file_get_contents($log_file);
            $lines = explode("\n", $file_content);
            
            foreach ($lines as $line) {
                if (strpos($line, 'RapidTextAI:') !== false) {
                    $log_entry = rapidtextai_parse_log_line($line);
                    if ($log_entry) {
                        $logs[] = $log_entry;
                    }
                }
            }
        }
        
        // Reverse to show newest first
        $logs = array_reverse($logs);
        
        // Limit to last 100 entries
        $logs = array_slice($logs, 0, 100);
    }
    
    return $logs;
}

function rapidtextai_parse_log_line($line) {
    // Parse typical PHP error log format: [timestamp] message
    $pattern = '/^\[([^\]]+)\].*?RapidTextAI:\s*(.+)$/';
    
    if (preg_match($pattern, $line, $matches)) {
        $timestamp = $matches[1];
        $message = trim($matches[2]);
        
        // Determine log level based on message content
        $level = 'info';
        if (stripos($message, 'error') !== false || stripos($message, 'failed') !== false) {
            $level = 'error';
        } elseif (stripos($message, 'warning') !== false || stripos($message, 'warn') !== false) {
            $level = 'warning';
        } elseif (stripos($message, 'success') !== false) {
            $level = 'success';
        }
        
        return array(
            'timestamp' => $timestamp,
            'message' => $message,
            'level' => $level
        );
    }
    
    return null;
}

function rapidtextai_clear_error_logs() {
    // Get PHP error log file path
    $log_file = ini_get('error_log');
    
    // If not set, try default locations
    if (empty($log_file) || !file_exists($log_file)) {
        $possible_paths = array(
            ABSPATH . 'wp-content/debug.log',
            ABSPATH . 'error_log',
            WP_CONTENT_DIR . '/debug.log'
        );
        
        foreach ($possible_paths as $path) {
            if (file_exists($path)) {
                $log_file = $path;
                break;
            }
        }
    }
    
    if (!empty($log_file) && file_exists($log_file) && is_writable($log_file)) {
        // Read current log content
        $content = file_get_contents($log_file);
        $lines = explode("\n", $content);
        
        // Filter out RapidTextAI logs
        $filtered_lines = array();
        foreach ($lines as $line) {
            if (strpos($line, 'RapidTextAI:') === false) {
                $filtered_lines[] = $line;
            }
        }
        
        // Write back the filtered content
        $filtered_content = implode("\n", $filtered_lines);
        return file_put_contents($log_file, $filtered_content) !== false;
    }
    
    return false;
}