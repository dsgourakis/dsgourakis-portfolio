/**
 * Header Actions: the phone menu (a full-screen <dialog>, see render.php).
 *
 * The open and close buttons use HTML invoker commands
 * (commandfor="site-menu" command="show-modal" / "close"), so current
 * browsers need no script for them. <dialog> itself traps focus, closes on
 * Escape and hands focus back to the menu button.
 *
 * This adds:
 * - the same buttons for browsers without invoker commands;
 * - closing the menu when the screen gets wide enough for the normal menu
 *   (e.g. a tablet turned sideways), so it can't be stuck open but hidden.
 *
 * Closing it after a link is used is done by the client-navigation script.
 */

const menu = document.getElementById( 'site-menu' );

if ( menu ) {
	if ( ! ( 'command' in window.HTMLButtonElement.prototype ) ) {
		document
			.querySelectorAll( '[commandfor="site-menu"]' )
			.forEach( ( button ) => {
				button.addEventListener( 'click', () => {
					if ( button.getAttribute( 'command' ) === 'close' ) {
						menu.close();
					} else {
						menu.showModal();
					}
				} );
			} );
	}

	const wide = window.matchMedia( '(min-width: 782px)' );
	wide.addEventListener( 'change', () => {
		if ( wide.matches && menu.open ) {
			menu.close();
		}
	} );
}
