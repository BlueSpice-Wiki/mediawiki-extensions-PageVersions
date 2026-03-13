<?php

namespace MediaWiki\Extension\PageVersions\Hook;

use MediaWiki\Extension\PageVersions\PageVersionStore;
use MediaWiki\Hook\SkinTemplateNavigation__UniversalHook;

class AddContentAction implements SkinTemplateNavigation__UniversalHook {

	/**
	 * @param PageVersionStore $store
	 */
	public function __construct(
		private readonly PageVersionStore $store
	) {
	}

	/**
	 * @inheritDoc
	 */
	public function onSkinTemplateNavigation__Universal( $sktemplate, &$links ): void {
		if ( !$sktemplate->getTitle()->exists() ) {
			return;
		}
		$revId = $sktemplate->getContext()->getOutput()->getRevisionId();
		if ( !$revId ) {
			return;
		}
		if ( !$this->store->revisionAvailable( $revId, $sktemplate->getTitle()->getArticleID() ) ) {
			return;
		}
		$links['actions']['createPageVersion'] = [
			"class" => '',
			"text" => $sktemplate->msg( 'pageversions-ca-create-label' )->text(),
			"href" => "#",
			'position' => 30,
		];
		$sktemplate->getOutput()->addModules( [ 'ext.pageVersions.bootstrap' ] );
	}
}
