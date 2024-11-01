<?php
/* Init the cloud storage service libraries */
require dirname(dirname(__DIR__)) .'/vendor/php/vendor/autoload.php';
/* Init the file manager libraries */
require dirname(dirname(__DIR__)) .'/vendor/php/autoload.php';

use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;
use Google\Cloud\Storage\StorageClient;
use League\Flysystem\AwsS3v3\AwsS3Adapter;
use League\Flysystem\Filesystem;
use Superbalist\Flysystem\GoogleStorage\GoogleStorageAdapter;


/**
 * Add-On for UCM - File Manager
 * Package: Ultimate Media On The Cloud
 * Plugin URI: https://www.phprockets.com/file-manager-addon-ucm
 * Date: 19-Aug-2019
 */
class PhpRockets_UCM_FileManager_AddOn extends elFinderVolumeLocalFileSystem
{
    /* Plugin Data */
    private static $data = [];

    /**
     * AddOn Information
     **/
    public $ajax = [
        'url' => [
            '-client-connect' => 'clientConnect',
            '-reload-buckets' => 'reloadBuckets',
            '-fm-save-general' => 'saveSettings',
            '-fm-save-roles' => 'saveRoles',
            '-fm-save-ui' => 'saveUI',
            '-fm-save-file-types' => 'saveEditFileTypes',
            '-fm-save-image-editors' => 'saveImageEditors',
            '-fm-save-code-editors' => 'saveCodeEditors',
            '-fm-save-advanced' => 'saveAdvancedSettings',
            '-fm-update-file-acl' => 'updateFileAcl',
        ]
    ];

    /**
     * PhpRockets_UCM_FileManager_AddOn constructor.
     */
    public function __construct() {
        /* Set it's own data */
        self::$data = [
            'addon_dir' => dirname(plugin_dir_path(__DIR__)),
            'addon_url' => str_replace('includes/classes/', '', plugin_dir_url(__FILE__))
        ];

        self::$data['configs'] = include self::$data['addon_dir'] .'/includes/fm.php';
        parent::__construct();
    }

    /**
     * Init the object
     */
    public function register()
    {
        $this->registerAjaxUrlHook();
        add_filter('alter_register_ucm_asset', [__CLASS__, 'localizeFm']);
    }

    /**
     * Register ajax functions
     */
    public function registerAjaxUrlHook()
    {
        $ucm_configs = $this->loadUcmPluginConfigs();
        $ajax = [];
        foreach ($this->ajax['url'] as $url => $callback) {
            $ajax['wp_ajax_'. $ucm_configs['plugin_url_prefix'] . $url] = $callback;
        }

        /* Check if auto import media is set */
        $fm_import_media = get_option($ucm_configs['plugin_db_prefix'] .'fm_import_media', false);
        if ($fm_import_media) {
            $ajax['wp_ajax_'. $ucm_configs['plugin_url_prefix'] .'-import-media'] = 'importUploadMedias';
        }

        foreach ($ajax as $action => $callback) {
            add_action($action, [$this, $callback]);
        }
    }

    /**
     * @param $key
     * @return mixed|string
     */
    public static function getAddOnData($key)
    {
        return isset(self::$data[$key]) ? self::$data[$key] : '';
    }

    /**
     * Will load Ultimate Media On The Cloud plugin configurations
     */
    private function loadUcmPluginConfigs()
    {
        return include (defined('ULTIMATE_MEDIA_PLG_DIR') ? ULTIMATE_MEDIA_PLG_DIR : UCM_FM_DIR) .'/includes/requires/plugin.configs.php';
    }

    /**
     * @return false|string
     */
    public static function index()
    {
        /* Load required backend asset files */
        $ucm = new PhpRockets_UltimateMedia();
        $ucm_hook = new PhpRockets_UltimateMedia_Hooks();
        $ucm_hook::loadBackEndAssets();
        $ucm_hook::loadFooBox();

        $tab = $ucm::getQuery('ucm-tab');
        switch ($tab) {
            case 'settings':
                add_filter('ucm_fm_general_form', [__CLASS__, 'settingsForm']);
                add_filter('ucm_fm_roles_form', [__CLASS__, 'RolesForm']);
                add_filter('ucm_fm_ui_form', [__CLASS__, 'UIForm']);
                add_filter('ucm_fm_file_type_form', [__CLASS__, 'AllowFileTypesForm']);
                add_filter('ucm_fm_image_editors_form', [__CLASS__, 'ImageEditorsForm']);
                add_filter('ucm_fm_code_editors_form', [__CLASS__, 'CodeEditorsForm']);
                add_filter('ucm_fm_advanced_form', [__CLASS__, 'AdvancedSettingsForm']);

                return $ucm::renderTemplate(self::getAddOnData('addon_dir') . '/includes/tpl/settings', [
                    'self_addon' => new self
                ], true, false, 'external');
                break;
            default:
                /* Query available accounts from DB */
                $accounts = PhpRockets_Model_Accounts::query(['order_by' => 'id ASC']);
                $selected_account = $ucm::getQuery('account');
                $selected_bucket = $ucm::getQuery('bucket');

                $account = null;
                if ($accounts) {
                    if ($selected_account) {
                        $account = PhpRockets_Model_Accounts::query([
                            'conditions' => ['id' => $selected_account]
                        ]);
                    }

                    if (!$selected_bucket) {
                        $account_data = unserialize($account['value']);
                        $selected_bucket = $account_data['bucket'];
                    }
                }

                add_filter('ucm_fm_init', [__CLASS__, 'initFM'], 10, 2);
                return $ucm::renderTemplate(self::getAddOnData('addon_dir') . '/includes/tpl/index', [
                    'form' => self::accountSelectionForm($ucm, $accounts, $selected_account, $selected_bucket),
                    'accounts' => $accounts,
                    'account' => $account,
                    'selected_bucket' => $selected_bucket,
                    'self_addon' => new self
                ], true, false, 'external');
                break;
        }
    }

    /**
     * Set l10n translation
     */
    public static function localizeFm()
    {
        $self = new self();
        $ucm = new PhpRockets_UltimateMedia();
        $vars = [];

        $ucm_tab = $ucm::getQuery('ucm-tab');
        /** If standing page is main page and load extra assets */
        if (!$ucm_tab || ($ucm_tab&& $ucm_tab !== 'settings')) {
            add_filter('ucm_fm_style', [__CLASS__, 'styleLoadRequireTags']);
            add_filter('ucm_fm_require', [__CLASS__, 'jsLoadRequireScriptTag']);

            $vars['ucm_fm_assets_url'] = self::getAddOnData('addon_url') .'vendor/js';
            $vars['ucm_fm_plugin_url'] = self::getAddOnData('addon_url') .'vendor/js';
            $vars['ucm_fm_url'] = admin_url() . 'admin.php?page=' . $ucm::$configs->getMenuSlug('file_manager');

            $fm_image_edit_file_types = $ucm::ucmGetOption('fm_image_edit_file_types');
            $fm_custom_image_edit_file_types = $ucm::ucmGetOption('fm_custom_image_edit_file_types');
            if ($fm_custom_image_edit_file_types) {
                $fm_image_edit_file_types = $fm_image_edit_file_types ? $fm_image_edit_file_types .','. $fm_custom_image_edit_file_types : $fm_custom_image_edit_file_types;
            }
            $active_editors = [
                'fm_photopea_editor_enable' => $ucm::ucmGetOption('fm_photopea_editor_enable', false),
                'fm_tui_editor_enable' => $ucm::ucmGetOption('fm_tui_editor_enable', false),
                'fm_pixo_editor_enable' => $ucm::ucmGetOption('fm_pixo_editor_enable', false),
                'fm_pixo_app_key' => $ucm::ucmGetOption('fm_pixo_app_key', false),
                'fm_image_edit_file_types' => $fm_image_edit_file_types,
                'fm_aceeditor_editor_enable' => $ucm::ucmGetOption('fm_aceeditor_editor_enable', false),
                'fm_tinymce_editor_enable' => $ucm::ucmGetOption('fm_tinymce_editor_enable', false),
                'fm_textarea_editor_enable' => $ucm::ucmGetOption('fm_textarea_editor_enable', false),
                'fm_aceeditor_editor_file_types' => $ucm::ucmGetOption('fm_aceeditor_editor_file_types'),
                'fm_tinymce_file_types' => $ucm::ucmGetOption('fm_tinymce_file_types'),
                'fm_textarea_file_types' => $ucm::ucmGetOption('fm_textarea_file_types'),
            ];
            $vars['ucm_fm_settings_active_editors'] = json_encode($active_editors);
        }

        /* These are used for UCM FM Settings */
        $ucm_l10n = [
            '_missing_bucket' => __('Please choose a bucket.', 'ultimate-media-on-the-cloud'),
        ];
        wp_localize_script( 'phprockets-ucm-general', 'phprockets_fm_l10n', $ucm_l10n);
        wp_enqueue_script('phprockets-ucm-fm', $self::getAddOnData('addon_url') .'assets/js/ucm-fm'. $ucm::$configs->enqueue_assets_suffix .'.js', ['jquery']);

        $ajax_urls = $self->ajax['url'];
        /* Check if auto import media is set */
        $fm_import_media = $ucm::ucmGetOption('fm_import_media', false);
        if ($fm_import_media) {
            $ajax_urls['-import-media'] = 'importUploadMedias';
        }

        foreach ($ajax_urls as $url => $callback) {
            $vars[$callback] = admin_url('admin-ajax.php?action='. $ucm::$configs->plugin_url_prefix . $url);
        }
        wp_localize_script( 'phprockets-ucm-general', 'phprockets_ucm_fm', $vars);
    }

