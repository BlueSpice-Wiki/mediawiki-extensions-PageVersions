<?php

namespace MediaWiki\Extension\PageVersions\Integration\MetaItemProvider;

use MediaWiki\Context\IContextSource;
use MediaWiki\Extension\PageVersions\PageVersion;
use MediaWiki\Extension\PageVersions\PageVersionStore;
use MediaWiki\Message\Message;
use MWStake\MediaWiki\Component\CommonUserInterface\Component\Literal;

class PageVersionsMetadataTool extends Literal {

	/** @var PageVersion|null */
	private ?PageVersion $version = null;

	/**
	 *
	 */
	public function __construct(
		private readonly PageVersionStore $store
	) {
		parent::__construct( 'page-versions-tool', '' );
	}

	/**
	 *
	 * @param IContextSource $context
	 * @return bool
	 */
	public function shouldRender( $context ): bool {
		$title = $context->getTitle();
		if ( !$title || !$title->exists() || !$title->canExist() ) {
			return false;
		}
		$revId = $context->getOutput()->getRevisionId();
		if ( !$revId ) {
			return false;
		}
		$version = $this->store->getVersionForRevisionId( $revId );
		if ( $version ) {
			$this->version = $version;
			return true;
		}
		return false;
	}

	/**
	 *
	 * @return string
	 */
	public function getHtml(): string {
		if ( $this->version === null ) {
			return '';
		}
		return \Html::element( 'span', [
			'class' => 'page-versions-version-label'
		], Message::newFromKey( 'pageversions-version-badge-label', $this->version->getVersion() )->text() );
	}

}
