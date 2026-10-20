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
        <div id="<?php echo esc_attr($id); ?>"
             class="gws-po"
             popover="manual"
             role="dialog"
             aria-modal="false"
             aria-label="<?php echo esc_attr(get_the_title($item)); ?>"
             data-gws-trigger="<?php echo esc_attr($trigger); ?>"
             data-gws-delay="<?php echo esc_attr($delay); ?>"
             data-gws-days="<?php echo esc_attr($days); ?>"
             data-gws-id="<?php echo esc_attr($item->ID); ?>">
            <button type="button" class="gws-po__close" data-gws-close aria-label="PopOver schliessen" title="Schliessen">
                <span aria-hidden="true">&times;</span>
            </button>
            <div class="gws-po__content"><?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- The_content filtered WP block HTML. ?></div>
        </div>
        <?php
    }
}
add_action('wp_footer', 'gws_po_render_frontend', 20);
