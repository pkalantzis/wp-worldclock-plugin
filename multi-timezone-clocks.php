<?php
/**
 * Plugin Name: WP Multi Timezone Clocks
 * Description: Admin-configured clocks (label + timezone + type). Shortcode renders one clock by ID.
 * Version: 0.1.0
 * Author: Panagiotis Kalantzis
 * License: GPLv2 or later
 * Requires PHP: 7.4
 * 
 * Changelog:
 * - 0.1.0: Initial version.
 */

if (!defined('ABSPATH')) exit;

class MTC_Timezone_Clocks {
    const VERSION    = '1.5.0';
    const OPTION_KEY = 'mtc_clocks';
    const MENU_SLUG  = 'mtc-timezone-clocks';

    public function __construct() {
        add_action('admin_menu', [$this, 'admin_menu']);
        add_action('admin_init', [$this, 'handle_save']);

        add_action('wp_enqueue_scripts', [$this, 'register_assets']);

        add_shortcode('mtc_clock', [$this, 'shortcode_single_clock']);
    }

    /** ---------- Assets ---------- */

    public function register_assets() {
        $url = plugin_dir_url(__FILE__);
        wp_register_script('mtc-clocks', $url . 'assets/mtc-clocks.js', [], self::VERSION, true);
        wp_register_style('mtc-clocks', $url . 'assets/mtc-clocks.css', [], self::VERSION);
    }

    private function enqueue_frontend_assets(): void {
        wp_enqueue_script('mtc-clocks');
        wp_enqueue_style('mtc-clocks');
    }

    /** ---------- Data ---------- */

    public function get_clocks(): array {
        $data = get_option(self::OPTION_KEY, []);
        if (!is_array($data)) $data = [];

        $out = [];
        $i = 1;

        foreach ($data as $row) {
            if (!is_array($row)) continue;

            $label = isset($row['label']) ? (string)$row['label'] : '';
            $tz    = isset($row['tz']) ? (string)$row['tz'] : '';
            $type  = isset($row['type']) ? (string)$row['type'] : 'analog';

            $label_position = isset($row['label_position']) ? (string)$row['label_position'] : 'above';
            $show_tz        = isset($row['show_tz']) ? (string)$row['show_tz'] : '1';
            $user_time      = isset($row['user_time']) ? (string)$row['user_time'] : '0';

            $type = ($type === 'digital') ? 'digital' : 'analog';
            $label_position = ($label_position === 'below') ? 'below' : 'above';
            $show_tz = ($show_tz === '0') ? '0' : '1';
            $user_time = ($user_time === '1') ? '1' : '0';

            $out[$i] = [
                'label'          => $label,
                'tz'             => $tz,
                'type'           => $type,
                'label_position' => $label_position,
                'show_tz'        => $show_tz,
                'user_time'      => $user_time,
            ];
            $i++;
        }
        return $out;
    }

    private function set_clocks(array $clocks): void {
        update_option(self::OPTION_KEY, array_values($clocks), false);
    }

    /** ---------- Admin UI ---------- */

    public function admin_menu() {
        add_options_page(
            'Timezone Clocks',
            'Timezone Clocks',
            'manage_options',
            self::MENU_SLUG,
            [$this, 'render_admin_page']
        );
    }

