<?php
/**
 * Plugin Name: Post Subheading — Working Sample
 * Description: Editable post subheadings with a native editor field and escaped shortcode output. Theme integration is separate.
 * Version: 0.1.0
 * Requires PHP: 8.0
 * License: GPL-2.0-or-later
 */
namespace IncomeSample\Subheading;
if (!defined('ABSPATH')) { exit; }
const KEY = '_income_sample_subheading';
function register(): void {
    register_post_meta('post', KEY, [
        'type' => 'string', 'single' => true, 'default' => '',
        'show_in_rest' => true, 'sanitize_callback' => 'sanitize_text_field',
        'auth_callback' => static function ($allowed, $key, $post_id): bool {
            return current_user_can('edit_post', (int) $post_id);
        },
    ]);
}
add_action('init', __NAMESPACE__ . '\\register');
function editor(\WP_Post $post): void {
    wp_nonce_field('income_subheading_save', 'income_subheading_nonce');
    echo '<p><label for="income-subheading">Subheading</label></p>';
    echo '<input type="text" id="income-subheading" name="income_subheading" class="widefat" value="' . esc_attr(get_post_meta($post->ID, KEY, true)) . '">';
    echo '<p class="description">Optional plain text. Display with [post_subheading] or a theme integration.</p>';
}
add_action('add_meta_boxes_post', static function (): void {
    add_meta_box('income-subheading', 'Post subheading', __NAMESPACE__ . '\\editor', 'post', 'normal');
});
function save(int $post_id): void {
    if ((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) { return; }
    if (get_post_type($post_id) !== 'post' || !current_user_can('edit_post', $post_id)) { return; }
    if (!isset($_POST['income_subheading_nonce']) || !is_string($_POST['income_subheading_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['income_subheading_nonce'])), 'income_subheading_save')) { return; }
    // An absent field is an unrelated save; an explicitly empty field clears it.
    if (!isset($_POST['income_subheading']) || !is_string($_POST['income_subheading'])) { return; }
    $text = sanitize_text_field(wp_unslash($_POST['income_subheading']));
    if ($text === '') { delete_post_meta($post_id, KEY); }
    else { update_post_meta($post_id, KEY, $text); }
}
add_action('save_post_post', __NAMESPACE__ . '\\save');
function render(int $post_id): string {
    if (get_post_type($post_id) !== 'post' || post_password_required($post_id)) { return ''; }
    if (!is_post_publicly_viewable($post_id) && !current_user_can('read_post', $post_id)) { return ''; }
    $text = get_post_meta($post_id, KEY, true);
    return is_string($text) && $text !== '' ? '<p class="post-subheading">' . esc_html($text) . '</p>' : '';
}
// Deliberately no arbitrary post-ID shortcode attribute; use the current post context.
add_shortcode('post_subheading', static function (): string { return render((int) get_the_ID()); });
