<?php

namespace MediaWiki\Extension\PageVersions\Integration;

use MediaWiki\Extension\PageReadConfirmations\Hook\PageReadConfirmationGetRequestInfoHook;
use MediaWiki\Extension\PageVersions\PageVersionStore;
use MediaWiki\Linker\LinkRenderer;
use MediaWiki\Page\PageIdentity;
use MWStake\MediaWiki\Component\CommonWebAPIs\Hook\MWStakeCommonWebAPIsQueryStoreResultHook;
use MWStake\MediaWiki\Component\DataStore\ResultSet;

class ReadConfirmation implements PageReadConfirmationGetRequestInfoHook, MWStakeCommonWebAPIsQueryStoreResultHook {

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
	 * @param PageIdentity $page
	 * @param array &$requestInfo
	 * @return void
	 */
	public function onPageReadConfirmationGetRequestInfo( PageIdentity $page, array &$requestInfo ): void {
		$version = $this->pageVersionStore->getVersionForRevisionId( $requestInfo['revision'] );
		if ( !$version ) {
			return;
		}

		$oldText = $requestInfo['version_link']['text'];
		$requestInfo['version_link']['text'] = "{$version->getVersion()} ($oldText)";
		$requestInfo['version_link']['anchor'] = $this->linkRenderer->makeKnownLink(
			$page, $requestInfo['version_link']['text'], [], [ 'version' => $version->getVersion() ]
		);
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
