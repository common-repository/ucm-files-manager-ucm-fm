<?php /**@var PhpRockets_UltimateMedia_Root $ucm */ /**@var PhpRockets_Model_Accounts $account */ ?>
<?php
    $self_addon = new PhpRockets_UCM_FileManager_AddOn();
    if ($account) {
        $account_data = unserialize($account['value']);
    }
    /* Get Settings */
    $fm_locale = $ucm::ucmGetOption('fm_locale', 'en');
    $fm_file_layout = $ucm::ucmGetOption('fm_file_layout', 'list');
    $fm_edit_file_types = $ucm::ucmGetOption('fm_edit_file_types');
    $fm_custom_edit_file_types = $ucm::ucmGetOption('fm_custom_edit_file_types');
    if ($fm_custom_edit_file_types) {
        $fm_edit_file_types = $fm_edit_file_types ? $fm_edit_file_types .','. $fm_custom_edit_file_types : $fm_custom_edit_file_types;
    }
    $fm_import_media = $ucm::ucmGetOption('fm_import_media', false);
    $ucm_fm = 'ucm-file-manager';
    $force_reload = 0;
    if ($account) {
        $force_reload = 1;
    }
?>
<script>
    var ucm_fm_element = '#<?php echo $ucm_fm ?>',
        fm_force_reload = <?php echo $force_reload ?>;
    define('elFinderConfig', {
        opts: {
            defaultView: '<?php echo $fm_file_layout ?>',
        },
        defaultOpts : {
            url : phprockets_ucm_fm.clientConnect,
            customData: {
                storage_type: <?php echo $account ? 1 : 0 ?>,
                <?php if ($account) : ?>
                account_id: '<?php echo $account['id'] ?>',
                bucket: '<?php echo $selected_bucket ?>'
                <?php endif ?>
            },
            defaultView: '<?php echo $fm_file_layout ?>',
            rememberLastDir: false,
            useBrowserHistory: false,
            lang : '<?php echo $fm_locale ?>',
            resizable: false,
            height: 550,
            uiOptions : {
                toolbar : [
                    ['back', 'forward'],
                    ['reload'],
                    ['home', 'up'],
                    ['mkdir', 'mkfile', 'upload'],
                    ['open', 'download', 'getfile'],
                    ['info'],
                    ['quicklook'],
                    ['copy', 'cut', 'paste'],
                    ['rm'],
                    ['duplicate', 'rename', 'edit'],
                    ['extract', 'archive'],
                    ['search'],
                    ['view'],
                    ['fullscreen']
                ],

                tree : {
                    openRootOnLoad : true,
                    syncTree : true
                },

                navbar : {
                    minWidth : 150,
                    maxWidth : 500
                },
                cwd : {
                    oldSchool : true,
                    listView: {
                        <?php if ($account) : ?>
                            columns : ['cloudpermission', 'size', 'kind', 'date'],
                            columnsCustomName : {
                                cloudpermission : 'Permission'
                            }
                        <?php else : ?>
                            columns : ['perm', 'size', 'kind', 'date'],
                        <?php endif ?>
                    }
                },
            },
            contextmenu : {
                // navbarfolder menu
                navbar : ['reload', 'open', '|', 'upload', 'mkdir', 'mkfile', 'copy', 'cut', 'paste', 'duplicate', '|', 'rm', '|', 'info'],

                // current directory menu
                cwd    : ['reload', 'back', '|', 'upload', 'mkdir', 'mkfile', 'paste', '|', 'info'],

                // current directory file menu
                files  : [
                    'getfile', '|','open', 'quicklook', '|', 'download', '|', 'copy', 'cut', 'paste', 'duplicate', '|',
                    'rm', '|', 'edit', 'rename', 'resize', '|', 'archive', 'extract', '|', 'info', 'permission', '|', 'cloudpermissionprivate', 'cloudpermissionpublic'
                ]
            },
            cssAutoLoad : false, // Disable CSS auto loading

            commandsOptions : {
                edit: {
                    extraOptions : {
                        // set API key to enable Creative Cloud image editor
                        // see https://console.adobe.io/
                        creativeCloudApiKey : '',
                        // browsing manager URL for CKEditor, TinyMCE
                        // uses self location with the empty value
                        managerUrl : '',
                        pixo: {
                            apikey: '<?php echo $ucm::ucmGetOption('fm_pixo_app_key') ?>'
                        }
                    },
                    mimes : [<?php if ($fm_edit_file_types) : $fm_edit_file_types = explode(',', $fm_edit_file_types); ?><?php foreach ($fm_edit_file_types as $fm_edit_file_type) : ?>'<?php echo $fm_edit_file_type ?>',<?php endforeach ?><?php endif ?>],
                }
                ,quicklook : {
                    // to enable CAD-Files and 3D-Models preview with sharecad.org
                    sharecadMimes : ['image/vnd.dwg', 'image/vnd.dxf', 'model/vnd.dwf', 'application/vnd.hp-hpgl', 'application/plt', 'application/step', 'model/iges', 'application/vnd.ms-pki.stl', 'application/sat', 'image/cgm', 'application/x-msmetafile'],
                    // to enable preview with Google Docs Viewer
                    googleDocsMimes : ['application/pdf', 'image/tiff', 'application/vnd.ms-office', 'application/msword', 'application/vnd.ms-word', 'application/vnd.ms-excel', 'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.openxmlformats-officedocument.presentationml.presentation', 'application/postscript', 'application/rtf'],
                    // to enable preview with Microsoft Office Online Viewer
                    // these MIME types override "googleDocsMimes"
                    officeOnlineMimes : ['application/vnd.ms-office', 'application/msword', 'application/vnd.ms-word', 'application/vnd.ms-excel', 'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.openxmlformats-officedocument.presentationml.presentation', 'application/vnd.oasis.opendocument.text', 'application/vnd.oasis.opendocument.spreadsheet', 'application/vnd.oasis.opendocument.presentation']
                },
                info : {
                    custom : {
                        cloudpermission : {
                            label : 'Permission',
                            tpl : '<div class="elfinder-info-extra"><span class="elfinder-info-spinner"></span></div>',
                            action : function(file, fm, dialog) {
                                dialog.find('div.elfinder-info-extra').html(file.cloudpermission || 'None');
                            }
                        }
                    }
                }
            }
            // bootCalback calls at before elFinder boot up
            ,bootCallback : function(fm, extraObj) {
                /* any bind functions etc. */
                fm.bind('init', function() {
                    // any your code
                });
                // for example set document.title dynamically.
                var title = document.title;
                fm.bind('open', function() {
                    var path = '',
                        cwd  = fm.cwd();
                    if (cwd) {
                        path = fm.path(cwd.hash) || null;
                    }
                    document.title = path? path + ' : ' + title : title;
                    if (fm_force_reload) {
                        fm.exec('reload');
                        fm_force_reload = 0;
                    }
                }).bind('destroy', function() {
                    document.title = title;
                });

                <?php if ($fm_import_media && !$account) : ?>
                    var uploadFiles = [];
                    fm.bind('upload', function(e) {
                        if (e.data && e.data.added && e.data.added.length) {
                            var ntfNode = fm.getUI('notify');
                            uploadFiles = uploadFiles.concat(e.data.added);
                            setTimeout(function() {
                                var hasDialog = ntfNode.children('.elfinder-notify-upload').length? true : false;
                                var cnt;
                                if (! hasDialog && (cnt = uploadFiles.length)) {
                                    var files = [];
                                    for(var idx = 0;idx <= uploadFiles.length - 1;idx++) {
                                        files.push(uploadFiles[idx].url);
                                    }

                                    phpR_UCM.fm.wp.importMedias(files);
                                    uploadFiles = []; //Reset
                                }
                            }, 100);
                        }
                    });
                <?php endif ?>
            }
        },
        managers : {
            '<?php echo $ucm_fm ?>': {}
        }
    });
</script>