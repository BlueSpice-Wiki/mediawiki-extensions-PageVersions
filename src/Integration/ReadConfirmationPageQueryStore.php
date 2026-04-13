<?php

namespace MediaWiki\Extension\PageVersions\Integration;

use MediaWiki\Extension\PageVersions\PageVersionStore;
use MediaWiki\Linker\LinkRenderer;
use MWStake\MediaWiki\Component\CommonWebAPIs\Hook\MWStakeCommonWebAPIsQueryStoreResultHook;
use MWStake\MediaWiki\Component\DataStore\ResultSet;

class ReadConfirmationPageQueryStore implements MWStakeCommonWebAPIsQueryStoreResultHook {

	/**
	 * @param PageVersionStore $pageVersionStore
	 * @param LinkRenderer $linkRenderer
	 */
	public function __construct(
		private readonly PageVersionStore $pageVersionStore,
		private readonly LinkRenderer $linkRenderer
	) {
	}

	/**
	 * @inheritDoc
	 */
	public function onMWStakeCommonWebAPIsQueryStoreResult( $store, &$result ) {
		if ( $store->getPath() !== '/page_read_confirmations/{page}' ) {
			return;
		}
		$records = [];
		foreach ( $result->getRecords() as $record ) {
			$version = $record->get( 'prc_rev' ) ?
				$this->pageVersionStore->getVersionForRevisionId( $record->get( 'prc_rev' ) ) : null;
			if ( !$version ) {
				$record->set( 'revision_link', null );
			} else {
				$link = $this->linkRenderer->makeKnownLink(
					$version->getRevision()->getPage(),
					$version->getVersion(),
					[], [ 'version' => $version->getVersion() ]
				);
				$record->set( 'revision_link', $link );
			}

			$records[] = $record;
		}
		$result = new ResultSet( $records, $result->getTotal() );
	}
}
