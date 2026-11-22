/**
 * Pure helpers for editing the list of chips. Each one returns a NEW array
 * and never changes the one passed in: React (and setAttributes) only notice
 * a change when they get a different array.
 */

/**
 * Add an item to the end of the list.
 *
 * @param {string[]} items Current items.
 * @param {string}   value Text typed by the user.
 * @return {string[]} New list. Unchanged if the value is empty or already there.
 */
export function addItem( items, value ) {
	const item = value.trim();

	if ( ! item || items.includes( item ) ) {
		return items;
	}

	return [ ...items, item ];
}

/**
 * Replace the item at `index`.
 *
 * @param {string[]} items Current items.
 * @param {number}   index Position to change.
 * @param {string}   value New text.
 * @return {string[]} New list.
 */
export function updateItem( items, index, value ) {
	return items.map( ( item, i ) => ( i === index ? value : item ) );
}

/**
 * Remove the item at `index`.
 *
 * @param {string[]} items Current items.
 * @param {number}   index Position to remove.
 * @return {string[]} New list.
 */
export function removeItem( items, index ) {
	return items.filter( ( _, i ) => i !== index );
}

/**
 * Move the item at `from` to position `to`.
 *
 * @param {string[]} items Current items.
 * @param {number}   from  Current position.
 * @param {number}   to    New position.
 * @return {string[]} New list. Unchanged if either position is out of range.
 */
export function moveItem( items, from, to ) {
	if ( from < 0 || to < 0 || from >= items.length || to >= items.length ) {
		return items;
	}

	const next = [ ...items ];
	const [ item ] = next.splice( from, 1 );
	next.splice( to, 0, item );

	return next;
}

/**
 * Drop empty entries (e.g. a chip whose text was deleted in the sidebar).
 *
 * @param {string[]} items Current items.
 * @return {string[]} Trimmed, non-empty items.
 */
export function cleanItems( items ) {
	return items.map( ( item ) => item.trim() ).filter( Boolean );
}
