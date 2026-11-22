/**
 * Client-side navigation + the sliding menu pill.
 *
 * Clicking an internal link no longer reloads the page. WordPress's router
 * (@wordpress/interactivity-router) fetches the next page and swaps only
 * <main> (marked as a router region in ClientNavigation.php). The header
 * stays on screen, so the menu pill can slide to the new link.
 *
 * The router does the hard parts: it loads the new page's CSS and script
 * modules, updates the title, keeps history working and tells screen
 * readers the page loaded. This file adds what it leaves out:
 * - scroll to the top, and move focus to the new page's heading;
 * - mark the current menu link and move the pill;
 * - close the mobile menu after a link in it is used;
 * - filter links (the Project Grid's technology chips, marked with
 *   data-dsg-filter) stay in place instead: no scroll to the top, focus
 *   stays on the chosen chip, and only the cards animate in.
 *
 * Anything unexpected falls back to a normal page load.
 */
import {
	decodeCloudflareEmail,
	isCurrentPath,
	isRoutableLink,
	shouldHandleClick,
} from './utils.js';

// "nav." matters: the Navigation block also copies its custom class onto
// the inner <ul>, and the pill lives in the <nav>, outside that list.
const PILL_NAV = 'nav.dsg-pill-nav';
const REGION = 'main[data-wp-router-region]';

// The latest navigation started; an older one that finishes later is ignored.
let latestHref = '';

/**
 * Load the router only when it's first needed (hover or click).
 *
 * @return {Promise<Object>} The router's actions.
 */
async function router() {
	const { actions } = await import( '@wordpress/interactivity-router' );
	return actions;
}

/* Menu: current link and pill ------------------------------------------ */

/**
 * Mark the menu links that point to the given page as current.
 *
 * @param {string} pagePath Path of the page being shown.
 */
function markCurrentLinks( pagePath ) {
	// The Navigation block's links, and the phone menu's (dsg/header-actions).
	document
		.querySelectorAll(
			'.wp-block-navigation-item > a[href], .site-menu__item > a[href]'
		)
		.forEach( ( link ) => {
			const url = new URL( link.href, window.location.href );
			const current =
				url.origin === window.location.origin &&
				isCurrentPath( url.pathname, pagePath );

			link.parentElement.classList.toggle( 'current-menu-item', current );
			if ( current ) {
				link.setAttribute( 'aria-current', 'page' );
			} else {
				link.removeAttribute( 'aria-current' );
			}
		} );
}

/**
 * Move a menu's pill under a link (or hide it when no link is current).
 *
 * The pill's position is passed to CSS as custom properties; the theme's
 * style.css does the drawing and the sliding transition.
 *
 * @param {HTMLElement}      nav     A pill menu.
 * @param {HTMLElement|null} link    The link to move under.
 * @param {boolean}          animate False to jump without sliding (first paint, resizes).
 */
function movePill( nav, link, animate = true ) {
	const pill = nav.querySelector( '.dsg-nav-pill' );
	if ( ! pill ) {
		return;
	}

	if ( ! link ) {
		pill.classList.remove( 'is-visible' );
		return;
	}

	// The pill is positioned inside the bar's border, so measure from there.
	const bar = pill.parentElement;
	const box = bar.getBoundingClientRect();
	const target = link.getBoundingClientRect();
	const x = target.left - box.left - bar.clientLeft;
	const y = target.top - box.top - bar.clientTop;

	pill.classList.toggle( 'is-animated', animate );
	pill.style.setProperty( '--pill-x', `${ x }px` );
	pill.style.setProperty( '--pill-y', `${ y }px` );
	pill.style.setProperty( '--pill-w', `${ target.width }px` );
	pill.style.setProperty( '--pill-h', `${ target.height }px` );
	pill.classList.add( 'is-visible' );
}

/**
 * Put every pill under its menu's current link.
 *
 * @param {boolean} animate False to jump without sliding.
 */
function placePills( animate ) {
	document.querySelectorAll( PILL_NAV ).forEach( ( nav ) => {
		movePill( nav, nav.querySelector( 'a[aria-current="page"]' ), animate );
	} );
}

