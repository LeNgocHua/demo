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

require_once __DIR__ . '/includes/class-presets.php';

class WP_Huadev_Envelope {
    const OPTION_KEY = 'wp_huadev_envelope_options';

    public static function init() {
        register_activation_hook(__FILE__, [__CLASS__, 'activate']);
        add_action('init', [__CLASS__, 'register_shortcodes']);
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_assets']);
        add_action('admin_menu', [__CLASS__, 'register_admin_page']);
        add_action('admin_enqueue_scripts', [__CLASS__, 'admin_enqueue']);
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

    public static function activate() {
        // Create custom table for presets
        WP_Huadev_Envelope_Presets::create_table();
        // Ensure a default preset exists using current options
        $opts = self::get_options();
        $exists = WP_Huadev_Envelope_Presets::find_by_slug('default');
        if (!$exists) {
            WP_Huadev_Envelope_Presets::create([
                'slug' => 'default',
                'name' => 'Default',
                'bg_color' => $opts['bg_color'],
                'envelope_color' => $opts['envelope_color'],
                'pocket_color1' => $opts['pocket_color1'],
                'pocket_color2' => $opts['pocket_color2'],
                'seal_emoji' => $opts['seal_emoji'],
                'image_url' => $opts['image_url'],
                'float' => $opts['float'] ? 1 : 0,
            ]);
        }
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
            'slug' => '',
            'bg_color' => $opts['bg_color'],
            'envelope_color' => $opts['envelope_color'],
            'pocket_color1' => $opts['pocket_color1'],
            'pocket_color2' => $opts['pocket_color2'],
            'seal_emoji' => $opts['seal_emoji'],
            'image_url' => $opts['image_url'],
            'float' => $opts['float'] ? '1' : '0',
        ], $atts, 'huadev_envelope');

        // If a preset slug is provided, override with preset values
        if (!empty($atts['slug'])) {
            $preset = WP_Huadev_Envelope_Presets::find_by_slug($atts['slug']);
            if ($preset) {
                $atts['bg_color'] = $preset['bg_color'];
                $atts['envelope_color'] = $preset['envelope_color'];
                $atts['pocket_color1'] = $preset['pocket_color1'];
                $atts['pocket_color2'] = $preset['pocket_color2'];
                $atts['seal_emoji'] = $preset['seal_emoji'];
                $atts['image_url'] = $preset['image_url'];
                $atts['float'] = $preset['float'] ? '1' : '0';
            }
        }

        wp_enqueue_style('huadev-envelope');
        wp_enqueue_script('huadev-envelope');

