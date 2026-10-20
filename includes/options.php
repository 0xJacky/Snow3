<?php
/** Option definitions shared by the plugin runtime and uninstall.php. */

if (!defined('ABSPATH')) {
    exit;
}

/** One definition for defaults, validation, and the settings form. */
function snow3_option_schema() {
    return array(
        'load'    => array('default' => 0, 'min' => 0, 'max' => 1, 'integer' => true, 'enum' => array(0, 1)),
        'style'   => array('default' => 1, 'min' => 1, 'max' => 2, 'integer' => true, 'enum' => array(1, 2)),
        'way'     => array('default' => 0, 'min' => -1000, 'max' => 1000),
        'speed'   => array('default' => 0.5, 'min' => 0, 'max' => 1000),
        'front'   => array('default' => 0, 'min' => -1000, 'max' => 1000),
        'rotateX' => array('default' => 45, 'min' => -360, 'max' => 360),
        'rotateY' => array('default' => 360, 'min' => -360, 'max' => 360),
        'gravity' => array('default' => 0, 'min' => -1, 'max' => 1),
        'num'     => array('default' => 500, 'min' => 0, 'max' => 2000, 'integer' => true),
    );
}

/** Every option Snow3 may have stored, including the retired 1.x jQuery switch. */
function snow3_stored_option_names() {
    $names = array('snow3_jquery');
    foreach (array_keys(snow3_option_schema()) as $key) {
        $names[] = 'snow3_' . $key;
    }
    return $names;
}
