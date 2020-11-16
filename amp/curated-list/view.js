/* eslint-disable */

let isFetching = false;
let isEndOfData = false;
buildLoadMoreHandler( document.querySelector( '.newspack-listings__curated-list' ) );
buildSortHandler( document.querySelector( '.newspack-listings__curated-list' ) );
function buildLoadMoreHandler( blockWrapperEl ) {
	const btnEl = blockWrapperEl.querySelector( '[data-next]' );
	if ( ! btnEl ) return;
	const postsContainerEl = blockWrapperEl.querySelector( '.newspack-listings__list-container' );
	const btnText = btnEl.textContent.trim();
	const loadingText = blockWrapperEl.querySelector( '.loading' ).textContent;
	btnEl.addEventListener( 'click', function() {
		if ( isFetching || isEndOfData ) return false;
		isFetching = true;
		blockWrapperEl.classList.remove( 'is-error' );
		blockWrapperEl.classList.add( 'is-loading' );
		if ( loadingText ) btnEl.textContent = loadingText;
		const requestURL = new URL( btnEl.getAttribute( 'data-next' ) );
		apiFetchWithRetry( { url: requestURL.toString(), onSuccess, onError }, 3 );
		function onSuccess( data, next ) {
			if ( isPostsDataValid( data ) ) {
				data.forEach( item => {
					const tempDIV = document.createElement( 'div' );
					tempDIV.innerHTML = item.html.trim();
					postsContainerEl.appendChild( tempDIV.childNodes[ 0 ] );
				} );
				if ( next ) btnEl.setAttribute( 'data-next', next );
				if ( ! data.length || ! next ) {
					isEndOfData = true;
					blockWrapperEl.removeChild( btnEl );
					blockWrapperEl.classList.remove( 'has-more-button' );
				}
				isFetching = false;
				blockWrapperEl.classList.remove( 'is-loading' );
				btnEl.textContent = btnText;
			}
		}
		function onError() {
			isFetching = false;
			blockWrapperEl.classList.remove( 'is-loading' );
			blockWrapperEl.classList.add( 'is-error' );
			btnEl.textContent = btnText;
		}
	} );
}
function buildSortHandler( blockWrapperEl ) {
	const sortUi = blockWrapperEl.querySelector( '.newspack-listings__sort-ui' );
	const sortBy = blockWrapperEl.querySelector( '.newspack-listings__sort-select-control' );
	const sortOrder = blockWrapperEl.querySelectorAll( 'input' );
	const btnEl = blockWrapperEl.querySelector( '[data-next]' );
	if ( ! sortBy || ! sortOrder.length || ! sortUi ) return;
	const triggers = Array.prototype.concat.call( Array.prototype.slice.call( sortOrder ), [
		sortBy,
	] );
	const postsContainerEl = blockWrapperEl.querySelector( '.newspack-listings__list-container' );
	console.log( postsContainerEl );
	const restURL = sortUi.getAttribute( 'data-url' );
	let isFetching = false;
	let _sortBy = sortUi.querySelector( '[selected]' ).value;
	let _order = sortUi.querySelector( '[checked]' ).value;
	const sortHandler = e => {
		if ( isFetching ) return false;
		isFetching = true;
		blockWrapperEl.classList.remove( 'is-error' );
		blockWrapperEl.classList.add( 'is-loading' );
		if ( e.target.tagName.toLowerCase() === 'select' ) {
			_sortBy = e.target.value;
		} else {
			_order = e.target.value;
		}
		Array.prototype.forEach.call( sortOrder, button => button.removeAttribute( 'disabled' ) );
		const requestURL = `${ restURL }&${ encodeURIComponent(
			'query[sortBy]'
		) }=${ _sortBy }&${ encodeURIComponent( 'query[order]' ) }=${ _order }`;
		apiFetchWithRetry( { url: requestURL, onSuccess, onError }, 3 );
		function onSuccess( data, next ) {
			if ( ! isPostsDataValid( data ) ) return onError();
			postsContainerEl.textContent = '';
			data.forEach( item => {
				const tempDIV = document.createElement( 'div' );
				tempDIV.innerHTML = item.html.trim();
				postsContainerEl.appendChild( tempDIV.childNodes[ 0 ] );
			} );
			if ( next && btnEl ) btnEl.setAttribute( 'data-next', next );
			isFetching = false;
			blockWrapperEl.classList.remove( 'is-loading' );
		}
		function onError() {
			isFetching = false;
			blockWrapperEl.classList.remove( 'is-loading' );
			blockWrapperEl.classList.add( 'is-error' );
		}
	};
	triggers.forEach( trigger => trigger.addEventListener( 'change', sortHandler ) );
}
function apiFetchWithRetry( options, n ) {
	const xhr = new XMLHttpRequest();
	xhr.onreadystatechange = () => {
		if ( xhr.readyState !== 4 || n === 0 ) return;
		if ( xhr.status >= 200 && xhr.status < 300 ) {
			const data = JSON.parse( xhr.responseText );
			const next = xhr.getResponseHeader( 'next-url' );
			options.onSuccess( data, next );
			return;
		}
		options.onError();
		apiFetchWithRetry( options, n - 1 );
	};
	xhr.open( 'GET', options.url );
	xhr.send();
}
function isPostsDataValid( data ) {
	if (
		data &&
		Array.isArray( data ) &&
		data.length &&
		hasOwnProp( data[ 0 ], 'html' ) &&
		typeof data[ 0 ].html === 'string'
	) {
		return true;
	}

	return false;
}
function hasOwnProp( obj, prop ) {
	return Object.prototype.hasOwnProperty.call( obj, prop );
}
