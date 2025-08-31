<?php
/**
 * Plugin Name: Simple Image Slider (Lightweight)
 * Plugin URI:  https://example.com/simple-image-slider
 * Description: A lightweight, responsive image slider with shortcode. Upload images, drag-and-drop ordering, and basic settings.
 * Version:     1.0.0
 * Author:      Fatheha
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: simple-image-slider
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'SIS_VERSION', '1.0.0' );
define( 'SIS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SIS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

class Simple_Image_Slider {
    public function __construct() {
        add_action('init', array($this, 'register_post_type'));
        add_action('add_meta_boxes', array($this, 'register_metaboxes'));
        add_action('save_post', array($this, 'save_metaboxes'));
        add_action('admin_enqueue_scripts', array($this, 'admin_assets'));
        add_action('wp_enqueue_scripts', array($this, 'frontend_assets'));
        add_shortcode('sis', array($this, 'shortcode'));
    }

    public function register_post_type() {
        $labels = array(
            'name'               => __('Image Sliders', 'simple-image-slider'),
            'singular_name'      => __('Image Slider', 'simple-image-slider'),
            'menu_name'          => __('Image Sliders', 'simple-image-slider'),
            'name_admin_bar'     => __('Image Slider', 'simple-image-slider'),
            'add_new'            => __('Add New', 'simple-image-slider'),
            'add_new_item'       => __('Add New Slider', 'simple-image-slider'),
            'new_item'           => __('New Slider', 'simple-image-slider'),
            'edit_item'          => __('Edit Slider', 'simple-image-slider'),
            'view_item'          => __('View Slider', 'simple-image-slider'),
            'all_items'          => __('All Sliders', 'simple-image-slider'),
            'search_items'       => __('Search Sliders', 'simple-image-slider'),
            'not_found'          => __('No sliders found.', 'simple-image-slider'),
        );
        $args = array(
            'labels'             => $labels,
            'public'             => false,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'menu_icon'          => 'dashicons-images-alt2',
            'supports'           => array('title'),
        );
        register_post_type('sis_slider', $args);
    }

    public function register_metaboxes() {
        add_meta_box('sis_images', __('Slider Images', 'simple-image-slider'), array($this, 'metabox_images'), 'sis_slider', 'normal', 'high');
        add_meta_box('sis_settings', __('Slider Settings', 'simple-image-slider'), array($this, 'metabox_settings'), 'sis_slider', 'side', 'default');
    }

    public function metabox_images($post) {
        wp_nonce_field('sis_save', 'sis_nonce');
        $images = get_post_meta($post->ID, '_sis_images', true);
        if (!is_array($images)) $images = array();

        echo '<p><button type="button" class="button button-primary" id="sis-add-images">'.__('Add Images', 'simple-image-slider').'</button></p>';
        echo '<ul id="sis-image-list" class="sis-image-list">';
        foreach ($images as $img) {
            $thumb = wp_get_attachment_image_src($img['id'], 'thumbnail');
            $cap = isset($img['caption']) ? esc_attr($img['caption']) : '';
            $alt = get_post_meta($img['id'], '_wp_attachment_image_alt', true);
            echo '<li class="sis-image-item" data-id="'.intval($img['id']).'">';
            echo '<img src="'.esc_url($thumb ? $thumb[0] : '').'" alt="'.esc_attr($alt).'"/>';
            echo '<input type="text" class="widefat sis-caption" placeholder="'.esc_attr__('Caption (optional)', 'simple-image-slider').'" value="'.$cap.'" />';
            echo '<button class="button-link-delete sis-remove">&times;</button>';
            echo '</li>';
        }
        echo '</ul>';
        echo '<input type="hidden" id="sis-images-json" name="sis_images_json" value="'.esc_attr(wp_json_encode($images)).'"/>';
        echo '<p class="description">'.__('Drag and drop to reorder. Captions are optional.', 'simple-image-slider').'</p>';
    }

    public function metabox_settings($post) {
        $defaults = array(
            'autoplay' => 1,
            'speed' => 3000,
            'arrows' => 1,
            'dots' => 1,
            'layout' => 'full',
            'height' => 400,
            'transition' => 'slide',
            'fit' => 'cover'
        );
        $settings = wp_parse_args(get_post_meta($post->ID, '_sis_settings', true), $defaults);

        ?>
        <p>
            <label><input type="checkbox" name="sis_settings[autoplay]" value="1" <?php checked($settings['autoplay'], 1); ?>> <?php _e('Autoplay', 'simple-image-slider'); ?></label>
        </p>
        <p>
            <label><?php _e('Autoplay Speed (ms)', 'simple-image-slider'); ?><br>
            <input type="number" name="sis_settings[speed]" min="100" step="100" value="<?php echo esc_attr($settings['speed']); ?>" class="small-text"></label>
        </p>
        <p>
            <label><input type="checkbox" name="sis_settings[arrows]" value="1" <?php checked($settings['arrows'], 1); ?>> <?php _e('Show Arrows', 'simple-image-slider'); ?></label><br>
            <label><input type="checkbox" name="sis_settings[dots]" value="1" <?php checked($settings['dots'], 1); ?>> <?php _e('Show Dots', 'simple-image-slider'); ?></label>
        </p>
        <p>
            <label><?php _e('Layout', 'simple-image-slider'); ?><br>
            <select name="sis_settings[layout]">
                <option value="full" <?php selected($settings['layout'], 'full'); ?>><?php _e('Full Width', 'simple-image-slider'); ?></option>
                <option value="fixed" <?php selected($settings['layout'], 'fixed'); ?>><?php _e('Fixed Width', 'simple-image-slider'); ?></option>
            </select></label>
        </p>
        <p>
            <label><?php _e('Slider Height (px)', 'simple-image-slider'); ?><br>
            <input type="number" name="sis_settings[height]" min="100" step="10" value="<?php echo esc_attr($settings['height']); ?>" class="small-text"></label>
        </p>
        <p>
            <label><?php _e('Transition', 'simple-image-slider'); ?><br>
            <select name="sis_settings[transition]">
                <option value="slide" <?php selected($settings['transition'], 'slide'); ?>><?php _e('Slide', 'simple-image-slider'); ?></option>
                <option value="fade" <?php selected($settings['transition'], 'fade'); ?>><?php _e('Fade', 'simple-image-slider'); ?></option>
            </select></label>
        </p>
        <p>
            <label><?php _e('Image Fit', 'simple-image-slider'); ?><br>
            <select name="sis_settings[fit]">
                <option value="cover" <?php selected($settings['fit'], 'cover'); ?>><?php _e('Cover', 'simple-image-slider'); ?></option>
                <option value="contain" <?php selected($settings['fit'], 'contain'); ?>><?php _e('Contain', 'simple-image-slider'); ?></option>
                <option value="original" <?php selected($settings['fit'], 'original'); ?>><?php _e('Original', 'simple-image-slider'); ?></option>
            </select></label>
        </p>
        <p class="description"><?php _e('Use shortcode like: [sis id="123"]', 'simple-image-slider'); ?></p>
        <?php
    }

    public function save_metaboxes($post_id) {
        if ( ! isset($_POST['sis_nonce']) || ! wp_verify_nonce($_POST['sis_nonce'], 'sis_save') ) return;
        if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return;
        if ( ! current_user_can('edit_post', $post_id) ) return;

        // Images
        $images_json = isset($_POST['sis_images_json']) ? wp_unslash($_POST['sis_images_json']) : '[]';
        $images = json_decode($images_json, true);
        if (!is_array($images)) $images = array();
        // Sanitize
        $clean = array();
        foreach ($images as $img) {
            if (!isset($img['id'])) continue;
            $clean[] = array(
                'id' => intval($img['id']),
                'caption' => isset($img['caption']) ? sanitize_text_field($img['caption']) : ''
            );
        }
        update_post_meta($post_id, '_sis_images', $clean);

        // Settings
        $defaults = array(
            'autoplay' => 0, 'speed' => 3000, 'arrows' => 0, 'dots' => 0, 'layout' => 'full', 'height' => 400, 'transition' => 'slide', 'fit' => 'cover'
        );
        $raw = isset($_POST['sis_settings']) ? (array) $_POST['sis_settings'] : array();
        $settings = array(
            'autoplay' => isset($raw['autoplay']) ? 1 : 0,
            'speed' => isset($raw['speed']) ? max(100, intval($raw['speed'])) : 3000,
            'arrows' => isset($raw['arrows']) ? 1 : 0,
            'dots' => isset($raw['dots']) ? 1 : 0,
            'layout' => in_array($raw['layout'] ?? 'full', array('full','fixed'), true) ? $raw['layout'] : 'full',
            'height' => isset($raw['height']) ? max(100, intval($raw['height'])) : 400,
            'transition' => in_array($raw['transition'] ?? 'slide', array('slide','fade'), true) ? $raw['transition'] : 'slide',
            'fit' => in_array($raw['fit'] ?? 'cover', array('cover','contain','original'), true) ? $raw['fit'] : 'cover'
        );
        update_post_meta($post_id, '_sis_settings', $settings);
    }

    public function admin_assets($hook) {
        global $post_type;
        if ( ('post-new.php' === $hook || 'post.php' === $hook) && 'sis_slider' === $post_type ) {
            wp_enqueue_media();
            wp_enqueue_script('jquery-ui-sortable');
            wp_enqueue_style('sis-admin', SIS_PLUGIN_URL . 'assets/admin/admin.css', array(), SIS_VERSION);
            wp_enqueue_script('sis-admin', SIS_PLUGIN_URL . 'assets/admin/admin.js', array('jquery', 'jquery-ui-sortable'), SIS_VERSION, true);
        }
    }

    public function frontend_assets() {
        wp_register_style('sis-frontend', SIS_PLUGIN_URL . 'assets/frontend/style.css', array(), SIS_VERSION);
        wp_register_script('sis-frontend', SIS_PLUGIN_URL . 'assets/frontend/slider.js', array(), SIS_VERSION, true);
    }

    public function shortcode($atts) {
        $atts = shortcode_atts(array('id' => 0), $atts, 'sis');
        $post_id = intval($atts['id']);
        if (!$post_id) return '';

        $images = get_post_meta($post_id, '_sis_images', true);
        $settings = get_post_meta($post_id, '_sis_settings', true);
        if (empty($images) || !is_array($images)) return '';

        wp_enqueue_style('sis-frontend');
        wp_enqueue_script('sis-frontend');

        $uid = 'sis-' . $post_id . '-' . wp_generate_password(6, false, false);
        $classes = array('sis-wrapper', 'transition-' . esc_attr($settings['transition']), 'fit-' . esc_attr($settings['fit']));
        if ($settings['layout'] === 'full') $classes[] = 'layout-full';
        else $classes[] = 'layout-fixed';

        ob_start();
        ?>
        <div class="<?php echo esc_attr(implode(' ', $classes)); ?>" id="<?php echo esc_attr($uid); ?>" style="--sis-height: <?php echo intval($settings['height']); ?>px;">
            <div class="sis-track">
                <?php foreach ($images as $index => $img): 
                    $src = wp_get_attachment_image_src($img['id'], 'large');
                    $alt = get_post_meta($img['id'], '_wp_attachment_image_alt', true);
                    $caption = isset($img['caption']) ? $img['caption'] : '';
                ?>
                    <figure class="sis-slide" data-index="<?php echo intval($index); ?>">
                        <img src="<?php echo esc_url($src ? $src[0] : ''); ?>" alt="<?php echo esc_attr($alt); ?>" loading="lazy"/>
                        <?php if (!empty($caption)): ?>
                            <figcaption class="sis-caption"><?php echo esc_html($caption); ?></figcaption>
                        <?php endif; ?>
                    </figure>
                <?php endforeach; ?>
            </div>

            <?php if (!empty($settings['arrows'])): ?>
            <button class="sis-arrow sis-prev" aria-label="<?php esc_attr_e('Previous slide', 'simple-image-slider'); ?>">&lsaquo;</button>
            <button class="sis-arrow sis-next" aria-label="<?php esc_attr_e('Next slide', 'simple-image-slider'); ?>">&rsaquo;</button>
            <?php endif; ?>

            <?php if (!empty($settings['dots'])): ?>
            <div class="sis-dots" role="tablist" aria-label="<?php esc_attr_e('Slider pagination', 'simple-image-slider'); ?>"></div>
            <?php endif; ?>
        </div>
        <script>
        window.SIS_QUEUES = window.SIS_QUEUES || [];
        window.SIS_QUEUES.push({
            id: "<?php echo esc_js($uid); ?>",
            settings: <?php echo wp_json_encode(array(
                'autoplay' => (bool)$settings['autoplay'],
                'speed' => (int)$settings['speed'],
                'arrows' => (bool)$settings['arrows'],
                'dots' => (bool)$settings['dots'],
                'transition' => (string)$settings['transition']
            )); ?>
        });
        </script>
        <?php
        return ob_get_clean();
    }
}

new Simple_Image_Slider();
