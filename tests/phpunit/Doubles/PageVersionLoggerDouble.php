<?php

namespace MediaWiki\Extension\PageVersions\Tests\Doubles;

use ArrayObject;
use MediaWiki\Extension\PageVersions\PageVersion;
use MediaWiki\Extension\PageVersions\Util\PageVersionLogger;
use MediaWiki\User\UserIdentity;
use Psr\Log\NullLogger;

readonly class PageVersionLoggerDouble extends PageVersionLogger {

	public function __construct(
		private ArrayObject $state
	) {
		parent::__construct( new NullLogger() );
	}

	public function logNewVersion( UserIdentity $actor, PageVersion $version ): void {
		$this->state['newVersionLogs'][] = [ $actor, $version ];
	}

	public function logDeleteVersion( UserIdentity $actor, PageVersion $version ): void {
		$this->state['deleteVersionLogs'][] = [ $actor, $version ];
	}
}
