<?php

namespace MediaWiki\Extension\PageVersions\Integration\MetaItemProvider;

use BlueSpice\Discovery\IMetaItemProvider;
use MediaWiki\Extension\PageVersions\PageVersionStore;
use MWStake\MediaWiki\Component\CommonUserInterface\IComponent;

class PageVersionMetadata implements IMetaItemProvider {

	/**
	 * @param PageVersionStore $versionStore
	 */
	public function __construct(
		private readonly PageVersionStore $versionStore
	) {
	}

	/**
	 *
	 * @inheritDoc
	 */
	public function getName(): string {
		return 'page-versions';
	}

	/**
	 *
	 * @inheritDoc
	 */
	public function getComponent(): IComponent {
		return new PageVersionsMetadataTool( $this->versionStore );
	}
}
