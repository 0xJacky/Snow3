<?php
/**
 * Plugin Name: Snow3
 * Plugin URI: https://github.com/0xJacky/Snow3
 * Description: 无需 jQuery 或 Three.js 的 3D 飘雪效果，保留原版设置和主题调用接口。
 * Version: 2.0.0
 * Author: Jacky
 * Author URI: https://jackyu.cn/
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: snow3
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) {
    exit;
}

/** Cache-busting version for every enqueued asset; keep in sync with the header above. */
define('SNOW3_VERSION', '2.0.0');

require_once __DIR__ . '/includes/options.php';

/** Reject non-numeric/non-finite input; clamp numeric values to supported bounds. */
function snow3_sanitize_option($key, $value) {
    $schema = snow3_option_schema();
    if (!isset($schema[$key])) {
        return null;
    }
    $rule = $schema[$key];
    if ((!is_string($value) && !is_int($value) && !is_float($value)) || !is_numeric($value)) {
        return $rule['default'];
    }
    $number = (float) $value;
    if (!is_finite($number)) {
        return $rule['default'];
    }
    if (isset($rule['enum']) && !in_array($number, $rule['enum'], false)) {
        return $rule['default'];
    }
    $number = max($rule['min'], min($rule['max'], $number));
    return !empty($rule['integer']) ? (int) round($number) : $number;
}

/** Validate reads too, so old or directly edited options never enter markup unchecked. */
function snow3_get_option($key) {
    $schema = snow3_option_schema();
    if (!isset($schema[$key])) {
        return null;
    }
    return snow3_sanitize_option($key, get_option('snow3_' . $key, $schema[$key]['default']));
}

/** A count of 0 turns snow off, so no assets or container are emitted at all. */
function snow3_is_enabled() {
    return snow3_get_option('num') > 0;
}

add_action('init', 'snow3_load_textdomain');
add_action('admin_init', 'register_snow3_settings');
add_action('admin_menu', 'snow3_settings');
add_action('wp_enqueue_scripts', 'snow3_enqueue_automatic');
// Render before WordPress prints footer scripts, including when invoked late by a template.
add_action('wp_footer', 'snow3_render_automatic', 5);

function snow3_load_textdomain() {
    load_plugin_textdomain('snow3', false, dirname(plugin_basename(__FILE__)) . '/languages');
}

function register_snow3_settings() {
    foreach (snow3_option_schema() as $key => $rule) {
        register_setting('snow3_options', 'snow3_' . $key, array(
            'type' => !empty($rule['integer']) ? 'integer' : 'number',
            'default' => $rule['default'],
            'sanitize_callback' => function ($value) use ($key) {
                return snow3_sanitize_option($key, $value);
            },
        ));
    }
}

/**
 * Top-level menu, as in 1.x. Pages under Settings get "Settings saved" printed by
 * options-head.php above the title, which common.js then moves below it, so the
 * notice visibly jumps; here the page prints it in its final place instead.
 */
function snow3_settings() {
    add_menu_page(__('Snow3 飘雪设置', 'snow3'), 'Snow3', 'manage_options', 'snow3', 'snow3_settings_page', 'dashicons-cloud');
}