    /**
     * @param PhpRockets_UltimateMedia_Root $ucm
     * @return string|void
     */
    public static function styleLoadRequireTags($ucm)
    {
        wp_enqueue_style('fm-elfinder', self::getAddOnData('addon_url') .'vendor/css/elfinder'. $ucm::$configs->enqueue_assets_suffix  .'.css');
        wp_enqueue_style('fm-elfinder-theme', self::getAddOnData('addon_url') .'vendor/css/theme'. $ucm::$configs->enqueue_assets_suffix .'.css');
        $fm_theme = $ucm::ucmGetOption('fm_theme');
        if ($fm_theme && $fm_theme !== 'default') {
            $fm_theme_alias = self::_getThemesAlias();
            wp_enqueue_style('fm-elfinder-theme-child', self::getAddOnData('addon_url') .'vendor/'. $fm_theme_alias[$fm_theme]['path'] . $ucm::$configs->enqueue_assets_suffix .'.css');
        }
    }

    /**
     * @param PhpRockets_UltimateMedia_Root $ucm
     * @return string|void
     */
    public static function jsLoadRequireScriptTag($ucm)
    {
        print '<script data-main="'. self::getAddOnData('addon_url') .'vendor/main'. $ucm::$configs->enqueue_assets_suffix .'.js" src="'. self::getAddOnData('addon_url') .'vendor/require.min.js"></script>';
    }

    /**
     * Save FM General Settings
     * @throws Exception
     */
    public function saveSettings()
    {
        $ucm = new PhpRockets_UltimateMedia();
        if ($ucm::isPost()) {
            $data = $ucm::getPost('data');
            /* Perform validation inputs */
            $validation = new Validation();
            $validation->validation_rules([
                'dir_path' => 'required',
                'fm_locale' => 'required'
            ]);
            $validated = $validation->run($data);
            if ($validated === false) {
                $error_messages = $validation->get_errors_array();
                wp_send_json_error(['message' => implode('<br>', $error_messages)]);
                wp_die();
            }

            if (false === strpos($data['dir_path'], ABSPATH)) {
                wp_send_json_error(['message' => __('Invalid Dir Path. It can not be outside your web root!', 'ultimate-media-on-the-cloud')]);
                wp_die();
            }

            if (!file_exists($data['dir_path'])) {
                wp_send_json_error(['message' => __('Invalid Dir Path. Directory does not exist!', 'ultimate-media-on-the-cloud')]);
                wp_die();
            }

            $fm_allowed_file_types = $ucm::getPost('fm_allowed_file_types');
            /* Validate max file size input */
            $php_ini_max_size = (int)ini_get('post_max_size');
            if ($data['fm_max_file_size'] && !is_numeric($data['fm_max_file_size'])) {
                wp_send_json_error(['message' => __('Max file size must be a number', 'ultimate-media-on-the-cloud')]);
            } else if ($data['fm_max_file_size'] > $php_ini_max_size) {
                wp_send_json_error(['message' => __("Max file size must be less than {$php_ini_max_size}MB", 'ultimate-media-on-the-cloud')]);
            } else {
                $ucm::ucmUpdateOption('fm_dir_path', $data['dir_path']);
                $ucm::ucmUpdateOption('fm_locale', $data['fm_locale']);
                $ucm::ucmUpdateOption('fm_allowed_file_types', $fm_allowed_file_types);
                $ucm::ucmUpdateOption('fm_max_file_size', $data['fm_max_file_size']);
                $ucm::ucmUpdateOption('fm_trash', $data['fm_trash']);
                wp_send_json_success(['message' => __('Settings saved!', 'ultimate-media-on-the-cloud')]);
            }
        } else {
            wp_send_json_error(['message' => __('Invalid request', 'ultimate-media-on-the-cloud')]);
        }

        wp_die();
    }

    /**
     * Save FM Roles
     * @throws Exception
     */
    public function saveRoles()
    {
        $ucm = new PhpRockets_UltimateMedia();
        if ($ucm::isPost()) {
            $fm_allowed_roles = $ucm::getPost('fm_allowed_roles');
            $fm_restricted_users = $ucm::getPost('fm_restricted_users');
            if ($fm_allowed_roles) {
                $ucm::ucmUpdateOption('fm_allowed_roles', implode(',', $fm_allowed_roles));
            } else {
                $ucm::ucmUpdateOption('fm_allowed_roles', '');
            }
            $ucm::ucmUpdateOption('fm_restricted_users', $fm_restricted_users);
            wp_send_json_success(['message' => __('Roles Settings saved!', 'ultimate-media-on-the-cloud')]);
        } else {
            wp_send_json_error(['message' => __('Invalid request', 'ultimate-media-on-the-cloud')]);
        }

        wp_die();
    }

    /**
     * Save UI Settings
     * @throws Exception
     */
    public function saveUI()
    {
        $ucm = new PhpRockets_UltimateMedia();
        if ($ucm::isPost()) {
            $data = $ucm::getPost('data');
            /* Perform validation inputs */
            $validation = new Validation();
            $validation->validation_rules([
                'fm_theme' => 'required',
                'fm_file_layout' => 'required'
            ]);
            $validated = $validation->run($data);
            if ($validated === false) {
                $error_messages = $validation->get_errors_array();
                wp_send_json_error(['message' => implode('<br>', $error_messages)]);
                wp_die();
            }

            $ucm::ucmUpdateOption('fm_theme', $data['fm_theme']);
            $ucm::ucmUpdateOption('fm_file_layout', $data['fm_file_layout']);
            wp_send_json_success(['message' => __('Layout Settings saved!', 'ultimate-media-on-the-cloud')]);
        } else {
            wp_send_json_error(['message' => __('Invalid request', 'ultimate-media-on-the-cloud')]);
        }

        wp_die();
    }

    /**
     * Save the edit file types
     * @throws Exception
     */
    public static function saveEditFileTypes()
    {
        $ucm = new PhpRockets_UltimateMedia();
        if ($ucm::isPost()) {
            $data = [
                'fm_edit_file_types' => $ucm::getPost('fm_edit_file_types'),
                'fm_custom_edit_file_types' => $ucm::getPost('fm_custom_edit_file_types')
            ];

            $ucm::ucmUpdateOption('fm_edit_file_types', $data['fm_edit_file_types'] ? implode(',', $data['fm_edit_file_types']) : '');
            $ucm::ucmUpdateOption('fm_custom_edit_file_types', $data['fm_custom_edit_file_types']);
            wp_send_json_success(['message' => __('File Types Settings saved!', 'ultimate-media-on-the-cloud')]);
        } else {
            wp_send_json_error(['message' => __('Invalid request', 'ultimate-media-on-the-cloud')]);
        }

        wp_die();
    }

    /**
     * Save the image editors
     * @throws Exception
     */
    public static function saveImageEditors()
    {
        $ucm = new PhpRockets_UltimateMedia();
        if ($ucm::isPost()) {
            $data = $ucm::getPost('data');
            $fm_image_edit_file_types = $ucm::getPost('fm_image_edit_file_types');

            $ucm::ucmUpdateOption('fm_photopea_editor_enable', isset($data['fm_photopea_editor_enable']) && $data['fm_photopea_editor_enable'] === 'on' ? 1 : 0);
            $ucm::ucmUpdateOption('fm_tui_editor_enable', isset($data['fm_tui_editor_enable']) && $data['fm_tui_editor_enable'] === 'on' ? 1 : 0);
            $ucm::ucmUpdateOption('fm_pixo_editor_enable', isset($data['fm_pixo_editor_enable']) && $data['fm_pixo_editor_enable'] === 'on' ? 1 : 0);
            $ucm::ucmUpdateOption('fm_pixo_app_key', $data['fm_pixo_app_key']);
            $ucm::ucmUpdateOption('fm_custom_image_edit_file_types', $data['fm_custom_image_edit_file_types']);
            $ucm::ucmUpdateOption('fm_edit_file_types', $fm_image_edit_file_types ? implode(',', $fm_image_edit_file_types) : '');
            wp_send_json_success(['message' => __('Image Editors Settings saved!', 'ultimate-media-on-the-cloud')]);
        } else {
            wp_send_json_error(['message' => __('Invalid request', 'ultimate-media-on-the-cloud')]);
        }

        wp_die();
    }

    /**
     * Save the code editors
     * @throws Exception
     */
    public static function saveCodeEditors()
    {
        $ucm = new PhpRockets_UltimateMedia();
        if ($ucm::isPost()) {
            $data = $ucm::getPost('data');

            $ucm::ucmUpdateOption('fm_aceeditor_editor_enable', isset($data['fm_aceeditor_editor_enable']) && $data['fm_aceeditor_editor_enable'] === 'on' ? 1 : 0);
            $ucm::ucmUpdateOption('fm_tinymce_editor_enable', isset($data['fm_tinymce_editor_enable']) && $data['fm_tinymce_editor_enable'] === 'on' ? 1 : 0);
            $ucm::ucmUpdateOption('fm_textarea_editor_enable', isset($data['fm_textarea_editor_enable']) && $data['fm_textarea_editor_enable'] === 'on' ? 1 : 0);
            $ucm::ucmUpdateOption('fm_aceeditor_editor_file_types', $data['fm_aceeditor_editor_file_types']);
            $ucm::ucmUpdateOption('fm_tinymce_file_types', $data['fm_tinymce_file_types']);
            $ucm::ucmUpdateOption('fm_textarea_file_types', $data['fm_textarea_file_types']);
            wp_send_json_success(['message' => __('Code Editors Settings saved!', 'ultimate-media-on-the-cloud')]);
        } else {
            wp_send_json_error(['message' => __('Invalid request', 'ultimate-media-on-the-cloud')]);
        }

        wp_die();
    }

