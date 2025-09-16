<?php
/*
* Plugin Name: AI Content Writer & Auto Post Generator for WordPress by RapidTextAI
* Description: Add an AI-powered tool to your wordpress to generate articles using advanced options and models for using meta box using Gemini, GPT4, Deepseek and Grok.
* Version: 3.6.0
* Author: Rapidtextai.com
* Text Domain: rapidtextai
* License: GPL-2.0-or-later
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly 
define('RAPIDTEXTAI_PLUGIN_DIR', plugin_dir_path( __FILE__ ));
define('RAPIDTEXTAI_PLUGIN_URL', plugin_dir_url( __FILE__ ));
require_once RAPIDTEXTAI_PLUGIN_DIR . 'rapidtext-ai-meta-box.php';
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
    
        //echo 'matches rapidtextai_ai_text_block <pre>';print_r($matches);echo '</pre>';
        if (isset($matches[0]) && isset($atts['wpb_input_text']) && trim($atts['wpb_input_text']) != '') {
            foreach ($matches[0] as $shortcode_instance) {

                $attribute_pattern = '/' . $attribute_to_update . '=["\'](.*?)["\']/';
                preg_match($attribute_pattern, $shortcode_instance, $attribute_match);
                //echo '<pre>';print_r($attribute_match);echo '</pre>';

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

                    
                   // echo '<pre>';print_r($jsonelem_arr);echo '</pre>';

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
            'max_images' => intval($_POST['rapidtextai_max_images']),
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
    example response Example: Topic: Complete Guide to Sustainable Gardening for Beginners; Keywords: sustainable gardening, eco-friendly plants, organic fertilizer, water conservation, composting methods; Tone: friendly and informative; Audience: homeowners and gardening beginners; Length: 2500-3000 words; CTA: Download our free sustainable gardening checklist";
    
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


// Schedule the cron job
add_action('rapidtextai_auto_blogging_cron', 'rapidtextai_generate_auto_blog_post');

// Function to generate blog post
function rapidtextai_generate_auto_blog_post() {
    // Get settings
    $settings = get_option('rapidtextai_auto_blogging', array());
    
    // Check if auto blogging is enabled
    if (empty($settings) || empty($settings['enabled'])) {
        return;
    }
    
    // Get a random topic
    $topics = explode("\n", $settings['topics']);
    $topics = array_map('trim', $topics);
    $topics = array_filter($topics);
    
    if (empty($topics)) {
        error_log('RapidTextAI: No topics available for auto blogging.');
        return;
    }
    
    $selected_topic = $topics[array_rand($topics)];
    
    // Get API key
    $api_key = get_option('rapidtextai_api_key', '');
    if (empty($api_key)) {
        error_log('RapidTextAI: API key not found. Please set up your authentication.');
        return;
    }
    
    // Set up post data for the completionarticle endpoint
    $post_data = array(
        'model' => $settings['model'],
        'messages' => [
            [
                'role' => 'user',
                'content' => "Write a comprehensive article about: " . $selected_topic
            ]
        ],
        'chatsession' => rad2deg(time()), // Use current time as session ID
    );
    
    // Use max_completion_tokens for gpt-5, max_tokens for other models
    if (strpos($settings['model'], 'gpt-5') !== false) {
        $post_data['max_completion_tokens'] = 4000;
        $post_data['reasoning_effort'] = 'low';
    } else {
        $post_data['max_tokens'] = 4000;
        $post_data['temperature'] = 0.7;
    }

    // API endpoint with API key
    $api_url = "https://app.rapidtextai.com/openai/v1/chat/completionsarticle?gigsixkey=" . urlencode($api_key);
    
    // Make the API request
    $response = wp_remote_post($api_url, array(
        'body' => json_encode($post_data),
        'headers' => array(
            'Content-Type' => 'application/json',
        ),
        'timeout' => 120,
    ));
    
    if (is_wp_error($response)) {
        error_log('RapidTextAI: Failed to connect to API: ' . $response->get_error_message());
        return;
    }
    
    // Parse the response
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    
    if (json_last_error() !== JSON_ERROR_NONE || empty($data)) {
        error_log('RapidTextAI: Invalid response from API');
        return;
    }
    
    // Handle the OpenAI-style response structure to extract post content
    if (!isset($data['choices'][0]['message']['content'])) {
        error_log('RapidTextAI: Response missing required data structure');
        // log data
        
        return;
    }
    // Get the content from the response and convert from markdown to HTML if needed
    $content = $data['choices'][0]['message']['content'];
    
    // Extract title from content (assuming first line is a heading)
    $title = '';
    $post_content = $content;
    
    // Try to extract a title from first line if it looks like a heading
    if (preg_match('/^#\s+(.+)$/m', $content, $matches) || preg_match('/^(.+)\n[=]+\s*$/m', $content, $matches)) {
        $title = trim($matches[1]);
        // Remove the title from content
        $post_content = preg_replace('/^#\s+(.+)$\n+/m', '', $post_content, 1);
        $post_content = preg_replace('/^(.+)\n[=]+\s*$\n+/m', '', $post_content, 1);
    } else {
        // Use first sentence as title if no heading
        $sentences = explode('.', $content, 2);
        $title = trim($sentences[0]);
        if (strlen($title) > 60) {
            $title = substr($title, 0, 57) . '...';
        }
    }
    
    // Convert markdown content to HTML
    $post_content = rapidtextai_simple_markdown_to_html($post_content);
    
    // Generate an excerpt
    $excerpt = wp_trim_words(wp_strip_all_tags($post_content), $settings['excerpt_length'], '...');
    
    // Prepare generated post structure
    $generated_post = array(
        'title' => $title,
        'content' => $post_content,
        'excerpt' => $excerpt,
        'taxonomies' => array(
            'post_tag' => array(),
            'category' => array()
        )
    );
    
    // Generate tags if enabled
    if ($settings['generate_tags']) {
        // Set up request for generating tags
        $tag_prompt = "Generate only {$settings['tags_count']} comma-separated tags for this content: " . substr($content, 0, 1000);
        
        $tag_data = array(
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Generate only keywords as tags, keep them simple and relevant.'
                ],
                [
                    'role' => 'user',
                    'content' => $tag_prompt
                ]
            ],
            'temperature' => 0.5,
            'max_tokens' => 100
        );
        
        $tag_response = wp_remote_post($api_url, array(
            'body' => json_encode($tag_data),
            'headers' => array('Content-Type' => 'application/json'),
            'timeout' => 30
        ));
        
        if (!is_wp_error($tag_response)) {
            $tag_body = wp_remote_retrieve_body($tag_response);
            $tag_data = json_decode($tag_body, true);
            
            if (isset($tag_data['choices'][0]['message']['content'])) {
                $tags = explode(',', $tag_data['choices'][0]['message']['content']);
                $tags = array_map('trim', $tags);
                $generated_post['taxonomies']['post_tag'] = $tags;
            }
        }
    }
    
    // Prepare content with images if enabled
    if ($settings['include_images']) {
        // Extract headings for image insertion
        preg_match_all('/<h[2-4][^>]*>(.*?)<\/h[2-4]>/i', $post_content, $headings);
        $images = 0;
        if (!empty($headings[1])) {
            foreach ($headings[1] as $key => $heading) {
                // Skip conclusion headings
                if (stripos($heading, 'conclusion') !== false) {
                    continue;
                }
                
                $image_data = rapidtextai_get_image_for_heading($heading);
                
                if ($image_data) {
                    $images++;
                    $image_html = '<div class="wp-block-image"><figure class="aligncenter">';
                    $image_html .= '<img width="100%" src="' . esc_url($image_data['link']) . '" alt="' . esc_attr($heading) . '"/>';
                    $image_html .= '<figcaption class="wp-element-caption">Source: <a href="' . esc_url($image_data['context_link']) . '">' . esc_html($image_data['display_link']) . '</a></figcaption>';
                    $image_html .= '</figure></div>';
                    
                    // Insert image before this specific heading
                    $heading_pattern = '/<h[2-4][^>]*>' . preg_quote($heading, '/') . '<\/h[2-4]>/i';
                    $replacement = $image_html . '$0';
                    $generated_post['content'] = preg_replace($heading_pattern, $replacement, $generated_post['content'], 1);

                    // maximum number of images reached
                    if ($images >= $settings['max_images']) {
                        break;
                    }
                }
            }
        }
    }
    
    // Create post
    $post_data = array(
        'post_title'    => $generated_post['title'],
        'post_content'  => $generated_post['content'],
        'post_excerpt'  => $generated_post['excerpt'],
        'post_status'   => $settings['post_status'],
        'post_author'   => $settings['post_author'],
        'post_category' => $settings['post_category'],
    );
    
    $post_id = wp_insert_post($post_data);
    
    if (!is_wp_error($post_id)) {
        // Add taxonomy terms
        if ($settings['generate_tags'] && !empty($generated_post['taxonomies']['post_tag'])) {
            $tags = array_slice($generated_post['taxonomies']['post_tag'], 0, $settings['tags_count']);
            wp_set_post_tags($post_id, $tags, false);
        }
        
        // Log success
        error_log('RapidTextAI: Successfully generated post ID ' . $post_id . ' with title: ' . $generated_post['title']);
    } else {
        error_log('RapidTextAI: Failed to create post: ' . $post_id->get_error_message());
    }
}

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