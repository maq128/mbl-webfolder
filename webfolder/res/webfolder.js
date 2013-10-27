var g_domToolbar, g_domFiles, g_curPath;

function detectBrowser()
{
	var ua = navigator.userAgent,
		N = navigator.appName, tem,
		M = ua.match( /(opera|chrome|safari|firefox|msie|trident)\/?\s*([\d\.]+)/i ) || [];
		M = M[2] ? [M[1], M[2]] : [N, navigator.appVersion, '-?'];
	if ( M && ( tem = ua.match( /version\/([\.\d]+)/i ) ) != null ) M[2] = tem[1];
	return M;
}

function str_prefix( str )
{
	str = '00' + str;
	return str.substr( str.length - 2 );
}

function str_segment( str )
{
	str = '' + str;
	var segments = [];
	while ( str.length > 3 ) {
		segments.unshift( str.substr( str.length - 3 ) );
		str = str.substr( 0, str.length - 3 );
	}
	if ( str.length > 0 ) {
		segments.unshift( str );
	}
	return segments.join( ',' );
}

function htmlPath( text, path )
{
	var html = '<span data-path="' + path + '"><span class="switch-path">' + text + '</span></span>';
	return html;
}

function showMask( bWaiting )
{
	$('.page-mask').show();
	$('.page-mask .waiting')[ bWaiting ? 'show' : 'hide' ]();
}

function hideMask()
{
	$('.page-mask').hide();
}

function createUploader( clzButton )
{
	var isChrome = ( ('' + detectBrowser()[0]).toLowerCase() == 'chrome' );
	if ( ! isChrome ) {
		$(document.body).delegate( clzButton, 'click', function( evt ) {
			alert('抱歉，目前上传功能仅在 Chrome 中支持。');
		});
		return;
	}

	var domUploader = $('.upload-zone');
	domUploader.fineUploader({
		request: {
			endpoint: 'wfs.php',
			params: {
				m: 'upload',
				path: 'dummy'
			}
		},
		dragAndDrop: {
			hideDropzones: true
		},
		text: {
			uploadButton: '选择文件',
			dragZone: '拖拽文件或目录到这里<br>即可上传<br><br>如果包含了目录<br>则相应的目录结构会被保留',
			cancelButton: '取消',
			formatProgress: '{percent}% / {total_size}',
			waitingForResponse: '正在上传...'
		},
		failedUploadTextDisplay: {
			mode: 'custom',
			maxChars: 50,
			responseProperty: 'error',
			enableTooltip: false
		}
	}).on( 'upload', function( evt, id, name ) {

		domDropArea.hide();
		domClose.hide();

		var fileCtrl = domUploader.fineUploader( 'getFile', id );
		var lastModifiedDate = fileCtrl ? fileCtrl.lastModifiedDate : new Date();
		domUploader.fineUploader( 'setParams', {
			m: 'upload',
			path: g_curPath,
			mtime: lastModifiedDate.getTime()
		});

	}).on( 'complete', function( evt, id, name, responseJSON, xhr ) {

		//console.log(id, name, responseJSON);
		if ( ! responseJSON.success ) {
			domUploader.failureNum ++;
		} else if ( responseJSON.name != name ) {
			domUploader.failureNum ++;
			var domLi = domUploader.fineUploader( 'getItemByFileId', id );
			var domStatus = domLi.find( '.qq-upload-status-text' );
			domStatus.html( '[<span class="btn-reset-filename">正名</span>]' );
			var domBtn = domStatus.find( '.btn-reset-filename' );
			domBtn.attr( 'title', '因同名文件已经存在，该文件已经上传并保存为：\r\n  /' + responseJSON.dir + '/' + responseJSON.name + '\r\n点击可以恢复原名，并覆盖旧文件。' );
			domBtn.on( 'click', function() {
				var params = {
					m: 'moveFso',
					path: responseJSON.dir + '/' + responseJSON.name,
					toDir: responseJSON.dir,
					toName: name,
					force: true
				};
				$.get( "wfs.php", params, function( data ) {
					if ( data.success ) {
						domStatus.empty();
					} else {
						domStatus.html( data.error );
					}
				}, 'json' );
			});
		}
		var n = domUploader.fineUploader( 'getInProgress' );
		if ( n > 0 ) return;

		if ( domUploader.failureNum > 0 ) {
			domClose.show();
			return;
		}

		domUploader.end();
	});

	domUploader.begin = function() {
		showMask( false );
		domUploader.show();
		domDropArea.show();
		domButton.hide();
		domClose.show();
		domUploader.failureNum = 0;
	};

	domUploader.end = function() {
		hideMask();
		domUploader.hide();
		domList.empty();
		switchToDir( g_curPath );
	};

	var domDropArea = domUploader.find( '.qq-upload-drop-area' );
	var domButton = domUploader.find( '.qq-upload-button' );
	var domList = domUploader.find( '.qq-upload-list' );
	var domClose = $('<div class="btn-close"></div>')
						.appendTo( domUploader )
						.on( 'click', domUploader.end );

	$(document.body).delegate( clzButton, 'click', function( evt ) {
		domUploader.begin();
	});
}

