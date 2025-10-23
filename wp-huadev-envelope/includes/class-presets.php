<?php
if (!defined('ABSPATH')) { exit; }

class WP_Huadev_Envelope_Presets {
    public static function table_name() {
        global $wpdb;
        return $wpdb->prefix . 'huadev_envelope_presets';
    }

    public static function create_table() {
        global $wpdb;
        $table = self::table_name();
        $charset_collate = $wpdb->get_charset_collate();
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $sql = "CREATE TABLE {$table} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            slug VARCHAR(191) NOT NULL,
            name VARCHAR(191) NOT NULL,
            bg_color VARCHAR(20) NOT NULL DEFAULT '',
            envelope_color VARCHAR(20) NOT NULL DEFAULT '',
            pocket_color1 VARCHAR(20) NOT NULL DEFAULT '',
            pocket_color2 VARCHAR(20) NOT NULL DEFAULT '',
            seal_emoji VARCHAR(191) NOT NULL DEFAULT '',
            image_url TEXT,
            float_animation TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY slug (slug)
        ) {$charset_collate};";
        dbDelta($sql);
    }

    public static function sanitize_fields($data) {
        $out = [];
        $out['slug'] = isset($data['slug']) ? sanitize_title($data['slug']) : '';
        $out['name'] = isset($data['name']) ? sanitize_text_field($data['name']) : '';
        $out['bg_color'] = isset($data['bg_color']) ? sanitize_hex_color($data['bg_color']) : '';
        $out['envelope_color'] = isset($data['envelope_color']) ? sanitize_hex_color($data['envelope_color']) : '';
        $out['pocket_color1'] = isset($data['pocket_color1']) ? sanitize_hex_color($data['pocket_color1']) : '';
        $out['pocket_color2'] = isset($data['pocket_color2']) ? sanitize_hex_color($data['pocket_color2']) : '';
        $out['seal_emoji'] = isset($data['seal_emoji']) ? wp_kses_post($data['seal_emoji']) : '';
        $out['image_url'] = isset($data['image_url']) ? esc_url_raw($data['image_url']) : '';
        $out['float_animation'] = isset($data['float']) ? (int) (bool) $data['float'] : (isset($data['float_animation']) ? (int) (bool) $data['float_animation'] : 1);
        return $out;
    }

    public static function create($data) {
        global $wpdb;
        $table = self::table_name();
        $fields = self::sanitize_fields($data);
        if (empty($fields['slug'])) {
            return new WP_Error('invalid_slug', __('Slug is required', 'wp-huadev-envelope'));
        }
        $exists = self::find_by_slug($fields['slug']);
        if ($exists) {
            return new WP_Error('duplicate_slug', __('Slug already exists', 'wp-huadev-envelope'));
        }
        $ok = $wpdb->insert($table, [
            'slug' => $fields['slug'],
            'name' => $fields['name'],
            'bg_color' => $fields['bg_color'],
            'envelope_color' => $fields['envelope_color'],
            'pocket_color1' => $fields['pocket_color1'],
            'pocket_color2' => $fields['pocket_color2'],
            'seal_emoji' => $fields['seal_emoji'],
            'image_url' => $fields['image_url'],
            'float_animation' => $fields['float_animation'],
        ], [
            '%s','%s','%s','%s','%s','%s','%s','%s','%d'
        ]);
        if (!$ok) {
            return new WP_Error('db_error', __('Failed to create preset', 'wp-huadev-envelope'));
        }
        return self::find_by_slug($fields['slug']);
    }

    public static function update_by_slug($slug, $data) {
        global $wpdb;
        $table = self::table_name();
        $fields = self::sanitize_fields($data);
        unset($fields['slug']);
        $ok = $wpdb->update($table, [
            'name' => $fields['name'],
            'bg_color' => $fields['bg_color'],
            'envelope_color' => $fields['envelope_color'],
            'pocket_color1' => $fields['pocket_color1'],
            'pocket_color2' => $fields['pocket_color2'],
            'seal_emoji' => $fields['seal_emoji'],
            'image_url' => $fields['image_url'],
            'float_animation' => $fields['float_animation'],
        ], [ 'slug' => sanitize_title($slug) ], [
            '%s','%s','%s','%s','%s','%s','%s','%d'
        ], ['%s']);
        if ($ok === false) {
            return new WP_Error('db_error', __('Failed to update preset', 'wp-huadev-envelope'));
        }
        return self::find_by_slug($slug);
    }

    public static function delete_by_slug($slug) {
        global $wpdb;
        $table = self::table_name();
        $wpdb->delete($table, [ 'slug' => sanitize_title($slug) ], ['%s']);
        return true;
    }

    public static function find_by_slug($slug) {
        global $wpdb;
        $table = self::table_name();
        $slug = sanitize_title($slug);
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE slug = %s", $slug), ARRAY_A);
        if (!$row) return null;
        return self::map_row_to_options($row);
    }

    public static function all($limit = 100, $offset = 0) {
        global $wpdb;
        $table = self::table_name();
        $limit = absint($limit); if ($limit <= 0) $limit = 100;
        $offset = absint($offset);
        $rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} ORDER BY id DESC LIMIT %d OFFSET %d", $limit, $offset), ARRAY_A);
        $out = [];
        foreach ($rows as $row) { $out[] = self::map_row_to_options($row); }
        return $out;
    }

    private static function map_row_to_options($row) {
        return [
            'slug' => $row['slug'],
            'name' => $row['name'],
            'bg_color' => $row['bg_color'] ?: '#f8f4f2',
            'envelope_color' => $row['envelope_color'] ?: '#812927',
            'pocket_color1' => $row['pocket_color1'] ?: '#a33f3d',
            'pocket_color2' => $row['pocket_color2'] ?: '#a84644',
            'seal_emoji' => $row['seal_emoji'] ?: '💍',
            'image_url' => $row['image_url'] ?: '',
            'float' => (bool) $row['float_animation'],
        ];
    }
}
