<?php

namespace MediaWiki\Extension\PageVersions\Hook;

use MediaWiki\Extension\PageVersions\PageVersionStore;
use MediaWiki\Hook\MediaWikiPerformActionHook;

readonly class ResolveVersionParam implements MediaWikiPerformActionHook {

	/**
	 * @param PageVersionStore $store
	 */
	public function __construct(
		private PageVersionStore $store
	) {
	}

	/**
	 * @inheritDoc
	 */
	public function onMediaWikiPerformAction( $output, $article, $title, $user, $request, $mediaWiki ) {
		$version = $request->getText( 'version', null );
		if ( !$version ) {
			return;
		}
		if ( !$title->exists() || !$title->canExist() ) {
			return;
		}
		$revision = $this->store->getRevisionForVersion( $version );
		if ( $revision && $revision->getPageId() === $title->getArticleId() ) {
			$request->setVal( 'oldid', $revision->getId() );
		}
	}
}