function switchToDir( path )
{
	showMask( true );
	$.get( "wfs.php", { m: 'list', path: path }, function( data ) {
		hideMask();
		g_domToolbar.empty();
		g_domFiles.empty();

		if ( ! data.success ) {
			g_domToolbar.html( data.error );
			return;
		}

		g_curPath = data.path;
		var isTop = ( data.path.length == 0 );

		// 生成工具条内容
		var html = [];
		var nodes = [];
		$.each( data.path.split( '/' ), function( idx, name ) {
			if ( name.length == 0 ) return;
			nodes.push( name );
			html.push( htmlPath( name, nodes.join( '/' ) ) );
		});
		html = htmlPath( '/', '' ) + html.join( '/' ) + '&gt; ';

		if ( ! isTop ) {
			html += [
				'<button class="btn-create-dir">创建子目录</button>',
				'<button class="btn-upload-file">上传文件</button>'
			].join(' ');
			html = '<input class="cur-path" value="/' + g_curPath + '" disabled/><br>' + html;
		}

		g_domToolbar.html( html );

		// 如果不是“根目录”……
		if ( ! isTop ) {
			g_domFiles.append( [
				'<tr>',
					'<td class="dir-name dir-name-up" data-path="' + data.path + '/..">',
						'<span class="switch-path">[返回上一级]</span>',
					'</td>',
					'<td></td>',
					'<td></td>',
				'</tr>'
			].join('') );
		}

		// 列示子目录
		$.each( data.subdirs, function( idx, subdir ) {
			g_domFiles.append( [
				'<tr>',
					'<td class="dir-name" data-path="' + data.path + '/' + subdir.name + '">',
						'<span class="switch-path">' + subdir.name + '</span>',
						isTop ? '' : '<div class="remove-dir" title="删除目录">&#211;</div>',
						isTop ? '' : '<div class="rename-fso" title="重命名">&#68;</div>',
						isTop ? '' : '<div class="move-fso" title="移动">&#98;</div>',
						isTop ? '' : '<div class="download-dir" title="下载整个目录压缩包">&#46;</div>',
					'</td>',
					'<td></td>',
					'<td></td>',
				'</tr>'
			].join('') );
		});

		// 列示文件
		$.each( data.files, function( idx, file ) {
			var mtime = new Date( file.mtime * 1000 );
			mtime = [
				mtime.getYear() + 1900,
				str_prefix( mtime.getMonth() + 1, 2 ),
				str_prefix( mtime.getDate(), 2 )
			].join('-') + ' ' + [
				str_prefix( mtime.getHours(), 2 ),
				str_prefix( mtime.getMinutes(), 2 ),
				str_prefix( mtime.getSeconds(), 2 )
			].join(':');
			g_domFiles.append( [
				'<tr>',
					'<td class="file-name" data-path="' + data.path + '/' + file.name + '">',
						'<a class="download-file" target="_blank" href="wfs.php?m=download&path=' + encodeURIComponent(data.path + '/' + file.name) + '">' + file.name + '</a>',
						'<div class="remove-file" title="删除文件">&#207;</div>',
						'<div class="rename-fso" title="重命名">&#68;</div>',
						'<div class="move-fso" title="移动">&#98;</div>',
					'</td>',
					'<td class="file-size">' + str_segment( file.size ) + '</td>',
					'<td class="file-mtime">' + mtime + '</td>',
				'</tr>'
			].join('') );
		});
	}, 'json' );
}

