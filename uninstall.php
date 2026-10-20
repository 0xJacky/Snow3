<?php
/** Remove configuration only when WordPress explicitly uninstalls Snow3. */
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

require_once __DIR__ . '/includes/options.php';

$snow3_delete_options = function () {
    foreach (snow3_stored_option_names() as $name) {
        delete_option($name);
    }
};

// Options live in each site's own table, so a network uninstall must visit every site.
if (is_multisite()) {
    foreach (get_sites(array('fields' => 'ids', 'number' => 0)) as $site_id) {
        switch_to_blog($site_id);
        $snow3_delete_options();
        restore_current_blog();
    }
} else {
    $snow3_delete_options();
}
