ext.pageVersions.ui.PageVersionPanel = function ( config ) {
	config = Object.assign( {
		expanded: false,
		padded: true
	}, config || {} );
	ext.pageVersions.ui.PageVersionPanel.parent.call( this, config );

	this.pageId = config.pageId;
	this.revisionId = config.revisionId;

	this.$element.addClass( 'ext-page-versions-version-panel' );

	this.init();
};

OO.inheritClass( ext.pageVersions.ui.PageVersionPanel, OO.ui.PanelLayout );

ext.pageVersions.ui.PageVersionPanel.prototype.init = async function () {
	this.$element.append( new OO.ui.ProgressBarWidget( { progress: false } ).$element );
	try {
		const nextVersions = await ext.pageVersions.api.getNextVersions( this.pageId );
		this.$element.empty();

		if ( nextVersions.current ) {
			this.$element.append( new OO.ui.LabelWidget( {
				label: new OO.ui.HtmlSnippet(
					mw.msg( 'page-versions-next-versions-current', nextVersions.current )
				)
			} ).$element );
		}

		const options = [];
		if ( nextVersions.patch ) {
			options.push( new OO.ui.RadioOptionWidget( {
				data: ext.pageVersions.VERSION_PATCH,
				label: mw.msg('pageversions-ui-version-type-label-patch', nextVersions.patch )
			} ) );
		}
		if ( nextVersions.minor ) {
			options.push( new OO.ui.RadioOptionWidget( {
				data: ext.pageVersions.VERSION_MINOR,
				label: mw.msg('pageversions-ui-version-type-label-minor', nextVersions.minor )
			} ) );
		}
		if ( nextVersions.major ) {
			options.push( new OO.ui.RadioOptionWidget( {
				data: ext.pageVersions.VERSION_MAJOR,
				label: mw.msg('pageversions-ui-version-type-label-major', nextVersions.major )
			} ) );
		}

		this.picker = new OO.ui.RadioSelectWidget( {
			items: options
		} );
		this.picker.$element.css( 'margin-top', '10px' );

		this.$element.append( this.picker.$element );

		this.commentField = new OO.ui.TextInputWidget();
		this.$element.append( new OO.ui.FieldLayout( this.commentField, {
			label: mw.msg( 'page-versions-next-versions-comment-label' ),
			align: 'top'
		} ).$element );

		this.emit( 'loaded' );

	} catch ( e ) {
		this.$element.html( new OO.ui.MessageWidget( {
			label: mw.msg( 'page-versions-next-versions-load-failed' ),
			type: 'error'
		} ).$element );
		console.error( 'Failed to load next versions:', e ); // eslint-disable-line no-console
	}
};

ext.pageVersions.ui.PageVersionPanel.prototype.create = async function () {
	const selected = this.picker.findSelectedItem();
	if ( selected ) {
		const type = selected.getData();
		try {
			const res = await ext.pageVersions.api.createVersion( this.revisionId, type, this.commentField.getValue() );
			if ( !res.success ) {
				throw new Error( 'API responded with success=false' );
			}
			return res.version;
		} catch ( e ) {
			return Promise.reject( e );
		}
	}
};