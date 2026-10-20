<?php
/** Run with PHP 7.4+: php tests/plugin.php [manual|auto] */
// Command line only: over HTTP this file would define WordPress stubs and print results.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
define('ABSPATH', __DIR__ . '/');
$GLOBALS['options'] = array();
$GLOBALS['hooks'] = array();
$GLOBALS['settings'] = array();
$GLOBALS['scripts'] = array();
$GLOBALS['styles'] = array();
$GLOBALS['registered_styles'] = array();
$GLOBALS['deleted'] = array();
$GLOBALS['allowed'] = true;
$GLOBALS['checks'] = 0;
$GLOBALS['multisite'] = false;
$GLOBALS['blog'] = 1;
$GLOBALS['textdomain'] = null;

function add_action($name, $callback, $priority = 10) { $GLOBALS['hooks'][$name][] = array($callback, $priority); }
function get_option($name, $default = false) { return array_key_exists($name, $GLOBALS['options']) ? $GLOBALS['options'][$name] : $default; }
function register_setting($group, $name, $args) { $GLOBALS['settings'][$name] = $args; }
function add_menu_page($title, $menu, $capability, $slug, $callback, $icon) { $GLOBALS['page'] = compact('capability', 'slug', 'callback', 'icon'); }
function settings_errors() { echo '<div class="notice settings-error"></div>'; }
function current_user_can($capability) { return $capability === 'manage_options' && $GLOBALS['allowed']; }
function wp_style_is($handle, $list) { return $list === 'registered' && isset($GLOBALS['registered_styles'][$handle]); }
function wp_register_style($handle, $src, $deps, $version) { $GLOBALS['registered_styles'][$handle] = array('src' => $src, 'version' => $version, 'inline' => array()); }
function wp_add_inline_style($handle, $css) { $GLOBALS['registered_styles'][$handle]['inline'][] = $css; }
function wp_enqueue_style($handle) { assert_snow(isset($GLOBALS['registered_styles'][$handle]), 'Enqueue a registered style.'); $GLOBALS['styles'][$handle] = $GLOBALS['registered_styles'][$handle]; }
function wp_enqueue_script($handle, $url, $deps, $version, $footer) { $GLOBALS['scripts'][$handle] = compact('url', 'deps', 'version', 'footer'); }
function plugins_url($path, $file) { return 'https://example.test/plugins/snow3/' . $path . '?quote="&tag=<x>'; }
function esc_attr($value) { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }
function esc_html($value) { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }
function wp_json_encode($value) { return json_encode($value); }
function settings_fields($group) { echo '<input name="option_page" value="' . esc_attr($group) . '">'; }
function checked($value, $expected, $echo = true) { $result = (string) $value === (string) $expected ? 'checked="checked"' : ''; if ($echo) { echo $result; } return $result; }
function submit_button() { echo '<button type="submit">Save</button>'; }
function delete_option($name) { $GLOBALS['deleted'][] = $GLOBALS['blog'] . ':' . $name; }
function __($text, $domain) { assert_snow($domain === 'snow3', 'Text domain.'); return $text; }
function esc_html_e($text, $domain) { echo esc_html(__($text, $domain)); }
function wp_kses($value, $allowed) { return strip_tags($value, '<' . implode('><', array_keys($allowed)) . '>'); }
function load_plugin_textdomain($domain, $deprecated, $path) { $GLOBALS['textdomain'] = compact('domain', 'path'); }
function plugin_basename($file) { return 'Snow3/' . basename($file); }
function is_multisite() { return $GLOBALS['multisite']; }
function get_sites($args) { assert_snow($args['fields'] === 'ids' && $args['number'] === 0, 'Query every site id.'); return array(1, 2, 3); }
function switch_to_blog($id) { $GLOBALS['blog'] = $id; }
function restore_current_blog() { $GLOBALS['blog'] = 1; }
function assert_snow($condition, $message) { $GLOBALS['checks']++; if (!$condition) { throw new RuntimeException($message); } }
function capture_snow($callback) { ob_start(); $callback(); return ob_get_clean(); }

require dirname(__DIR__) . '/snow3.php';

