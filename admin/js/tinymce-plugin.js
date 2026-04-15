/**
 * TinyMCE plugin for i-Downloads — "Insert Download [iD]" toolbar button.
 *
 * Adds a button that opens the search modal (#idl-tmce-modal).
 * On selection, inserts [idl_download id=X] at the cursor position.
 */
( function () {
	'use strict';

	var DEBOUNCE_MS   = 300;
	var debounceTimer = null;

	tinymce.PluginManager.add(
		'idl_insert',
		function ( editor ) {

			// ── Button ─────────────────────────────────────────────────────────────
			editor.addButton(
				'idl_insert',
				{
					title: IDLTmce.i18n.insertDownload,
					icon: 'dashicons-download',
					tooltip: IDLTmce.i18n.insertDownload,
					onclick: openModal,
				}
			);

			// ── Modal helpers ──────────────────────────────────────────────────────
			function getModal() {
				return document.getElementById( 'idl-tmce-modal' );
			}

			function openModal() {
				var modal = getModal();
				if ( ! modal ) {
					return;
				}

				modal.removeAttribute( 'hidden' );

				var searchInput = document.getElementById( 'idl-tmce-search' );
				var catSelect   = document.getElementById( 'idl-tmce-category' );

				// Reset state
				searchInput.value = '';
				catSelect.value   = '0';
				fetchResults( '', 0 );

				// Autofocus search
				setTimeout(
					function () {
						searchInput.focus(); },
					50
				);

				// Bind events (attach once via flag)
				if ( ! modal._idlBound ) {
					modal._idlBound = true;

					// Backdrop click closes
					modal.querySelector( '.idl-tmce-modal__backdrop' ).addEventListener( 'click', closeModal );
					modal.querySelector( '.idl-tmce-modal__close' ).addEventListener( 'click', closeModal );
					modal.querySelector( '.idl-tmce-modal__cancel' ).addEventListener( 'click', closeModal );

					// Search input — debounced
					searchInput.addEventListener(
						'input',
						function () {
							clearTimeout( debounceTimer );
							debounceTimer = setTimeout(
								function () {
									fetchResults( searchInput.value, parseInt( catSelect.value, 10 ) || 0 );
								},
								DEBOUNCE_MS
							);
						}
					);

					// Category filter — immediate
					catSelect.addEventListener(
						'change',
						function () {
							fetchResults( searchInput.value, parseInt( catSelect.value, 10 ) || 0 );
						}
					);

					// Result click — event delegation
					document.getElementById( 'idl-tmce-results' ).addEventListener(
						'click',
						function ( e ) {
							var btn = e.target.closest( '.idl-tmce-modal__item' );
							if ( btn ) {
								insertDownload( parseInt( btn.dataset.id, 10 ) );
							}
						}
					);

					// Esc key
					document.addEventListener(
						'keydown',
						function ( e ) {
							if ( e.key === 'Escape' && ! getModal().hasAttribute( 'hidden' ) ) {
								closeModal();
							}
						}
					);
				}
			}

			function closeModal() {
				var modal = getModal();
				if ( modal ) {
					modal.setAttribute( 'hidden', '' );
				}
			}

			function fetchResults( search, category ) {
				var resultsEl         = document.getElementById( 'idl-tmce-results' );
				resultsEl.textContent = '';
				var loading           = document.createElement( 'p' );
				loading.className     = 'idl-tmce-modal__loading';
				loading.textContent   = IDLTmce.i18n.loading;
				resultsEl.appendChild( loading );

				var data = new FormData();
				data.append( 'action',   'idl_tmce_search' );
				data.append( 'nonce',    IDLTmce.nonce );
				data.append( 'search',   search );
				data.append( 'category', category );

				function showError() {
					resultsEl.textContent = '';
					var err               = document.createElement( 'p' );
					err.className         = 'idl-tmce-modal__empty';
					err.textContent       = IDLTmce.i18n.loadError;
					resultsEl.appendChild( err );
				}

				fetch( IDLTmce.ajaxUrl, { method: 'POST', body: data } )
				.then(
					function ( r ) {
						return r.json(); }
				)
					.then(
						function ( json ) {
							if ( json.success ) {
									resultsEl.innerHTML = json.data.html;
							} else {
								showError();
							}
						}
					)
					.catch( showError );
			}

			function insertDownload( id ) {
				if ( ! id ) {
					return;
				}
				editor.insertContent( '[idl_download id=' + id + ']' );
				closeModal();
			}
		}
	);
} )();
