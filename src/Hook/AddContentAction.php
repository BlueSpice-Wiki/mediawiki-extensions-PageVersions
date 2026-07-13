<?php

namespace MediaWiki\Extension\PageVersions\Hook;

use MediaWiki\Extension\PageVersions\PageVersionManager;
use MediaWiki\Extension\PageVersions\PageVersionStore;
use MediaWiki\Hook\SkinTemplateNavigation__UniversalHook;
use MediaWiki\Revision\RevisionLookup;

class AddContentAction implements SkinTemplateNavigation__UniversalHook {

	/**
	 * @param PageVersionStore $store
	 * @param PageVersionManager $manager
	 * @param RevisionLookup $revisionLookup
	 */
	public function __construct(
		private readonly PageVersionStore $store,
		private readonly PageVersionManager $manager,
		private readonly RevisionLookup $revisionLookup
	) {
	}

	/**
	 * @inheritDoc
	 */
	public function onSkinTemplateNavigation__Universal( $sktemplate, &$links ): void {
		if ( !$sktemplate->getTitle()->exists() ) {
			return;
		}
		if ( !$this->store->isEnabled( $sktemplate->getTitle() ) ) {
			return;
		}
		$revId = $sktemplate->getContext()->getOutput()->getRevisionId();
		if ( !$revId ) {
			return;
		}
		$revision = $this->revisionLookup->getRevisionById( $revId );
		if ( !$revision ) {
			return;
		}
		$sktemplate->getOutput()->addModules( [ 'ext.pageVersions.bootstrap' ] );
		if ( !$this->store->revisionAvailable( $revId, $sktemplate->getTitle()->getArticleID() ) ) {
			return;
		}

		if ( !$this->manager->checkCanCreate( $revision, $sktemplate->getUser() ) ) {
			return;
		}
		$links['actions']['createPageVersion'] = [
			"class" => '',
			"text" => $sktemplate->msg( 'pageversions-ca-create-label' )->text(),
			"href" => "#",
			'position' => 30,
		];
	}
}
