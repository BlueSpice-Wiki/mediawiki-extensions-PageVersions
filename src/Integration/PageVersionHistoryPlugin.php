<?php

namespace MediaWiki\Extension\PageVersions\Integration;

use MediaWiki\Extension\EnhancedStandardUIs\IHistoryPlugin;
use MediaWiki\Extension\PageVersions\PageVersionStore;

class PageVersionHistoryPlugin implements IHistoryPlugin {

	/**
	 *
	 * @param PageVersionStore $store
	 */
	public function __construct( private readonly PageVersionStore $store ) {
	}

	/**
	 * @inheritDoc
	 */
	public function getRLModules( $historyAction ): array {
		return [
			'ext.pageVersions.history'
		];
	}

	/**
	 * @inheritDoc
	 */
	public function ammendRow( $historyAction, &$entry, &$attribs, &$classes ) {
		$version = $this->store->getVersionForRevisionId( $entry['id'] );
		$entry['pv_version'] = $version ? $version->getVersion() : '';
	}
}
