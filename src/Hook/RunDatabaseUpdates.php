<?php

namespace MediaWiki\Extension\PageVersions\Hook;

use MediaWiki\Installer\Hook\LoadExtensionSchemaUpdatesHook;

class RunDatabaseUpdates implements LoadExtensionSchemaUpdatesHook {

	/**
	 * @inheritDoc
	 */
	public function onLoadExtensionSchemaUpdates( $updater ) {
		$base = dirname( __DIR__, 2 ) . '/db';
		$dbType = $updater->getDB()->getType();

		$updater->addExtensionTable(
			'page_version',
			"$base/$dbType/page_version.sql"
		);
	}
}
