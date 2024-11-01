(function(editors, elFinder) {
	if (typeof define === 'function' && define.amd) {
		define(['elfinder'], editors);
	} else if (elFinder) {
		var optEditors = elFinder.prototype._options.commandsOptions.edit.editors;
		elFinder.prototype._options.commandsOptions.edit.editors = optEditors.concat(editors(elFinder));
	}
}(function(elFinder) {
	"use strict";
	var apps = {},
		// get query of getfile
		getfile = window.location.search.match(/getfile=([a-z]+)/),
		useRequire = elFinder.prototype.hasRequire,
		hasFlash = (function() {
			var hasFlash;
			try {
				hasFlash = !!(new ActiveXObject('ShockwaveFlash.ShockwaveFlash'));
			} catch (e) {
				hasFlash = !!(typeof window.orientation === 'undefined' || (navigator && navigator.mimeTypes["application/x-shockwave-flash"]));
			}
			return hasFlash;
		})(),
		ext2mime = {
			bmp: 'image/x-ms-bmp',
			dng: 'image/x-adobe-dng',
			gif: 'image/gif',
			jpeg: 'image/jpeg',
			jpg: 'image/jpeg',
			pdf: 'application/pdf',
			png: 'image/png',
			ppm: 'image/x-portable-pixmap',
			psd: 'image/vnd.adobe.photoshop',
			pxd: 'image/x-pixlr-data',
			svg: 'image/svg+xml',
			tiff: 'image/tiff',
			webp: 'image/webp',
			xcf: 'image/x-xcf',
			sketch: 'application/x-sketch',
			php: 'text/x-php',
			phpx: 'application/x-php'
		},
		mime2ext,
		getExtention = function(mime, fm) {
			if (!mime2ext) {
				mime2ext = fm.arrayFlip(ext2mime);
			}
			var ext = mime2ext[mime] || fm.mimeTypes[mime];
			if (ext === 'jpeg') {
				ext = 'jpg';
			}
			return ext;
		},
		changeImageType = function(src, toMime) {
			var dfd = $.Deferred();
			try {
				var canvas = document.createElement('canvas'),
					ctx = canvas.getContext('2d'),
					img = new Image(),
					conv = function() {
						var url = canvas.toDataURL(toMime),
							mime, m;
						if (m = url.match(/^data:([a-z0-9]+\/[a-z0-9.+-]+)/i)) {
							mime = m[1];
						} else {
							mime = '';
						}
						if (mime.toLowerCase() === toMime.toLowerCase()) {
							dfd.resolve(canvas.toDataURL(toMime), canvas);
						} else {
							dfd.reject();
						}
					};

				img.src = src;
				$(img).on('load', function() {
					try {
						canvas.width = img.width;
						canvas.height = img.height;
						ctx.drawImage(img, 0, 0);
						conv();
					} catch(e) {
						dfd.reject();
					}
				}).on('error', function () {
					dfd.reject();
				});
				return dfd;
			} catch(e) {
				return dfd.reject();
			}
		},
		initImgTag = function(id, file, content, fm) {
			var node = $(this).children('img:first').data('ext', getExtention(file.mime, fm)),
				spnr = $('<div class="elfinder-edit-spinner elfinder-edit-image"/>')
					.html('<span class="elfinder-spinner-text">' + fm.i18n('ntfloadimg') + '</span><span class="elfinder-spinner"/>')
					.hide()
					.appendTo(this),
				url;
			
			if (!content.match(/^data:/)) {
				url = fm.openUrl(file.hash);
				node.attr('_src', content);
			}
			node.attr('id', id+'-img')
				.attr('src', url || content)
				.css({'height':'', 'max-width':'100%', 'max-height':'100%', 'cursor':'pointer'})
				.data('loading', function(done) {
					var btns = node.closest('.elfinder-dialog').find('button,.elfinder-titlebar-button');
					btns.prop('disabled', !done)[done? 'removeClass' : 'addClass']('ui-state-disabled');
					node.css('opacity', done? '' : '0.3');
					spnr[done? 'hide' : 'show']();
					return node;
				});
		},
		imgBase64 = function(node, mime) {
			var style = node.attr('style'),
				img, canvas, ctx, data;
			try {
				// reset css for getting image size
				node.attr('style', '');
				// img node
				img = node.get(0);
				// New Canvas
				canvas = document.createElement('canvas');
				canvas.width  = img.width;
				canvas.height = img.height;
				// restore css
				node.attr('style', style);
				// Draw Image
				canvas.getContext('2d').drawImage(img, 0, 0);
				// To Base64
				data = canvas.toDataURL(mime);
			} catch(e) {
				data = node.attr('src');
			}
			return data;
		},
		pixlrCallBack = function() {
			if (!hasFlash || window.parent === window) {
				return;
			}
			var pixlr = window.location.search.match(/[?&]pixlr=([^&]+)/),
				image = window.location.search.match(/[?&]image=([^&]+)/),
				p, ifm, url, node, ext;
			if (pixlr) {
				// case of redirected from pixlr.com
				p = window.parent;
				ifm = p.$('#'+pixlr[1]+'iframe').hide();
				node = p.$('#'+pixlr[1]).data('resizeoff')();
				if (image[1].substr(0, 4) === 'http') {
					url = image[1];
					ext = url.replace(/.+\.([^.]+)$/, '$1');
					if (node.data('ext') !== ext) {
						node.closest('.ui-dialog').trigger('changeType', {
							extention: ext,
							mime : ext2mime[ext]
						});
					}
					if (window.location.protocol === 'https:') {
						url = url.replace(/^http:/, 'https:');
					}
					node.on('load error', function() {
							node.data('loading')(true);
						})
						.attr('src', url)
						.data('loading')();
				} else {
					node.data('loading')(true);
				}
				ifm.trigger('destroy').remove();
			}
		},
		pixlrSetup = function(opts, fm) {
			if (!hasFlash || fm.UA.ltIE8) {
				this.disabled = true;
			}
		},
		pixlrLoad = function(mode, base) {
			var self = this,
				fm = this.fm,
				clPreventBack = fm.res('class', 'preventback'),
				node = $(base).children('img:first')
					.data('loading')()
					.data('resizeoff', function() {
						$(window).off('resize.'+node.attr('id'));
						dialog.addClass(clPreventBack);
						return node;
					})
					.on('click', function() {
						launch();
					}),
				dialog = $(base).closest('.ui-dialog'),
				elfNode = fm.getUI(),
				uiToast = fm.getUI('toast'),
				container = $('<iframe class="ui-front" allowtransparency="true">'),
				file = this.file,
				timeout = 15,
				error = function(error) {
					if (error) {
						container.trigger('destroy').remove();
						node.data('loading')(true);
						fm.error(error);
					} else {
						uiToast.appendTo(dialog.closest('.ui-dialog'));
						fm.toast({
							mode: 'info',
							msg: 'Can not launch Pixlr yet. Waiting ' + timeout + ' seconds.',
							button: {
								text: 'Abort',
								click: function() {
									container.trigger('destroy').remove();
									node.data('loading')(true);
								}
							},
							onHidden: function() {
								uiToast.children().length === 1 && uiToast.appendTo(fm.getUI());
							}
						});
						errtm = setTimeout(error, timeout * 1000);
					}
				},
				launch = function() {
					var src = 'https://pixlr.com/'+mode+'/?s=c',
						myurl = window.location.href.toString().replace(/#.*$/, ''),
						opts = {};

					errtm = setTimeout(error, timeout * 1000);
					myurl += (myurl.indexOf('?') === -1? '?' : '&') + 'pixlr='+node.attr('id');
					src += '&referrer=elFinder&locktitle=true';
					src += '&exit='+encodeURIComponent(myurl+'&image=0');
					src += '&target='+encodeURIComponent(myurl);
					src += '&title='+encodeURIComponent(file.name);
					src += '&image='+encodeURIComponent(node.attr('_src'));
					
					opts.src = src;
					opts.css = {
						width: '100%',
						height: $(window).height()+'px',
						position: 'fixed',
						display: 'block',
						backgroundColor: 'transparent',
						border: 'none',
						top: 0,
						right: 0
					};

					// trigger event 'editEditorPrepare'
					self.trigger('Prepare', {
						node: base,
						editorObj: void(0),
						instance: container,
						opts: opts
					});

					container
						.attr('id', node.attr('id')+'iframe')
						.attr('src', opts.src)
						.css(opts.css)
						.one('load', function() {
							errtm && clearTimeout(errtm);
							setTimeout(function() {
								if (container.is(':hidden')) {
									error('Please disable your ad blocker.');
								}
							}, 1000);
							dialog.addClass(clPreventBack);
							fm.toggleMaximize(container, true);
							fm.toFront(container);
						})
						.on('destroy', function() {
							fm.toggleMaximize(container, false);
						})
						.on('error', error)
						.appendTo(elfNode.hasClass('elfinder-fullscreen')? elfNode : 'body');
				},
				errtm;
			$(base).on('saveAsFail', launch);
			launch();
		},
		iframeClose = function(ifm) {
			var $ifm = $(ifm),
				dfd = $.Deferred().always(function() {
					$ifm.off('load', load);
				}),
				ab = 'about:blank',
				chk = function() {
					tm = setTimeout(function() {
						var src;
						try {
							src = base.contentWindow.location.href;
						} catch(e) {
							src = null;
						}
						if (src === ab) {
							dfd.resolve();
						} else if (--cnt > 0){
							chk();
						} else {
							dfd.reject();
						}
					}, 500);
				},
				load = function() {
					tm && clearTimeout(tm);
					dfd.resolve();
				},
				cnt = 20, // 500ms * 20 = 10sec wait
				tm;
			$ifm.one('load', load);
			ifm.src = ab;
			chk();
			return dfd;
		};
	
	// check callback from pixlr
	pixlrCallBack();
	
	// check getfile callback function
	if (getfile) {
		getfile = getfile[1];
		if (getfile === 'ckeditor') {
			elFinder.prototype._options.getFileCallback = function(file, fm) {
				window.opener.CKEDITOR.tools.callFunction((function() {
					var reParam = new RegExp('(?:[\?&]|&amp;)CKEditorFuncNum=([^&]+)', 'i'),
						match = window.location.search.match(reParam);
					return (match && match.length > 1) ? match[1] : '';
				})(), fm.convAbsUrl(file.url));
				fm.destroy();
				window.close();
			};
		}
	}

	var ucm_fm_editors = JSON.parse(phprockets_ucm_fm.ucm_fm_settings_active_editors);
	var fm_editors = [];

	if (parseInt(ucm_fm_editors.fm_photopea_editor_enable) && ucm_fm_editors.fm_image_edit_file_types !== '') {
		fm_editors.push({
			// Photopea advanced image editor
			info : {
				id : 'photopea',
				name : 'Edit With Photopea',
				iconImg : 'img/editor-icons.png 0 -160',
				single: true,
				noContent: true,
				arrayBufferContent: true,
				openMaximized: true,
				canMakeEmpty: true,
				integrate: {
					title: 'Photopea',
					link: 'https://www.photopea.com/learn/'
				}
			},
			mimes : ucm_fm_editors.fm_image_edit_file_types.split(','),
			html : '<iframe style="width:100%;height:100%;border:none;"></iframe>',
			// setup on elFinder bootup
			setup : function(opts, fm) {
				if (fm.UA.IE || fm.UA.Mobile) {
					this.disabled = true;
				}
			},
			// Initialization of editing node (this: this editors HTML node)
			init : function(id, file, dum, fm) {
				var orig = 'https://www.photopea.com',
					ifm = $(this).hide()
					//.css('box-sizing', 'border-box')
						.on('load', function() {
							//spnr.remove();
							ifm.show();
						})
						.on('error', function() {
							spnr.remove();
							ifm.show();
						}),
					editor = this.editor,
					confObj = editor.confObj,
					spnr = $('<div class="elfinder-edit-spinner elfinder-edit-photopea"/>')
						.html('<span class="elfinder-spinner-text">' + fm.i18n('nowLoading') + '</span><span class="elfinder-spinner"/>')
						.appendTo(ifm.parent()),
					saveTypes = fm.arrayFlip(['jpg', 'png', 'gif', 'bmp', 'tiff', 'webp']),
					getType = function(mime) {
						var ext = getExtention(mime, fm),
							extmime = ext2mime[ext];

						if (!confObj.mimesFlip[extmime]) {
							ext = '';
						} else if (ext === 'jpeg') {
							ext = 'jpg';
						}
						if (!ext || !saveTypes[ext]) {
							ext = 'psd';
							extmime = ext2mime[ext];
							ifm.closest('.ui-dialog').trigger('changeType', {
								extention: ext,
								mime : extmime,
								keepEditor: true
							});
						}
						return ext;
					},
					mime = file.mime,
					liveMsg, type, quty;

				if (!confObj.mimesFlip) {
					confObj.mimesFlip = fm.arrayFlip(confObj.mimes, true);
				}
				if (!confObj.liveMsg) {
					confObj.liveMsg = function(ifm, spnr, file) {
						var url = fm.openUrl(file.hash);
						if (!fm.isSameOrigin(url)) {
							url = fm.openUrl(file.hash, true);
						}
						var wnd = ifm.get(0).contentWindow,
							phase = 0,
							data = null,
							dfdIni = $.Deferred().done(function() {
								spnr.remove();
								phase = 1;
								wnd.postMessage(data, '*');
							}),
							dfdGet;

						this.load = function() {
							return fm.request({
								data    : {cmd : 'get'},
								options : {
									url: url,
									type: 'get',
									cache : true,
									dataType : 'binary',
									responseType :'arraybuffer',
									processData: false
								}
							})
								.done(function(d) {
									data = d;
								});
						};

						this.receive = function(e) {
							var ev = e.originalEvent,
								state;
							if (ev.origin === orig && ev.source === wnd) {
								if (ev.data === 'done') {
									if (phase === 0) {
										dfdIni.resolve();
									} else if (phase === 1) {
										phase = 2;
										ifm.trigger('contentsloaded');
									} else {
										if (dfdGet && dfdGet.state() === 'pending') {
											dfdGet.reject('errDataEmpty');
										}
									}
								} else {
									if (dfdGet && dfdGet.state() === 'pending') {
										if (typeof ev.data === 'object') {
											dfdGet.resolve('data:' + mime + ';base64,' + fm.arrayBufferToBase64(ev.data));
										} else {
											dfdGet.reject('errDataEmpty');
										}
									}
								}
							}
						};

						this.getContent = function() {
							var type, q;
							if (phase > 1) {
								dfdGet && dfdGet.state() === 'pending' && dfdGet.reject();
								dfdGet = null;
								dfdGet = $.Deferred();
								if (phase === 2) {
									phase = 3;
									dfdGet.resolve('data:' + mime + ';base64,' + fm.arrayBufferToBase64(data));
									data = null;
									return dfdGet;
								}
								if (ifm.data('mime')) {
									mime = ifm.data('mime');
									type = getType(mime);
								}
								if (q = ifm.data('quality')) {
									type += ':' + (q / 100);
								}
								wnd.postMessage('app.activeDocument.saveToOE("' + type + '")', orig);
								return dfdGet;
							}
						};
					};
				}

				ifm.parent().css('padding', 0);
				type = getType(file.mime);
				liveMsg = editor.liveMsg = new confObj.liveMsg(ifm, spnr, file);
				$(window).on('message.' + fm.namespace, liveMsg.receive);
				liveMsg.load().done(function() {
					var d = JSON.stringify({
						files : [],
						environment : {
							lang: fm.lang.replace(/_/g, '-')
						}
					});
					ifm.attr('src', orig + '/#' + encodeURI(d));
				}).fail(function(err) {
					err && fm.error(err);
					editor.initFail = true;
				});

				// jpeg quality controls
				if (file.mime === 'image/jpeg' || file.mime === 'image/webp') {
					ifm.data('quality', fm.storage('jpgQuality') || fm.option('jpgQuality'));
					quty = $('<input type="number" class="ui-corner-all elfinder-resize-quality elfinder-tabstop"/>')
						.attr('min', '1')
						.attr('max', '100')
						.attr('title', '1 - 100')
						.on('change', function() {
							var q = quty.val();
							ifm.data('quality', q);
						})
						.val(ifm.data('quality'));
					$('<div class="ui-dialog-buttonset elfinder-edit-extras elfinder-edit-extras-quality"/>')
						.append(
							$('<span>').html(fm.i18n('quality') + ' : '), quty, $('<span/>')
						)
						.prependTo(ifm.parent().next());
				}
			},
			load : function(base) {
				var dfd = $.Deferred(),
					self = this,
					fm = this.fm,
					$base = $(base);
				if (self.initFail) {
					dfd.reject();
				} else {
					$base.on('contentsloaded', function() {
						dfd.resolve(self.liveMsg);
					});
				}
				return dfd;
			},
			getContent : function() {
				return this.editor.liveMsg? this.editor.liveMsg.getContent() : void(0);
			},
			save : function(base, liveMsg) {
				var $base = $(base),
					quality = $base.data('quality'),
					hash = $base.data('hash'),
					file;
				if (typeof quality !== 'undefined') {
					this.fm.storage('jpgQuality', quality);
				}
				if (hash) {
					file = this.fm.file(hash);
					$base.data('mime', file.mime);
				} else {
					$base.removeData('mime');
				}
			},
			// On dialog closed
			close : function(base, liveMsg) {
				$(base).attr('src', '');
				liveMsg && $(window).off('message.' + this.fm.namespace, liveMsg.receive);
			}
		});
	}

	if (parseInt(ucm_fm_editors.fm_tui_editor_enable) && ucm_fm_editors.fm_image_edit_file_types !== '') {
		fm_editors.push({
			// tui.image-editor - https://github.com/nhnent/tui.image-editor
			info : {
				id: 'tuiimgedit',
				name: 'Edit With TUI',
				iconImg: 'img/editor-icons.png 0 -48',
				dataScheme: true,
				schemeContent: true,
				openMaximized: true,
				canMakeEmpty: false,
				integrate: {
					title: 'TOAST UI Image Editor',
					link: 'http://ui.toast.com/tui-image-editor/'
				}
			},
			// MIME types to accept
			mimes : ucm_fm_editors.fm_image_edit_file_types.split(','),
			// HTML of this editor
			html : '<div class="elfinder-edit-imageeditor"><canvas></canvas></div>',
			// called on initialization of elFinder cmd edit (this: this editor's config object)
			setup : function(opts, fm) {
				if (fm.UA.ltIE8 || fm.UA.Mobile) {
					this.disabled = true;
				} else {
					this.opts = Object.assign({
						version: 'latest'
					}, opts.extraOptions.tuiImgEditOpts || {}, {
						iconsPath : fm.baseUrl + 'img/tui-',
						theme : {}
					});
					if (!fm.isSameOrigin(this.opts.iconsPath)) {
						this.disabled = true;
						fm.debug('warning', 'Setting `commandOptions.edit.extraOptions.tuiImgEditOpts.iconsPath` MUST follow the same origin policy.');
					}
				}
			},
			// Initialization of editing node (this: this editors HTML node)
			init : function(id, file, content, fm) {
				this.data('url', content);
			},
			load : function(base) {
				var self = this,
					fm   = this.fm,
					dfrd = $.Deferred(),
					cdns = fm.options.cdns,
					ver  = self.confObj.opts.version,
					init = function(editor) {
						var $base = $(base),
							bParent = $base.parent(),
							opts = self.confObj.opts,
							iconsPath = opts.iconsPath,
							tmpContainer = $('<div class="tui-image-editor-container">').appendTo(bParent),
							tmpDiv = [
								$('<div class="tui-image-editor-submenu"/>').appendTo(tmpContainer),
								$('<div class="tui-image-editor-controls"/>').appendTo(tmpContainer)
							],
							iEditor = new editor(base, {
								includeUI: {
									loadImage: {
										path: $base.data('url'),
										name: self.file.name
									},
									theme: Object.assign(opts.theme, {
										'menu.normalIcon.path': iconsPath + 'icon-d.svg',
										'menu.normalIcon.name': 'icon-d',
										'menu.activeIcon.path': iconsPath + 'icon-b.svg',
										'menu.activeIcon.name': 'icon-b',
										'menu.disabledIcon.path': iconsPath + 'icon-a.svg',
										'menu.disabledIcon.name': 'icon-a',
										'menu.hoverIcon.path': iconsPath + 'icon-c.svg',
										'menu.hoverIcon.name': 'icon-c',
										'submenu.normalIcon.path': iconsPath + 'icon-d.svg',
										'submenu.normalIcon.name': 'icon-d',
										'submenu.activeIcon.path': iconsPath + 'icon-c.svg',
										'submenu.activeIcon.name': 'icon-c'
									}),
									initMenu: 'filter',
									menuBarPosition: 'bottom'
								},
								cssMaxWidth: Math.max(300, bParent.width()),
								cssMaxHeight: Math.max(200, bParent.height() - (tmpDiv[0].height() + tmpDiv[1].height() + 3 /*margin*/)),
								usageStatistics: false
							}),
							canvas = $base.find('canvas:first').get(0),
							zoom = function(v) {
								if (typeof v !== 'undefined') {
									var c = $(canvas),
										w = parseInt(c.attr('width')),
										h = parseInt(c.attr('height')),
										a = w / h,
										mw, mh;
									if (v === 0) {
										mw = w;
										mh = h;
									} else {
										mw = parseInt(c.css('max-width')) + Number(v);
										mh = mw / a;
										if (mw > w && mh > h) {
											mw = w;
											mh = h;
										}
									}
									per.text(Math.round(mw / w * 100) + '%');
									iEditor.resizeCanvasDimension({width: mw, height: mh});
									// continually change more
									if (zoomMore) {
										setTimeout(function() {
											zoomMore && zoom(v);
										}, 50);
									}
								}
							},
							zup = $('<span class="ui-icon ui-icon-plusthick"/>').data('val', 10),
							zdown = $('<span class="ui-icon ui-icon-minusthick"/>').data('val', -10),
							per = $('<button/>').css('width', '4em').text('%').attr('title', '100%').data('val', 0),
							quty, qutyTm, zoomTm, zoomMore;

						tmpContainer.remove();
						$base.removeData('url').data('mime', self.file.mime);
						// jpeg quality controls
						if (self.file.mime === 'image/jpeg') {
							$base.data('quality', fm.storage('jpgQuality') || fm.option('jpgQuality'));
							quty = $('<input type="number" class="ui-corner-all elfinder-resize-quality elfinder-tabstop"/>')
								.attr('min', '1')
								.attr('max', '100')
								.attr('title', '1 - 100')
								.on('change', function() {
									var q = quty.val();
									$base.data('quality', q);
									qutyTm && cancelAnimationFrame(qutyTm);
									qutyTm = requestAnimationFrame(function() {
										canvas.toBlob(function(blob) {
											blob && quty.next('span').text(' (' + fm.formatSize(blob.size) + ')');
										}, 'image/jpeg', Math.max(Math.min(q, 100), 1) / 100);
									});
								})
								.val($base.data('quality'));
							$('<div class="ui-dialog-buttonset elfinder-edit-extras elfinder-edit-extras-quality"/>')
								.append(
									$('<span>').html(fm.i18n('quality') + ' : '), quty, $('<span/>')
								)
								.prependTo($base.parent().next());
						} else if (self.file.mime === 'image/svg+xml') {
							$base.closest('.ui-dialog').trigger('changeType', {
								extention: 'png',
								mime : 'image/png',
								keepEditor: true
							});
						}
						// zoom scale controls
						$('<div class="ui-dialog-buttonset elfinder-edit-extras"/>')
							.append(
								zdown, per, zup
							)
							.attr('title', fm.i18n('scale'))
							.on('click', 'span,button', function() {
								zoom($(this).data('val'));
							})
							.on('mousedown mouseup mouseleave', 'span', function(e) {
								zoomMore = false;
								zoomTm && clearTimeout(zoomTm);
								if (e.type === 'mousedown') {
									zoomTm = setTimeout(function() {
										zoomMore = true;
										zoom($(e.target).data('val'));
									}, 500);
								}
							})
							.prependTo($base.parent().next());

						// wait canvas ready
						setTimeout(function() {
							dfrd.resolve(iEditor);
							if (quty) {
								quty.trigger('change');
								iEditor.on('redoStackChanged undoStackChanged', function() {
									quty.trigger('change');
								});
							}
							// show initial scale
							zoom(null);
						}, 100);
					},
					loader;

				if (!self.confObj.editor) {
					loader = $.Deferred();
					fm.loadCss([
						cdns.tui + '/tui-color-picker/latest/tui-color-picker.css',
						cdns.tui + '/tui-image-editor/'+ver+'/tui-image-editor.css'
					]);
					if (fm.hasRequire) {
						require.config({
							paths : {
								'fabric/dist/fabric.require' : cdns.fabric16 + '/fabric.require.min',
								'tui-code-snippet' : cdns.tui + '/tui.code-snippet/latest/tui-code-snippet.min',
								'tui-color-picker' : cdns.tui + '/tui-color-picker/latest/tui-color-picker.min',
								'tui-image-editor' : cdns.tui + '/tui-image-editor/'+ver+'/tui-image-editor.min'
							}
						});
						require(['tui-image-editor'], function(ImageEditor) {
							loader.resolve(ImageEditor);
						});
					} else {
						fm.loadScript([
							cdns.fabric16 + '/fabric.min.js',
							cdns.tui + '/tui.code-snippet/latest/tui-code-snippet.min.js'
						], function() {
							fm.loadScript([
								cdns.tui + '/tui-color-picker/latest/tui-color-picker.min.js'
							], function() {
								fm.loadScript([
									cdns.tui + '/tui-image-editor/'+ver+'/tui-image-editor.min.js'
								], function() {
									loader.resolve(window.tui.ImageEditor);
								}, {
									loadType: 'tag'
								});
							}, {
								loadType: 'tag'
							});
						}, {
							loadType: 'tag'
						});
					}
					loader.done(function(editor) {
						self.confObj.editor = editor;
						init(editor);
					});
				} else {
					init(self.confObj.editor);
				}
				return dfrd;
			},
			getContent : function(base) {
				var editor = this.editor,
					fm = editor.fm,
					$base = $(base),
					quality = $base.data('quality');
				if (editor.instance) {
					if ($base.data('mime') === 'image/jpeg') {
						quality = quality || fm.storage('jpgQuality') || fm.option('jpgQuality');
						quality = Math.max(0.1, Math.min(1, quality / 100));
					}
					return editor.instance.toDataURL({
						format: getExtention($base.data('mime'), fm),
						quality: quality
					});
				}
			},
			save : function(base) {
				var $base = $(base),
					quality = $base.data('quality'),
					hash = $base.data('hash'),
					file;
				this.instance.deactivateAll();
				if (typeof quality !== 'undefined') {
					this.fm.storage('jpgQuality', quality);
				}
				if (hash) {
					file = this.fm.file(hash);
					$base.data('mime', file.mime);
				}
			}
		});
	}

	if (parseInt(ucm_fm_editors.fm_pixo_editor_enable) && ucm_fm_editors.fm_image_edit_file_types !== '') {
		fm_editors.push({
			// Pixo is cross-platform image editor
			info : {
				id : 'pixo',
				name : 'Edit With Pixo',
				iconImg : 'img/editor-icons.png 0 -208',
				dataScheme: true,
				schemeContent: true,
				single: true,
				canMakeEmpty: false,
				integrate: {
					title: 'Pixo Editor',
					link: 'https://pixoeditor.com/privacy-policy/'
				}
			},
			// MIME types to accept
			mimes : ucm_fm_editors.fm_image_edit_file_types.split(','),
			// HTML of this editor
			html : '<div class="elfinder-edit-imageeditor"><img/></div>',
			// called on initialization of elFinder cmd edit (this: this editor's config object)
			setup : function(opts, fm) {
				if (fm.UA.ltIE8 || !opts.extraOptions || !opts.extraOptions.pixo || !opts.extraOptions.pixo.apikey) {
					this.disabled = true;
				} else {
					this.editorOpts = opts.extraOptions.pixo;
				}
			},
			// Initialization of editing node (this: this editors HTML node)
			init : function(id, file, content, fm) {
				initImgTag.call(this, id, file, content, fm);
			},
			// Get data uri scheme (this: this editors HTML node)
			getContent : function() {
				return $(this).children('img:first').attr('src');
			},
			// Launch Pixo editor when dialog open
			load : function(base) {
				var self = this,
					fm = this.fm,
					$base = $(base),
					node = $base.children('img:first'),
					dialog = $base.closest('.ui-dialog'),
					elfNode = fm.getUI(),
					dfrd = $.Deferred(),
					container = $('#elfinder-pixo-container'),
					init = function(onload) {
						var opts;

						if (!container.length) {
							container = $('<div id="elfinder-pixo-container" class="ui-front"/>').css({
								position: 'fixed',
								top: 0,
								right: 0,
								width: '100%',
								height: $(window).height(),
								overflow: 'hidden'
							}).hide().appendTo(elfNode.hasClass('elfinder-fullscreen')? elfNode : 'body');
							// bind switch fullscreen event
							elfNode.on('resize.'+fm.namespace, function(e, data) {
								e.preventDefault();
								e.stopPropagation();
								data && data.fullscreen && container.appendTo(data.fullscreen === 'on'? elfNode : 'body');
							});
							fm.bind('destroy', function() {
								editor && editor.cancelEditing();
								container.remove();
							});
						} else {
							// always moves to last
							container.appendTo(container.parent());
						}
						node.on('click', launch);
						// Constructor options
						opts = Object.assign({
							type: 'child',
							parent: container.get(0),
							onSave: function(arg) {
								// Check current file.hash, all callbacks are called on multiple instances
								var mime = arg.toBlob().type,
									ext = getExtention(mime, fm),
									draw = function(url) {
										node.one('load error', function() {
											node.data('loading') && node.data('loading')(true);
										})
											.attr('crossorigin', 'anonymous')
											.attr('src', url);
									},
									url = arg.toDataURL();
								node.data('loading')();
								delete base._canvas;
								if (node.data('ext') !== ext) {
									changeImageType(url, self.file.mime).done(function(res, cv) {
										if (cv) {
											base._canvas = canvas = cv;
											quty.trigger('change');
											qBase && qBase.show();
										}
										draw(res);
									}).fail(function() {
										dialog.trigger('changeType', {
											extention: ext,
											mime : mime
										});
										draw(url);
									});
								} else {
									draw(url);
								}
							},
							onClose: function() {
								dialog.removeClass(fm.res('class', 'preventback'));
								fm.toggleMaximize(container, false);
								container.hide();
								fm.toFront(dialog);
							}
						}, self.confObj.editorOpts);
						// trigger event 'editEditorPrepare'
						self.trigger('Prepare', {
							node: base,
							editorObj: Pixo,
							instance: void(0),
							opts: opts
						});
						// make editor instance
						editor = new Pixo.Bridge(opts);
						dfrd.resolve(editor);
						$base.on('saveAsFail', launch);
						if (onload) {
							onload();
						}
					},
					launch = function() {
						dialog.addClass(fm.res('class', 'preventback'));
						fm.toggleMaximize(container, true);
						fm.toFront(container);
						container.show().data('curhash', self.file.hash);
						editor.edit(node.get(0));
						node.data('loading')(true);
					},
					qBase, quty, qutyTm, canvas, editor;

				node.data('loading')();

				// jpeg quality controls
				if (self.file.mime === 'image/jpeg') {
					quty = $('<input type="number" class="ui-corner-all elfinder-resize-quality elfinder-tabstop"/>')
						.attr('min', '1')
						.attr('max', '100')
						.attr('title', '1 - 100')
						.on('change', function() {
							var q = quty.val();
							qutyTm && cancelAnimationFrame(qutyTm);
							qutyTm = requestAnimationFrame(function() {
								if (canvas) {
									canvas.toBlob(function(blob) {
										blob && quty.next('span').text(' (' + fm.formatSize(blob.size) + ')');
									}, 'image/jpeg', Math.max(Math.min(q, 100), 1) / 100);
								}
							});
						})
						.val(fm.storage('jpgQuality') || fm.option('jpgQuality'));
					qBase = $('<div class="ui-dialog-buttonset elfinder-edit-extras elfinder-edit-extras-quality"/>')
						.hide()
						.append(
							$('<span>').html(fm.i18n('quality') + ' : '), quty, $('<span/>')
						)
						.prependTo($base.parent().next());
					$base.data('quty', quty);
				}

				// load script then init
				if (typeof Pixo === 'undefined') {
					fm.loadScript(['https://pixoeditor.com:8443/editor/scripts/bridge.m.js'], function() {
						init(launch);
					}, {loadType: 'tag'});
				} else {
					init();
					launch();
				}
				return dfrd;
			},
			// Convert content url to data uri scheme to save content
			save : function(base) {
				var self = this,
					$base = $(base),
					node = $base.children('img:first'),
					q;
				if (base._canvas) {
					q = $base.data('quty')? Math.max(Math.min($base.data('quty').val(), 100), 1) / 100 : void(0);
					node.attr('src', base._canvas.toDataURL(self.file.mime, q));
				} else if (node.attr('src').substr(0, 5) !== 'data:') {
					node.attr('src', imgBase64(node, this.file.mime));
				}
			},
			close : function(base, editor) {
				editor && editor.destroy();
			}
		});
	}

	if (parseInt(ucm_fm_editors.fm_aceeditor_editor_enable) && ucm_fm_editors.fm_aceeditor_editor_file_types !== '') {
		fm_editors.push({
			mimes: ucm_fm_editors.fm_aceeditor_editor_file_types.split(','),
			// ACE Editor
			// called on initialization of elFinder cmd edit (this: this editor's config object)
			setup : function(opts, fm) {
				if (fm.UA.ltIE8 || !fm.options.cdns.ace) {
					this.disabled = true;
				}
			},
			// `mimes` is not set for support everything kind of text file
			info : {
				id : 'aceeditor',
				name : 'Edit With AceEditor',
				iconImg : 'img/editor-icons.png 0 -96'
			},
			load : function(textarea) {
				var self = this,
					fm   = this.fm,
					dfrd = $.Deferred(),
					cdn  = fm.options.cdns.ace,
					start = function() {
						var editor, editorBase, mode,
							ta = $(textarea),
							taBase = ta.parent(),
							dialog = taBase.parent(),
							id = textarea.id + '_ace',
							ext = self.file.name.replace(/^.+\.([^.]+)|(.+)$/, '$1$2').toLowerCase(),
							// MIME/mode map
							mimeMode = {
								'text/x-php'			  : 'php',
								'application/x-php'		  : 'php',
								'text/html'				  : 'html',
								'application/xhtml+xml'	  : 'html',
								'text/javascript'		  : 'javascript',
								'application/javascript'  : 'javascript',
								'text/css'				  : 'css',
								'text/x-c'				  : 'c_cpp',
								'text/x-csrc'			  : 'c_cpp',
								'text/x-chdr'			  : 'c_cpp',
								'text/x-c++'			  : 'c_cpp',
								'text/x-c++src'			  : 'c_cpp',
								'text/x-c++hdr'			  : 'c_cpp',
								'text/x-shellscript'	  : 'sh',
								'application/x-csh'		  : 'sh',
								'text/x-python'			  : 'python',
								'text/x-java'			  : 'java',
								'text/x-java-source'	  : 'java',
								'text/x-ruby'			  : 'ruby',
								'text/x-perl'			  : 'perl',
								'application/x-perl'	  : 'perl',
								'text/x-sql'			  : 'sql',
								'text/xml'				  : 'xml',
								'application/docbook+xml' : 'xml',
								'application/xml'		  : 'xml'
							};

						// set base height
						taBase.height(taBase.height());

						// set basePath of ace
						ace.config.set('basePath', cdn);

						// Base node of Ace editor
						editorBase = $('<div id="'+id+'" style="width:100%; height:100%;"/>').text(ta.val()).insertBefore(ta.hide());

						// Editor flag
						ta.data('ace', true);

						// Aceeditor instance
						editor = ace.edit(id);

						// Ace editor configure
						editor.$blockScrolling = Infinity;
						editor.setOptions({
							theme: 'ace/theme/monokai',
							fontSize: '14px',
							wrap: true,
						});
						ace.config.loadModule('ace/ext/modelist', function() {
							// detect mode
							mode = ace.require('ace/ext/modelist').getModeForPath('/' + self.file.name).name;
							if (mode === 'text') {
								if (mimeMode[self.file.mime]) {
									mode = mimeMode[self.file.mime];
								}
							}
							// show MIME:mode in title bar
							taBase.prev().children('.elfinder-dialog-title').append(' (' + self.file.mime + ' : ' + mode.split(/[\/\\]/).pop() + ')');
							editor.setOptions({
								mode: 'ace/mode/' + mode
							});
							if (dfrd.state() === 'resolved') {
								dialog.trigger('resize');
							}
						});
						ace.config.loadModule('ace/ext/language_tools', function() {
							ace.require('ace/ext/language_tools');
							editor.setOptions({
								enableBasicAutocompletion: true,
								enableSnippets: true,
								enableLiveAutocompletion: false
							});
						});
						ace.config.loadModule('ace/ext/settings_menu', function() {
							ace.require('ace/ext/settings_menu').init(editor);
						});

						// Short cuts
						editor.commands.addCommand({
							name : "saveFile",
							bindKey: {
								win : 'Ctrl-s',
								mac : 'Command-s'
							},
							exec: function(editor) {
								self.doSave();
							}
						});
						editor.commands.addCommand({
							name : "closeEditor",
							bindKey: {
								win : 'Ctrl-w|Ctrl-q',
								mac : 'Command-w|Command-q'
							},
							exec: function(editor) {
								self.doCancel();
							}
						});

						editor.resize();

						// TextArea button and Setting button
						$('<div class="ui-dialog-buttonset"/>').css('float', 'left')
							.append(
								$('<button/>').html(self.fm.i18n('TextArea'))
									.button()
									.on('click', function(){
										if (ta.data('ace')) {
											ta.removeData('ace');
											editorBase.hide();
											ta.val(editor.session.getValue()).show().trigger('focus');
											$(this).text('AceEditor');
										} else {
											ta.data('ace', true);
											editorBase.show();
											editor.setValue(ta.hide().val(), -1);
											editor.focus();
											$(this).html(self.fm.i18n('TextArea'));
										}
									})
							)
							.append(
								$('<button>Ace editor setting</button>')
									.button({
										icons: {
											primary: 'ui-icon-gear',
											secondary: 'ui-icon-triangle-1-e'
										},
										text: false
									})
									.on('click', function(){
										editor.showSettingsMenu();
										$('#ace_settingsmenu')
											.css('font-size', '80%')
											.find('div[contains="setOptions"]').hide().end()
											.parent().parent().appendTo($('#elfinder'));
									})
							)
							.prependTo(taBase.next());

						// trigger event 'editEditorPrepare'
						self.trigger('Prepare', {
							node: textarea,
							editorObj: ace,
							instance: editor,
							opts: {}
						});

						//dialog.trigger('resize');
						dfrd.resolve(editor);
					};

				// check ace & start
				if (!self.confObj.loader) {
					self.confObj.loader = $.Deferred();
					self.fm.loadScript([ cdn+'/ace.js' ], function() {
						self.confObj.loader.resolve();
					}, void 0, {obj: window, name: 'ace'});
				}
				self.confObj.loader.done(start);

				return dfrd;
			},
			close : function(textarea, instance) {
				instance && instance.destroy();
			},
			save : function(textarea, instance) {
				instance && $(textarea).data('ace') && (textarea.value = instance.session.getValue());
			},
			focus : function(textarea, instance) {
				instance && $(textarea).data('ace') && instance.focus();
			},
			resize : function(textarea, instance, e, data) {
				instance && instance.resize();
			}
		});
	}

	if (parseInt(ucm_fm_editors.fm_tinymce_editor_enable) && ucm_fm_editors.fm_tinymce_file_types !== '') {
		fm_editors.push({
			// TinyMCE for html file
			info : {
				id : 'tinymce',
				name : 'TinyMCE',
				iconImg : 'img/editor-icons.png 0 -64'
			},
			// exts  : ['htm', 'html', 'xhtml'],
			mimes: ucm_fm_editors.fm_tinymce_file_types.split(','),
			setup : function(opts, fm) {
				var confObj = this;
				if (!fm.options.cdns.tinymce) {
					confObj.disabled = true;
				} else {
					confObj.mceOpts = {};
					if (opts.extraOptions) {
						confObj.uploadOpts = Object.assign({}, opts.extraOptions.uploadOpts || {});
						confObj.mceOpts = Object.assign({}, opts.extraOptions.tinymce || {});
					} else {
						confObj.uploadOpts = {};
					}
				}
			},
			load : function(textarea) {
				var self = this,
					fm   = this.fm,
					dfrd = $.Deferred(),
					init = function() {
						var base = $(textarea).show().parent(),
							dlg = base.closest('.elfinder-dialog'),
							h = base.height(),
							delta = base.outerHeight(true) - h,
							// hide MCE dialog and modal block
							hideMceDlg = function() {
								var mceW;
								if (tinymce.activeEditor.windowManager.windows) {
									mceW = tinymce.activeEditor.windowManager.windows[0];
									mceDlg = $(mceW? mceW.getEl() : void(0)).hide();
									mceCv = $('#mce-modal-block').hide();
								} else {
									mceDlg = $('.tox-dialog-wrap').hide();
								}
							},
							// Show MCE dialog and modal block
							showMceDlg = function() {
								mceCv && mceCv.show();
								mceDlg && mceDlg.show();
							},
							tVer = tinymce.majorVersion,
							opts, mceDlg, mceCv;

						// set base height
						base.height(h);
						// fit height function
						textarea._setHeight = function(height) {
							if (tVer < 5) {
								var base = $(this).parent(),
									h = height || base.innerHeight(),
									ctrH = 0,
									areaH;
								base.find('.mce-container-body:first').children('.mce-top-part,.mce-statusbar').each(function() {
									ctrH += $(this).outerHeight(true);
								});
								areaH = h - ctrH - delta;
								base.find('.mce-edit-area iframe:first').height(areaH);
							}
						};

						// TinyMCE configure options
						opts = {
							selector: '#' + textarea.id,
							resize: false,
							plugins: 'print preview fullpage searchreplace autolink directionality visualblocks visualchars fullscreen image link media template codesample table charmap hr pagebreak nonbreaking anchor toc insertdatetime advlist lists wordcount imagetools textpattern help',
							toolbar: 'formatselect | bold italic strikethrough forecolor backcolor | link image media | alignleft aligncenter alignright alignjustify | numlist bullist outdent indent | removeformat',
							image_advtab: true,
							init_instance_callback : function(editor) {
								// fit height on init
								textarea._setHeight(h);
								// re-build on dom move
								dlg.one('beforedommove.'+fm.namespace, function() {
									tinymce.execCommand('mceRemoveEditor', false, textarea.id);
								}).one('dommove.'+fm.namespace, function() {
									self.load(textarea).done(function(editor) {
										self.instance = editor;
									});
								});
								// return editor instance
								dfrd.resolve(editor);
							},
							file_picker_callback : function (callback, value, meta) {
								var gf = fm.getCommand('getfile'),
									revar = function() {
										if (prevVars.hasVar) {
											gf.callback = prevVars.callback;
											gf.options.folders = prevVars.folders;
											gf.options.multiple = prevVars.multi;
											fm.commandMap.open = prevVars.open;
											prevVars.hasVar = false;
										}
										dlg.off('resize close', revar);
										showMceDlg();
									},
									prevVars = {};
								prevVars.callback = gf.callback;
								prevVars.folders = gf.options.folders;
								prevVars.multi = gf.options.multiple;
								prevVars.open = fm.commandMap.open;
								prevVars.hasVar = true;
								gf.callback = function(file) {
									var url, info;

									if (file.mime === 'directory') {
										fm.one('open', function() {
											fm.commandMap.open = 'getfile';
										}).getCommand('open').exec(file.hash);
										return;
									}

									// URL normalization
									url = fm.convAbsUrl(file.url);

									// Make file info
									info = file.name + ' (' + fm.formatSize(file.size) + ')';

									// Provide file and text for the link dialog
									if (meta.filetype == 'file') {
										callback(url, {text: info, title: info});
									}

									// Provide image and alt text for the image dialog
									if (meta.filetype == 'image') {
										callback(url, {alt: info});
									}

									// Provide alternative source and posted for the media dialog
									if (meta.filetype == 'media') {
										callback(url);
									}
									dlg.trigger('togleminimize');
								};
								gf.options.folders = true;
								gf.options.multiple = false;
								fm.commandMap.open = 'getfile';

								hideMceDlg();
								dlg.trigger('togleminimize').one('resize close', revar);
								fm.toast({
									mode: 'info',
									msg: fm.i18n('dblclickToSelect')
								});

								return false;
							},
							images_upload_handler : function (blobInfo, success, failure) {
								var file = blobInfo.blob(),
									err = function(e) {
										var dlg = e.data.dialog || {};
										if (dlg.hasClass('elfinder-dialog-error') || dlg.hasClass('elfinder-confirm-upload')) {
											hideMceDlg();
											dlg.trigger('togleminimize').one('resize close', revert);
											fm.unbind('dialogopened', err);
										}
									},
									revert = function() {
										dlg.off('resize close', revert);
										showMceDlg();
									},
									clipdata = true;

								// check file object
								if (file.name) {
									// file blob of client side file object
									clipdata = void(0);
								}
								fm.bind('dialogopened', err).exec('upload', Object.assign({
									files: [file],
									clipdata: clipdata // to get unique name on connector
								}, self.confObj.uploadOpts), void(0), fm.cwd().hash).done(function(data) {
									if (data.added && data.added.length) {
										fm.url(data.added[0].hash, { async: true }).done(function(url) {
											showMceDlg();
											success(fm.convAbsUrl(url));
										}).fail(function() {
											failure(fm.i18n('errFileNotFound'));
										});
									} else {
										failure(fm.i18n(data.error? data.error : 'errUpload'));
									}
								}).fail(function(err) {
									var error = fm.parseError(err);
									if (error) {
										if (error === 'errUnknownCmd') {
											error = 'errPerm';
										} else if (error === 'userabort') {
											error = 'errAbort';
										}
									}
									failure(fm.i18n(error? error : 'errUploadNoFiles'));
								});
							}
						};

						// TinyMCE 5 supports "height: 100%"
						if (tVer >= 5) {
							opts.height = '100%';
						}

						// trigger event 'editEditorPrepare'
						self.trigger('Prepare', {
							node: textarea,
							editorObj: tinymce,
							instance: void(0),
							opts: opts
						});

						// TinyMCE configure
						tinymce.init(Object.assign(opts, self.confObj.mceOpts));
					};

				if (!self.confObj.loader) {
					self.confObj.loader = $.Deferred();
					self.fm.loadScript([fm.options.cdns.tinymce + (fm.options.cdns.tinymce.match(/\.js/)? '' : '/tinymce.min.js')], function() {
						self.confObj.loader.resolve();
					}, {
						loadType: 'tag'
					});
				}
				self.confObj.loader.done(init);
				return dfrd;
			},
			close : function(textarea, instance) {
				instance && tinymce.execCommand('mceRemoveEditor', false, textarea.id);
			},
			save : function(textarea, instance) {
				instance && instance.save();
			},
			focus : function(textarea, instance) {
				instance && instance.focus();
			},
			resize : function(textarea, instance, e, data) {
				// fit height to base node on dialog resize
				instance && textarea._setHeight();
			}
		});
	}

	if (parseInt(ucm_fm_editors.fm_textarea_editor_enable) && ucm_fm_editors.fm_textarea_file_types !== '') {
		fm_editors.push({
			// Simple Text (basic textarea editor)
			mimes: ucm_fm_editors.fm_textarea_file_types.split(','),
			info : {
				id : 'textarea',
				name : 'TextArea',
				useTextAreaEvent : true
			},
			load : function(textarea) {
				// trigger event 'editEditorPrepare'
				this.trigger('Prepare', {
					node: textarea,
					editorObj: void(0),
					instance: void(0),
					opts: {}
				});
				textarea.setSelectionRange && textarea.setSelectionRange(0, 0);
				$(textarea).trigger('focus').show();
			},
			save : function(){}
		});
	}

	return fm_editors;
}, window.elFinder));
