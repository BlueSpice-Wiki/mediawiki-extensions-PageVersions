<?php

namespace MediaWiki\Extension\PageVersions\Integration\Galaxy;

use BlueSpice\GalaxyDistributionConnector\Hook\GalaxyLogStoreOnRevisionLinkHook;
use MediaWiki\Extension\PageVersions\PageVersionStore;
use MediaWiki\Linker\LinkRenderer;
use MediaWiki\Revision\RevisionRecord;

class IntegrateIntoGalaxyLogStore implements GalaxyLogStoreOnRevisionLinkHook {

	/**
	 * @param PageVersionStore $versionStore
	 * @param LinkRenderer $linkRenderer
	 */
	public function __construct(
		private readonly PageVersionStore $versionStore,
		private readonly LinkRenderer $linkRenderer
	) {
	}

	/**
	 * @inheritDoc
	 */
	public function onGalaxyLogStoreOnRevisionLink(
		string &$revisionLink, RevisionRecord $record, string $linkText
	): void {
		$version = $this->versionStore->getVersionForRevisionId( $record->getId() );
		if ( !$version ) {
			return;
		}

		$revisionLink = $this->linkRenderer->makeKnownLink(
			$record->getPage(),
			"{$version->getVersion()} - $linkText",
			[],
			[ 'version' => $version->getVersion() ]
		);
	}
}