/* Things outside the router's reach ------------------------------------ */

const PROTECTED_EMAIL_LINK = 'a[href^="/cdn-cgi/l/email-protection#"]';

/**
 * Put back email addresses hidden by Cloudflare (see decodeCloudflareEmail).
 *
 * Only text and attributes are changed, never elements: the router keeps
 * its own copy of the page's elements and would lose track of new ones.
 *
 * @param {HTMLElement} root Where to look.
 */
function restoreEmails( root ) {
	root.querySelectorAll( '[data-cfemail]' ).forEach( ( element ) => {
		const email = decodeCloudflareEmail(
			element.getAttribute( 'data-cfemail' )
		);
		const text = [ ...element.childNodes ].find(
			( node ) => node.nodeType === window.Node.TEXT_NODE
		);
		if ( email && text ) {
			text.data = email;
			element.removeAttribute( 'data-cfemail' );
		}
	} );

	root.querySelectorAll( PROTECTED_EMAIL_LINK ).forEach( ( link ) => {
		const email = decodeCloudflareEmail(
			link.getAttribute( 'href' ).split( '#' )[ 1 ]
		);
		if ( email ) {
			link.setAttribute( 'href', `mailto:${ email }` );
		}
	} );
}

/**
 * Point the admin bar's "Edit Page" and "Edit Site" at the page now shown.
 * The links come from <main> (see ClientNavigation::admin_bar_links()).
 *
 * @param {HTMLElement} main The page's <main>.
 */
function syncAdminBar( main ) {
	const root = document.getElementById( 'wp-admin-bar-root-default' );
	const json = main.getAttribute( 'data-dsg-admin-bar' );
	if ( ! root || ! json ) {
		return;
	}

	let links;
	try {
		links = JSON.parse( json );
	} catch {
		return;
	}

	Object.entries( links ).forEach( ( [ id, link ] ) => {
		let item = document.getElementById( `wp-admin-bar-${ id }` );

		// The first page had no such link: add it, as WordPress would.
		if ( ! item && link ) {
			item = document.createElement( 'li' );
			item.id = `wp-admin-bar-${ id }`;
			item.innerHTML = '<a class="ab-item"></a>';
			root.append( item );
		}
		if ( ! item ) {
			return;
		}

		item.hidden = ! link;
		const anchor = item.querySelector( ':scope > a' );
		if ( link && anchor ) {
			anchor.href = link.href;
			anchor.textContent = link.title;
		}
	} );
}

/**
 * Fix what the router doesn't update after it swaps <main>.
 *
 * @param {HTMLElement} main The page's <main>.
 */
function afterSwap( main ) {
	restoreEmails( main );
	syncAdminBar( main );
}

/* Navigation ----------------------------------------------------------- */

/**
 * Close the phone menu (the dsg/header-actions block's dialog) if it's open.
 * Closing it hands focus back to the menu button; focusNewContent() then
 * moves it on to the new page.
 */
function closeMobileMenu() {
	document.querySelector( 'dialog.site-menu[open]' )?.close();
}

/**
 * Move keyboard and screen reader focus to the new page's heading, so the
 * next Tab starts at the new content instead of somewhere in the old one.
 *
 * @param {HTMLElement} main The page's <main>.
 */
function focusNewContent( main ) {
	const target = main.querySelector( 'h1' ) || main;

	if ( ! target.hasAttribute( 'tabindex' ) ) {
		// Focusable by script only, not by Tab; removed again once focus leaves.
		target.setAttribute( 'tabindex', '-1' );
		target.addEventListener(
			'blur',
			() => target.removeAttribute( 'tabindex' ),
			{ once: true }
		);
	}

	target.focus( { preventScroll: true } );
}

/**
 * After a filter link: put focus back on the chip that was chosen (the
 * old one was replaced along with the rest of <main>).
 *
 * @param {HTMLElement} main The page's <main>.
 * @param {string}      href The chip's href attribute.
 * @return {boolean} True when the chip was found and focused.
 */
