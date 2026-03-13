<?php

use MediaWiki\Extension\PageVersions\PageVersionManager;
use MediaWiki\Extension\PageVersions\PageVersionStore;
use MediaWiki\Extension\PageVersions\Util\PageVersionLogger;
use MediaWiki\Extension\PageVersions\Util\VersionBumper;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;

return [
	'PageVersions.Store' => static function ( MediaWikiServices $services ) {
		return new PageVersionStore(
			lb: $services->getDBLoadBalancer(),
			revisionLookup: $services->getRevisionLookup(),
			userFactory: $services->getUserFactory()
		);
	},
	'PageVersions.Manager' => static function ( MediaWikiServices $services ) {
		return new PageVersionManager(
			store: $services->getService( 'PageVersions.Store' ),
			permissionManager: $services->getPermissionManager(),
			versionBumper: $services->getService( 'PageVersions._Bumper' ),
			hookContainer: $services->getHookContainer(),
			logger: $services->getService( 'PageVersions._Logger' )
		);
	},
	'PageVersions._Logger' => static function () {
		return new PageVersionLogger(
			logger: LoggerFactory::getInstance( 'PageVersions' )
		);
	},
	'PageVersions._Bumper' => static function ( MediaWikiServices $services ) {
		return new VersionBumper( $services->getMainConfig() );
	}
];
