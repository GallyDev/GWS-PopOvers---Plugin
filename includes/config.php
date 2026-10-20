<?php
if (!defined('ABSPATH')) { exit; }

function gws_po_register_post_type() {
    register_post_type('gws_popover', array(
        'labels' => array(
            'name' => 'GWS PopOvers', 'singular_name' => 'GWS PopOver',
            'add_new_item' => 'Neues PopOver', 'edit_item' => 'PopOver bearbeiten',
            'menu_name' => 'GWS PopOvers',
        ),
        'public' => false,
        'publicly_queryable' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'show_in_rest' => true,
        'exclude_from_search' => true,
        'menu_icon' => 'dashicons-welcome-view-site',
        'supports' => array('title', 'editor', 'revisions', 'custom-fields'),
        'capability_type' => 'post',
    ));

    // Native Fields und ACF/SCF schreiben dieselben Meta-Keys.
    foreach (array('gws_po_trigger' => 'string', 'gws_po_display' => 'string', 'gws_po_delay' => 'integer', 'gws_po_cookie_days' => 'integer') as $key => $type) {
        register_post_meta('gws_popover', $key, array(
            'single' => true, 'type' => $type, 'show_in_rest' => true,
            'sanitize_callback' => $type === 'integer' ? 'absint' : 'sanitize_text_field',
            'auth_callback' => function () { return current_user_can('edit_posts'); },
        ));
    }
    register_post_meta('gws_popover', 'gws_po_targets', array(
        'single' => true, 'type' => 'array',
        'show_in_rest' => array('schema' => array('type' => 'array', 'items' => array('type' => 'integer'))),
        'sanitize_callback' => 'gws_po_sanitize_targets',
        'auth_callback' => function () { return current_user_can('edit_posts'); },
    ));
}
add_action('init', 'gws_po_register_post_type');

function gws_po_sanitize_targets($value) {
    if (!is_array($value)) { return array(); }
    return array_values(array_unique(array_filter(array_map('absint', $value))));
}

/** Prüft SCF/ACF, nachdem beide Plugins initialisiert wurden. */
function gws_po_register_field_group() {
    if (!function_exists('acf_add_local_field_group')) { return; }
    acf_add_local_field_group(array(
        'key' => 'group_gws_popovers',
        'title' => 'GWS PopOver Einstellungen',
        'fields' => array(
            array(
                'key' => 'field_gws_po_trigger', 'label' => 'Auslöser',
                'name' => 'gws_po_trigger', 'type' => 'radio',
                'choices' => array('exit' => 'Exit Intent', 'timed' => 'Zeitgesteuert'),
                'default_value' => 'exit', 'layout' => 'horizontal',
            ),
            array(
                'key' => 'field_gws_po_display', 'label' => 'Darstellung',
                'instructions' => 'Modal blockiert die Seite; nicht-modal lässt die Seite bedienbar.',
                'name' => 'gws_po_display', 'type' => 'radio',
                'choices' => array('modal' => 'Modal', 'nonmodal' => 'Nicht-modal'),
                'default_value' => 'modal', 'layout' => 'horizontal',
            ),
            array(
                'key' => 'field_gws_po_delay', 'label' => 'Verzögerung nach Pageload (ms)',
                'name' => 'gws_po_delay', 'type' => 'number',
                'default_value' => 5000, 'min' => 0, 'step' => 100,
                'conditional_logic' => array(array(array('field' => 'field_gws_po_trigger', 'operator' => '==', 'value' => 'timed'))),
            ),
            array(
                'key' => 'field_gws_po_targets', 'label' => 'Nur auf diesen Seiten/Beiträgen',
                'instructions' => 'Leer lassen = global auf der gesamten Website.',
                'name' => 'gws_po_targets', 'type' => 'relationship',
                'post_type' => array('post', 'page'), 'filters' => array('search', 'post_type'),
                'return_format' => 'id',
            ),
            array(
                'key' => 'field_gws_po_cookie_days', 'label' => 'Nach Schliessen ausblenden (Tage)',
                'instructions' => '0 = nur während der aktuellen Browser-Sitzung.',
                'name' => 'gws_po_cookie_days', 'type' => 'number',
                'default_value' => 7, 'min' => 0, 'step' => 1,
            ),
        ),
        'location' => array(array(array('param' => 'post_type', 'operator' => '==', 'value' => 'gws_popover'))),
        'position' => 'normal', 'style' => 'default', 'active' => true,
    ));
}
add_action('acf/init', 'gws_po_register_field_group');

/** Fallback ohne SCF/ACF. */
function gws_po_add_native_metabox() {
    if (function_exists('acf_add_local_field_group')) { return; }
    add_meta_box('gws_po_settings', 'GWS PopOver Einstellungen', 'gws_po_native_metabox', 'gws_popover', 'normal', 'high');
}
add_action('add_meta_boxes_gws_popover', 'gws_po_add_native_metabox');

