<?php
if (!defined('ABSPATH')) { exit; }

/** Nur passende veröffentlichte Popovers auf regulären Frontend-Seiten. */
function gws_po_get_current_popovers() {
    if (is_admin() || is_feed() || is_robots() || is_trackback()) { return array(); }
    $current_id = is_singular() ? get_queried_object_id() : 0;
    $items = get_posts(array(
        'post_type' => 'gws_popover', 'post_status' => 'publish',
        'posts_per_page' => -1, 'orderby' => 'menu_order date', 'order' => 'ASC',
        'suppress_filters' => false,
    ));
    return array_values(array_filter($items, static function ($item) use ($current_id) {
        $targets = gws_po_sanitize_targets(get_post_meta($item->ID, 'gws_po_targets', true));
        return empty($targets) || ($current_id > 0 && in_array($current_id, $targets, true));
    }));
}

function gws_po_enqueue_frontend() {
    if (empty(gws_po_get_current_popovers())) { return; }
    wp_enqueue_style('gws-popovers', GWS_PO_URL . 'assets/popovers.css', array(), GWS_PO_VERSION);
    wp_enqueue_script('gws-popovers', GWS_PO_URL . 'assets/popovers.js', array(), GWS_PO_VERSION, array('in_footer' => true));
}
add_action('wp_enqueue_scripts', 'gws_po_enqueue_frontend');

function gws_po_render_frontend() {
    $items = gws_po_get_current_popovers();
    if (!$items) { return; }
    foreach ($items as $item) {
        $trigger = get_post_meta($item->ID, 'gws_po_trigger', true);
        if (!in_array($trigger, array('exit', 'timed'), true)) { $trigger = 'exit'; }
        $display = get_post_meta($item->ID, 'gws_po_display', true);
        if (!in_array($display, array('modal', 'nonmodal'), true)) { $display = 'modal'; }
        $raw_delay = get_post_meta($item->ID, 'gws_po_delay', true);
        $raw_days = get_post_meta($item->ID, 'gws_po_cookie_days', true);
        $delay = $raw_delay === '' ? 5000 : absint($raw_delay);
        $days = $raw_days === '' ? 7 : absint($raw_days);
        $id = 'gws-po-' . $item->ID;
        // do_blocks(), shortcodes, Embed und Theme-Block-Styles via the_content Filter.
        // Globale $post-Umstellung schützt Blöcke/Shortcodes, die get_the_ID() nutzen.
        global $post;
        $original_post = $post;
        $post = $item;
        setup_postdata($post);
        $content = apply_filters('the_content', $item->post_content);
        $post = $original_post;
        if ($original_post instanceof WP_Post) { setup_postdata($original_post); } else { wp_reset_postdata(); }
        ?>
        <dialog id="<?php echo esc_attr($id); ?>"
             class="gws-po"
             data-gws-display="<?php echo esc_attr($display); ?>"
             aria-labelledby="<?php echo esc_attr($id); ?>-title"
             data-gws-trigger="<?php echo esc_attr($trigger); ?>"
             data-gws-delay="<?php echo esc_attr($delay); ?>"
             data-gws-days="<?php echo esc_attr($days); ?>"
             data-gws-id="<?php echo esc_attr($item->ID); ?>">
            <h2 id="<?php echo esc_attr($id); ?>-title" class="gws-po__heading"><?php echo esc_html(get_the_title($item)); ?></h2>
            
            <div class="gws-po__content">
                <button type="button" class="gws-po__close" data-gws-close autofocus aria-label="<?php echo esc_attr__('Dialog schliessen', 'gws-popovers'); ?>">
                    <span aria-hidden="true">&times;</span>
                </button>
                <?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- The_content filtered WP block HTML. ?></div>
        </dialog>
        <?php
    }
}
add_action('wp_footer', 'gws_po_render_frontend', 20);
