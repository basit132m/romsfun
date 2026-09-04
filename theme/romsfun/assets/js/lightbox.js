/**
 * Screenshot lightbox.
 *
 * Vanilla, ~3KB, loaded only on ROM pages that actually have screenshots. A library here would
 * mean shipping tens of kilobytes to every visitor for a feature most of them never open.
 */
( function () {
	'use strict';

	var gallery = document.querySelector( '[data-rf-gallery]' );
	if ( !gallery ) {
		return;
	}

	var links   = Array.prototype.slice.call( gallery.querySelectorAll( '[data-rf-shot]' ) );
	var index   = 0;
	var lastFocused = null;

	var box = document.createElement( 'div' );
	box.className = 'rf-lightbox';
	box.setAttribute( 'role', 'dialog' );
	box.setAttribute( 'aria-modal', 'true' );
	box.setAttribute( 'aria-label', 'Screenshot viewer' );
	box.hidden = true;
	box.innerHTML =
		'<button class="rf-lightbox__close" aria-label="Close">&times;</button>' +
		'<button class="rf-lightbox__nav rf-lightbox__nav--prev" aria-label="Previous">&#8249;</button>' +
		'<figure class="rf-lightbox__figure"><img alt=""><figcaption></figcaption></figure>' +
		'<button class="rf-lightbox__nav rf-lightbox__nav--next" aria-label="Next">&#8250;</button>';
	document.body.appendChild( box );

	var img     = box.querySelector( 'img' );
	var caption = box.querySelector( 'figcaption' );
	var btnClose = box.querySelector( '.rf-lightbox__close' );

	function show( i ) {
		index = ( i + links.length ) % links.length;

		var link = links[ index ];
		img.src = link.getAttribute( 'href' );
		img.alt = link.getAttribute( 'data-alt' ) || '';
		caption.textContent = ( index + 1 ) + ' / ' + links.length;
	}

	function open( i ) {
		lastFocused = document.activeElement;
		show( i );
		box.hidden = false;
		// Stops the page behind from scrolling while the viewer is up.
		document.body.style.overflow = 'hidden';
		btnClose.focus();
	}

	function close() {
		box.hidden = true;
		document.body.style.overflow = '';
		img.src = '';
		if ( lastFocused ) {
			lastFocused.focus();
		}
	}

	links.forEach( function ( link, i ) {
		link.addEventListener( 'click', function ( e ) {
			e.preventDefault();
			open( i );
		} );
	} );

	btnClose.addEventListener( 'click', close );
	box.querySelector( '.rf-lightbox__nav--prev' ).addEventListener( 'click', function () { show( index - 1 ); } );
	box.querySelector( '.rf-lightbox__nav--next' ).addEventListener( 'click', function () { show( index + 1 ); } );

	// Clicking the backdrop closes; clicking the image itself must not.
	box.addEventListener( 'click', function ( e ) {
		if ( e.target === box ) {
			close();
		}
	} );

	document.addEventListener( 'keydown', function ( e ) {
		if ( box.hidden ) {
			return;
		}

		if ( e.key === 'Escape' ) {
			close();
		} else if ( e.key === 'ArrowLeft' ) {
			show( index - 1 );
		} else if ( e.key === 'ArrowRight' ) {
			show( index + 1 );
		} else if ( e.key === 'Tab' ) {
			// Keep focus inside the dialog while it is open.
			e.preventDefault();
			btnClose.focus();
		}
	} );
}() );
