document.addEventListener( 'DOMContentLoaded', function () {
	const buttons = document.querySelectorAll( '.mva-tab-button' );
	const panels = document.querySelectorAll( '.mva-tab-panel' );

	buttons.forEach( function ( button ) {
		button.addEventListener( 'click', function () {
			buttons.forEach( b => b.classList.remove( 'active' ) );
			panels.forEach( p => p.classList.remove( 'active' ) );

			button.classList.add( 'active' );
			let target = document.getElementById( button.dataset.target );
			if ( target ) {
				target.classList.add( 'active' );
			}
		} );
	} );
} );