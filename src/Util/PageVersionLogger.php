<?php

namespace MediaWiki\Extension\PageVersions\Util;

use MediaWiki\Extension\PageVersions\PageVersion;
use MediaWiki\Logging\ManualLogEntry;
use MediaWiki\Page\PageIdentity;
use MediaWiki\User\User;
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

		$this->addEntry(
			'add-version',
			$version->getRevision()->getPage(),
			$actor,
			[
				'4:revision:revision' => $version->getRevision()->getId(),
				'5::version' => $version->getVersion(),
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

		$this->addEntry(
			'remove-version',
			$version->getRevision()->getPage(),
			$actor,
			[
				'4:revision:revision' => $version->getRevision()->getId(),
				'5::version' => $version->getVersion(),
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

	/**
	 * @param string $action
	 * @param PageIdentity $page
	 * @param User $actor
	 * @param array $params
	 * @return void
	 */
	private function addEntry( string $action, PageIdentity $page, UserIdentity $actor, array $params = [] ) {
		$logEntry = new ManualLogEntry( 'ext-page-versions', $action );
		$logEntry->setPerformer( $actor );
		$logEntry->setTarget( $page );

		$logEntry->setParameters( $params );

		$logId = $logEntry->insert();

		$logEntry->publish( $logId );
	}
}
