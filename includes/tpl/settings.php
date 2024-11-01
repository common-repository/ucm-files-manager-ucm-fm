<?php /**@var PhpRockets_UCM_FileManager_AddOn $self_addon */ /**@var PhpRockets_UltimateMedia $ucm */?>
<div class="columns mt10">
    <div class="ucm-settings column is-three-fifths">
        <div class="ucm-settings-body box column is-full has-background-white relative">
            <?php include ULTIMATE_MEDIA_PLG_DIR .'/includes/systems/tpl/common/box-loading-on-save.php' ?>
            <h1><i class="fa fa-cogs"> </i> <?php _e('UCM Files Manager - Settings', 'ultimate-media-on-the-cloud') ?></h1>
            <hr>
            <div class="tabs is-boxed">
                <ul>
                    <li class="is-active">
                        <a class="ucm-settings-nav" href="javascript:;" data-target="ucm-fm-general">
                            <span class="icon is-small"><i class="fas fa-cogs" aria-hidden="true"></i></span>
                            <span>General</span>
                        </a>
                    </li>
                    <li>
                        <a class="ucm-settings-nav" href="javascript:;" data-target="ucm-fm-roles">
                            <span class="icon is-small"><i class="fas fa-users" aria-hidden="true"></i></span>
                            <span>Roles</span>
                        </a>
                    </li>
                    <li>
                        <a class="ucm-settings-nav" href="javascript:;" data-target="ucm-fm-ui">
                            <span class="icon is-small"><i class="fas fa-desktop" aria-hidden="true"></i></span>
                            <span>Look & Feel</span>
                        </a>
                    </li>
                    <li>
                        <a class="ucm-settings-nav" href="javascript:;" data-target="ucm-fm-editors">
                            <span class="icon is-small"><i class="fas fa-cube" aria-hidden="true"></i></span>
                            <span>Inline Editor</span>
                        </a>
                    </li>
                    <li>
                        <a class="ucm-settings-nav" href="javascript:;" data-target="ucm-fm-advanced">
                            <span class="icon is-small"><i class="fas fa-cog" aria-hidden="true"></i></span>
                            <span>Advanced</span>
                        </a>
                    </li>
                </ul>
            </div>


            <?php apply_filters('ucm_fm_general_form', $ucm); ?>
            <?php apply_filters('ucm_fm_roles_form', $ucm); ?>
            <?php apply_filters('ucm_fm_ui_form', $ucm); ?>
            <?php apply_filters('ucm_fm_advanced_form', $ucm); ?>
            <div class="panel-body" id="ucm-fm-editors">
                <div class="ucm-accounts-tab-links">
                    <a class="is-active" href="javascript:;" data-target="ucm-edit-file-types" data-id="ucm-edit-file-types">
                        <i class="fa fa-tags"> </i> Allow edit file type
                    </a>
                    <a href="javascript:;" data-target="ucm-image-editors" data-id="ucm-image-editors">
                        <i class="fa fa-image"> </i> Image Editors
                    </a>
                    <a href="javascript:;" data-target="ucm-image-editors" data-id="ucm-code-editors">
                        <i class="fa fa-image"> </i> Text / Code Editors
                    </a>
                </div>
                <?php apply_filters('ucm_fm_file_type_form', $ucm); ?>
                <?php apply_filters('ucm_fm_image_editors_form', $ucm); ?>
                <?php apply_filters('ucm_fm_code_editors_form', $ucm); ?>
            </div>
            <br>
            <p align="right">
                <strong><i><?php _e('Plugin Version '. $ucm::$configs->getUcmConfig('current_version'), 'ultimate-media-on-the-cloud') ?></i></strong>
                <a class="button is-success" href="<?php echo admin_url() .'admin.php?page='. $ucm::$configs->getMenuSlug('file_manager') ?>"><?php _e('Back to File Manager', 'ultimate-media-on-the-cloud') ?></a>
            </p>
        </div>
    </div>
    <?php include ULTIMATE_MEDIA_PLG_DIR .'/includes/systems/tpl/common/news.php' ?>
</div>
<?php include ULTIMATE_MEDIA_PLG_DIR .'/includes/systems/tpl/common/toast-message.php' ?>