    public function handle_save() {
        if (!is_admin()) return;
        if (!isset($_POST['mtc_action']) || $_POST['mtc_action'] !== 'save') return;
        if (!current_user_can('manage_options')) return;

        check_admin_referer('mtc_save_settings', 'mtc_nonce');

        $count = isset($_POST['mtc_count']) ? intval($_POST['mtc_count']) : 1;
        if ($count < 1) $count = 1;
        if ($count > 50) $count = 50;

        $labels  = (isset($_POST['mtc_label']) && is_array($_POST['mtc_label'])) ? $_POST['mtc_label'] : [];
        $tzs     = (isset($_POST['mtc_tz']) && is_array($_POST['mtc_tz'])) ? $_POST['mtc_tz'] : [];
        $types   = (isset($_POST['mtc_type']) && is_array($_POST['mtc_type'])) ? $_POST['mtc_type'] : [];
        $lpos    = (isset($_POST['mtc_label_position']) && is_array($_POST['mtc_label_position'])) ? $_POST['mtc_label_position'] : [];
        $showtz  = (isset($_POST['mtc_show_tz']) && is_array($_POST['mtc_show_tz'])) ? $_POST['mtc_show_tz'] : [];
        $usertime= (isset($_POST['mtc_user_time']) && is_array($_POST['mtc_user_time'])) ? $_POST['mtc_user_time'] : [];

        $new = [];
        for ($i = 1; $i <= $count; $i++) {
            $label = isset($labels[$i]) ? sanitize_text_field(wp_unslash($labels[$i])) : '';
            $tz    = isset($tzs[$i]) ? sanitize_text_field(wp_unslash($tzs[$i])) : '';

            $type  = isset($types[$i]) ? sanitize_text_field(wp_unslash($types[$i])) : 'analog';
            $type  = ($type === 'digital') ? 'digital' : 'analog';

            $label_position = isset($lpos[$i]) ? sanitize_text_field(wp_unslash($lpos[$i])) : 'above';
            $label_position = ($label_position === 'below') ? 'below' : 'above';

            // Checkboxes: present => "1", absent => "0"
            $show_tz   = isset($showtz[$i]) && $showtz[$i] === '1' ? '1' : '0';
            $user_time = isset($usertime[$i]) && $usertime[$i] === '1' ? '1' : '0';

            $new[] = [
                'label'          => $label,
                'tz'             => $tz,
                'type'           => $type,
                'label_position' => $label_position,
                'show_tz'        => $show_tz,
                'user_time'      => $user_time,
            ];
        }

        $this->set_clocks($new);

        wp_safe_redirect(add_query_arg(
            ['page' => self::MENU_SLUG, 'mtc_saved' => '1'],
            admin_url('options-general.php')
        ));
        exit;
    }