function gws_po_native_metabox($post) {
    wp_nonce_field('gws_po_save', 'gws_po_nonce');
    $trigger = get_post_meta($post->ID, 'gws_po_trigger', true) ?: 'exit';
    $display = get_post_meta($post->ID, 'gws_po_display', true) ?: 'modal';
    $delay = get_post_meta($post->ID, 'gws_po_delay', true);
    $days = get_post_meta($post->ID, 'gws_po_cookie_days', true);
    $targets = gws_po_sanitize_targets(get_post_meta($post->ID, 'gws_po_targets', true));
    $entries = get_posts(array('post_type' => array('page', 'post'), 'post_status' => array('publish', 'private', 'draft'), 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC'));
    ?>
    <p><label for="gws_po_trigger"><strong>Auslöser</strong></label><br>
        <select id="gws_po_trigger" name="gws_po_trigger">
            <option value="exit" <?php selected($trigger, 'exit'); ?>>Exit Intent</option>
            <option value="timed" <?php selected($trigger, 'timed'); ?>>Zeitgesteuert</option>
        </select>
    </p>
    <p><label for="gws_po_display"><strong>Darstellung</strong></label><br>
        <select id="gws_po_display" name="gws_po_display">
            <option value="modal" <?php selected($display, 'modal'); ?>>Modal (Hintergrund gesperrt)</option>
            <option value="nonmodal" <?php selected($display, 'nonmodal'); ?>>Nicht-modal (Hintergrund bedienbar)</option>
        </select>
    </p>
    <p><label for="gws_po_delay"><strong>Verzögerung nach Pageload (Millisekunden)</strong></label><br>
        <input type="number" id="gws_po_delay" name="gws_po_delay" min="0" step="100" value="<?php echo esc_attr($delay === '' ? 5000 : $delay); ?>"></p>
    <p><label for="gws_po_targets"><strong>Nur auf diesen Seiten/Beiträgen</strong></label><br>
        <select id="gws_po_targets" name="gws_po_targets[]" multiple size="9" style="width:100%;max-width:540px">
            <?php foreach ($entries as $entry) : ?>
                <option value="<?php echo esc_attr($entry->ID); ?>" <?php selected(in_array((int) $entry->ID, $targets, true)); ?>><?php echo esc_html(($entry->post_type === 'page' ? 'Seite: ' : 'Beitrag: ') . $entry->post_title . ' (#' . $entry->ID . ')'); ?></option>
            <?php endforeach; ?>
        </select><br><small>Keine Auswahl = global. Mehrfachauswahl mit Strg/Cmd.</small></p>
    <p><label for="gws_po_cookie_days"><strong>Nach Schliessen ausblenden (Tage)</strong></label><br>
        <input type="number" id="gws_po_cookie_days" name="gws_po_cookie_days" min="0" step="1" value="<?php echo esc_attr($days === '' ? 7 : $days); ?>">
        <br><small>0 = Browser-Sitzung.</small></p>
    <?php
}

function gws_po_save_native_metabox($post_id) {
    if (function_exists('acf_add_local_field_group')) { return; }
    if (!isset($_POST['gws_po_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['gws_po_nonce'])), 'gws_po_save')) { return; }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) { return; }
    if (wp_is_post_revision($post_id) || !current_user_can('edit_post', $post_id)) { return; }
    $trigger = isset($_POST['gws_po_trigger']) ? sanitize_text_field(wp_unslash($_POST['gws_po_trigger'])) : 'exit';
    update_post_meta($post_id, 'gws_po_trigger', in_array($trigger, array('exit', 'timed'), true) ? $trigger : 'exit');
    $display = isset($_POST['gws_po_display']) ? sanitize_text_field(wp_unslash($_POST['gws_po_display'])) : 'modal';
    update_post_meta($post_id, 'gws_po_display', in_array($display, array('modal', 'nonmodal'), true) ? $display : 'modal');
    update_post_meta($post_id, 'gws_po_delay', isset($_POST['gws_po_delay']) ? absint($_POST['gws_po_delay']) : 5000);
    update_post_meta($post_id, 'gws_po_cookie_days', isset($_POST['gws_po_cookie_days']) ? absint($_POST['gws_po_cookie_days']) : 7);
    $targets = isset($_POST['gws_po_targets']) && is_array($_POST['gws_po_targets']) ? wp_unslash($_POST['gws_po_targets']) : array();
    update_post_meta($post_id, 'gws_po_targets', gws_po_sanitize_targets($targets));
}
add_action('save_post_gws_popover', 'gws_po_save_native_metabox');
