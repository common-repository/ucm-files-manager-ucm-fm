<?php
/*
Plugin Name: UCM Files Manager (UCM FM)
Plugin URI: https://www.phprockets.com/ucm-wordpress-files-manager/
Description: UCM Files Manager (UCM FM) allows you to manage your hosting files right within your Wordpress backend admin panel, you can create, edit, move, remove, upload file(s), folder at your hosting space. The online Cloud Storage are also supported, you could manage your cloud assets directly from your backend panel. Most efficient way to manage your file without login to hosting cpanel.
Author: PhpRockets Team
Version: 1.1
Author URI: https://www.phprockets.com
Network: True
Text Domain: ucm-wp-files-manager-addon
*/
include_once ABSPATH .'wp-admin/includes/plugin.php';
require_once __DIR__ .'/includes/classes/PhpRockets_UCM_FileManager_AddOn.php';

$ucm_lite_alias = 'ultimate-media-on-the-cloud-lite/ultimate-media-on-the-cloud.php';
$ucm_pro_alias = 'ultimate-media-on-the-cloud/ultimate-media-on-the-cloud.php';
if (!defined('UCM_PLUGIN_TYPE')) {
    if (is_plugin_active($ucm_lite_alias)) {
        define('UCM_PLUGIN_TYPE', 'LITE');
    } else if (is_plugin_active($ucm_pro_alias)) {
        define('UCM_PLUGIN_TYPE', 'PRO');
    } else {
        define('UCM_PLUGIN_TYPE', 'UNACTIVATED');
    }
}

/* Minimum version requirement for this add-on */
$requirements = [
    'LITE'  => '1.50.5',
    'PRO'   => '1.50.5'
];

/* Requirement check */
if (UCM_PLUGIN_TYPE !== 'UNACTIVATED') {

    /* Perform requirements check before executing the add-on */
    $pass_requirement = true;
    switch (UCM_PLUGIN_TYPE) {
        case 'LITE':
            $plugin_data = get_plugin_data(dirname(plugin_dir_path(__FILE__)) .'/'. $ucm_lite_alias);
            if (!version_compare($plugin_data['Version'], $requirements['LITE'], '>=')) {
                $pass_requirement = false;
                $required_version = $requirements['LITE'];
            }
            break;
        case 'PRO':
            $plugin_data = get_plugin_data(dirname(plugin_dir_path(__FILE__)) .'/'. $ucm_pro_alias);
            if (!version_compare($plugin_data['Version'], $requirements['PRO'], '>=')) {
                $pass_requirement = false;
                $required_version = $requirements['PRO'];
            }
            break;
    }

    /* If the requirements are not passed */
    if (!$pass_requirement) {
        $path = plugin_basename( __FILE__ );
        add_action("after_plugin_row_{$path}", static function($plugin_file, $plugin_data, $status ) use ($required_version) {
            _e('<tr class="active"><td>&nbsp;</td><td colspan="2">
            <div class="update-message notice inline notice-warning notice-alt"><p>'. __('The Add-On requires <b>Ultimate Media On The Cloud</b> version <b>'. $required_version .'</b> or later. Please update the main plugin in order to use the AddOn!', 'ultimate-media-on-the-cloud') .'</p></div>
            </td></tr>', 'ultimate-media-on-the-cloud');
        }, 10, 3 );
        define('UCM_FM_LOADED', false);
    } else {
        define('UCM_FM_LOADED', true);
        define('UCM_FM_DIR', UCM_PLUGIN_TYPE === 'PRO' ? dirname(dirname(plugin_dir_path(__FILE__)) .'/'. $ucm_pro_alias) : dirname(dirname(plugin_dir_path(__FILE__)) .'/'. $ucm_lite_alias));
        require_once 'includes/addon.init.php';
    }
} else {
    $required_version = $requirements['LITE'];
    $path = plugin_basename( __FILE__ );
    add_action("after_plugin_row_{$path}", static function($plugin_file, $plugin_data, $status ) use ($required_version) {
        _e('<tr class="active"><td>&nbsp;</td><td colspan="2">
            <div class="update-message notice inline notice-warning notice-alt"><p>'. __('The Add-On requires <b>Ultimate Media On The Cloud</b> version <b>'. $required_version .'</b> or later. Please install the main plugin in order to use the AddOn!', 'ultimate-media-on-the-cloud') .'</p></div>
            </td></tr>', 'ultimate-media-on-the-cloud');
    }, 10, 3 );
    define('UCM_FM_LOADED', false);
}
register_activation_hook(__FILE__, [PhpRockets_UCM_FileManager_AddOn::class, 'whileActivation']);
register_uninstall_hook(__FILE__, [PhpRockets_UCM_FileManager_AddOn::class, 'whileUnInstall']);