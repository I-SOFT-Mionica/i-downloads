/* global IDL, jQuery */
( function ( $, IDL ) {
	'use strict';

	var $fileList = $( '#idl-file-list-body' );
	var postId    = $( '#post_ID' ).val();

	// -------------------------------------------------------------------------
	// Helpers
	// -------------------------------------------------------------------------
	function esc( s ) {
		return String( s == null ? '' : s )
			.replace( /&/g, '&amp;' )
			.replace( /</g, '&lt;' )
			.replace( />/g, '&gt;' )
			.replace( /"/g, '&quot;' )
			.replace( /'/g, '&#39;' );
	}

	function formatSize( bytes ) {
		bytes = Number( bytes ) || 0;
		if ( bytes === 0 ) {
			return '—'; }
		var units = [ 'B', 'KB', 'MB', 'GB', 'TB' ];
		var i     = 0;
		while ( bytes >= 1024 && i < units.length - 1 ) {
			bytes /= 1024; i++; }
		return bytes.toFixed( i === 0 ? 0 : 1 ) + ' ' + units[ i ];
	}

	function fileRowHtml( file ) {
		var ext     = ( file.file_name || '' ).split( '.' ).pop().toUpperCase();
		var isLocal = file.file_type === 'local';
		var source  = isLocal
			? esc( file.file_name )
			: '<a href="' + esc( file.external_url ) + '" target="_blank" rel="noopener noreferrer">' + esc( file.external_url ) + '</a>';
		var badge   = isLocal
			? '<span class="idl-badge idl-badge--local">' + esc( ext ) + '</span>'
			: ( parseInt( file.is_mirror, 10 )
				? '<span class="idl-badge idl-badge--mirror">' + esc( IDL.i18n.mirror ) + '</span>'
				: '<span class="idl-badge idl-badge--external">' + esc( IDL.i18n.external ) + '</span>' );

		return '<tr class="idl-file-row" data-file-id="' + esc( file.id ) + '">'
			+ '<td class="idl-col-sort"><span class="dashicons dashicons-move idl-sort-handle"></span></td>'
			+ '<td class="idl-file-title" data-title="' + esc( file.title || '' ) + '" data-description="' + esc( file.description || '' ) + '">'
				+ esc( file.title || file.file_name || file.external_url )
			+ '</td>'
			+ '<td class="idl-file-source">' + source + '</td>'
			+ '<td>' + badge + '</td>'
			+ '<td>' + esc( formatSize( file.file_size ) ) + '</td>'
			+ '<td>' + esc( file.download_count || 0 ) + '</td>'
			+ '<td>'
				+ '<button type="button" class="button button-small idl-btn-edit-file" data-file-id="' + esc( file.id ) + '">' + esc( IDL.i18n.edit ) + '</button> '
				+ '<button type="button" class="button button-small idl-btn-delete-file" data-file-id="' + esc( file.id ) + '">' + esc( IDL.i18n.remove ) + '</button>'
			+ '</td>'
			+ '</tr>';
	}

	function appendFileRow( file ) {
		$( '#idl-no-files-row' ).remove();
		$fileList.append( fileRowHtml( file ) );
	}

	// -------------------------------------------------------------------------
	// Tabs
	// -------------------------------------------------------------------------
	$( '.idl-tab-nav' ).on(
		'click',
		'.idl-tab-btn',
		function () {
			var tab = $( this ).data( 'tab' );
			$( '.idl-tab-btn' ).removeClass( 'is-active' ).attr( 'aria-selected', 'false' );
			$( this ).addClass( 'is-active' ).attr( 'aria-selected', 'true' );
			$( '.idl-tab-panel' ).prop( 'hidden', true ).removeClass( 'is-active' );
			$( '.idl-tab-panel[data-tab="' + tab + '"]' ).prop( 'hidden', false ).addClass( 'is-active' );

			if ( 'browse' === tab ) {
				loadBrowseList();
			}
		}
	);

	// -------------------------------------------------------------------------
	// Sortable file list
	// -------------------------------------------------------------------------
	$fileList.sortable(
		{
			handle: '.idl-sort-handle',
			placeholder: 'idl-sortable-placeholder',
			update: function () {
				var order = {};
				$fileList.find( '.idl-file-row' ).each(
					function ( i ) {
						order[ $( this ).data( 'file-id' ) ] = i;
					}
				);
				$.post(
					IDL.ajaxUrl,
					{
						action: 'idl_save_file_order',
						nonce:  IDL.nonce,
						order:  order,
						}
				);
			},
		}
	);

	// -------------------------------------------------------------------------
	// Edit file metadata (title + description)
	// -------------------------------------------------------------------------
	$fileList.on(
		'click',
		'.idl-btn-edit-file',
		function () {
			var $btn   = $( this );
			var fileId = $btn.data( 'file-id' );
			var $row   = $btn.closest( '.idl-file-row' );

			// Toggle: close if this row's editor is already open below it.
			var $existing = $row.next( '.idl-file-edit-row' );
			if ( $existing.length ) {
				$existing.remove();
				return;
			}
			// Close any other open editor first.
			$fileList.find( '.idl-file-edit-row' ).remove();

			var $titleCell = $row.find( '.idl-file-title' );
			var title      = $titleCell.data( 'title' ) || '';
			var desc       = $titleCell.data( 'description' ) || '';

			var $editor = $(
				'<tr class="idl-file-edit-row" data-edit-for="' + fileId + '">'
				+ '<td colspan="7">'
				+ '<div class="idl-file-edit">'
				+ '<p><label>' + esc( IDL.i18n.title ) + '<br>'
				+ '<input type="text" class="widefat idl-edit-title" />'
				+ '</label></p>'
				+ '<p><label>' + esc( IDL.i18n.description ) + '<br>'
				+ '<textarea class="widefat idl-edit-description" rows="3"></textarea>'
				+ '</label></p>'
				+ '<p>'
				+ '<button type="button" class="button button-primary idl-edit-save">' + esc( IDL.i18n.save ) + '</button> '
				+ '<button type="button" class="button idl-edit-cancel">' + esc( IDL.i18n.cancel ) + '</button>'
				+ '<span class="idl-edit-status" aria-live="polite"></span>'
				+ '</p>'
				+ '</div>'
				+ '</td>'
				+ '</tr>'
			);
			$editor.find( '.idl-edit-title' ).val( title );
			$editor.find( '.idl-edit-description' ).val( desc );
			$row.after( $editor );
			$editor.find( '.idl-edit-title' ).trigger( 'focus' );
		}
	);

	$fileList.on(
		'click',
		'.idl-edit-cancel',
		function () {
			$( this ).closest( '.idl-file-edit-row' ).remove();
		}
	);

	$fileList.on(
		'click',
		'.idl-edit-save',
		function () {
			var $editor  = $( this ).closest( '.idl-file-edit-row' );
			var fileId   = $editor.data( 'edit-for' );
			var $row     = $fileList.find( '.idl-file-row[data-file-id="' + fileId + '"]' );
			var newTitle = $editor.find( '.idl-edit-title' ).val();
			var newDesc  = $editor.find( '.idl-edit-description' ).val();
			var $status  = $editor.find( '.idl-edit-status' );
			var $save    = $( this );

			$save.prop( 'disabled', true );
			$status.text( IDL.i18n.saving );

			$.post(
				IDL.ajaxUrl,
				{
					action:      'idl_update_file_meta',
					nonce:       IDL.nonce,
					file_id:     fileId,
					title:       newTitle,
					description: newDesc,
				},
				function ( res ) {
					if ( res.success ) {
						var $titleCell = $row.find( '.idl-file-title' );
						$titleCell
						.text( res.data.file.title || res.data.file.file_name || res.data.file.external_url )
						.attr( 'data-title', res.data.file.title || '' )
						.attr( 'data-description', res.data.file.description || '' );
						$editor.remove();
					} else {
						$save.prop( 'disabled', false );
						$status.text( ( res.data && res.data.message ) ? res.data.message : IDL.i18n.error );
					}
				}
			).fail(
				function () {
					$save.prop( 'disabled', false );
					$status.text( IDL.i18n.networkError );
				}
			);
		}
	);

	// -------------------------------------------------------------------------
	// Remove file
	// -------------------------------------------------------------------------
	$fileList.on(
		'click',
		'.idl-btn-delete-file',
		function () {
			if ( ! window.confirm( IDL.i18n.confirmDelete ) ) {
				return;
			}
			var $btn   = $( this );
			var fileId = $btn.data( 'file-id' );
			var $row   = $btn.closest( '.idl-file-row' );

			$btn.prop( 'disabled', true );

			$.post(
				IDL.ajaxUrl,
				{
					action:  'idl_delete_file',
					nonce:   IDL.nonce,
					file_id: fileId,
				},
				function ( res ) {
					if ( res.success ) {
						$row.next( '.idl-file-edit-row' ).remove();
						$row.remove();
						if ( $fileList.find( '.idl-file-row' ).length === 0 ) {
							$fileList.append( '<tr class="idl-no-files" id="idl-no-files-row"><td colspan="7">' + esc( IDL.i18n.noFiles ) + '</td></tr>' );
						}
					} else {
						$btn.prop( 'disabled', false );
						alert( res.data && res.data.message ? res.data.message : IDL.i18n.error );
					}
				}
			);
		}
	);

	// -------------------------------------------------------------------------
	// Upload — dropzone + file input
	// -------------------------------------------------------------------------
	var $dropzone  = $( '#idl-dropzone' );
	var $fileInput = $( '#idl-file-input' );
	var $queue     = $( '#idl-upload-queue' );

	$dropzone.on(
		'click',
		function () {
			// Must call the native DOM click() — jQuery's .trigger('click') fires
			// a synthetic event that browsers do not treat as a user gesture, and
			// the file-picker dialog will not open.
			if ( $fileInput[ 0 ] ) {
				$fileInput[ 0 ].click();
			}
		}
	);

	$fileInput.on(
		'change',
		function ( e ) {
			handleFiles( e.target.files );
			$fileInput.val( '' );
		}
	);

	$dropzone.on(
		'dragover dragenter',
		function ( e ) {
			e.preventDefault();
			e.stopPropagation();
			$dropzone.addClass( 'is-dragover' );
		}
	);

	$dropzone.on(
		'dragleave dragend drop',
		function () {
			$dropzone.removeClass( 'is-dragover' );
		}
	);

	$dropzone.on(
		'drop',
		function ( e ) {
			e.preventDefault();
			e.stopPropagation();
			var files = e.originalEvent.dataTransfer && e.originalEvent.dataTransfer.files;
			if ( files && files.length ) {
				handleFiles( files );
			}
		}
	);

	function handleFiles( fileList ) {
		for ( var i = 0; i < fileList.length; i++ ) {
			uploadOne( fileList[ i ] );
		}
	}

	function uploadOne( file ) {
		var $item = $(
			'<li class="idl-upload-item">'
			+ '<span class="idl-upload-name"></span>'
			+ '<span class="idl-upload-bar"><span class="idl-upload-bar-fill"></span></span>'
			+ '<span class="idl-upload-status"></span>'
			+ '</li>'
		);
		$item.find( '.idl-upload-name' ).text( file.name );
		$queue.append( $item );

		var fd = new FormData();
		fd.append( 'action',      'idl_upload_file' );
		fd.append( 'nonce',       IDL.nonce );
		fd.append( 'download_id', postId );
		fd.append( 'file',        file );

		var xhr = new XMLHttpRequest();
		xhr.open( 'POST', IDL.ajaxUrl, true );

		xhr.upload.addEventListener(
			'progress',
			function ( evt ) {
				if ( evt.lengthComputable ) {
					var pct = Math.round( ( evt.loaded / evt.total ) * 100 );
					$item.find( '.idl-upload-bar-fill' ).css( 'width', pct + '%' );
					$item.find( '.idl-upload-status' ).text( pct + '%' );
				}
			}
		);

		xhr.onload = function () {
			try {
				var res = JSON.parse( xhr.responseText );
				if ( res.success ) {
					$item.addClass( 'is-done' );
					$item.find( '.idl-upload-bar-fill' ).css( 'width', '100%' );
					$item.find( '.idl-upload-status' ).text( '✓' );
					appendFileRow( res.data.file );
					setTimeout(
						function () {
							$item.fadeOut(
								400,
								function () {
												$item.remove(); }
							); },
						800
					);
				} else {
					$item.addClass( 'is-error' );
					$item.find( '.idl-upload-status' ).text( res.data && res.data.message ? res.data.message : IDL.i18n.error );
				}
			} catch ( err ) {
				$item.addClass( 'is-error' );
				$item.find( '.idl-upload-status' ).text( IDL.i18n.serverError );
			}
		};

		xhr.onerror = function () {
			$item.addClass( 'is-error' );
			$item.find( '.idl-upload-status' ).text( IDL.i18n.networkError );
		};

		xhr.send( fd );
	}

	// -------------------------------------------------------------------------
	// Browse — list untracked files in the category folder
	// -------------------------------------------------------------------------
	var browseLoaded = false;
	function loadBrowseList() {
		if ( browseLoaded ) {
			return; }
		var $list = $( '#idl-browse-list' );
		if ( ! $list.length ) {
			return; }

		$.post(
			IDL.ajaxUrl,
			{
				action:      'idl_browse_category',
				nonce:       IDL.nonce,
				download_id: postId,
			},
			function ( res ) {
				browseLoaded = true;
				$list.empty();
				if ( ! res.success || ! res.data.files || res.data.files.length === 0 ) {
					$list.append( '<li class="idl-browse-empty">' + esc( IDL.i18n.noFolderFiles ) + '</li>' );
					return;
				}
				res.data.files.forEach(
					function ( item ) {
						var $li = $( '<li class="idl-browse-item"></li>' );
						$li.append( '<span class="idl-browse-name">' + esc( item.name ) + '</span>' );
						$li.append( '<span class="idl-browse-size">' + esc( formatSize( item.size ) ) + '</span>' );
						if ( item.tracked ) {
								$li.append( '<span class="idl-browse-tag">' + esc( IDL.i18n.alreadyLinked ) + '</span>' );
								$li.addClass( 'is-tracked' );
						} else {
							var $btn = $( '<button type="button" class="button button-small"></button>' ).text( IDL.i18n.linkButton );
							$btn.on(
								'click',
								function () {
									$btn.prop( 'disabled', true ).text( IDL.i18n.linking );
									$.post(
										IDL.ajaxUrl,
										{
											action:      'idl_import_file',
											nonce:       IDL.nonce,
											download_id: postId,
											rel_path:    item.rel,
										},
										function ( r ) {
											if ( r.success ) {
												appendFileRow( r.data.file );
												$li.addClass( 'is-tracked' );
												$btn.replaceWith( '<span class="idl-browse-tag">' + esc( IDL.i18n.linked ) + '</span>' );
											} else {
												$btn.prop( 'disabled', false ).text( IDL.i18n.retry );
												alert( r.data && r.data.message ? r.data.message : IDL.i18n.error );
											}
										}
									);
								}
							);
							$li.append( $btn );
						}
						$list.append( $li );
					}
				);
			}
		);
	}

	// Reset browse cache when switching away and back (picks up newly uploaded files).
	$( '.idl-tab-nav' ).on(
		'click',
		'.idl-tab-btn',
		function () {
			browseLoaded = false;
		}
	);

	// -------------------------------------------------------------------------
	// External link
	// -------------------------------------------------------------------------
	$( document ).on(
		'click',
		'.idl-btn-ext-save',
		function () {
			var url      = $( '#idl-ext-url' ).val().trim();
			var title    = $( '#idl-ext-title' ).val().trim();
			var isMirror = $( '#idl-ext-mirror' ).is( ':checked' ) ? 1 : 0;

			if ( ! url ) {
				$( '#idl-ext-url' ).trigger( 'focus' );
				return;
			}

			$.post(
				IDL.ajaxUrl,
				{
					action:      'idl_add_external',
					nonce:       IDL.nonce,
					download_id: postId,
					url:         url,
					title:       title,
					is_mirror:   isMirror,
				},
				function ( res ) {
					if ( res.success ) {
						// Fetch fresh row by reloading — external add does not return full row.
						location.reload();
					} else {
						alert( res.data && res.data.message ? res.data.message : IDL.i18n.error );
					}
				}
			);
		}
	);

} )( jQuery, IDL );
