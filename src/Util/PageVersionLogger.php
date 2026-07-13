<?php

namespace MediaWiki\Extension\PageVersions\Util;

use MediaWiki\Extension\PageVersions\PageVersion;
use MediaWiki\User\UserIdentity;
use Psr\Log\LoggerInterface;

class PageVersionLogger {

	/**
	 * @param LoggerInterface $logger
	 */
	public function __construct(
		private LoggerInterface $logger
	) {
	}

	/**
	 * @param UserIdentity $actor
	 * @param PageVersion $version
	 * @return void
	 */
	public function logNewVersion( UserIdentity $actor, PageVersion $version ): void {
		$this->logger->info(
			'User {user} created new page version {version} for revision {revisionId} of page {pageTitle}',
			[
				'user' => $actor->getName(),
				'version' => $version->getVersion(),
				'revisionId' => $version->getRevision()->getId(),
				'pageTitle' => $version->getRevision()->getPage()->getDBkey()
			]
		);
	}

	/**
	 * @param UserIdentity $actor
	 * @param PageVersion $version
	 * @return void
	 */
	public function logDeleteVersion( UserIdentity $actor, PageVersion $version ): void {
		$this->logger->info(
			'User {user} deleted page version {version} for revision {revisionId} of page {pageTitle}',
			[
				'user' => $actor->getName(),
				'version' => $version->getVersion(),
				'revisionId' => $version->getRevision()->getId(),
				'pageTitle' => $version->getRevision()->getPage()->getDBkey()
			]
		);
	}

	/**
	 * @param string $e
	 * @param array $context
	 * @return void
	 */
	public function logError( string $e, array $context ): void {
		$this->logger->error( $e, $context );
	}
}
