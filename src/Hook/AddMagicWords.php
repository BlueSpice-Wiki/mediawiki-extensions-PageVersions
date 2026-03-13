<?php

namespace MediaWiki\Extension\PageVersions\Hook;

use MediaWiki\Extension\PageVersions\PageVersionStore;
use MediaWiki\Hook\GetMagicVariableIDsHook;
use MediaWiki\Hook\ParserFirstCallInitHook;
use MediaWiki\Hook\ParserGetVariableValueSwitchHook;
use MediaWiki\Parser\Parser;
use MediaWiki\Title\TitleFactory;

class AddMagicWords implements ParserFirstCallInitHook, ParserGetVariableValueSwitchHook, GetMagicVariableIDsHook {

	/**
	 * @param PageVersionStore $versionStore
	 * @param TitleFactory $titleFactory
	 */
	public function __construct(
		private readonly PageVersionStore $versionStore,
		private readonly TitleFactory $titleFactory
	) {
	}

	/**
	 * @inheritDoc
	 */
	public function onParserFirstCallInit( $parser ) {
		$parser->setFunctionHook( 'PAGEVERSION', [ $this, 'onParserFunction' ] );
	}

	/**
	 * @inheritDoc
	 */
	public function onGetMagicVariableIDs( &$variableIDs ) {
		$variableIDs[] = 'PAGEVERSION';
	}

	/**
	 * @param Parser $parser
	 * @param string $args
	 * @return string
	 */
	public function onParserFunction( Parser $parser, string $args ): string {
		$pagename = trim( $args );
		if ( !$pagename ) {
			return '';
		}
		$page = $this->titleFactory->newFromText( $pagename );
		if ( !$page ) {
			return '';
		}
		return $this->versionStore->getCurrentPageVersion( $page->getId() ) ?? '';
	}

	/**
	 * @inheritDoc
	 */
	public function onParserGetVariableValueSwitch( $parser, &$variableCache, $magicWordId, &$ret, $frame ) {
		if ( $magicWordId !== 'PAGEVERSION' ) {
			return;
		}
		$pRef = $parser->getPage();
		if ( !$pRef ) {
			$ret = '';
			return;
		}
		$page = $this->titleFactory->castFromPageReference( $pRef );
		if ( !$page || !$page->exists() ) {
			$ret = '';
			return;
		}
		$ret = $this->versionStore->getCurrentPageVersion( $page->getId() ) ?? '';
	}
}
