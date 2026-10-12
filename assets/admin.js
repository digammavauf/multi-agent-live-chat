( function () {
	document.addEventListener( 'DOMContentLoaded', function () {
		var list = document.getElementById( 'wla-agents' );
		var add = document.getElementById( 'wla-add-agent' );
		var template = document.getElementById( 'wla-agent-template' );
		if ( ! list || ! add || ! template ) return;
		var maxAgents = parseInt( list.getAttribute( 'data-max-agents' ), 10 ) || 25;
		var limitMessage = document.querySelector( '.wla-agent-limit' );

		function updateRow( row ) {
			var platform = row.querySelector( '.wla-agent-platform' ).value;
			var messenger = platform === 'messenger';
			var viber = platform === 'viber';
			var label = row.querySelector( '.wla-contact-label' );
			var input = label.querySelector( 'input' );
			label.firstChild.textContent = messenger ? 'Messenger username ' : ( viber ? 'Viber chat link or business number ' : 'Mobile number ' );
			input.type = ( messenger || viber ) ? 'text' : 'tel';
			input.placeholder = messenger ? 'yourpage' : ( viber ? 'https://viber.me/15551234567' : '15551234567' );
		}

		function updateNames() {
			list.querySelectorAll( '.wla-agent-row' ).forEach( function ( row, index ) {
				row.querySelectorAll( '[name]' ).forEach( function ( field ) {
					field.name = field.name.replace( /\[agents\]\[[^\]]+\]/, '[agents][' + index + ']' );
				} );
			} );
		}

		function updateLimit() {
			var count = list.querySelectorAll( '.wla-agent-row' ).length;
			var atLimit = count >= maxAgents;
			add.disabled = atLimit;
			if ( limitMessage ) {
				limitMessage.textContent = atLimit ? 'Agent limit reached (' + maxAgents + '). Remove an agent to add another.' : 'You can configure up to ' + maxAgents + ' agents. ' + count + ' currently configured.';
			}
		}

		add.addEventListener( 'click', function () {
			if ( list.querySelectorAll( '.wla-agent-row' ).length >= maxAgents ) return;
			var index = list.querySelectorAll( '.wla-agent-row' ).length;
			list.insertAdjacentHTML( 'beforeend', template.innerHTML.replace( /__INDEX__/g, String( index ) ) );
			updateRow( list.lastElementChild );
			updateLimit();
		} );

		list.addEventListener( 'change', function ( event ) {
			if ( event.target.classList.contains( 'wla-agent-platform' ) ) updateRow( event.target.closest( '.wla-agent-row' ) );
		} );
		list.addEventListener( 'click', function ( event ) {
			if ( event.target.classList.contains( 'wla-remove-agent' ) ) {
				event.target.closest( '.wla-agent-row' ).remove();
				updateNames();
				updateLimit();
			}
		} );
		updateLimit();
	} );
} )();
