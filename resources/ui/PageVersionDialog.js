ext.pageVersions.ui.PageVersionDialog = function ( config ) {
	ext.pageVersions.ui.PageVersionDialog.parent.call( this, config );
};

OO.inheritClass( ext.pageVersions.ui.PageVersionDialog, OO.ui.ProcessDialog );

ext.pageVersions.ui.PageVersionDialog.static.name = 'pageVersionDialog';
ext.pageVersions.ui.PageVersionDialog.static.title = mw.msg( 'pageversions-ca-create-label' );
ext.pageVersions.ui.PageVersionDialog.static.actions = [
	{
		action: 'submit',
		label: mw.msg( 'pageversions-ui-action-create' ),
		flags: [ 'primary', 'progressive' ]
	},
	{
		action: 'cancel',
		label: mw.msg( 'pageversions-ui-action-cancel' ),
		flags: [ 'safe', 'close' ]
	}
];

ext.pageVersions.ui.PageVersionDialog.prototype.initialize = function () {
	ext.pageVersions.ui.PageVersionDialog.parent.prototype.initialize.call( this );

	this.panel = new ext.pageVersions.ui.PageVersionPanel( {
		pageId: mw.config.get( 'wgArticleId' ),
		revisionId: mw.config.get( 'wgRevisionId' ),
		expanded: false
	} );
	this.panel.connect( this, { loaded: 'updateSize' } );
	this.$body.append( this.panel.$element );
};

ext.pageVersions.ui.PageVersionDialog.prototype.getActionProcess = function ( action ) {
	return ext.pageVersions.ui.PageVersionDialog.parent.prototype.getActionProcess
		.call( this, action )
		.next(
			function () {
				if ( action === 'submit' ) {
					this.pushPending();
					const dfd = $.Deferred();
					this.panel.create().then( () => {
						this.close( { action: 'create' } );
					} ).catch( () => {
						mw.notify( mw.msg( 'page-versions-error' ), { type: 'error' } );
						this.close();
					} );
					return dfd.promise();
				}
			}, this
		);
};
