/**
 * Light/dark toggle (the button printed by dsg-core's dsg/header-actions block).
 *
 * Until the visitor clicks it, the site follows their system setting
 * (prefers-color-scheme), including when that changes while the page is
 * open. A click saves their choice in localStorage, and that wins from then
 * on. Dark is the fallback. Light sets data-theme="light" on <html>, which
 * swaps in the light palette (see functions.php). An inline script in
 * <head> picks the scheme on each page load before anything is painted.
 *
 * The button ships with `hidden`, so visitors without JavaScript never see
 * a button that does nothing.
 */

// Same key as DSG_THEME_SCHEME_KEY in functions.php.
const KEY = 'dsg-color-scheme';
const BUTTONS = '.color-scheme-toggle';
const root = document.documentElement;
const systemLight = window.matchMedia( '(prefers-color-scheme: light)' );

/**
 * The scheme on screen right now.
 *
 * @return {'light'|'dark'} The current scheme.
 */
function current() {
	return root.dataset.theme === 'light' ? 'light' : 'dark';
}

/**
 * Show a scheme and update every toggle's state to match.
 *
 * @param {'light'|'dark'} scheme Scheme to show.
 */
function apply( scheme ) {
	root.dataset.theme = scheme;
	document.querySelectorAll( BUTTONS ).forEach( ( button ) => {
		button.setAttribute( 'aria-pressed', String( scheme === 'dark' ) );
	} );
}

/**
 * The choice saved by a click, if any.
 *
 * @return {string|null} 'light', 'dark' or null.
 */
function saved() {
	try {
		return window.localStorage.getItem( KEY );
	} catch {
		return null;
	}
}

/**
 * Remember the choice. Fails quietly where storage is blocked.
 *
 * @param {'light'|'dark'} scheme Scheme to save.
 */
function save( scheme ) {
	try {
		window.localStorage.setItem( KEY, scheme );
	} catch {}
}

document.querySelectorAll( BUTTONS ).forEach( ( button ) => {
	button.hidden = false;
	button.addEventListener( 'click', () => {
		const next = current() === 'dark' ? 'light' : 'dark';
		apply( next );
		save( next );
	} );
} );

apply( current() );

// No choice made yet: follow the system setting when it changes.
systemLight.addEventListener( 'change', () => {
	if ( ! saved() ) {
		apply( systemLight.matches ? 'light' : 'dark' );
	}
} );

// Keep other open tabs of the site in step.
window.addEventListener( 'storage', ( event ) => {
	if ( event.key === KEY ) {
		apply( event.newValue === 'light' ? 'light' : 'dark' );
	}
} );
