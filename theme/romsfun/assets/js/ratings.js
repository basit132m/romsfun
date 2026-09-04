/**
 * Visitor rating widget.
 *
 * The page HTML is served from Varnish and Cloudflare, so the numbers rendered server-side can be
 * a few minutes stale. That is an acceptable trade for a cacheable page; the moment someone votes
 * we update the DOM from the API response so their own action is reflected immediately.
 */
( function () {
	'use strict';

	var widget = document.querySelector( '[data-rf-rate]' );
	if ( !widget ) {
		return;
	}

	var postId   = Number( widget.getAttribute( 'data-rf-rate' ) );
	var endpoint = widget.getAttribute( 'data-rf-endpoint' );
	var stars    = Array.prototype.slice.call( widget.querySelectorAll( '[data-rf-star]' ) );
	var status   = document.querySelector( '[data-rf-rate-status]' );
	var done     = widget.getAttribute( 'data-rf-rated' ) === '1';

	function paint( upTo ) {
		stars.forEach( function ( star, i ) {
			star.classList.toggle( 'is-on', i < upTo );
		} );
	}

	function reset() {
		paint( Number( widget.getAttribute( 'data-rf-current' ) ) || 0 );
	}

	function refreshSummary( data ) {
		var avgEl   = document.querySelector( '[data-rf-avg]' );
		var countEl = document.querySelector( '[data-rf-count]' );

		if ( avgEl ) {
			avgEl.textContent = Number( data.average ).toFixed( 1 );
		}

		if ( countEl ) {
			countEl.textContent = data.count;
		}

		Object.keys( data.distribution || {} ).forEach( function ( star ) {
			var row = document.querySelector( '[data-rf-bar="' + star + '"]' );
			var num = document.querySelector( '[data-rf-bar-count="' + star + '"]' );
			if ( !row ) {
				return;
			}
			var pct = data.count ? ( data.distribution[ star ] / data.count ) * 100 : 0;
			row.style.setProperty( '--rf-bar', pct.toFixed( 1 ) + '%' );
			if ( num ) {
				num.textContent = data.distribution[ star ];
			}
		} );

		widget.setAttribute( 'data-rf-current', Math.round( data.average ) );
	}

	function submit( value ) {
		if ( done ) {
			return;
		}

		done = true;
		widget.classList.add( 'is-busy' );

		fetch( endpoint, {
			method:  'POST',
			headers: { 'Content-Type': 'application/json' },
			body:    JSON.stringify( { post_id: postId, stars: value } )
		} )
			.then( function ( r ) { return r.json(); } )
			.then( function ( data ) {
				widget.classList.remove( 'is-busy' );
				widget.classList.add( 'is-rated' );
				widget.setAttribute( 'data-rf-rated', '1' );

				if ( status ) {
					status.textContent = data.message || '';
				}

				refreshSummary( data );
				paint( value );
			} )
			.catch( function () {
				widget.classList.remove( 'is-busy' );
				done = false;
				if ( status ) {
					status.textContent = 'Could not save your rating. Please try again.';
				}
			} );
	}

	stars.forEach( function ( star, i ) {
		star.addEventListener( 'mouseenter', function () {
			if ( !done ) { paint( i + 1 ); }
		} );
		star.addEventListener( 'focus', function () {
			if ( !done ) { paint( i + 1 ); }
		} );
		star.addEventListener( 'click', function () { submit( i + 1 ); } );
	} );

	widget.addEventListener( 'mouseleave', function () {
		if ( !done ) { reset(); }
	} );
}() );