function createDir( name )
{
	showMask( true );
	$.get( "wfs.php", { m: 'createDir', path: g_curPath, name: name }, function( data ) {
		hideMask();
		if ( data.success ) {
			switchToDir( g_curPath );
		} else {
			alert( data.error );
		}
	}, 'json' );
}

function removeDir( path )
{
	showMask( true );
	$.get( "wfs.php", { m: 'removeDir', path: path }, function( data ) {
		hideMask();
		if ( data.success ) {
			switchToDir( g_curPath );
		} else {
			alert( data.error );
		}
	}, 'json' );
}

function removeFile( path )
{
	showMask( true );
	$.get( "wfs.php", { m: 'removeFile', path: path }, function( data ) {
		hideMask();
		if ( data.success ) {
			switchToDir( g_curPath );
		} else {
			alert( data.error );
		}
	}, 'json' );
}

function moveFso( path, newDir, newName )
{
	showMask( true );
	var params = {
		m: 'moveFso',
		path: path,
		toDir: newDir,
		toName: newName,
		force: false
	};
	$.get( "wfs.php", params, function( data ) {
		hideMask();
		if ( data.success ) {
			switchToDir( g_curPath );
		} else {
			alert( data.error );
		}
	}, 'json' );
}

$(document).ready( function() {
	g_domToolbar = $('#toolbar');
	g_domFiles = $('#list-files');

//	var onResize = function() {
//		var inner = $('#viewport-inner');
//		var w = inner.width();
//		var h = inner.height();
//		if ( w < window.innerWidth ) w = window.innerWidth;
//		if ( h < window.innerHeight ) h = window.innerHeight;
//		g_domViewport.css({
//			width: w,
//			height: h
//		});
//	};
//	$(window).resize( onResize );
//	onResize();

	// 创建“上传文件”
	createUploader( '.btn-upload-file' );

	// 点击“跳转到指定目录”
	$(document.body).delegate( '.switch-path', 'click', function( evt ) {
		var path = $(evt.currentTarget).parent().attr( 'data-path' );
		switchToDir( path );
	});

	// 点击“创建子目录”
	$(document.body).delegate( '.btn-create-dir', 'click', function( evt ) {
		var name = prompt( '请输入新的子目录名称', '新建子目录' );
		if ( ! name ) return;
		createDir( name );
	});

	// 点击“下载整个目录压缩包”
	$(document.body).delegate( '.download-dir', 'click', function( evt ) {
		alert('以压缩包的形式下载整个目录\r\n\r\n抱歉，该功能尚未实现');
	});

	// 点击“删除目录”
	$(document.body).delegate( '.remove-dir', 'click', function( evt ) {
		var path = $(evt.currentTarget).parent().attr( 'data-path' );
		var ok = confirm( [
			'即将删除目录 "' + path + '"',
			'确定要删除整个目录吗？'
		].join( '\r\n' ) );
		if ( ! ok ) return;
		removeDir( path );
	});

	// 点击“删除文件”
	$(document.body).delegate( '.remove-file', 'click', function( evt ) {
		var path = $(evt.currentTarget).parent().attr( 'data-path' );
		var ok = confirm( [
			'即将删除文件 "' + path + '"',
			'确定要删除这个文件吗？'
		].join( '\r\n' ) );
		if ( ! ok ) return;
		removeFile( path );
	});

	// 点击“重命名”
	$(document.body).delegate( '.rename-fso', 'click', function( evt ) {
		var path = $(evt.currentTarget).parent().attr( 'data-path' );
		var pos = path.lastIndexOf( '/' );
		var newDir = path.substr( 0, pos );
		var name = path.substr( pos + 1 );
		var newName = prompt( '请输入新的名字', name );
		if ( !newName || newName == name ) return;
		moveFso( path, newDir, newName );
	});

	// 点击“移动”
	$(document.body).delegate( '.move-fso', 'click', function( evt ) {
		var path = $(evt.currentTarget).parent().attr( 'data-path' );
		var pos = path.lastIndexOf( '/' );
		var oldDir = '/' + path.substr( 0, pos );
		var name = path.substr( pos + 1 );
		var newDir = prompt( '请输入目标目录的路径', oldDir );
		if ( !newDir || newDir == oldDir ) return;
		moveFso( path, newDir, name );
	});

	switchToDir( '' );
});
