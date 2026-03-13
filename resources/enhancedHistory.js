mw.hook( 'enhanced.versionhistory' ).add( ( gridCfg ) => {
	gridCfg.style = '';

	gridCfg.columns.pv_version = { // eslint-disable-line camelcase
		headerText: mw.message( 'pageversions-versionhistory-grid-header-version' ).text(),
		type: 'text',
		sortable: false
	};
} );
