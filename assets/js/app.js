// Profile menu toggle.
( function () {
	const initProfileMenu = () => {
		const container = document.getElementById( 'profile-navigation-container' );
		if ( ! container ) {
			return;
		}

		const button = document.getElementById( 'profile-navigation-button' );
		if ( ! button ) {
			return;
		}

		const menu = document.getElementById( 'profile-navigation' );
		if ( ! menu ) {
			return;
		}

		const collapseMenuOnClickOutside = ( event ) => {
			if ( ! container.contains( event.target ) ) {
				button.setAttribute( 'aria-expanded', 'false' );
				menu.classList.add( 'hidden' );
				document.removeEventListener( 'click', collapseMenuOnClickOutside );
			}
		}

		button.addEventListener( 'click', () => {
			if ( 'true' === button.getAttribute( 'aria-expanded' ) ) {
				button.setAttribute( 'aria-expanded', 'false' );
				menu.classList.add( 'hidden' );
				document.removeEventListener( 'click', collapseMenuOnClickOutside );
			} else {
				button.setAttribute( 'aria-expanded', 'true' );
				menu.classList.remove( 'hidden' );
				document.addEventListener( 'click', collapseMenuOnClickOutside );
			}
		} );
	};

	const init = () => {
		initProfileMenu();
	};

	if (
		document.readyState === 'complete' || // DOMContentLoaded + Images/Styles/etc loaded, so we call directly.
		document.readyState === 'interactive' // DOMContentLoaded fires at this point, so we call directly.
	) {
		init();
	} else {
		// DOMContentLoaded has not fired yet, delay callback until then.
		document.addEventListener( 'DOMContentLoaded', init );
	}
} )();