    /**
     * Save the code editors
     * @return void
     */
    public function saveAdvancedSettings()
    {
        $ucm = new PhpRockets_UltimateMedia();
        if ($ucm::isPost()) {
            $data = $ucm::getPost('data');
            if ($data['fm_local_hidden_path']) {
                $fm_local_hidden_path = explode(',', $data['fm_local_hidden_path']);
                if (in_array(ABSPATH, $fm_local_hidden_path, false) || in_array(rtrim(ABSPATH, '/'), $fm_local_hidden_path, false)) {
                    wp_send_json_error(['message' => 'You can not hide root path which is '. ABSPATH]);
                    wp_die();
                }
            }

            $ucm::ucmUpdateOption('fm_import_media', $data['fm_import_media']);
            $ucm::ucmUpdateOption('fm_local_hidden_path', $data['fm_local_hidden_path']);
            $ucm::ucmUpdateOption('fm_cloud_storage_upload_permission', $data['fm_cloud_storage_upload_permission']);
            wp_send_json_success(['message' => __('Advanced Settings saved!', 'ultimate-media-on-the-cloud')]);
        } else {
            wp_send_json_error(['message' => __('Invalid request', 'ultimate-media-on-the-cloud')]);
        }

        wp_die();
    }

    /**
     * @param PhpRockets_UltimateMedia $ucm
     * @return mixed
     */
    public static function settingsForm($ucm)
    {
        $errors = [];
        $form = [
            'div' => [
                'class' => 'panel-body',
                'id' => 'ucm-fm-general'
            ],
            'attr' => [
                'id' => 'frm-ucm-fm',
                'name' => 'frm_ucm_fm',
                'onsubmit' => 'return false'
            ],
            'fields' => [],
            'submit' => [
                'label' => __('Save Settings', 'ultimate-media-on-the-cloud'),
                'attr' => [
                    'href' => 'javascript:;',
                    'id' => 'fm-btn-general-save',
                    'class' => 'button is-info',
                    'onclick' => "phpR_UCM.fm.saveSettings(jQuery('form#frm-ucm-fm'), phprockets_ucm_fm.saveSettings);"
                ]
            ]
        ];

        $fm_dir_path = $ucm::ucmGetOption('fm_dir_path');
        if (!$fm_dir_path) {
            $fm_dir_path = ABSPATH;
        }
        $form['fields'][] = [
            'label' => __('Directory path', 'ultimate-media-on-the-cloud'),
            'type' => 'text',
            'icon' => 'fa fa-folder-open',
            'selected' => '',
            'attr' => [
                'name' => 'data[dir_path]',
                'class' => 'input',
                'value' => $fm_dir_path
            ],
            'column' => 'is-one-third',
            'help-text' => __('Choose the location you want to manage your files.', 'ultimate-media-on-the-cloud')
        ];

        $allowed_file_types = $ucm::ucmGetOption('fm_allowed_file_types');
        $form['fields'][] = [
            'label' => __('Upload file types', 'ultimate-media-on-the-cloud'),
            'type' => 'tags',
            'icon' => 'fa fa-tags',
            'placeholder' => __('Add file type', 'ultimate-media-on-the-cloud'),
            'attr' => [
                'name' => 'fm_allowed_file_types',
                'value' =>  $allowed_file_types ? explode(',', $allowed_file_types) : [],
                'id' => 'fm-allowed-file-types',
                'class' => 'tags-hidden-value'
            ],
            'help-text' => __('Allowed file types allow to upload.', 'ultimate-media-on-the-cloud')
        ];

        $php_ini_max_size = ini_get('post_max_size') ?: 0;
        $form['fields'][] = [
            'label' => __('Max file size', 'ultimate-media-on-the-cloud'),
            'type' => 'number',
            'icon' => 'fa fa-upload',
            'attr' => [
                'name' => 'data[fm_max_file_size]',
                'id' => 'fm-max-file-size',
                'class' => 'input',
                'value' => $ucm::ucmGetOption('fm_max_file_size', (int)$php_ini_max_size) ?: (int)$php_ini_max_size,
            ],
            'column' => 'is-one-third',
            'help-text' => __('Maximum file size allow to upload. <strong>In MB</strong>. System config max size is <strong style="color: red">'. $php_ini_max_size .'</strong>', 'ultimate-media-on-the-cloud')
        ];

        $form['fields'][] = [
            'label' => __('Language', 'ultimate-media-on-the-cloud'),
            'type' => 'select',
            'icon' => 'fa fa-globe',
            'value' => self::_getAvailableFmLanguages(),
            'selected' => $ucm::ucmGetOption('fm_locale') ?: 'en',
            'attr' => [
                'name' => 'data[fm_locale]',
                'id' => 'fm-locale'
            ],
            'column' => 'is-one-third'
        ];

        $fm_trash = $ucm::ucmGetOption('fm_trash');
        $form['fields'][] = [
            'label' => __('Enable Trash?', 'ultimate-media-on-the-cloud'),
            'type' => 'select',
            'icon' => 'fa fa-trash',
            'value' => [1 => 'Yes', 0 => 'No'],
            'selected' => $fm_trash,
            'attr' => [
                'name' => 'data[fm_trash]',
                'id' => 'fm-trash'
            ],
            'column' => 'is-one-third',
            'help-text' => __('Enable trash will temporary move delete file to Trash. (Only apply to LocalStorage)', 'ultimate-media-on-the-cloud')
        ];

        return $ucm::renderTemplate('common/_form', ['form' => $form, 'errors' => $errors]);
    }

    /**
     * @param PhpRockets_UltimateMedia $ucm
     * @return mixed
     */
    public static function RolesForm($ucm)
    {
        $errors = [];
        $form = [
            'div' => [
                'class' => 'panel-body',
                'id' => 'ucm-fm-roles'
            ],
            'attr' => [
                'id' => 'frm-ucm-fm-roles',
                'name' => 'frm_ucm_fm_roles',
                'onsubmit' => 'return false'
            ],
            'fields' => [],
            'submit' => [
                'label' => __('Save Roles Settings', 'ultimate-media-on-the-cloud'),
                'attr' => [
                    'href' => 'javascript:;',
                    'id' => 'fm-btn-roles-save',
                    'class' => 'button is-info',
                    'onclick' => "phpR_UCM.fm.saveSettings(jQuery('form#frm-ucm-fm-roles'), phprockets_ucm_fm.saveRoles);"
                ]
            ]
        ];

        $fm_roles_option = $ucm::ucmGetOption('fm_allowed_roles');
        $form['fields'][] = [
            'label' => __('Allowed user roles', 'ultimate-media-on-the-cloud'),
            'type' => 'checkbox',
            'icon' => 'fa fa-users',
            'value' => ['administrator' => 'Administrator <br>(all others admin)'],
            'selected' => $fm_roles_option,
            'attr' => [
                'name' => 'fm_allowed_roles[]',
                'id' => 'fm-allowed-roles',
                'class' => 'tags-hidden-value'
            ],
            'help-text' => __('Define system user roles are allowed to access <strong>File Manager</strong>.(If you uncheck all, all users will be denied except you)', 'ultimate-media-on-the-cloud')
        ];

        $fm_restricted_users = $ucm::ucmGetOption('fm_restricted_users');
        $form['fields'][] = [
            'label' => __('Restricted Users', 'ultimate-media-on-the-cloud'),
            'type' => 'tags',
            'hints' => true,
            'hints-target' => 'fm-users-list',
            'icon' => 'fa fa-users',
            'attr' => [
                'name' => 'fm_restricted_users',
                'value' => $fm_restricted_users ? explode(',', $fm_restricted_users) : [],
                'id' => 'fm-restricted-users',
                'class' => 'tags-input-hints tags-hidden-value'
            ],
            'help-text' => __('Specify system username to be denied to access <strong>FileManager</strong>. Type to search...', 'ultimate-media-on-the-cloud')
        ];

        $wp_users = get_users([
            'role' => 'administrator',
            'exclude' => [get_current_user_id()]
        ]);

        $users_array = [];
        foreach ($wp_users as $wp_user) {
            /** @var WP_User $wp_user */
            if (get_current_user_id() !== $wp_user->ID) {
                $users_array[] = $wp_user->user_login;
            }
        }
        $form['fields'][] = [
            'type' => 'hidden',
            'attr' => [
                'name' => 'fm_users_list',
                'value' => implode(',', $users_array),
                'id' => 'fm-users-list',
            ]
        ];

        return $ucm::renderTemplate('common/_form', ['form' => $form, 'errors' => $errors]);
    }

    /**
     * @param PhpRockets_UltimateMedia $ucm
     * @return mixed
     */
    public static function UIForm($ucm)
    {
        $errors = [];
        $form = [
            'div' => [
                'class' => 'panel-body',
                'id' => 'ucm-fm-ui'
            ],
            'attr' => [
                'id' => 'frm-ucm-fm-ui',
                'name' => 'frm_ucm_fm_ui',
                'onsubmit' => 'return false'
            ],
            'fields' => [],
            'submit' => [
                'label' => __('Save UI Settings', 'ultimate-media-on-the-cloud'),
                'attr' => [
                    'href' => 'javascript:;',
                    'id' => 'fm-btn-ui-save',
                    'class' => 'button is-info',
                    'onclick' => "phpR_UCM.fm.saveSettings(jQuery('form#frm-ucm-fm-ui'), phprockets_ucm_fm.saveUI);"
                ]
            ]
        ];

        $fm_theme = $ucm::ucmGetOption('fm_theme', 'default');
        $form['fields'][] = [
            'label' => __('Themes', 'ultimate-media-on-the-cloud'),
            'type' => 'select',
            'icon' => 'fa fa-desktop',
            'value' => self::_getAvailableThemes(),
            'selected' => $fm_theme,
            'attr' => [
                'name' => 'data[fm_theme]',
                'id' => 'fm-theme',
                'onchange' => 'phpR_UCM.fm.uiChange(this.value);'
            ],
            'help-text' => __('Change the UI template.', 'ultimate-media-on-the-cloud'),
            'column' => 'is-one-third',
        ];

        /* Preview template */
        $html_preview = '';
        foreach (self::_getThemesAlias() as $key => $data) {
            $html_preview .= '<div class="ucm-fm-ui-preview'. ($key === $fm_theme ? ' is-active': '') .'" id="fm-ui-'. $key .'">'.
                    '<a href="'. self::getAddOnData('addon_url') .'assets/images/themes/'. $data['screenshot'] .'" class="foobox">'.
                        '<img src="'. self::getAddOnData('addon_url') .'assets/images/themes/'. $data['screenshot'] .'" alt="'. $key .'" width="350">'.
                    '</a>'.
                    '</div>';
        }
        $form['fields'][] = [
            'type' => 'custom-html',
            'value' => $html_preview
        ];

        $form['fields'][] = [
            'label' => __('Default file layout', 'ultimate-media-on-the-cloud'),
            'type' => 'select',
            'icon' => 'fa fa-list',
            'value' => ['list' => 'List', 'icons' => 'Icon'],
            'selected' => $ucm::ucmGetOption('fm_file_layout') ?: 'list',
            'attr' => [
                'name' => 'data[fm_file_layout]',
                'id' => 'fm-file-layout'
            ],
            'column' => 'is-one-third',
            'help-text' => __('Preview: <a href="'. self::getAddOnData('addon_url') .'assets/images/2019-08-29_16-20-08.jpg" class="foobox" rel="fm-layout"><i class="fa fa-list"> </i> List Layout</a> <a href="'. self::getAddOnData('addon_url') .'assets/images/2019-08-29_16-20-49.jpg" class="foobox" rel="fm-layout"><i class="fa fa-th-large"> </i> Icon Layout</a>', 'ultimate-media-on-the-cloud')
        ];

        return $ucm::renderTemplate('common/_form', ['form' => $form, 'errors' => $errors]);
    }