    public function render_admin_page() {
        if (!current_user_can('manage_options')) return;

        $clocks = $this->get_clocks();
        $count  = max(1, count($clocks));
        $saved  = isset($_GET['mtc_saved']) && $_GET['mtc_saved'] === '1';
        ?>
        <div class="wrap">
            <h1>Timezone Clocks</h1>

            <?php if ($saved): ?>
                <div class="notice notice-success is-dismissible"><p>Settings saved.</p></div>
            <?php endif; ?>

            <p>
                Use IANA timezones like <code>Europe/Athens</code>, <code>America/New_York</code>, <code>Asia/Tokyo</code>.
                You can also enable “Visitor local time” per clock.
            </p>

            <form method="post" action="<?php echo esc_url(admin_url('options-general.php?page=' . self::MENU_SLUG)); ?>">
                <?php wp_nonce_field('mtc_save_settings', 'mtc_nonce'); ?>
                <input type="hidden" name="mtc_action" value="save" />

                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="mtc_count">Number of clocks</label></th>
                        <td>
                            <input type="number" id="mtc_count" name="mtc_count" min="1" max="50"
                                   value="<?php echo esc_attr($count); ?>" style="width: 90px;" />
                            <p class="description">Change and save to add/remove rows.</p>
                        </td>
                    </tr>
                </table>

                <h2 class="title">Clocks</h2>

                <table class="widefat striped" style="max-width: 1200px;">
                    <thead>
                        <tr>
                            <th style="width: 40px;">#</th>
                            <th style="width: 120px;">Type</th>
                            <th>Label</th>
                            <th>Timezone (IANA)</th>
                            <th style="width: 140px;">Label position</th>
                            <th style="width: 120px;">Show timezone</th>
                            <th style="width: 150px;">Visitor local time</th>
                            <th style="width: 260px;">Shortcode</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php for ($i = 1; $i <= $count; $i++):
                        $row = $clocks[$i] ?? [
                            'label' => '',
                            'tz' => '',
                            'type' => 'analog',
                            'label_position' => 'above',
                            'show_tz' => '1',
                            'user_time' => '0',
                        ];

                        $type = ($row['type'] ?? 'analog') === 'digital' ? 'digital' : 'analog';
                        $label = $row['label'] ?? '';
                        $tz = $row['tz'] ?? '';
                        $label_position = ($row['label_position'] ?? 'above') === 'below' ? 'below' : 'above';
                        $show_tz = ($row['show_tz'] ?? '1') === '0' ? '0' : '1';
                        $user_time = ($row['user_time'] ?? '0') === '1' ? '1' : '0';
                        ?>
                        <tr>
                            <td><strong><?php echo esc_html($i); ?></strong></td>

                            <td>
                                <select name="mtc_type[<?php echo esc_attr($i); ?>]">
                                    <option value="analog"  <?php selected($type, 'analog');  ?>>Analog</option>
                                    <option value="digital" <?php selected($type, 'digital'); ?>>Digital</option>
                                </select>
                            </td>

                            <td>
                                <input type="text" name="mtc_label[<?php echo esc_attr($i); ?>]"
                                       value="<?php echo esc_attr($label); ?>" style="width: 100%;" />
                            </td>

                            <td>
                                <input type="text" name="mtc_tz[<?php echo esc_attr($i); ?>]"
                                       value="<?php echo esc_attr($tz); ?>" placeholder="Europe/Athens"
                                       style="width: 100%; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono', 'Courier New', monospace;" />
                                <div class="description">Ignored when “Visitor local time” is enabled.</div>
                            </td>

                            <td>
                                <select name="mtc_label_position[<?php echo esc_attr($i); ?>]">
                                    <option value="above" <?php selected($label_position, 'above'); ?>>Above</option>
                                    <option value="below" <?php selected($label_position, 'below'); ?>>Below</option>
                                </select>
                            </td>

                            <td style="text-align:center;">
                                <label>
                                    <input type="checkbox"
                                           name="mtc_show_tz[<?php echo esc_attr($i); ?>]"
                                           value="1" <?php checked($show_tz, '1'); ?> />
                                    Yes
                                </label>
                            </td>

                            <td style="text-align:center;">
                                <label>
                                    <input type="checkbox"
                                           name="mtc_user_time[<?php echo esc_attr($i); ?>]"
                                           value="1" <?php checked($user_time, '1'); ?> />
                                    Yes
                                </label>
                            </td>

                            <td>
                                <code>[mtc_clock id="<?php echo esc_html($i); ?>"]</code><br/>
                                <small>
                                    Override: <code>type="digital"</code>, <code>label_position="below"</code>,
                                    <code>show_tz="0"</code>, <code>user_time="1"</code>
                                </small>
                            </td>
                        </tr>
                    <?php endfor; ?>
                    </tbody>
                </table>

                <?php submit_button('Save Changes'); ?>
            </form>
        </div>
        <?php
    }

    /** ---------- Rendering helpers ---------- */

    private function effective_type(string $configured_type, string $override): string {
        // override: config|analog|digital
        $override = strtolower(trim($override));
        if ($override === 'analog') return 'analog';
        if ($override === 'digital') return 'digital';
        return ($configured_type === 'digital') ? 'digital' : 'analog';
    }

    private function effective_label_position(string $configured, string $override): string {
        // override: config|above|below
        $override = strtolower(trim($override));
        if ($override === 'below') return 'below';
        if ($override === 'above') return 'above';
        return ($configured === 'below') ? 'below' : 'above';
    }

    private function effective_bool01(string $configured01, string $override): string {
        // override: config|1|0
        $override = strtolower(trim($override));
        if ($override === '1' || $override === '0') return $override;
        return ($configured01 === '0') ? '0' : '1';
    }

