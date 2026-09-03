/**
 * Customizer live preview.
 *
 * Loaded only inside the Customizer preview frame, never on the public site, so it costs visitors
 * nothing. Colours are written straight to the CSS custom properties the stylesheet already reads,
 * which is why a change shows instantly without a page reload.
 */
( function ( $ ) {
	'use strict';

	var root = document.documentElement;

	var cssVars = {
		brand_color:     '--rf-brand',
		header_bg:       '--rf-header-bg',
		header_text:     '--rf-header-text',
		footer_bg:       '--rf-footer-bg',
		footer_text:     '--rf-footer-text',
		page_bg:         '--rf-bg',
		surface_bg:      '--rf-surface',
		border_color:    '--rf-border',
		text_color:      '--rf-text',
		muted_color:     '--rf-text-muted'
	};

	Object.keys( cssVars ).forEach( function ( setting ) {
		wp.customize( setting, function ( value ) {
			value.bind( function ( to ) {
				root.style.setProperty( cssVars[ setting ], to );
			} );
		} );
	} );

	var pxVars = {
		container_width: '--rf-wrap',
		corner_radius:   '--rf-radius',
		base_font_size:  '--rf-font-size'
	};

	Object.keys( pxVars ).forEach( function ( setting ) {
		wp.customize( setting, function ( value ) {
			value.bind( function ( to ) {
				root.style.setProperty( pxVars[ setting ], parseInt( to, 10 ) + 'px' );
			} );
		} );
	} );

	wp.customize( 'logo_max_width', function ( value ) {
		value.bind( function ( to ) {
			var logo = document.querySelector( '.rf-logo img' );
			if ( logo ) {
				logo.style.maxWidth = parseInt( to, 10 ) + 'px';
			}
		} );
	} );

	wp.customize( 'download_label', function ( value ) {
		value.bind( function ( to ) {
			var label = document.querySelector( '.rf-btn__label' );
			if ( label ) {
				label.textContent = to;
			}
		} );
	} );

	wp.customize( 'footer_tagline', function ( value ) {
		value.bind( function ( to ) {
			var el = document.querySelector( '.rf-footer__tagline' );
			if ( el ) {
				el.textContent = to;
				el.hidden = ! to;
			}
		} );
	} );
}( jQuery ) );