    /**
     * @param PhpRockets_UltimateMedia $ucm
     * @return mixed
     */
    public static function AllowFileTypesForm($ucm)
    {
        $errors = [];
        $form = [
            'div' => [
                'class' => 'account-panel-body is-active',
                'id' => 'ucm-edit-file-types'
            ],
            'attr' => [
                'id' => 'ucm-frm-edit-file-types',
                'name' => 'ucm_frm_edit_file_types',
                'onsubmit' => 'return false'
            ],
            'fields' => [],
            'submit' => [
                'label' => __('Save File Types Settings', 'ultimate-media-on-the-cloud'),
                'attr' => [
                    'href' => 'javascript:;',
                    'class' => 'button is-info',
                    'onclick' => "phpR_UCM.fm.saveSettings(jQuery('form#ucm-frm-edit-file-types'), phprockets_ucm_fm.saveEditFileTypes);"
                ]
            ]
        ];

        $fm_edit_file_types = $ucm::ucmGetOption('fm_edit_file_types', '');
        $form['fields'][] = [
            'type' => 'checkbox',
            'inline' => true,
            'icon' => 'fa fa-tags',
            'value' => self::$data['configs']['fm_file_types'],
            'selected' => $fm_edit_file_types,
            'attr' => [
                'name' => 'fm_edit_file_types[]',
                'id' => 'fm-edit-file-types'
            ],
            'help-text' => __('List of allowed file types can be edited.', 'ultimate-media-on-the-cloud'),
            'column' => 'is-one-third',
        ];

        $fm_custom_edit_file_types = $ucm::ucmGetOption('fm_custom_edit_file_types', '');
        $form['fields'][] = [
            'label' => __('Custom file type', 'ultimate-media-on-the-cloud'),
            'type' => 'tags',
            'icon' => 'fa fa-tags',
            'placeholder' => __('Add file type', 'ultimate-media-on-the-cloud'),
            'attr' => [
                'name' => 'fm_custom_edit_file_types',
                'value' =>  $fm_custom_edit_file_types ? explode(',', $fm_custom_edit_file_types) : [],
                'class' => 'tags-hidden-value'
            ],
            'help-text' => __('Add your custom file type if the list above is not having your file type. Ex: application/json', 'ultimate-media-on-the-cloud')
        ];

        return $ucm::renderTemplate('common/_form', ['form' => $form, 'errors' => $errors]);
    }

    /**
     * @param PhpRockets_UltimateMedia $ucm
     * @return mixed
     */
    public static function ImageEditorsForm($ucm)
    {
        $errors = [];
        $form = [
            'div' => [
                'class' => 'account-panel-body',
                'id' => 'ucm-image-editors'
            ],
            'attr' => [
                'id' => 'frm-ucm-fm-image-editors',
                'name' => 'frm_ucm_fm_image_editors',
                'onsubmit' => 'return false'
            ],
            'fields' => [],
            'submit' => [
                'label' => __('Save Image Editors Settings', 'ultimate-media-on-the-cloud'),
                'attr' => [
                    'href' => 'javascript:;',
                    'id' => 'fm-btn-editors-save',
                    'class' => 'button is-info',
                    'onclick' => "phpR_UCM.fm.saveSettings(jQuery('form#frm-ucm-fm-image-editors'), phprockets_ucm_fm.saveImageEditors);"
                ]
            ]
        ];

        $fm_photopea_editor_enable = $ucm::ucmGetOption('fm_photopea_editor_enable', false);
        $form['fields'][] = [
            'label' => 'PhotoPea (Enable?)',
            'type' => 'checkbox',
            'icon' => 'fa fa-check',
            'value' => 1,
            'checked' => $fm_photopea_editor_enable ? true : false,
            'attr' => [
                'name' => 'data[fm_photopea_editor_enable]'
            ],
            'column' => 'is-one-third',
        ];

        /* Preview Editors */
        $form['fields'][] = [
            'type' => 'custom-html',
            'value' => self::_editorScreenShot('photopea.png', 'PhotoPea')
        ];

        $fm_tui_editor_enable = $ucm::ucmGetOption('fm_tui_editor_enable', false);
        $form['fields'][] = [
            'label' => 'TUI Image Editor (Enable?)',
            'type' => 'checkbox',
            'icon' => 'fa fa-check',
            'value' => 1,
            'checked' => $fm_tui_editor_enable ? true : false,
            'attr' => [
                'name' => 'data[fm_tui_editor_enable]'
            ],
            'column' => 'is-one-third',
        ];

        /* Preview Editors */
        $form['fields'][] = [
            'type' => 'custom-html',
            'value' => self::_editorScreenShot('tui-editor.jpg', 'Tui Image Editor')
        ];

        $fm_pixo_editor_enable = $ucm::ucmGetOption('fm_pixo_editor_enable', false);
        $form['fields'][] = [
            'label' => 'Pixo Editor (Enable?)',
            'type' => 'checkbox',
            'icon' => 'fa fa-check',
            'value' => 1,
            'checked' => $fm_pixo_editor_enable ? true : false,
            'attr' => [
                'name' => 'data[fm_pixo_editor_enable]'
            ],
            'column' => 'is-one-third',
        ];

        $fm_pixo_app_key = $ucm::ucmGetOption('fm_pixo_app_key', '');
        $form['fields'][] = [
            'label' => __('Pixo App Key', 'ultimate-media-on-the-cloud'),
            'type' => 'text',
            'icon' => 'fa fa-key',
            'selected' => '',
            'attr' => [
                'name' => 'data[fm_pixo_app_key]',
                'class' => 'input',
                'value' => $fm_pixo_app_key
            ],
            'column' => 'is-one-third',
            'help-text' => __('You can obtain Pixo AppKey by visit <a href="https://pixoeditor.com" target="_blank">https://pixoeditor.com</a>', 'ultimate-media-on-the-cloud')
        ];

        /* Preview Editors */
        $form['fields'][] = [
            'type' => 'custom-html',
            'value' => self::_editorScreenShot('pixo.jpg', 'Pixo Editor')
        ];

        $form['fields'][] = [
            'type' => 'legend',
            'text' => __('Allowed file types', 'ultimate-media-on-the-cloud'),
            'icon' => 'fa fa-tags'
        ];
        $fm_image_edit_file_types = $ucm::ucmGetOption('fm_image_edit_file_types', '');
        $form['fields'][] = [
            'type' => 'checkbox',
            'inline' => true,
            'icon' => 'fa fa-tags',
            'value' => self::$data['configs']['fm_image_edit_file_types'],
            'selected' => $fm_image_edit_file_types,
            'attr' => [
                'name' => 'fm_image_edit_file_types[]',
                'id' => 'fm-image-edit-file-types'
            ],
            'help-text' => __('Enable file types for Image editors.', 'ultimate-media-on-the-cloud'),
            'column' => 'is-one-third',
        ];

        $fm_custom_image_edit_file_types = $ucm::ucmGetOption('fm_custom_image_edit_file_types', '');
        $form['fields'][] = [
            'label' => __('Custom file type', 'ultimate-media-on-the-cloud'),
            'type' => 'tags',
            'icon' => 'fa fa-tags',
            'placeholder' => __('Add file type', 'ultimate-media-on-the-cloud'),
            'attr' => [
                'name' => 'data[fm_custom_image_edit_file_types]',
                'value' =>  $fm_custom_image_edit_file_types ? explode(',', $fm_custom_image_edit_file_types) : [],
                'class' => 'tags-hidden-value'
            ],
            'help-text' => __('Add your custom file type if the list above is not having your file type. Ex: image/gif', 'ultimate-media-on-the-cloud')
        ];

        return $ucm::renderTemplate('common/_form', ['form' => $form, 'errors' => $errors]);
    }

