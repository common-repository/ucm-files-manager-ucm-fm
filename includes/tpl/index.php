<?php /**@var PhpRockets_Model_Accounts $account*/ /**@var PhpRockets_UltimateMedia $ucm */?>
<?php
    apply_filters('ucm_fm_style', $ucm);
    apply_filters('ucm_fm_require', $ucm);
    apply_filters('ucm_fm_init', $account, $selected_bucket);
?>
<div class="columns mt10">
    <div class="ucm-settings column is-three-fifths">
        <div class="ucm-settings-body box column is-full has-background-white relative">
            <?php include ULTIMATE_MEDIA_PLG_DIR .'/includes/systems/tpl/common/box-loading-on-save.php' ?>
            <h1><i class="dashicons dashicons-editor-justify"> </i> <?php _e('Ultimate Media On The Cloud - Files Manager', 'ultimate-media-on-the-cloud') ?></h1>
            <hr>
            <div id="ucm-file-manager"></div>
            <br>
            <p align="right">
                <strong><i><?php _e('Plugin Version '. $ucm::$configs->getUcmConfig('current_version'), 'ultimate-media-on-the-cloud') ?></i></strong>
                <a class="button is-success" href="<?php echo admin_url() .'admin.php?page='. $ucm::$configs->getMenuSlug('menu_main') ?>"><?php _e('Back to Settings', 'ultimate-media-on-the-cloud') ?></a>
            </p>
        </div>
    </div>
    <div class="column is-one-third ml10">
        <div class="ucm-settings-body box has-background-white is-full relative">
            <?php include ULTIMATE_MEDIA_PLG_DIR .'/includes/systems/tpl/common/box-loading-on-save.php' ?>
            <h1><i class="dashicons dashicons-editor-justify"> </i> <?php _e('Target Location', 'ultimate-media-on-the-cloud') ?></h1>
            <hr>
            <?php echo $form ?>
        </div>
        <?php if (get_current_user_id() === (int)get_option($ucm::$configs->plugin_db_prefix .'fm_super_user')) : ?>
        <div class="ucm-settings-body box has-background-white is-full">
            <p><?php _e('<strong>Advanced File Manager Settings</strong> check the section below.', 'ultimate-media-on-the-cloud') ?></p>
            <a class="button is-info" href="<?php echo admin_url() .'admin.php?page='. $ucm::$configs->getMenuSlug('file_manager') ?>&ucm-tab=settings"><?php _e('Files Manager Settings', 'ultimate-media-on-the-cloud') ?></a>
        </div>
        <?php endif ?>
    </div>
</div>
<?php include ULTIMATE_MEDIA_PLG_DIR .'/includes/systems/tpl/common/toast-message.php' ?>