function focusFilter( main, href ) {
	const chip = [ ...main.querySelectorAll( 'a[data-dsg-filter]' ) ].find(
		( link ) => link.getAttribute( 'href' ) === href
	);

	chip?.focus( { preventScroll: true } );
	return Boolean( chip );
}

/**
 * Replay an entrance animation (see style.css).
 *
 * @param {HTMLElement|null} element The element to animate.
 */
function playEntrance( element ) {
	if ( ! element ) {
		return;
	}
	element.classList.remove( 'is-entering' );
	// Reading a layout value restarts the CSS animation.
	void element.offsetWidth; // eslint-disable-line no-void
	element.classList.add( 'is-entering' );
}

/**
 * Show another page of the site without a full reload.
 *
 * @param {string} href         The page URL.
 * @param {string} [filterHref] For filter links: the chip's href attribute.
 */
async function navigate( href, filterHref = '' ) {
	const main = document.querySelector( REGION );
	if ( ! main ) {
		window.location.assign( href );
		return;
	}

	latestHref = href;
	main.setAttribute( 'aria-busy', 'true' );

	try {
		await ( await router() ).navigate( href );
	} catch {
		window.location.assign( href );
		return;
	} finally {
		main.removeAttribute( 'aria-busy' );
	}

	// Another link was clicked while this page was loading.
	if ( latestHref !== href ) {
		return;
	}

	afterSwap( main );
	markCurrentLinks( window.location.pathname );
	placePills( true );
	closeMobileMenu();

	// Filtering: stay where the visitor is, and only animate the cards.
	if ( filterHref && focusFilter( main, filterHref ) ) {
		playEntrance(
			main.querySelector( '.wp-block-dsg-project-grid__items' )
		);
		return;
	}

	// The router scrolls to a #section by itself; otherwise start at the top.
	if ( ! new URL( href, window.location.href ).hash ) {
		window.scrollTo( { top: 0, behavior: 'instant' } );
	}

	focusNewContent( main );
	playEntrance( main );
}

/* Events --------------------------------------------------------------- */

document.addEventListener( 'click', ( event ) => {
	const link = event.target.closest?.( 'a' );
	if ( ! shouldHandleClick( event, link ) ) {
		return;
	}

	event.preventDefault();

	// A link in the phone menu: close it now, not once the page has loaded.
	if ( link.closest( '.site-menu' ) ) {
		closeMobileMenu();
	}

	// Slide the pill right away, while the page loads.
	const nav = link.closest( PILL_NAV );
	if ( nav ) {
		movePill( nav, link, true );
	}

	navigate(
		link.href,
		link.hasAttribute( 'data-dsg-filter' )
			? link.getAttribute( 'href' )
			: ''
	);
} );

// Start downloading a page when the pointer or keyboard focus reaches its link.
const prefetch = async ( event ) => {
	const link = event.target.closest?.( 'a' );
	if ( isRoutableLink( link ) ) {
		( await router() ).prefetch( link.href );
	}
};
document.addEventListener( 'pointerover', prefetch );
document.addEventListener( 'focusin', prefetch );

// Back/forward: the router swaps the content; keep the menu in step.
window.addEventListener( 'popstate', () => {
	markCurrentLinks( window.location.pathname );
	placePills( true );
} );

// The router swaps a back/forward page in on its own schedule, so watch
// <main> for it. (Link clicks call afterSwap() directly, in navigate().)
const region = document.querySelector( REGION );
if ( region ) {
	let pending = false;
	new window.MutationObserver( () => {
		if ( pending ) {
			return;
		}
		pending = true;
		window.requestAnimationFrame( () => {
			pending = false;
			afterSwap( region );
		} );
	} ).observe( region, {
		childList: true,
		subtree: true,
		attributes: true,
		attributeFilter: [ 'data-dsg-admin-bar' ],
	} );
}

// Place the pills without sliding: on load, once fonts change the link
// widths, and whenever the menu changes size.
placePills( false );
document.fonts?.ready.then( () => placePills( false ) );
const resizeObserver = new window.ResizeObserver( () => placePills( false ) );
document
	.querySelectorAll( `${ PILL_NAV } .wp-block-navigation__container` )
	.forEach( ( list ) => resizeObserver.observe( list ) );
