jQuery( document ).ready( function($) {
	if ( $.cookie('toggler_current_tab') ) {
		$( 'div.tabbed.active' ).removeClass( 'active' );
		$( $.cookie( 'toggler_current_tab' ) ).addClass( 'active' );
		$( 'h2.nav-tab-wrapper a.nav-tab-active' ).removeClass( 'nav-tab-active' );
		$( 'h2.nav-tab-wrapper a[href="' + $.cookie( 'toggler_current_tab' ) + '"]' ).addClass( 'nav-tab-active' );
	}

	$( 'div.tabbed' ).not( '.active' ).hide();

	$( 'h2.nav-tab-wrapper a' ).click( function( e ) {
		e.preventDefault();
		var $this = $( this );
		var $cur = $( 'h2.nav-tab-wrapper a.nav-tab-active' );

		if ( $this.hasClass( 'nav-tab-active' ) ) {
			return false;
		}

		$.cookie( 'toggler_current_tab', $this.attr( 'href' ) );
		$this.addClass( 'nav-tab-active' );
		$cur.removeClass( 'nav-tab-active' );
		$( 'div.tabbed.active' ).removeClass( 'active' ).hide();
		$( $this.attr( 'href' ) ).addClass( 'active' ).show();
	});

	$( '#reactivated-list' ).hide();

	$( 'a#reactivated-list-toggle' ).click( function( e ) {
		e.preventDefault();

		if ( $( '#reactivated-list' ).is( ':hidden' ) ) {
			$( this ).html( 'Hide List' );
			$( '#reactivated-list' ).slideDown();
		} else {
			$( this ).html( 'Show List' );
			$( '#reactivated-list' ).slideUp();
		}
	});
});