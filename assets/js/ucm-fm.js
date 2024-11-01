/*! Ultimate Media On The Cloud | (c) PhpRockets Team | File Manager */
phpR_UCM.fm = {
    force_reload: 0,
    /**
     * File manager main page
     * */
    setForceReload: function(val) {
        this.force_reload = val;
    },
    getForceReload: function() {
        return this.force_reload;
    },
    account: {
        switchStorage: function() {
            if (parseInt(jQuery('#ucm-fm-storage-type').val()) === 0) {
                window.location.href=phprockets_ucm_fm.ucm_fm_url;
            } else {
                var account_id = jQuery('#ucm-fm-account-id').val(), bucket = jQuery('#ucm-fm-bucket').val();
                if (!bucket) {
                    phpR_UCM.toast.show('error', phpR_UCM.l10n._e('fm', '_missing_bucket'));
                    return;
                }
                window.location.href= phprockets_ucm_fm.ucm_fm_url +'&account='+ account_id + '&bucket='+ bucket;
            }
        },
        /**
         * @param id Account ID
         * */
        reloadBuckets: function(id) {
            phpR_UCM.toast.hide();
            phpR_UCM.showLoading();
            jQuery.ajax({
                url: phprockets_ucm_fm.reloadBuckets,
                type: 'POST',
                dataType: 'json',
                data: {
                    account_id:id
                },
                timeout: 36000,
                success: function (response) {
                    if (response.success) {
                        jQuery('#ucm-fm-bucket').html(response.data.html);
                    } else {
                        phpR_UCM.toast.show('error', response.data.message);
                        jQuery('#ucm-fm-bucket').html('');
                    }
                    phpR_UCM.hideLoading();

                },
                error: function (xhr, status, errorThrown) {
                    phpR_UCM.toast.show('error', errorThrown);
                    jQuery('#ucm-fm-bucket').html('');
                    phpR_UCM.hideLoading();
                }
            });
        },
        storageChange: function(value) {
            var jSelector = jQuery('.ucm-field-hidden');
            if (parseInt(value) === 0) {
                jSelector.css('display', 'none');
                jSelector.next('.ucm-helptext').hide();
            } else {
                jSelector.css('display', 'flex');
                jSelector.next('.ucm-helptext').show();
            }
        },
        uploadAcl: function(elFinder, files, id, bucket, acl) {
            phpR_UCM.toast.hide();
            phpR_UCM.showLoading();
            jQuery.ajax({
                url: phprockets_ucm_fm.updateFileAcl,
                type: 'POST',
                dataType: 'json',
                data: {
                    account_id: id,
                    bucket: bucket,
                    type: acl,
                    files: files
                },
                timeout: 36000,
                success: function (response) {
                    if (response.success) {
                        phpR_UCM.toast.show('success', response.data.message);
                        phpR_UCM.fm.setForceReload(1);
                        elFinder.exec('reload');
                    } else {
                        phpR_UCM.toast.show('error', response.data.message);
                    }
                    phpR_UCM.hideLoading();

                },
                error: function (xhr, status, errorThrown) {
                    phpR_UCM.hideLoading();
                    phpR_UCM.toast.show('error', errorThrown);
                }
            });
        }
    },
    bindSideFolderOpen: function(hash, elfinder) {
        var url = window.location.href;
        if (url.search('bucket') >= 0) {
            var path = elfinder.path(hash);
            if (path.search('/') >= 0) {
                path = path.split('/');
                var bucket = path[0];
            } else {
                var bucket = path;
            }

            var fix_url = url.split('bucket=');
            if (bucket !== fix_url[1]) {
                fix_url = fix_url[0] + 'bucket='+ bucket;
                window.location.href = fix_url;
            }
        }
    },
    wp: {
        importMedias: function(obj) {
            phpR_UCM.toast.hide();
            phpR_UCM.showLoading();
            jQuery.ajax({
                url: phprockets_ucm_fm.importUploadMedias,
                type: 'POST',
                dataType: 'json',
                data: {
                    files:obj
                },
                timeout: 36000,
                success: function (response) {
                    if (response.success) {
                        phpR_UCM.toast.show('success', response.data.message);
                    } else {
                        phpR_UCM.toast.show('error', response.data.message);
                    }
                    phpR_UCM.hideLoading();
                },
                error: function (xhr, status, errorThrown) {
                    phpR_UCM.hideLoading();
                    phpR_UCM.toast.show('error', errorThrown);
                }
            });
        }
    },
    /*! End file manager page */

    /**
     * Settings Page
     * */
    saveSettings: function(jForm, url) {
        phpR_UCM.toast.hide();
        phpR_UCM.showLoading();
        jQuery.ajax({
            url: url,
            type: 'POST',
            dataType: 'json',
            data: jForm.serialize(),
            timeout: 36000,
            success: function (response) {
                if (response.success) {
                    phpR_UCM.toast.show('success', response.data.message);
                } else {
                    phpR_UCM.toast.show('error', response.data.message);
                }
                phpR_UCM.hideLoading();
            },
            error: function (xhr, status, errorThrown) {
                phpR_UCM.hideLoading();
                phpR_UCM.toast.show('error', errorThrown);
            }
        });
    },
    uiChange: function(val) {
        jQuery('.ucm-fm-ui-preview').hide();
        jQuery('.ucm-fm-ui-preview#fm-ui-'+ val).show();
    }
    /*! End Settings Page */
};