    private function build_client_config(array $atts, array $clock): array {
        // Digital shortcode options (still override-only; could be moved to admin later if desired)
        $format = ($atts['format'] === '12') ? '12' : '24';
        $seconds = ($atts['seconds'] === '0') ? '0' : '1';
        $show_date = ($atts['show_date'] === '1') ? '1' : '0';

        // Analog shortcode options
        $size = intval($atts['size']);
        if ($size < 80) $size = 80;
        if ($size > 600) $size = 600;
        $smooth = ($atts['smooth'] === '1') ? '1' : '0';

        // Per-clock defaults with shortcode override
        $label_position = $this->effective_label_position(
            (string)($clock['label_position'] ?? 'above'),
            (string)$atts['label_position']
        );
        $show_tz = $this->effective_bool01(
            (string)($clock['show_tz'] ?? '1'),
            (string)$atts['show_tz']
        );
        $user_time = $this->effective_bool01(
            (string)($clock['user_time'] ?? '0'),
            (string)$atts['user_time']
        );

        return [
            'format'         => $format,
            'seconds'        => $seconds,
            'show_date'      => $show_date,
            'size'           => (string)$size,
            'smooth'         => $smooth,

            'label_position' => $label_position,
            'show_tz'        => $show_tz,
            'user_time'      => $user_time,
        ];
    }

    private function render_clock(array $clock, array $atts): string {
        $label = $clock['label'] ?? '';
        $tz    = $clock['tz'] ?? '';
        $configured_type = ($clock['type'] ?? 'analog') === 'digital' ? 'digital' : 'analog';
        $kind = $this->effective_type($configured_type, (string)$atts['type']);

        $cfg = $this->build_client_config($atts, $clock);

        // For visitor local time, use tz sentinel "local"
        if ($cfg['user_time'] === '1') {
            $tz = 'local';
        }

        $label_position = $cfg['label_position'];
        $size = intval($cfg['size']);

        $wrap_classes = 'mtc-wrap mtc-label-' . $label_position;

        ob_start();
        ?>
        <div class="<?php echo esc_attr($wrap_classes); ?>" data-mtc="<?php echo esc_attr(wp_json_encode($cfg)); ?>">
            <div class="mtc-clock" data-kind="<?php echo esc_attr($kind); ?>" data-tz="<?php echo esc_attr($tz); ?>">

                <?php if ($label !== '' && $label_position === 'above'): ?>
                    <div class="mtc-label"><?php echo esc_html($label); ?></div>
                <?php endif; ?>

                <?php if ($kind === 'analog'): ?>
                    <canvas class="mtc-canvas"
                            width="<?php echo esc_attr($size); ?>"
                            height="<?php echo esc_attr($size); ?>"
                            aria-label="<?php echo esc_attr(($label ?: 'Clock') . ' analog clock'); ?>"></canvas>
                <?php else: ?>
                    <div class="mtc-digital">
                        <div class="mtc-time" aria-live="polite">--:--</div>
                        <div class="mtc-date" hidden></div>
                    </div>
                <?php endif; ?>

                <?php if ($label !== '' && $label_position === 'below'): ?>
                    <div class="mtc-label"><?php echo esc_html($label); ?></div>
                <?php endif; ?>

                <div class="mtc-tz"><?php echo esc_html($tz); ?></div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /** ---------- Shortcode ---------- */

    public function shortcode_single_clock($atts): string {
        $atts = shortcode_atts([
            'id'             => '1',

            // overrides
            'type'           => 'config', // config|analog|digital
            'label_position' => 'config', // config|above|below
            'show_tz'        => 'config', // config|1|0
            'user_time'      => 'config', // config|1|0

            // digital
            'format'         => '24',     // 24|12
            'seconds'        => '1',      // 1|0
            'show_date'      => '0',      // 1|0

            // analog
            'size'           => '160',    // 80..600
            'smooth'         => '0',      // 1|0
        ], $atts, 'mtc_clock');

        $id = intval($atts['id']);
        if ($id < 1) $id = 1;

        $clocks = $this->get_clocks();
        if (!isset($clocks[$id])) {
            return '<div class="mtc-error">Clock not found (check id).</div>';
        }

        $this->enqueue_frontend_assets();
        return $this->render_clock($clocks[$id], $atts);
    }
}

new MTC_Timezone_Clocks();