assert_snow(isset($GLOBALS['hooks']['admin_init']), 'Settings must register on admin_init independently of admin_menu.');
assert_snow($GLOBALS['hooks']['admin_init'][0][0] === 'register_snow3_settings', 'Settings hook callback.');
assert_snow($GLOBALS['hooks']['wp_footer'][0] === array('snow3_render_automatic', 5), 'Container must precede footer scripts.');
assert_snow(!function_exists('snow3_remove'), 'Deactivation must retain options.');
assert_snow(SNOW3_VERSION === '2.0.0', 'Single asset version constant.');
assert_snow(strpos(file_get_contents(dirname(__DIR__) . '/snow3.php'), 'Version: ' . SNOW3_VERSION) !== false, 'Header version matches asset version.');
snow3_load_textdomain();
assert_snow($GLOBALS['textdomain'] === array('domain' => 'snow3', 'path' => 'Snow3/languages'), 'Load translations from the plugin languages folder.');
register_snow3_settings();
assert_snow(count($GLOBALS['settings']) === 9, 'Register every live option.');
assert_snow(!isset($GLOBALS['settings']['snow3_jquery']), 'Retired jQuery switch is no longer a setting.');
snow3_settings();
assert_snow($GLOBALS['page']['capability'] === 'manage_options', 'Settings capability.');
assert_snow($GLOBALS['page']['slug'] === 'snow3', 'Top-level settings page slug.');

foreach (snow3_option_schema() as $key => $rule) {
    $sanitize = $GLOBALS['settings']['snow3_' . $key]['sanitize_callback'];
    foreach (array(array(), new stdClass(), null, true, '', 'hello', '1e999', INF, -INF, NAN, '"><script>alert(1)</script>') as $bad) {
        assert_snow($sanitize($bad) === $rule['default'], $key . ': reject malformed or non-finite input.');
        $GLOBALS['options']['snow3_' . $key] = $bad;
        assert_snow(snow3_get_option($key) === $rule['default'], $key . ': validate stored values too.');
    }
    unset($GLOBALS['options']['snow3_' . $key]);
}
assert_snow(snow3_sanitize_option('style', 1.5) === 1, 'Reject fractional style.');
assert_snow(snow3_sanitize_option('load', -1) === 0, 'Reject unsupported mode.');
assert_snow(snow3_sanitize_option('num', -100) === 0, 'Clamp negative particle counts.');
assert_snow(snow3_sanitize_option('num', 1e100) === 2000, 'Clamp large finite particle counts before integer conversion.');
assert_snow(snow3_sanitize_option('num', '12.7') === 13, 'Integer particle counts.');
foreach (array('speed' => array(0, 1000), 'way' => array(-1000, 1000), 'front' => array(-1000, 1000), 'rotateX' => array(-360, 360), 'rotateY' => array(-360, 360), 'gravity' => array(-1, 1)) as $key => $bounds) {
    assert_snow(snow3_sanitize_option($key, -99999) == $bounds[0], $key . ': lower bound.');
    assert_snow(snow3_sanitize_option($key, 99999) == $bounds[1], $key . ': upper bound.');
}
assert_snow(snow3_sanitize_option('gravity', '0.25') === 0.25, 'Keep decimal settings.');
assert_snow(snow3_get_option('num') === 500, 'Legacy default count.');
assert_snow(snow3_get_option('speed') === 0.5, 'Default snowfall speed is 0.5.');

$GLOBALS['allowed'] = false;
assert_snow(capture_snow('snow3_settings_page') === '', 'No settings content without capability.');
$GLOBALS['allowed'] = true;
$form = capture_snow('snow3_settings_page');
assert_snow(strpos($form, 'action="options.php"') !== false, 'Use WordPress Settings API endpoint.');
$heading = strpos($form, '</h1>'); $anchor = strpos($form, 'class="wp-header-end"'); $notice = strpos($form, 'settings-error');
assert_snow($heading < $anchor && $anchor < $notice && $notice < strpos($form, '<form'), 'Saved notice renders in place after the heading anchor.');
assert_snow(strpos($form, 'aria-describedby="snow3-num-help"') !== false, 'Accessible numeric help.');
assert_snow(strpos($form, 'for="snow3-num"') !== false, 'Accessible numeric label.');
assert_snow(strpos($form, 'snow3_jquery') === false, 'No hidden field for the retired jQuery switch.');
assert_snow(strpos($form, '默认 500。范围 0 至 2000。') !== false, 'Help text ranges come from the schema.');
assert_snow(strpos($form, '<code>snow3();</code>') !== false, 'Manual-mode help keeps code markup.');

$GLOBALS['options']['snow3_num'] = 0;
foreach (array(0, 1) as $load) {
    $GLOBALS['options']['snow3_load'] = $load;
    snow3_enqueue_automatic();
    add_snow_style();
    assert_snow(capture_snow('snow3_render_automatic') === '' && capture_snow('snow3') === '', 'Zero snowflakes render nothing.');
}
assert_snow(count($GLOBALS['scripts']) === 0 && count($GLOBALS['styles']) === 0, 'Zero snowflakes enqueue nothing.');
unset($GLOBALS['options']['snow3_num'], $GLOBALS['options']['snow3_load']);

