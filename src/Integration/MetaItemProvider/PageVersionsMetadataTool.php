<?php

namespace MediaWiki\Extension\PageVersions\Integration\MetaItemProvider;

use Html;
use MediaWiki\Context\IContextSource;
use MediaWiki\Extension\PageVersions\PageVersion;
use MediaWiki\Extension\PageVersions\PageVersionStore;
use MediaWiki\Message\Message;
use MediaWiki\Title\Title;
use MWStake\MediaWiki\Component\CommonUserInterface\Component\Literal;

class PageVersionsMetadataTool extends Literal {

	/** @var PageVersion|null */
	private ?PageVersion $version = null;
	private ?Title $title = null;

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
		$this->title = $title;
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
		if ( $this->version === null || $this->title === null ) {
			return '';
		}

		$label = Message::newFromKey(
			'pageversions-version-badge-label',
			$this->version->getVersion()
		)->text();

		$menuItems = '';
		foreach ( $this->getVersions() as $version => $url ) {
			$itemClasses = 'dropdown-item';
			if ( $version === $this->version->getVersion() ) {
				$itemClasses .= ' active';
			}
			$menuItems .= Html::rawElement(
				'li',
				[],
				Html::element(
					'a',
					[
						'class' => $itemClasses,
						'href' => $url
					],
					$version
				)
			);
		}
		return Html::rawElement(
			'div',
			[ 'class' => 'dropdown page-versions-version-tool' ],
			Html::element(
				'a',
				[
					'class' => 'dropdown-toggle page-versions-version-label text-decoration-none',
					'href' => '#',
					'role' => 'button',
					'data-bs-toggle' => 'dropdown',
					'aria-expanded' => 'false'
				],
				$label
			) . Html::rawElement(
				'ul',
				[ 'class' => 'dropdown-menu dropdown-menu-end' ],
				$menuItems
			)
		);
	}

	/**
	 * @return array
	 */
	private function getVersions(): array {
		$versionList = [];
		$versions = $this->store->getPageVersions( $this->title->getArticleID() );
		foreach ( $versions as $version ) {
			$versionList[$version->getVersion()] = $this->title->getLocalURL( [ 'version' => $version->getVersion() ] );
		}
		return $versionList;
	}
}