    /**
     * @param PhpRockets_UltimateMedia $ucm
     * @return mixed
     */
    public static function CodeEditorsForm($ucm)
    {
        $errors = [];
        $form = [
            'div' => [
                'class' => 'account-panel-body',
                'id' => 'ucm-code-editors'
            ],
            'attr' => [
                'id' => 'frm-ucm-fm-code-editors',
                'name' => 'frm_ucm_fm_code_editors',
                'onsubmit' => 'return false'
            ],
            'fields' => [],
            'submit' => [
                'label' => __('Save Code Editors Settings', 'ultimate-media-on-the-cloud'),
                'attr' => [
                    'href' => 'javascript:;',
                    'class' => 'button is-info',
                    'onclick' => "phpR_UCM.fm.saveSettings(jQuery('form#frm-ucm-fm-code-editors'), phprockets_ucm_fm.saveCodeEditors);"
                ]
            ]
        ];

        $fm_aceeditor_editor_enable = $ucm::ucmGetOption('fm_aceeditor_editor_enable', false);
        $form['fields'][] = [
            'label' => 'Ace Editor (Enable?)',
            'type' => 'checkbox',
            'icon' => 'fa fa-check',
            'value' => 1,
            'checked' => $fm_aceeditor_editor_enable ? true : false,
            'attr' => [
                'name' => 'data[fm_aceeditor_editor_enable]'
            ],
            'column' => 'is-one-third',
        ];
        $fm_aceeditor_editor_file_types = $ucm::ucmGetOption('fm_aceeditor_editor_file_types', '');
        $form['fields'][] = [
            'label' => __('Allowed file types', 'ultimate-media-on-the-cloud'),
            'type' => 'tags',
            'inline' => true,
            'icon' => 'fa fa-tags',
            'attr' => [
                'name' => 'data[fm_aceeditor_editor_file_types]',
                'value' =>  $fm_aceeditor_editor_file_types ? explode(',', $fm_aceeditor_editor_file_types) : [],
                'class' => 'tags-hidden-value'
            ],
            'help-text' => __('Enable file types for ACE Editor', 'ultimate-media-on-the-cloud'),
            'column' => 'is-one-third',
        ];

        /* Preview Editors */
        $form['fields'][] = [
            'type' => 'custom-html',
            'value' => self::_editorScreenShot('aceeditor.png', 'Ace Editor')
        ];

        $fm_tinymce_editor_enable = $ucm::ucmGetOption('fm_tinymce_editor_enable', false);
        $form['fields'][] = [
            'label' => 'TinyMCE (Enable?)',
            'type' => 'checkbox',
            'icon' => 'fa fa-check',
            'value' => 1,
            'checked' => $fm_tinymce_editor_enable ? true : false,
            'attr' => [
                'name' => 'data[fm_tinymce_editor_enable]'
            ],
            'column' => 'is-one-third',
        ];
        $fm_tinymce_file_types = $ucm::ucmGetOption('fm_tinymce_file_types', '');
        $form['fields'][] = [
            'label' => __('Allowed file types', 'ultimate-media-on-the-cloud'),
            'type' => 'tags',
            'inline' => true,
            'icon' => 'fa fa-tags',
            'attr' => [
                'name' => 'data[fm_tinymce_file_types]',
                'value' =>  $fm_tinymce_file_types ? explode(',', $fm_tinymce_file_types) : [],
                'class' => 'tags-hidden-value'
            ],
            'help-text' => __('Enable file types for TinyMCE', 'ultimate-media-on-the-cloud'),
            'column' => 'is-one-third',
        ];

        /* Preview Editors */
        $form['fields'][] = [
            'type' => 'custom-html',
            'value' => self::_editorScreenShot('TinyMCE.png', 'TinyMCE')
        ];

        $fm_textarea_editor_enable = $ucm::ucmGetOption('fm_textarea_editor_enable', false);
        $form['fields'][] = [
            'label' => 'Simple Textarea (Enable?)',
            'type' => 'checkbox',
            'icon' => 'fa fa-check',
            'value' => 1,
            'checked' => $fm_textarea_editor_enable ? true : false,
            'attr' => [
                'name' => 'data[fm_textarea_editor_enable]'
            ],
            'column' => 'is-one-third',
        ];
        $fm_textarea_file_types = $ucm::ucmGetOption('fm_textarea_file_types', '');
        $form['fields'][] = [
            'label' => __('Allowed file types', 'ultimate-media-on-the-cloud'),
            'type' => 'tags',
            'inline' => true,
            'icon' => 'fa fa-tags',
            'attr' => [
                'name' => 'data[fm_textarea_file_types]',
                'value' =>  $fm_textarea_file_types ? explode(',', $fm_textarea_file_types) : [],
                'class' => 'tags-hidden-value'
            ],
            'help-text' => __('Enable file types for Simple Textarea', 'ultimate-media-on-the-cloud'),
            'column' => 'is-one-third',
        ];

        return $ucm::renderTemplate('common/_form', ['form' => $form, 'errors' => $errors]);
    }

    /**
     * @param PhpRockets_UltimateMedia $ucm
     * @return mixed
     */
    public static function AdvancedSettingsForm($ucm)
    {
        $errors = [];
        $form = [
            'div' => [
                'class' => 'panel-body',
                'id' => 'ucm-fm-advanced'
            ],
            'attr' => [
                'id' => 'frm-ucm-fm-advanced',
                'name' => 'frm_ucm_fm',
                'onsubmit' => 'return false'
            ],
            'fields' => [],
            'submit' => [
                'label' => __('Save Advanced Settings', 'ultimate-media-on-the-cloud'),
                'attr' => [
                    'href' => 'javascript:;',
                    'class' => 'button is-info',
                    'onclick' => "phpR_UCM.fm.saveSettings(jQuery('form#frm-ucm-fm-advanced'), phprockets_ucm_fm.saveAdvancedSettings);"
                ]
            ]
        ];

        $fm_local_hidden_path = $ucm::ucmGetOption('fm_local_hidden_path');
        $form['fields'][] = [
            'label' => __('Hidden path (LocalStorage)', 'ultimate-media-on-the-cloud'),
            'type' => 'tags',
            'icon' => 'fa fa-tags',
            'placeholder' => __('Add file type', 'ultimate-media-on-the-cloud'),
            'attr' => [
                'name' => 'data[fm_local_hidden_path]',
                'value' =>  $fm_local_hidden_path ? explode(',', $fm_local_hidden_path) : [],
                'class' => 'tags-hidden-value'
            ],
            'help-text' => __('Add the path(s) you want to hide from <strong>File Manager</strong> (Only support Local Storage)', 'ultimate-media-on-the-cloud')
        ];

        $fm_import_media = $ucm::ucmGetOption('fm_import_media');
        $wp_upload_dir = wp_upload_dir();
        $form['fields'][] = [
            'label' => __('Import to Wordpress Media?', 'ultimate-media-on-the-cloud'),
            'type' => 'select',
            'icon' => 'fa fa-trash',
            'value' => [0 => 'No', 1 => 'Yes'],
            'selected' => (int)$fm_import_media,
            'attr' => [
                'name' => 'data[fm_import_media]'
            ],
            'column' => 'is-one-third',
            'help-text' => __('Auto import into Wordpress media when you upload to WP uploads folder <strong>'. $wp_upload_dir['basedir'] .'</strong>', 'ultimate-media-on-the-cloud')
        ];

        $fm_cloud_storage_upload_permission = $ucm::ucmGetOption('fm_cloud_storage_upload_permission', 'private');
        $form['fields'][] = [
            'label' => __('Cloud Upload Permission', 'ultimate-media-on-the-cloud'),
            'type' => 'select',
            'icon' => 'fa fa-lock',
            'value' => ['private' => 'Private', 'public-read' => 'Public'],
            'selected' => $fm_cloud_storage_upload_permission,
            'attr' => [
                'name' => 'data[fm_cloud_storage_upload_permission]'
            ],
            'column' => 'is-one-third',
            'help-text' => __('When upload file to cloud storage, default is <strong>Private</strong> or <strong>Public</strong>', 'ultimate-media-on-the-cloud')
        ];

        return $ucm::renderTemplate('common/_form', ['form' => $form, 'errors' => $errors]);
    }

    /**
     * @param PhpRockets_UltimateMedia $ucm
     * @param array                    $accounts
     * @param integer                  $account_id
     * @param string                   $bucket_name
     * @return mixed
     */
    private static function accountSelectionForm($ucm, $accounts, $account_id, $bucket_name)
    {
        $is_local = $account_id ? false : true;
        $errors = [];
        $form = [
            'div' => [
                'class' => 'account-panel-body is-active'
            ],
            'attr' => [
                'id' => 'frm-ucm-fl-accounts',
                'name' => 'frm_ucm_fl_accounts',
                'onsubmit' => 'return false'
            ],
            'fields' => [],
            'submit' => [
                'label' => __('Switch To', 'ultimate-media-on-the-cloud'),
                'attr' => [
                    'href' => 'javascript:;',
                    'onclick' => 'phpR_UCM.fm.account.switchStorage()',
                    'class' => 'button is-info'
                ]
            ]
        ];

        $form['fields'][] = [
            'label' => __('Storage', 'ultimate-media-on-the-cloud'),
            'type' => 'select',
            'icon' => 'fa fa-database',
            'value' => [0 => 'LocalStorage', 1 => 'Cloud Account'],
            'selected' => $account_id ? 1 : 0,
            'attr' => [
                'name' => 'storage_type',
                'id' => 'ucm-fm-storage-type',
                'onchange' => 'phpR_UCM.fm.account.storageChange(this.value)'
            ],
            'column' => 'is-one-third',
            'help-text' => __('Choose the location you want to manage your files.', 'ultimate-media-on-the-cloud')
        ];

        if (!$accounts) {
            $errors[] = __('There is no account. You should add some by going to <a class="button is-small is-info" href="'. admin_url() .'admin.php?page='. $ucm::$configs->getMenuSlug('account_menu') .'">Manage Cloud Account</a>', 'ultimate-media-on-the-cloud');
            unset($form['submit']);
        } else {
            $accounts_arr = [];
            foreach ($accounts as $storage_account) {
                if (!$account_id) {
                    $account_id = $storage_account['id'];
                }
                switch ($storage_account['storage_adapter']) {
                    case 'aws':
                        $accounts_arr[$storage_account['id']] = 'S3 - '. $storage_account['name'];
                        break;
                    case 'google_cloud':
                        $accounts_arr[$storage_account['id']] = 'GCloud - '. $storage_account['name'];
                        break;
                    case 'digitalocean':
                        $accounts_arr[$storage_account['id']] = 'DigitalOcean - '. $storage_account['name'];
                        break;
                    case 'wasabi':
                        $accounts_arr[$storage_account['id']] = 'Wasabi - '. $storage_account['name'];
                        break;
                }
            }

            $form['fields'][] = [
                'div' => [
                    'class' => 'columns field'. ($is_local ? ' ucm-field-hidden' : '')
                ],
                'label' => __('Accounts', 'ultimate-media-on-the-cloud'),
                'type' => 'select',
                'icon' => 'fa fa-users',
                'value' => $accounts_arr,
                'selected' => !empty($account_id) ? (int)$account_id : '',
                'attr' => [
                    'name' => 'account_id',
                    'id' => 'ucm-fm-account-id',
                    'onchange' => 'phpR_UCM.fm.account.reloadBuckets(this.value)'
                ],
                'column' => 'is-one-third',
            ];

            /* Fetch the list of buckets */
            $account = PhpRockets_Model_Accounts::query([
                'conditions' => ['id' => $account_id]
            ]);

            if (!class_exists($account['addon_class'])) {
                $bucket_arr = new WP_Error();
                $bucket_arr->add('exception', 'AddOn is not loaded');
            } else {
                $account_addon = new $account['addon_class'];
                /** @var PhpRockets_UCM_Addons $account_addon */
                $bucket_arr = $account_addon->listBuckets($account['id']);
                if (is_wp_error($bucket_arr)) {
                    /** @var WP_Error $bucket_arr */
                    $errors = $bucket_arr->get_error_messages('exception');
                }
            }

            $form['fields'][] = [
                'div' => [
                    'class' => 'columns field'. ($is_local ? ' ucm-field-hidden' : '')
                ],
                'label' => __('Bucket', 'ultimate-media-on-the-cloud'),
                'type' => 'select',
                'icon' => 'fa fa-folder-open',
                'value' => is_wp_error($bucket_arr) ? [] : $bucket_arr,
                'selected' => !empty($bucket_name) ? $bucket_name : '',
                'attr' => [
                    'name' => 'bucket_name',
                    'id' => 'ucm-fm-bucket'
                ],
                'column' => 'is-one-third',
                'help-text' => __('Choose the target Account & Bucket then click Switch button to switch between Account & Bucket.', 'ultimate-media-on-the-cloud'),
                'help-text-attr' => [
                    'style' => $is_local ? 'display: none;' : ''
                ]
            ];
        }

        return $ucm::renderTemplate('common/_form', ['form' => $form, 'errors' => $errors], false);
    }

