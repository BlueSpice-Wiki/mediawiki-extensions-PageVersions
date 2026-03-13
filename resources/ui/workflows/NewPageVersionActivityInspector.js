ext.pageVersions.ui.workflows.NewPageVersionActivityInspector = function ( element, dialog ) {
	ext.pageVersions.ui.workflows.NewPageVersionActivityInspector.parent
		.call( this, element, dialog );
};

OO.inheritClass(
	ext.pageVersions.ui.workflows.NewPageVersionActivityInspector,
	workflows.editor.inspector.ActivityInspector
);

ext.pageVersions.ui.workflows.NewPageVersionActivityInspector.prototype.getDialogTitle =
	function () {
		return mw.message( 'pageversions-ca-create-label' ).text();
	};

ext.pageVersions.ui.workflows.NewPageVersionActivityInspector.prototype.getItems = function () {
	return [
		{
			type: 'section_label',
			title: mw.message( 'workflows-ui-editor-inspector-properties' ).text()
		},
		{
			type: 'text',
			name: 'properties.pagename',
			label: mw.msg( 'pageversions-workflow-inspector-field-pagename' )
		},
		{
			name: 'version_type',
			type: 'radio_multiselect',
			label: mw.msg( 'pageversions-workflow-inspector-field-version-type-label' ),
			options: [
				{
					data: ext.pageVersions.VERSION_PATCH,
					label: mw.msg( 'pageversions-workflow-version-type-label-patch' )
				},
				{
					data: ext.pageVersions.VERSION_MINOR,
					label: mw.msg( 'pageversions-workflow-version-type-label-minor' )
				},
				{
					data: ext.pageVersions.VERSION_MAJOR,
					label: mw.msg( 'pageversions-workflow-version-type-label-major' )
				}
			]
		},
		{
			type: 'text',
			name: 'properties.pageId',
			hidden: true
		}
	];
};

workflows.editor.inspector.Registry.register( 'new_page_version', ext.pageVersions.ui.workflows.NewPageVersionActivityInspector );

workflows.editor.element.registry.register( 'new_page_version', {
	isUserActivity: false,
	class: 'activity-trigger-page-version activity-bootstrap-icon',
	label: mw.message( 'pageversions-ca-create-label' ).text(),
	defaultData: {
		properties: {
			revision: '',
			pageId: '',
			pagename: '',
			version_type: 'major' // eslint-disable-line camelcase
		}
	}
} );