$mode = isset($argv[1]) ? $argv[1] : 'manual';
assert_snow(in_array($mode, array('manual', 'auto'), true), 'Valid test mode.');
$GLOBALS['options']['snow3_load'] = $mode === 'manual' ? 1 : 0;
if ($mode === 'manual') {
    assert_snow(capture_snow('snow3_render_automatic') === '', 'Manual mode disables automatic output.');
    snow3_enqueue_automatic();
    assert_snow(count($GLOBALS['scripts']) === 0, 'Manual mode does not auto-enqueue.');
    $output = capture_snow('snow3');
} else {
    assert_snow(capture_snow('snow3') === '', 'Manual shorthand remains inactive in automatic mode.');
    snow3_enqueue_automatic();
    $output = capture_snow('snow3_render_automatic');
}
assert_snow(substr_count($output, 'data-snow3=') === 1, 'Exactly one declarative container.');
assert_snow(strpos($output, '<script') === false, 'No inline executable code.');
assert_snow(strpos($output, '<x>') === false, 'JSON attribute escapes HTML metacharacters.');
assert_snow(strpos($output, '&quot;') !== false, 'JSON attribute escapes double quotes.');
preg_match('/data-snow3="([^"]+)"/', $output, $match);
$config = json_decode(html_entity_decode($match[1], ENT_QUOTES, 'UTF-8'), true);
assert_snow(is_array($config) && count($config) === 9, 'Valid JSON with only runtime settings.');
assert_snow($config['num'] === 500 && $config['style'] === 1, 'JSON numeric types.');
assert_snow($config['speed'] === 0.5, 'Runtime JSON uses the slow default.');
assert_snow(strpos($config['texture'], 'img/Snow1.png') !== false, 'Validated style selects bundled texture.');
assert_snow(capture_snow('add_snow_script') === '', 'Direct calls do not duplicate output.');
$GLOBALS['options']['snow3_load'] = 0;
assert_snow(capture_snow('snow3_render_automatic') === '', 'Automatic footer does not duplicate manual output.');
$GLOBALS['options']['snow3_load'] = 1;
assert_snow(capture_snow('snow3') === '', 'Manual shorthand does not duplicate output.');
assert_snow(count($GLOBALS['scripts']) === 1 && isset($GLOBALS['scripts']['SnowJS']), 'Only native runtime enqueued.');
assert_snow($GLOBALS['scripts']['SnowJS']['deps'] === array(), 'No jQuery or Three dependency.');
assert_snow($GLOBALS['scripts']['SnowJS']['footer'] === array('in_footer' => true, 'strategy' => 'defer'), 'Deferred footer script.');
assert_snow(strpos($GLOBALS['scripts']['SnowJS']['url'], 'js/snow3.js') !== false, 'Readable runtime path.');
assert_snow(count($GLOBALS['styles']) === 1, 'One stylesheet handle.');
assert_snow($GLOBALS['styles']['SnowCSS']['src'] === false, 'No render-blocking stylesheet request.');
assert_snow($GLOBALS['styles']['SnowCSS']['inline'] === array(file_get_contents(dirname(__DIR__) . '/css/Snow.css')), 'Inline CSS added once from the stylesheet source.');
assert_snow(count($GLOBALS['deleted']) === 0, 'Normal runtime preserves settings.');
define('WP_UNINSTALL_PLUGIN', true);
require dirname(__DIR__) . '/uninstall.php';
assert_snow(count($GLOBALS['deleted']) === 10, 'Uninstall deletes all ten legacy options.');
assert_snow(count(array_unique($GLOBALS['deleted'])) === 10, 'Uninstall deletes each option once.');
assert_snow(in_array('1:snow3_jquery', $GLOBALS['deleted'], true), 'Uninstall removes the retired jQuery switch.');
$GLOBALS['deleted'] = array();
$GLOBALS['multisite'] = true;
require dirname(__DIR__) . '/uninstall.php';
assert_snow(count(array_unique($GLOBALS['deleted'])) === 30, 'Multisite uninstall deletes every option on every site.');
assert_snow($GLOBALS['blog'] === 1, 'Multisite uninstall restores the current site.');
echo 'PASS ' . $mode . ': ' . $GLOBALS['checks'] . " checks\n";