    /**
     * Init the client ElFinder
     * @param $account
     * @param $selected_bucket
     * @return void
     */
    public static function initFM($account, $selected_bucket)
    {
        $ucm = new PhpRockets_UltimateMedia();
        $js = $ucm::renderTemplate(self::getAddOnData('addon_dir') . '/includes/tpl/fm_main', [
            'account' => $account,
            'selected_bucket' => $selected_bucket
        ], false, false, 'external');

        $squeeze = new \Patchwork\JSqueeze();
        print (defined('WP_DEBUG') && WP_DEBUG ) ? $js : $squeeze->squeeze($js);
    }

    /**
     * Handle client browse files
     * @throws Exception
     */
    public function clientConnect()
    {
        add_filter('ucm_fm_other_places', [__CLASS__, 'fmListOtherPlaces'], 10, 2);
        $ucm = new PhpRockets_UltimateMedia_Root();
        $storage_type = $ucm::isPost() ? $ucm::getPost('storage_type') : $ucm::getQuery('storage_type');
        $selected_bucket = $ucm::isPost() ? $ucm::getPost('bucket') : $ucm::getQuery('bucket');
        $roots = [];

        /* Get maximum allow upload file size */
        $uploadMaxSize = $ucm::ucmGetOption('fm_max_file_size');
        if (!$uploadMaxSize) {
            $uploadMaxSize = ini_get('post_max_size') ?: '10M';
        } else {
            $uploadMaxSize .= 'M';
        }

        if ((int)$storage_type === 1) {
            $account_id = $ucm::isPost() ? $ucm::getPost('account_id') : $ucm::getQuery('account_id');

            if ($account_id) {
                $account = PhpRockets_Model_Accounts::query([
                    'conditions' => [
                        'id' => $account_id
                    ]
                ]);
                if ($account) {
                    $account_data = unserialize($account['value']);
                    if (!$selected_bucket) {
                        $selected_bucket = $account_data['bucket'];
                    }

                    /** @var PhpRockets_UCM_Addons $addon */
                    $addon = new $account['addon_class'];

                    if ($account['storage_adapter']) {
                        switch ($account['storage_adapter']) {
                            case 'aws':
                                $endpoint = 'https://' . $selected_bucket . '.s3.amazonaws.com';
                                $args = [
                                    'driver' => 's3',
                                    'region' => $account_data['region'],
                                    'bucket' => $selected_bucket,
                                    'url' => $endpoint,
                                    'version' => 'latest',
                                    'credentials' => [
                                        'key' => $account_data['app_key'],
                                        'secret' => $account_data['app_secret']
                                    ]
                                ];
                                $client = $addon->initClient($args);

                                /* Check if bucket is located under another Location Constraint*/
                                $bucket_location = (array)$client->getBucketLocation([
                                    'Bucket' => $selected_bucket
                                ])->toArray();

                                if ($args['region'] !== $bucket_location['LocationConstraint']) {
                                    $args['region'] = $bucket_location['LocationConstraint'];
                                    $client = $addon->initClient($args);
                                }

                                $adapter = new AwsS3Adapter($client, $selected_bucket);
                                break;

                            case 'google_cloud':
                                /**
                                 * The credentials are manually specified by passing in a keyFilePath.
                                 */
                                $key_file = $ucm::$configs->local_dir_save_key . $account_data['auth_file'];
                                $client = new StorageClient([
                                    'projectId' => $account_data['project_id'],
                                    'keyFilePath' => $key_file,
                                ]);
                                $bucket = $client->bucket($selected_bucket);
                                $endpoint = 'https://storage.googleapis.com/'. $selected_bucket;
                                $adapter = new GoogleStorageAdapter($client, $bucket);
                                break;

                            case 'digitalocean':
                                $aws_config = [
                                    'key' => $account_data['app_key'],
                                    'secret' => $account_data['app_secret'],
                                    'region' => $account_data['region'],
                                    'bucket' => $selected_bucket
                                ];
                                $endpoint = 'https://' .$aws_config['bucket']. '.' .$aws_config['region']. '.digitaloceanspaces.com';
                                $client = new S3Client([
                                        'driver' => 's3',
                                        'region' => $aws_config['region'],
                                        'endpoint' => $endpoint,
                                        'bucket_endpoint' => true,
                                        'version' => 'latest',
                                        'credentials' => [
                                            'key' => $aws_config['key'],
                                            'secret' => $aws_config['secret']
                                        ],
                                        'signature_version' => 'v4-unsigned-body'
                                    ]
                                );

                                $adapter = new AwsS3Adapter($client, $selected_bucket);
                                break;

                            case 'wasabi':
                                $aws_config = [
                                    'key' => $account_data['app_key'],
                                    'secret' => $account_data['app_secret'],
                                    'region' => $account_data['region'],
                                    'bucket' => $selected_bucket
                                ];
                                $endpoint = $ucm::ucmGetOption('option_scheme') .'://'. $aws_config['bucket'] .'.s3.'. $aws_config['region'] . '.wasabisys.com';
                                $client = new S3Client([
                                        'driver' => 's3',
                                        'region' => $aws_config['region'],
                                        'endpoint' => $endpoint,
                                        'bucket_endpoint' => true,
                                        'version' => 'latest',
                                        'credentials' => [
                                            'key' => $aws_config['key'],
                                            'secret' => $aws_config['secret']
                                        ],
                                        'signature_version' => 'v4-unsigned-body'
                                    ]
                                );

                                $adapter = new AwsS3Adapter($client, $selected_bucket);
                                break;
                        }

                        /* Init Cloud Storage Object FM */
                        if (isset($adapter, $endpoint)) {
                            $cloud_filesystem = new Filesystem($adapter, Array ( 'url' => $endpoint ));
                        }
                    }
                }
            }

            if (isset($cloud_filesystem, $endpoint, $account, $client)) {
                $roots[] = [
                    'driver' => 'Flysystem',
                    'alias' => $selected_bucket,
                    'filesystem' => $cloud_filesystem,
                    'URL' => $endpoint,
                    'tmbURL' => 'self',
                    'uploadMaxSize' => $uploadMaxSize,
                ];

                $fm_other_places_args = [
                    'account' => $account,
                    'client' => $client,
                    'uploadMaxSize' => $uploadMaxSize,
                    'ucm' => $ucm
                ];
                $extra_places = apply_filters('ucm_fm_other_places', $fm_other_places_args, $selected_bucket);
                if ($extra_places) {
                    $roots = array_merge($roots, $extra_places);
                }
            }
        } else {
            $wp_upload_dir = wp_upload_dir();
            $fm_dir_path = $ucm::ucmGetOption('fm_dir_path');
            /* Local Storage */
            $allowed_upload_files = $ucm::ucmGetOption('fm_allowed_file_types', []);
            if ($allowed_upload_files) {
                $allowed_upload_files = explode(',', $allowed_upload_files);
            }

            $sub_dir = str_replace(ABSPATH, '', $fm_dir_path);
            $localStorage = [
                'driver'        => 'LocalFileSystem',
                'path'          => $fm_dir_path,
                'URL'           => get_bloginfo('url') . ($sub_dir ? '/'. $sub_dir : ''),
                'trashHash'     => 't1_Lw',
                'winHashFix'    => DIRECTORY_SEPARATOR !== '/',
                'uploadDeny'    => ['all'],
                'uploadAllow'   => $allowed_upload_files,
                'uploadOrder'   => ['deny', 'allow'],
                'accessControl' => 'access',
                'alias' => 'LocalStorage',
                'uploadMaxSize' => $uploadMaxSize
            ];

            /* If FM enable the Trash */
            $fm_enable_trash = $ucm::ucmGetOption('fm_trash', 0);
            if (!$fm_enable_trash) {
                unset($localStorage['trashHash']);
            } else {
                if (!file_exists($wp_upload_dir['basedir'] .'/ucm-files-manager/')) {
                    if (!mkdir($concurrentDirectory = $wp_upload_dir['basedir'] . '/ucm-files-manager/', 0777) && !is_dir($concurrentDirectory)) {
                        echo json_encode(['norError' => sprintf('Directory "%s" was not created', $concurrentDirectory)]);
                        wp_die();
                    }
                }

                if (!file_exists($wp_upload_dir['basedir'] .'/ucm-files-manager/.trash')) {
                    if (!mkdir($concurrentDirectory = $wp_upload_dir['basedir'] . '/ucm-files-manager/.trash', 0777) && !is_dir($concurrentDirectory)) {
                        echo json_encode(['norError' => sprintf('Directory "%s" was not created', $concurrentDirectory)]);
                        wp_die();
                    }
                }

                /* Hide Trash Folder */
                $attributes = [];
                $attributes[] = [
                    'pattern' => '/ucm-files-manager/',
                    'hidden' => true
                ];

                $fm_local_hidden_path = $ucm::ucmGetOption('fm_local_hidden_path');
                if ($fm_local_hidden_path) {
                    $fm_local_hidden_path = explode(',', $fm_local_hidden_path);
                    foreach ($fm_local_hidden_path as $hidden_path) {
                        $attributes[] = [
                            'pattern' => '!^/'. ltrim(str_replace($fm_dir_path, '', $hidden_path), '/') .'!',
                            'hidden' => true
                        ];
                    }
                }

                $localStorage['attributes'] = $attributes;
            }
            $roots[] = $localStorage;

            if ($fm_enable_trash) {
                $roots[] = [
                    'id'            => '1',
                    'driver'        => 'Trash',
                    'path'          => $wp_upload_dir['basedir'] .'/ucm-files-manager/.trash/',
                    'tmbURL'        => $wp_upload_dir['basedir'] .'/ucm-files-manager/.trash/.tmb/',
                    'winHashFix'    => DIRECTORY_SEPARATOR !== '/',
                    'uploadDeny'    => ['all'],
                    'uploadAllow'   => $allowed_upload_files,
                    'uploadOrder'   => ['deny', 'allow'],
                    'accessControl' => 'access',
                ];
            }
        }

        /* Set OPTS */
        $opts = [
            'roots' => $roots
        ];
        if ((int)$storage_type) {
            $opts['bind'] = ['open' => [__CLASS__ .'::addCloudPermissionColumn']];
        }

        $connector = new elFinderConnector(new elFinder($opts));
        $connector->run();
        wp_die();
    }