        ob_start();
        include __DIR__ . '/includes/template.php';
        return ob_get_clean();
    }

    public static function register_admin_page() {
        add_menu_page(
            __('Huadev Envelope', 'wp-huadev-envelope'),
            __('Huadev Envelope', 'wp-huadev-envelope'),
            'manage_options',
            'wp-huadev-envelope',
            [__CLASS__, 'render_admin_page'],
            'dashicons-email-alt2',
            56
        );
    }

    public static function admin_enqueue($hook) {
        if (!isset($_GET['page']) || $_GET['page'] !== 'wp-huadev-envelope') {
            return;
        }
        // Media library and admin helpers for picking images
        wp_enqueue_media();
        $base_url = plugins_url('', __FILE__);
        wp_enqueue_script('huadev-envelope-admin', $base_url . '/assets/js/admin.js', ['jquery'], '1.0.0', true);
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

        $c = isset($input['bg_color']) ? sanitize_hex_color($input['bg_color']) : '';
        $sanitized['bg_color'] = $c ? $c : $defaults['bg_color'];
        $c = isset($input['envelope_color']) ? sanitize_hex_color($input['envelope_color']) : '';
        $sanitized['envelope_color'] = $c ? $c : $defaults['envelope_color'];
        $c = isset($input['pocket_color1']) ? sanitize_hex_color($input['pocket_color1']) : '';
        $sanitized['pocket_color1'] = $c ? $c : $defaults['pocket_color1'];
        $c = isset($input['pocket_color2']) ? sanitize_hex_color($input['pocket_color2']) : '';
        $sanitized['pocket_color2'] = $c ? $c : $defaults['pocket_color2'];
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
        // Handle create/update submit
        $notice = '';
        if (isset($_POST['wp_huadev_envelope_nonce']) && wp_verify_nonce($_POST['wp_huadev_envelope_nonce'], 'save_preset') && current_user_can('manage_options')) {
            $data = [
                'slug' => isset($_POST['preset']['slug']) ? wp_unslash($_POST['preset']['slug']) : '',
                'name' => isset($_POST['preset']['name']) ? wp_unslash($_POST['preset']['name']) : '',
                'bg_color' => isset($_POST['preset']['bg_color']) ? wp_unslash($_POST['preset']['bg_color']) : '',
                'envelope_color' => isset($_POST['preset']['envelope_color']) ? wp_unslash($_POST['preset']['envelope_color']) : '',
                'pocket_color1' => isset($_POST['preset']['pocket_color1']) ? wp_unslash($_POST['preset']['pocket_color1']) : '',
                'pocket_color2' => isset($_POST['preset']['pocket_color2']) ? wp_unslash($_POST['preset']['pocket_color2']) : '',
                'seal_url' => isset($_POST['preset']['seal_url']) ? wp_unslash($_POST['preset']['seal_url']) : '',
                'image_url' => isset($_POST['preset']['image_url']) ? wp_unslash($_POST['preset']['image_url']) : '',
                'float' => isset($_POST['preset']['float']) ? 1 : 0,
            ];
            $exists = WP_Huadev_Envelope_Presets::find_by_slug($data['slug']);
            if ($exists) {
                $res = WP_Huadev_Envelope_Presets::update_by_slug($data['slug'], $data);
                $notice = is_wp_error($res) ? $res->get_error_message() : __('Preset updated.', 'wp-huadev-envelope');
            } else {
                $res = WP_Huadev_Envelope_Presets::create($data);
                $notice = is_wp_error($res) ? $res->get_error_message() : __('Preset created.', 'wp-huadev-envelope');
            }
        }

        $presets = WP_Huadev_Envelope_Presets::all();
        $endpoint_base = rest_url('huadev/v1/presets');
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Huadev Envelope Presets', 'wp-huadev-envelope'); ?></h1>
            <?php if ($notice): ?>
                <div class="notice notice-success"><p><?php echo esc_html($notice); ?></p></div>
            <?php endif; ?>

            <h2><?php echo esc_html__('Add / Update Preset', 'wp-huadev-envelope'); ?></h2>
            <form method="post">
                <?php wp_nonce_field('save_preset', 'wp_huadev_envelope_nonce'); ?>
                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row"><label for="preset_slug">Slug</label></th>
                            <td><input name="preset[slug]" id="preset_slug" type="text" class="regular-text" required /></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="preset_name">Name</label></th>
                            <td><input name="preset[name]" id="preset_name" type="text" class="regular-text" /></td>
                        </tr>
                        <tr>
                            <th scope="row"><label>Background Color</label></th>
                            <td><input name="preset[bg_color]" type="text" class="regular-text" placeholder="#f8f4f2" /></td>
                        </tr>
                        <tr>
                            <th scope="row"><label>Envelope Color</label></th>
                            <td><input name="preset[envelope_color]" type="text" class="regular-text" placeholder="#812927" /></td>
                        </tr>
                        <tr>
                            <th scope="row"><label>Pocket Color 1</label></th>
                            <td><input name="preset[pocket_color1]" type="text" class="regular-text" placeholder="#a33f3d" /></td>
                        </tr>
                        <tr>
                            <th scope="row"><label>Pocket Color 2</label></th>
                            <td><input name="preset[pocket_color2]" type="text" class="regular-text" placeholder="#a84644" /></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="preset_seal_url">Seal URL</label></th>
                            <td>
                                <input name="preset[seal_url]" id="preset_seal_url" type="url" class="regular-text" placeholder="https://..." />
                                <button type="button" class="button huadev-media-button" data-target="preset_seal_url" data-preview="preset_seal_url_preview"><?php echo esc_html__('Select from Media', 'wp-huadev-envelope'); ?></button>
                                <img id="preset_seal_url_preview" src="" alt="" style="max-width:48px; max-height:48px; margin-left:8px; display:none; border-radius:4px;" />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="preset_image_url">Image URL</label></th>
                            <td>
                                <input name="preset[image_url]" id="preset_image_url" type="url" class="regular-text" placeholder="https://..." />
                                <button type="button" class="button huadev-media-button" data-target="preset_image_url" data-preview="preset_image_url_preview"><?php echo esc_html__('Select from Media', 'wp-huadev-envelope'); ?></button>
                                <img id="preset_image_url_preview" src="" alt="" style="max-width:64px; max-height:48px; margin-left:8px; display:none; border-radius:4px;" />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label>Float Animation</label></th>
                            <td><label><input name="preset[float]" type="checkbox" value="1" checked /> <?php echo esc_html__('Enable', 'wp-huadev-envelope'); ?></label></td>
                        </tr>
                    </tbody>
                </table>
                <?php submit_button(__('Save Preset', 'wp-huadev-envelope')); ?>
            </form>

            <h2><?php echo esc_html__('Existing Presets', 'wp-huadev-envelope'); ?></h2>
            <?php if (empty($presets)): ?>
                <p><?php echo esc_html__('No presets yet. Create one above.', 'wp-huadev-envelope'); ?></p>
            <?php else: ?>
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th><?php echo esc_html__('Slug', 'wp-huadev-envelope'); ?></th>
                            <th><?php echo esc_html__('Name', 'wp-huadev-envelope'); ?></th>
                            <th><?php echo esc_html__('Seal', 'wp-huadev-envelope'); ?></th>
                            <th><?php echo esc_html__('Shortcode', 'wp-huadev-envelope'); ?></th>
                            <th><?php echo esc_html__('Embed Snippet', 'wp-huadev-envelope'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($presets as $p): ?>
                            <tr>
                                <td><code><?php echo esc_html($p['slug']); ?></code></td>
                                <td><?php echo esc_html($p['name']); ?></td>
                                <td>
                                    <?php if (!empty($p['seal_url'])): ?>
                                        <img src="<?php echo esc_url($p['seal_url']); ?>" alt="seal" style="width:36px; height:36px; object-fit:cover; border-radius:50%; box-shadow: inset 0 0 0 1px rgba(0,0,0,.06);" />
                                    <?php else: ?>
                                        <em><?php echo esc_html__('(none)', 'wp-huadev-envelope'); ?></em>
                                    <?php endif; ?>
                                </td>
                                <td><code>[huadev_envelope slug="<?php echo esc_attr($p['slug']); ?>"]</code></td>
                                <td>
                                    <textarea readonly rows="2" style="width:100%;" onclick="this.select()">&lt;script src="<?php echo esc_url(plugins_url('assets/js/embed.js', __FILE__)); ?>" data-preset="<?php echo esc_attr($p['slug']); ?>" data-endpoint="<?php echo esc_url($endpoint_base); ?>"&gt;&lt;/script&gt;</textarea>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php
    }

    public static function register_rest() {
        // Back-compat endpoint returning global options
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

        // List presets (admin)
        register_rest_route('huadev/v1', '/presets', [
            'methods' => 'GET',
            'callback' => function(WP_REST_Request $req) {
                $items = WP_Huadev_Envelope_Presets::all();
                return rest_ensure_response(['success' => true, 'data' => $items]);
            },
            'permission_callback' => function() {
                return current_user_can('manage_options');
            },
        ]);

        // Get preset by slug (public)
        register_rest_route('huadev/v1', '/presets/(?P<slug>[a-z0-9\-]+)', [
            'methods' => 'GET',
            'callback' => function(WP_REST_Request $req) {
                $slug = $req->get_param('slug');
                $item = WP_Huadev_Envelope_Presets::find_by_slug($slug);
                if (!$item) {
                    return new WP_Error('not_found', __('Preset not found', 'wp-huadev-envelope'), ['status' => 404]);
                }
                $resp = new WP_REST_Response(['success' => true, 'data' => $item]);
                $resp->header('Access-Control-Allow-Origin', '*');
                return $resp;
            },
            'permission_callback' => '__return_true',
        ]);
    }
}

WP_Huadev_Envelope::init();