function snow3_settings_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    $schema = snow3_option_schema();
    $fields = array(
        'way' => array(__('水平速度', 'snow3'), __('正值向右、负值向左。', 'snow3')),
        'speed' => array(__('下降速度', 'snow3'), __('数值越大，下落越快。', 'snow3')),
        'front' => array(__('纵深速度', 'snow3'), __('正值朝向屏幕、负值远离屏幕。', 'snow3')),
        'rotateX' => array(__('X 轴旋转', 'snow3'), __('初始速度绕 X 轴的随机旋转范围，单位为度。', 'snow3')),
        'rotateY' => array(__('Y 轴旋转', 'snow3'), __('初始速度绕 Y 轴的随机旋转范围，单位为度。', 'snow3')),
        'gravity' => array(__('重力加速度', 'snow3'), __('正值向下、负值向上；建议绝对值小于 0.5。', 'snow3')),
        'num' => array(__('雪花数量', 'snow3'), __('0 表示关闭飘雪，较少的雪花更省电。', 'snow3')),
    );
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Snow3 飘雪设置', 'snow3'); ?></h1>
        <hr class="wp-header-end">
        <?php settings_errors(); ?>
        <p><?php esc_html_e('使用原生 Canvas 绘制，无需加载 jQuery 或 Three.js。旧版 jQuery 选项会保留，但不再影响运行。', 'snow3'); ?></p>
        <form method="post" action="options.php">
            <?php settings_fields('snow3_options'); ?>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><?php esc_html_e('加载方式', 'snow3'); ?></th>
                    <td><fieldset><legend class="screen-reader-text"><?php esc_html_e('加载方式', 'snow3'); ?></legend>
                        <label for="snow3-load-auto"><input id="snow3-load-auto" type="radio" name="snow3_load" value="0" <?php checked(snow3_get_option('load'), 0); ?>> <?php esc_html_e('自动加载', 'snow3'); ?></label><br>
                        <label for="snow3-load-manual"><input id="snow3-load-manual" type="radio" name="snow3_load" value="1" <?php checked(snow3_get_option('load'), 1); ?>> <?php esc_html_e('手动加载', 'snow3'); ?></label>
                        <p class="description"><?php
                            echo wp_kses(
                                __('手动模式：在主题调用 <code>get_header()</code> 前调用 <code>add_snow_style();</code>，在 <code>wp_footer()</code> 前调用 <code>snow3();</code>。也支持原来的 <code>add_snow_script();</code> 接口；同一页面重复调用只会创建一个飘雪层。', 'snow3'),
                                array('code' => array())
                            );
                        ?></p>
                    </fieldset></td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('雪花样式', 'snow3'); ?></th>
                    <td><fieldset><legend class="screen-reader-text"><?php esc_html_e('雪花样式', 'snow3'); ?></legend>
                        <label for="snow3-style-round"><input id="snow3-style-round" type="radio" name="snow3_style" value="1" <?php checked(snow3_get_option('style'), 1); ?>> <?php esc_html_e('圆形 3D 风格', 'snow3'); ?></label><br>
                        <label for="snow3-style-flake"><input id="snow3-style-flake" type="radio" name="snow3_style" value="2" <?php checked(snow3_get_option('style'), 2); ?>> <?php esc_html_e('不规则 3D 风格', 'snow3'); ?></label>
                    </fieldset></td>
                </tr>
                <?php foreach ($fields as $key => $field) : ?>
                    <?php
                    $rule = $schema[$key];
                    /* translators: 1: default value, 2: minimum, 3: maximum. */
                    $range = sprintf(__('默认 %1$s。范围 %2$s 至 %3$s。', 'snow3'), $rule['default'], $rule['min'], $rule['max']);
                    ?>
                    <tr>
                        <th scope="row"><label for="snow3-<?php echo esc_attr($key); ?>"><?php echo esc_html($field[0]); ?></label></th>
                        <td>
                            <input class="small-text" id="snow3-<?php echo esc_attr($key); ?>" type="number" name="snow3_<?php echo esc_attr($key); ?>" value="<?php echo esc_attr(snow3_get_option($key)); ?>" min="<?php echo esc_attr($rule['min']); ?>" max="<?php echo esc_attr($rule['max']); ?>" step="<?php echo !empty($rule['integer']) ? '1' : 'any'; ?>" aria-describedby="snow3-<?php echo esc_attr($key); ?>-help">
                            <p class="description" id="snow3-<?php echo esc_attr($key); ?>-help"><?php echo esc_html($field[1] . $range); ?></p>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
            <p><?php esc_html_e('动画会遵循访客的“减少动态效果”偏好，并在页面不可见时暂停。', 'snow3'); ?></p>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

/** Legacy public template API: enqueue both assets once through WordPress. */
function add_snow_style() {
    if (!snow3_is_enabled()) {
        return;
    }
    // A few hundred bytes of inline CSS beat a render-blocking stylesheet request.
    if (!wp_style_is('SnowCSS', 'registered')) {
        wp_register_style('SnowCSS', false, array(), SNOW3_VERSION);
        wp_add_inline_style('SnowCSS', (string) file_get_contents(__DIR__ . '/css/Snow.css'));
    }
    wp_enqueue_style('SnowCSS');
    // WordPress < 6.3 reads the array as a truthy $in_footer and ignores the strategy.
    wp_enqueue_script('SnowJS', plugins_url('js/snow3.js', __FILE__), array(), SNOW3_VERSION, array('in_footer' => true, 'strategy' => 'defer'));
}

function snow3_enqueue_automatic() {
    if (snow3_get_option('load') === 0) {
        add_snow_style();
    }
}

function snow3_render_automatic() {
    if (snow3_get_option('load') === 0) {
        add_snow_script();
    }
}

/** Legacy public template API: a single declarative container, with no executable inline JS. */
function add_snow_script() {
    static $rendered = false;
    if ($rendered || !snow3_is_enabled()) {
        return;
    }
    $rendered = true;
    add_snow_style();
    $config = array();
    foreach (array_keys(snow3_option_schema()) as $key) {
        if ($key !== 'load') {
            $config[$key] = snow3_get_option($key);
        }
    }
    $config['texture'] = plugins_url('img/Snow' . $config['style'] . '.png', __FILE__);
    echo '<div class="Snow" data-snow3="' . esc_attr(wp_json_encode($config)) . '" aria-hidden="true"></div>';
}

/** Preserve manual-mode behavior for existing themes. */
function snow3() {
    if (snow3_get_option('load') === 1) {
        add_snow_script();
    }
}
