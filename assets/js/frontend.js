/* global uuprData */
( function () {
	'use strict';

	function ready( fn ) {
		if ( 'loading' !== document.readyState ) {
			fn();
		} else {
			document.addEventListener( 'DOMContentLoaded', fn );
		}
	}

	ready( function () {
		var form = document.getElementById( 'uupr-form' );
		if ( ! form ) {
			return;
		}

		var status  = document.getElementById( 'uupr-status' );
		var input   = document.getElementById( 'uupr-avatar-input' );
		var preview = document.getElementById( 'uupr-photo-preview' );
		var remove  = document.getElementById( 'uupr-remove-photo' );

		function setStatus( message, isError ) {
			status.textContent = message || '';
			status.className = 'uupr-status' + ( isError ? ' uupr-status-error' : '' );
		}

		// Live preview + client-side size guard (server still enforces).
		if ( input ) {
			input.addEventListener( 'change', function () {
				if ( ! input.files || ! input.files.length ) {
					return;
				}
				var file = input.files[ 0 ];
				if ( file.size > uuprData.maxBytes ) {
					setStatus( uuprData.i18n.tooLarge, true );
					input.value = '';
					return;
				}
				setStatus( '' );
				var reader = new FileReader();
				reader.onload = function ( e ) {
					var img = preview.querySelector( 'img' );
					if ( img ) {
						img.src = e.target.result;
					}
				};
				reader.readAsDataURL( file );
			} );
		}

		form.addEventListener( 'submit', function ( e ) {
			e.preventDefault();
			setStatus( uuprData.i18n.saving );

			var data = new FormData( form );
			data.append( 'action', 'uupr_save_profile' );
			data.append( 'nonce', uuprData.nonce );

			fetch( uuprData.ajaxUrl, {
				method: 'POST',
				credentials: 'same-origin',
				body: data
			} )
				.then( function ( r ) { return r.json(); } )
				.then( function ( res ) {
					if ( res && res.success ) {
						setStatus( res.data.message || uuprData.i18n.saved );
						if ( input ) {
							input.value = '';
						}
					} else {
						setStatus( ( res && res.data && res.data.message ) || uuprData.i18n.genericErr, true );
					}
				} )
				.catch( function () {
					setStatus( uuprData.i18n.genericErr, true );
				} );
		} );

		if ( remove ) {
			remove.addEventListener( 'click', function () {
				if ( ! window.confirm( uuprData.i18n.confirmDel ) ) {
					return;
				}
				var data = new FormData();
				data.append( 'action', 'uupr_delete_avatar' );
				data.append( 'nonce', uuprData.nonce );

				fetch( uuprData.ajaxUrl, {
					method: 'POST',
					credentials: 'same-origin',
					body: data
				} )
					.then( function ( r ) { return r.json(); } )
					.then( function ( res ) {
						if ( res && res.success ) {
							setStatus( res.data.message );
							var img = preview.querySelector( 'img' );
							if ( img && res.data.avatarUrl ) {
								img.src = res.data.avatarUrl;
							}
							remove.parentNode.removeChild( remove );
						} else {
							setStatus( ( res && res.data && res.data.message ) || uuprData.i18n.genericErr, true );
						}
					} )
					.catch( function () {
						setStatus( uuprData.i18n.genericErr, true );
					} );
			} );
		}
	} );
}() );
