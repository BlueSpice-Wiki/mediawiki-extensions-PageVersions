window.ext = window.ext || {};
window.ext.pageVersions = window.ext.pageVersions || {};

window.ext.pageVersions.ui = {
	workflows: {}
};
window.ext.pageVersions.VERSION_PATCH = 'patch';
window.ext.pageVersions.VERSION_MINOR = 'minor';
window.ext.pageVersions.VERSION_MAJOR = 'major';

window.ext.pageVersions.api = {
	createVersion: async ( revisionId, type, comment ) => {
		return await ext.pageVersions.api._ajax( 'create', {
			revision: revisionId,
			type: type,
			comment: comment
		}, 'POST' );
	},
	deleteVersion: async ( revisionId ) => {
		return await ext.pageVersions.api._ajax( 'delete', {
			revision: revisionId
		}, 'POST' );
	},
	getNextVersions: async ( pageId ) => {
		return await ext.pageVersions.api._ajax( 'next/' + pageId, {}, 'GET' );

	},
	_ajax: async ( path, params, method ) => {
		const base = mw.util.wikiScript( 'rest' ) + '/page_versions/';
		let url = base + path;

		const options = {
			method: method.toUpperCase(),
			headers: {
				'Content-Type': 'application/json'
			}
		};

		if ( options.method === 'POST' ) {
			options.body = JSON.stringify( params );
		} else if ( Object.keys( params ).length ) {
			const query = new URLSearchParams( params ).toString();
			url += ( url.includes( '?' ) ? '&' : '?' ) + query;
		}

		return fetch( url, options ).then( ( res ) => {
			if ( !res.ok ) {
				throw new Error( `REST request failed: ${res.status}` );
			}
			return res.json();
		} );
	}
};

$( () => {
	$( '#ca-createPageVersion' ).on( 'click', async ( e ) => {
		await mw.loader.using( [ 'ext.pageVersions.create' ] );

		const wm = OO.ui.getWindowManager();
		const dialog = new ext.pageVersions.ui.PageVersionDialog();
		wm.addWindows( [ dialog ] );
		wm.openWindow( dialog ).closed.then( ( data ) => {
			if ( data && data.action === 'create' ) {
				window.location.reload();
			}
		} );

	} );
} );