    /**
     * List other buckets place for cloud account
     *
     * @param array  $args
     * @param string $exclude_bucket
     * @return array
     */
    public static function fmListOtherPlaces($args, $exclude_bucket)
    {
        $roots = [];
        /** @var PhpRockets_UCM_Addons $addon */
        $addon = new $args['account']['addon_class'];
        $client = $args['client'];

        /* List other buckets places */
        switch ($args['account']['storage_adapter']) {
            case 'aws':
            case 'digitalocean':
                $buckets = $addon->listBuckets($args['account']['id']);
                foreach ($buckets as $bucket) {
                    if ($exclude_bucket !== $bucket) {
                        $adapter = new AwsS3Adapter($client, $bucket);
                        $endpoint = 'https://' . $bucket . '.s3.amazonaws.com';
                        $cloud_filesystem = new Filesystem($adapter, Array ( 'url' => $endpoint ));

                        $roots[] = [
                            'driver' => 'Flysystem',
                            'alias' => $bucket,
                            'filesystem' => $cloud_filesystem,
                            'URL' => $endpoint,
                            'tmbURL' => 'self',
                            'uploadMaxSize' => $args['uploadMaxSize'],
                        ];
                    }
                }
                break;

            case 'google_cloud':
                $buckets = $addon->listBuckets($args['account']['id']);
                $account_data = unserialize($args['account']['value']);
                $ucm = $args['ucm'];

                foreach ($buckets as $bucket) {
                    if ($exclude_bucket !== $bucket) {
                        $key_file = $ucm::$configs->local_dir_save_key . $account_data['auth_file'];
                        $client = new StorageClient([
                            'projectId' => $account_data['project_id'],
                            'keyFilePath' => $key_file,
                        ]);
                        $bucket_obj = $client->bucket($bucket);
                        $endpoint = 'https://storage.googleapis.com/'. $bucket;
                        $adapter = new GoogleStorageAdapter($client, $bucket_obj);
                        $cloud_filesystem = new Filesystem($adapter, Array ( 'url' => $endpoint ));

                        $roots[] = [
                            'driver' => 'Flysystem',
                            'alias' => $bucket,
                            'filesystem' => $cloud_filesystem,
                            'URL' => $endpoint,
                            'tmbURL' => 'self',
                            'uploadMaxSize' => $args['uploadMaxSize'],
                        ];
                    }
                }
                break;
        }

        return $roots;
    }

    /**
     * @param $cmd
     * @param $result
     * @param $args
     * @param elFinder $elfinder
     */
    public static function addCloudPermissionColumn($cmd, &$result, $args, $elfinder)
    {
        $ucm = new PhpRockets_UltimateMedia();
        $account_id = $ucm::isPost() ? $ucm::getPost('account_id') : $ucm::getQuery('account_id');
        $selected_bucket = $ucm::isPost() ? $ucm::getPost('bucket') : $ucm::getQuery('bucket');

        $account = PhpRockets_Model_Accounts::query([
            'conditions' => [
                'id' => $account_id
            ]
        ]);
        $account_data = unserialize($account['value']);
        /** @var PhpRockets_UCM_Addons $addon */
        $addon = new $account['addon_class'];

        switch ($account['storage_adapter']) {
            case 'aws':
                $endpoint = 'https://' . $selected_bucket . '.s3.'.'amazonaws.com';
                $args = [
                    'driver' => 's3',
                    'region' => $account_data['region'],
                    'bucket' => $selected_bucket,
                    'url' => $endpoint,
                    'version' => 'latest',
                    'credentials' => [
                        'key' => $account_data['app_key'],
                        'secret' => $account_data['app_secret']
                    ]
                ];
                $client = $addon->initClient($args);

                /* Check if bucket is located under another Location Constraint*/
                $bucket_location = (array)$client->getBucketLocation([
                    'Bucket' => $selected_bucket
                ])->toArray();

                if ($args['region'] !== $bucket_location['LocationConstraint']) {
                    $args['region'] = $bucket_location['LocationConstraint'];
                    $client = $addon->initClient($args);
                }

                break;

            case 'google_cloud':
                /**
                 * The credentials are manually specified by passing in a keyFilePath.
                 */
                $args = [
                    'project_id' => $account_data['project_id'],
                    'key_file' => $ucm::$configs->local_dir_save_key . $account_data['auth_file']
                ];
                $client = $addon->initClient($args);
                break;

            case 'digitalocean':
                $args = [
                    'app_key' => $account_data['app_key'],
                    'app_secret' => $account_data['app_secret'],
                    'bucket' => $selected_bucket,
                    'region' => $account_data['region']
                ];
                $client = $addon->initClient($args);
                break;
        }

        foreach($result['files'] as $i => $file) {
            $permission = 'PRIVATE';
            if ($file['mime'] === 'directory') {
                $permission = '-';
            } else {
                $volume = $elfinder->getVolume($file['hash']);
                $dir = $volume->decode($file['phash']);
                switch ($account['storage_adapter']) {
                    case 'aws':
                        try {
                            $acl = $client->getObjectAcl([
                                'Bucket' => $selected_bucket,
                                'Key' => ($dir === '/' ? '' : $dir .'/') . $file['name']
                            ]);

                            $grants = $acl['Grants'];
                            if (count($grants) > 1) {
                                foreach ($grants as $grant) {
                                    if (false !== strpos($grant['Grantee']['URI'], 'global/AllUsers')) {
                                        $permission = $grant['Permission'] === 'READ' ? __('PUBLIC-READ', 'ultimate-media-on-the-cloud') : 'PRIVATE';
                                        break;
                                    }
                                }
                            } else {
                                $permission = __('PRIVATE', 'ultimate-media-on-the-cloud');
                            }
                        } catch (S3Exception $e) {
                            $permission = 'N/A';
                        }
                        break;
                    case 'digitalocean':
                        try {
                            /** @var SpacesConnect $client */
                            $acl = $client->client->getObjectAcl([
                                'Bucket' => $selected_bucket,
                                'Key' => ($dir === '/' ? '' : $dir .'/') . $file['name']
                            ]);

                            $grants = $acl['Grants'];
                            if (count($grants) > 1) {
                                foreach ($grants as $grant) {
                                    if (false !== strpos($grant['Grantee']['URI'], 'global/AllUsers')) {
                                        $permission = $grant['Permission'] === 'READ' ? __('PUBLIC-READ', 'ultimate-media-on-the-cloud') : 'PRIVATE';
                                        break;
                                    }
                                }
                            } else {
                                $permission = __('PRIVATE', 'ultimate-media-on-the-cloud');
                            }
                        } catch (SpacesAPIException $e) {
                            $permission = 'N/A';
                        }
                        break;
                    case 'google_cloud':
                        /** @var StorageClient $client */
                        $bucket = $client->bucket($selected_bucket);
                        try {
                            $object = $bucket->object(($dir === '/' ? '' : $dir .'/') . $file['name']);
                            $acl = $object->acl();
                            try {
                                $acl->get(['entity' => 'allUsers']);
                                $permission = __('PUBLIC-READ', 'ultimate-media-on-the-cloud');
                            } catch (\Exception $e) {
                                $permission = __('PRIVATE', 'ultimate-media-on-the-cloud');
                            }
                        } catch (\Exception $e) {
                            $permission = __('Account Error!', 'ultimate-media-on-the-cloud');
                        }
                        break;
                }
            }
            $result['files'][$i]['cloudpermission'] = $permission;
        }
    }

