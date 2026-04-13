<?php

namespace MediaWiki\Extension\PageVersions\Integration;

use MediaWiki\Extension\PageReadConfirmations\Hook\PageReadConfirmationGetRequestInfoHook;
use MediaWiki\Extension\PageVersions\PageVersionStore;
use MediaWiki\Linker\LinkRenderer;
use MediaWiki\Page\PageIdentity;

class ReadConfirmation implements PageReadConfirmationGetRequestInfoHook {

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
}
