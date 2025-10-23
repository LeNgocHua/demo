<?php
/**
 * Plugin Name: Huadev Envelope Card
 * Description: Interactive envelope card with customizable colors and image. Provides shortcode [huadev_envelope] and embeddable script.
 * Version: 1.0.0
 * Author: You
 * License: GPL-2.0-or-later
 * Text Domain: wp-huadev-envelope
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_Huadev_Envelope {
    const OPTION_KEY = 'wp_huadev_envelope_options';

    public static function init() {
        add_action('init', [__CLASS__, 'register_shortcodes']);
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_assets']);
        add_action('admin_menu', [__CLASS__, 'register_admin_page']);
        add_action('admin_init', [__CLASS__, 'register_settings']);
        add_action('rest_api_init', [__CLASS__, 'register_rest']);
    }

    public static function default_options() {
        return [
            'bg_color' => '#f8f4f2',
            'envelope_color' => '#812927',
            'pocket_color1' => '#a33f3d',
            'pocket_color2' => '#a84644',
            'seal_emoji' => '💍',
            'image_url' => 'https://cdn.cinelove.me/templates/assets/5731de59-c0f3-4fa7-9860-e5e47b829ce3/c4d45265-947c-414c-b53f-f291586faeea.jpg?crop=0,135,1280,853&resize=800x',
            'float' => true,
        ];
    }

    public static function get_options() {
        $saved = get_option(self::OPTION_KEY, []);
        $defaults = self::default_options();
        return wp_parse_args(is_array($saved) ? $saved : [], $defaults);
    }

    public static function register_shortcodes() {
        add_shortcode('huadev_envelope', [__CLASS__, 'render_shortcode']);
    }

    public static function enqueue_assets() {
        $handle = 'huadev-envelope';
        $base_url = plugins_url('', __FILE__);
        wp_register_style($handle, $base_url . '/assets/css/envelope.css', [], '1.0.0');
        wp_register_script($handle, $base_url . '/assets/js/envelope.js', [], '1.0.0', true);
    }

    public static function render_shortcode($atts = [], $content = null) {
        $opts = self::get_options();

        $atts = shortcode_atts([
            'bg_color' => $opts['bg_color'],
            'envelope_color' => $opts['envelope_color'],
            'pocket_color1' => $opts['pocket_color1'],
            'pocket_color2' => $opts['pocket_color2'],
            'seal_emoji' => $opts['seal_emoji'],
            'image_url' => $opts['image_url'],
            'float' => $opts['float'] ? '1' : '0',
        ], $atts, 'huadev_envelope');

        wp_enqueue_style('huadev-envelope');
        wp_enqueue_script('huadev-envelope');

        ob_start();
        include __DIR__ . '/includes/template.php';
        return ob_get_clean();
    }

    public static function register_admin_page() {
        add_options_page(
            __('Huadev Envelope', 'wp-huadev-envelope'),
            __('Huadev Envelope', 'wp-huadev-envelope'),
            'manage_options',
            'wp-huadev-envelope',
            [__CLASS__, 'render_admin_page']
        );
    }

    public static function register_settings() {
        register_setting('wp_huadev_envelope_group', self::OPTION_KEY, [
            'type' => 'array',
            'sanitize_callback' => [__CLASS__, 'sanitize_options'],
            'default' => self::default_options(),
        ]);

        add_settings_section('wp_huadev_envelope_main', __('Settings', 'wp-huadev-envelope'), '__return_false', 'wp-huadev-envelope');

        $fields = [
            'bg_color' => __('Background Color', 'wp-huadev-envelope'),
            'envelope_color' => __('Envelope Color', 'wp-huadev-envelope'),
            'pocket_color1' => __('Pocket Color 1', 'wp-huadev-envelope'),
            'pocket_color2' => __('Pocket Color 2', 'wp-huadev-envelope'),
            'seal_emoji' => __('Seal Emoji', 'wp-huadev-envelope'),
            'image_url' => __('Image URL', 'wp-huadev-envelope'),
            'float' => __('Enable Float Animation', 'wp-huadev-envelope'),
        ];

        foreach ($fields as $key => $label) {
            add_settings_field(
                $key,
                $label,
                [__CLASS__, 'render_field'],
                'wp-huadev-envelope',
                'wp_huadev_envelope_main',
                ['key' => $key]
            );
        }
    }

    public static function sanitize_options($input) {
        $defaults = self::default_options();
        $sanitized = [];

        $sanitized['bg_color'] = isset($input['bg_color']) ? sanitize_hex_color($input['bg_color']) : $defaults['bg_color'];
        $sanitized['envelope_color'] = isset($input['envelope_color']) ? sanitize_hex_color($input['envelope_color']) : $defaults['envelope_color'];
        $sanitized['pocket_color1'] = isset($input['pocket_color1']) ? sanitize_hex_color($input['pocket_color1']) : $defaults['pocket_color1'];
        $sanitized['pocket_color2'] = isset($input['pocket_color2']) ? sanitize_hex_color($input['pocket_color2']) : $defaults['pocket_color2'];
        $sanitized['seal_emoji'] = isset($input['seal_emoji']) ? wp_kses_post($input['seal_emoji']) : $defaults['seal_emoji'];
        $sanitized['image_url'] = isset($input['image_url']) ? esc_url_raw($input['image_url']) : $defaults['image_url'];
        $sanitized['float'] = !empty($input['float']);

        return wp_parse_args($sanitized, $defaults);
    }

    public static function render_field($args) {
        $key = $args['key'];
        $opts = self::get_options();
        $value = isset($opts[$key]) ? $opts[$key] : '';
        switch ($key) {
            case 'bg_color':
            case 'envelope_color':
            case 'pocket_color1':
            case 'pocket_color2':
                printf('<input type="text" class="regular-text" name="%1$s[%2$s]" value="%3$s" placeholder="#ffffff" />', esc_attr(self::OPTION_KEY), esc_attr($key), esc_attr($value));
                break;
            case 'seal_emoji':
                printf('<input type="text" class="regular-text" name="%1$s[%2$s]" value="%3$s" />', esc_attr(self::OPTION_KEY), esc_attr($key), esc_attr($value));
                break;
            case 'image_url':
                printf('<input type="url" class="regular-text" name="%1$s[%2$s]" value="%3$s" placeholder="https://..." />', esc_attr(self::OPTION_KEY), esc_attr($key), esc_attr($value));
                break;
            case 'float':
                printf('<label><input type="checkbox" name="%1$s[%2$s]" value="1" %3$s /> %4$s</label>', esc_attr(self::OPTION_KEY), esc_attr($key), checked($value, true, false), esc_html__('Enable floating animation', 'wp-huadev-envelope'));
                break;
        }
    }

    public static function render_admin_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Huadev Envelope Settings', 'wp-huadev-envelope'); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('wp_huadev_envelope_group');
                do_settings_sections('wp-huadev-envelope');
                submit_button();
                ?>
            </form>

            <h2><?php echo esc_html__('Embed', 'wp-huadev-envelope'); ?></h2>
            <p><?php echo esc_html__('Use this script tag to embed on any site:', 'wp-huadev-envelope'); ?></p>
            <textarea readonly rows="4" style="width:100%;" onclick="this.select()">&lt;script src="<?php echo esc_url(plugins_url('assets/js/embed.js', __FILE__)); ?>" data-endpoint="<?php echo esc_url(rest_url('huadev/v1/options')); ?>"&gt;&lt;/script&gt;</textarea>
        </div>
        <?php
    }

    public static function register_rest() {
        register_rest_route('huadev/v1', '/options', [
            'methods' => 'GET',
            'callback' => function() {
                $opts = self::get_options();
                return rest_ensure_response([
                    'success' => true,
                    'data' => $opts,
                ]);
            },
            'permission_callback' => '__return_true',
        ]);
    }
}

WP_Huadev_Envelope::init();