    /**
     * Reload the account buckets
     * @throws Exception
     */
    public function reloadBuckets()
    {
        $ucm = new PhpRockets_UltimateMedia_Root();
        if ($ucm::isPost()) {
            $data['account_id'] = $ucm::getPost('account_id');

            /* Perform inputs validation */
            $validation = new Validation();
            $validation->validation_rules([
                'account_id' => 'required'
            ]);

            $validated = $validation->run($data);
            if ($validated === false) {
                wp_send_json_error(['message' => implode('<br>', $validation->get_errors_array())]);
                wp_die();
            }

            /* Fetch database account */
            $account = PhpRockets_Model_Accounts::query([
                'conditions' => [
                    'id' => $data['account_id']
                ]
            ]);
            if (!$account) {
                wp_send_json_error(['message' => __('Cloud account is not found', 'ultimate-media-on-the-cloud')]);
            } else {
                $account_addon = new $account['addon_class'];
                /** @var PhpRockets_UCM_Addons $account_addon */
                $bucket_arr = $account_addon->listBuckets($account['id'], true);

                if (is_wp_error($bucket_arr)) {
                    /**@var WP_Error $bucket_arr*/
                    wp_send_json_error(['message' => implode('<br>', $bucket_arr->get_error_messages('exception'))]);
                    wp_die();
                }

                $html = '';
                foreach ($bucket_arr as $bucket_name) {
                    $html .= '<option value="'. $bucket_name .'">'. $bucket_name . '</option>';
                }

                wp_send_json_success(['html' => $html]);
            }
        } else {
            wp_send_json_error(['message' => __('Invalid request', 'ultimate-media-on-the-cloud')]);
        }

        wp_die();
    }

    /**
     * Check if logged user can access file manager
     * @param WP_User $user
     * @return bool
     */
    public function isUserCanAccess($user)
    {
        $ucm_configs = $this->loadUcmPluginConfigs();
        if ($user->ID === (int)get_option($ucm_configs['plugin_db_prefix'] .'fm_super_user')) {
            return true;
        }

        $fm_allowed_roles = get_option($ucm_configs['plugin_db_prefix'] .'fm_allowed_roles');
        if (!$fm_allowed_roles) {
            return false;
        }
        $fm_restricted_users = get_option($ucm_configs['plugin_db_prefix'] .'fm_restricted_users');
        $fm_restricted_users = $fm_restricted_users ? explode(',', $fm_restricted_users) : [];

        $fm_allowed_roles = explode(',', $fm_allowed_roles);
        $u_roles = (array)$user->roles;

        foreach ($u_roles as $u_role) {
            if (in_array($u_role, $fm_allowed_roles, false)) {
                if (!$fm_restricted_users) {
                    return true;
                }

                if ($fm_restricted_users && !in_array($user->user_login, $fm_restricted_users, false)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Auto import Wordpress media for files
     * @throws Exception
     */
    public function importUploadMedias()
    {
        $ucm = new PhpRockets_UltimateMedia_Root();
        if ($ucm::isPost()) {
            $files = $ucm::getPost('files');

            /* Perform inputs validation */
            $validation = new Validation();
            $validation->validation_rules([
                'files' => 'required'
            ]);

            $validation::set_error_messages([
                'validate_required' => __('There is no file to be imported', 'ultimate-media-on-the-cloud')
            ]);
            $validated = $validation->run(['files' => $files]);
            if ($validated === false) {
                wp_send_json_error(['message' => implode('<br>', $validation->get_errors_array())]);
                wp_die();
            }

            $wp_upload_dir = wp_upload_dir();
            foreach ($files as $file) {
                if ($this->_isUrlInsideUploadDir($wp_upload_dir, $file)) {
                    $file_upload_path = str_replace($wp_upload_dir['baseurl'], '', $file);
                    $filename = $wp_upload_dir['basedir'] . $file_upload_path;

                    // Check the type of file. We'll use this as the 'post_mime_type'.
                    $filetype = wp_check_filetype( basename( $file ), null );

                    // Get the path to the upload directory.
                    $wp_upload_dir = wp_upload_dir();

                    // Prepare an array of post data for the attachment.
                    $attachment = array(
                        'guid'           => $wp_upload_dir['url'] . '/' . basename( $file_upload_path ),
                        'post_mime_type' => $filetype['type'],
                        'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $filename ) ),
                        'post_content'   => '',
                        'post_status'    => 'inherit'
                    );
                    $attachment_id = wp_insert_attachment($attachment, $filename);
                    if (is_wp_error($attachment_id)) {
                        wp_send_json_error(['message' => implode('<br>', $attachment_id->get_error_messages())]);
                        wp_die();
                    }

                    /* Unhook the Cloud upload */
                    apply_filters('ucm_set_hook_service', false);

                    /* Generate the attachment thumbnail */
                    $new_metadata = wp_generate_attachment_metadata($attachment_id, get_attached_file($attachment_id));
                    wp_update_attachment_metadata($attachment_id, $new_metadata );
                }
            }

            wp_send_json_success(['message' => __('Medias imported to system.', 'ultimate-media-on-the-cloud')]);
        } else {
            wp_send_json_error(['message' => __('Invalid request', 'ultimate-media-on-the-cloud')]);
        }

        wp_die();
    }

    /**
     * Update the cloud file ACL permission
     *
     * @return void
     * @throws Exception
     */
    public function updateFileAcl() {
        $ucm = new PhpRockets_UltimateMedia_Root();
        if ($ucm::isPost()) {
            $account_id = $ucm::getPost('account_id');
            $bucket = $ucm::getPost('bucket');
            $acl_type = $ucm::getPost('type');
            $files = $ucm::getPost('files');

            /* Perform inputs validation */
            $validation = new Validation();
            $validation->validation_rules([
                'account_id' => 'required',
                'bucket' => 'required',
                'acl_type' => 'required'
            ]);

            $validated = $validation->run([
                'account_id' => $account_id,
                'bucket' => $bucket,
                'acl_type' => $acl_type
            ]);
            if ($validated === false) {
                wp_send_json_error(['message' => implode('<br>', $validation->get_errors_array())]);
                wp_die();
            }

            /* Fetch database account */
            $account = PhpRockets_Model_Accounts::query([
                'conditions' => [
                    'id' => $account_id
                ]
            ]);
            if (!$account) {
                wp_send_json_error(['message' => __('Cloud account is not found', 'ultimate-media-on-the-cloud')]);
                wp_die();
            }
            foreach ($files as $key => $file) {
                $files[$key] = str_replace($bucket .'/', '', $file);
            }

            /** @var PhpRockets_UCM_Addons $account_addon */
            $account_addon = new $account['addon_class'];
            if ($acl_type === 'public') {
                $result = $account_addon->updateObjectsAcl($account, $files, $bucket);
            } else {
                $result = $account_addon->updateObjectsAcl($account, $files, $bucket, 'private');
            }

            if (is_wp_error($result)) {
                /** @var WP_Error $result */
                wp_send_json_error(['message' => implode('<br>', $result->get_error_messages())]);
                wp_die();
            }

            wp_send_json_success(['message' => __('Objects permission updated.', 'ultimate-media-on-the-cloud')]);
        } else {
            wp_send_json_error(['message' => __('Invalid request', 'ultimate-media-on-the-cloud')]);
        }

        wp_die();
    }

    /**
     * Check if file upload url inside WP upload dir
     *
     * @param [] $wp_upload_dir
     * @param string $url
     * @return bool
     */
    private function _isUrlInsideUploadDir($wp_upload_dir, $url)
    {
        $base_dir = $wp_upload_dir['basedir'];
        $upload_dir = str_replace(ABSPATH, '', $base_dir);

        return (false !== strpos($url, $upload_dir));
    }

    /**
     * Send the error to elFinder
     * @param $content
     * @return bool
     */
    public static function sendElFinderError($content)
    {
        _e(json_encode(['norError' => $content]));
        wp_die();

        return false;
    }

    /**
     * @return array
     */
    private static function _getAvailableThemes()
    {
        return self::$data['configs']['themes'];
    }

    /**
     * @return array
     */
    private static function _getThemesAlias()
    {
        return self::$data['configs']['themes_alias'];
    }

    /**
     * @return array
     */
    private static function _getAvailableFmLanguages() {
        return self::$data['configs']['languages'];
    }

    /**
     * @param        $file
     * @param string $alt
     * @param int    $width
     * @return string
     */
    private static function _editorScreenShot($file, $alt = '', $width = 350)
    {
        return '<div class="ucm-fm-ui-preview is-active">'.
            '<a href="'. self::getAddOnData('addon_url') .'assets/images/editors/'. $file .'" class="foobox">'.
            '<img src="'. self::getAddOnData('addon_url') .'assets/images/editors/'. $file .'" alt="'. $alt .'" width="'. $width .'">'.
            '</a>'.
            '</div>';
    }

    /**
     * Do the action when activating the plugin
     * @return void
     */
    public static function whileActivation()
    {
        if (UCM_FM_LOADED) {
            $ucm = new PhpRockets_UltimateMedia();
            $init_options = self::$data['configs']['init_options'];
            /*! INIT FM SETTINGS */
            foreach ($init_options as $option_name => $option_value) {
                if (!get_option($ucm::$configs->plugin_db_prefix . $option_name)) {
                    if (is_array($option_value)) {
                        $ucm::ucmUpdateOption($option_name, implode(',', $option_value));
                    } else {
                        $ucm::ucmUpdateOption($option_name, $option_value);
                    }
                }
            }
            /*! End init settings*/
            if (!get_option($ucm::$configs->plugin_db_prefix .'fm_super_user')) {
                $ucm::ucmUpdateOption('fm_super_user', get_current_user_id());
            }
        }
    }

    /**
     * Do the action when uninstalling the plugin
     * @return void
     */
    public static function whileUnInstall()
    {
        $ucm = new PhpRockets_UltimateMedia();
        $init_options = self::$data['configs']['init_options'];
        /*! DELETE INIT FM SETTINGS */
        foreach ($init_options as $option_name => $option_value) {
            $ucm::ucmDeleteOption($option_name);
        }
    }
}