<?php
/**
 * Add-On for UCM - File Manager
 * Package: Ultimate Media On The Cloud
 * Plugin URI: https://www.phprockets.com/file-manager-addon-ucm
 * Date: 19-Aug-2019
 */

/* Add AddOn menu to the main plugin */
if (!isset($GLOBALS['ucm']['menu'])) {
    $GLOBALS['ucm']['menu'] = [];
}
include_once ABSPATH .'wp-includes/pluggable.php';
$user = wp_get_current_user();

$fm_addon = new PhpRockets_UCM_FileManager_AddOn();
if ($fm_addon->isUserCanAccess($user)) {
    $GLOBALS['ucm']['menu'][] = [
        'key' => 'file_manager',
        'page_title' => 'UCM Files Manager',
        'text' => 'UCM Files Manager',
        'slug' => '-file-manager',
        'handle' => [PhpRockets_UCM_FileManager_AddOn::class, 'index']
    ];

    $fm_addon->register();
}
