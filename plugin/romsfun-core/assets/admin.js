/**
 * Screenshot gallery picker for the ROM editor.
 *
 * Uses the media modal WordPress already ships rather than a custom uploader, so uploads land in
 * the library like any other attachment and stay reusable.
 */
( function ( $ ) {
	'use strict';

	var frame;
	var $input = $( '#rf-screenshots-input' );
	var $list  = $( '#rf-screenshots' );

	if ( ! $input.length ) {
		return;
	}

	function currentIds() {
		return $input.val().split( ',' ).filter( Boolean ).map( Number );
	}

	function writeIds( ids ) {
		$input.val( ids.join( ',' ) );
	}

	$( '#rf-screenshots-add' ).on( 'click', function ( e ) {
		e.preventDefault();

		frame = wp.media( {
			title:    'Select screenshots',
			multiple: 'add',
			library:  { type: 'image' },
			button:   { text: 'Use these images' }
		} );

		frame.on( 'open', function () {
			var selection = frame.state().get( 'selection' );
			currentIds().forEach( function ( id ) {
				var attachment = wp.media.attachment( id );
				attachment.fetch();
				selection.add( attachment );
			} );
		} );

		frame.on( 'select', function () {
			var ids = [];
			$list.empty();

			frame.state().get( 'selection' ).each( function ( attachment ) {
				var a    = attachment.toJSON();
				var size = ( a.sizes && a.sizes.thumbnail ) ? a.sizes.thumbnail.url : a.url;

				ids.push( a.id );
				$list.append(
					$( '<span class="rf-shot-admin"></span>' )
						.attr( 'data-id', a.id )
						.append( $( '<img>' ).attr( { src: size, width: 120, height: 68 } ) )
						.append( '<button type="button" class="rf-shot-remove" aria-label="Remove">&times;</button>' )
				);
			} );

			writeIds( ids );
		} );

		frame.open();
	} );

	// Delegated so it keeps working for thumbnails added after page load.
	$list.on( 'click', '.rf-shot-remove', function () {
		var $item = $( this ).closest( '.rf-shot-admin' );
		var id    = Number( $item.data( 'id' ) );

		$item.remove();
		writeIds( currentIds().filter( function ( existing ) {
			return existing !== id;
		} ) );
	} );
}( jQuery ) );
