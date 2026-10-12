( function () {
	document.addEventListener( 'DOMContentLoaded', function () {
		var contact = document.querySelector( '.wla-contact' );
		if ( ! contact ) return;
		var toggle = contact.querySelector( '.wla-contact-button' );
		var panel = contact.querySelector( '.wla-agent-panel' );
		var close = contact.querySelector( '.wla-panel-close' );
		function syncAvailability() {
			var available = Array.prototype.some.call( contact.querySelectorAll( '.wla-agent-link' ), function ( link ) {
				return window.getComputedStyle( link ).display !== 'none';
			} );
			contact.hidden = ! available;
			if ( ! available ) {
				panel.hidden = true;
				toggle.setAttribute( 'aria-expanded', 'false' );
			}
		}
		function setOpen( open, restoreFocus ) {
			panel.hidden = ! open;
			toggle.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
			if ( open ) {
				var firstAvailable = Array.prototype.find.call( contact.querySelectorAll( '.wla-agent-link' ), function ( link ) {
					return window.getComputedStyle( link ).display !== 'none';
				} );
				if ( firstAvailable ) firstAvailable.focus();
			} else if ( false !== restoreFocus ) toggle.focus();
		}
		toggle.addEventListener( 'click', function () { setOpen( panel.hidden ); } );
		close.addEventListener( 'click', function () { setOpen( false ); } );
		document.addEventListener( 'keydown', function ( event ) {
			if ( 'Escape' === event.key && ! panel.hidden ) setOpen( false );
		} );
		document.addEventListener( 'click', function ( event ) {
			if ( ! panel.hidden && ! contact.contains( event.target ) ) setOpen( false, false );
		} );
		syncAvailability();
		window.addEventListener( 'resize', syncAvailability );
	} );
} )();
