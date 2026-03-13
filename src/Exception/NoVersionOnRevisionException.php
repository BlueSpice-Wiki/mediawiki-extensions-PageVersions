<?php

namespace MediaWiki\Extension\PageVersions\Exception;

use MediaWiki\Message\Message;
use MediaWiki\Revision\RevisionRecord;

class NoVersionOnRevisionException extends \Exception {

	/**
	 * @param RevisionRecord $revision
	 */
	public function __construct( RevisionRecord $revision ) {
		parent::__construct(
			Message::newFromKey( 'pageversions-revision-exception-no-version', [ $revision->getId() ] )->text()
		);
	}
}
