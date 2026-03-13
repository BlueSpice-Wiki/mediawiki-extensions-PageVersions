<?php

namespace MediaWiki\Extension\PageVersions;

use MediaWiki\Extension\PageVersions\Exception\NoVersionOnRevisionException;
use MediaWiki\Extension\PageVersions\Exception\RevisionUnavailableException;
use MediaWiki\Extension\PageVersions\Util\PageVersionLogger;
use MediaWiki\Extension\PageVersions\Util\VersionBumper;
use MediaWiki\HookContainer\HookContainer;
use MediaWiki\Permissions\Authority;
use MediaWiki\Permissions\PermissionManager;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\WikiMap\WikiMap;
use PermissionsError;

readonly class PageVersionManager {

	/**
	 * @param PageVersionStore $store
	 * @param PermissionManager $permissionManager
	 * @param VersionBumper $versionBumper
	 * @param HookContainer $hookContainer
	 * @param PageVersionLogger $logger
	 */
	public function __construct(
		private PageVersionStore $store,
		private PermissionManager $permissionManager,
		private VersionBumper $versionBumper,
		private HookContainer $hookContainer,
		private PageVersionLogger $logger
	) {
	}

	/**
	 * @param RevisionRecord $revision
	 * @param Authority $actor
	 * @param string $versionType
	 * @param string $comment
	 * @return string
	 * @throws PermissionsError
	 * @throws RevisionUnavailableException
	 */
	public function createNewVersion(
		RevisionRecord $revision, Authority $actor, string $versionType, string $comment = ''
	): string {
		$bumpIndex = $this->versionBumper->getBumpIndex( $versionType );
		$this->assertAvailable( $revision );
		$this->assertActorCan( 'create', $revision, $actor );
		$current = $this->store->getCurrentPageVersion( $revision->getPage()->getId() ) ?? '';
		$newVersion = $this->versionBumper->bump( $current, $bumpIndex );

		$version = new PageVersion(
			$revision,
			$newVersion,
			true,
			$actor,
			new \DateTime(),
			$comment,
			WikiMap::getCurrentWikiId()
		);

		$this->store->storePageVersion( $version );

		$this->hookContainer->run( 'PageVersionCreated', [ $version, $versionType ] );
		$this->logger->logNewVersion( $actor->getUser(), $version );

		return $newVersion;
	}

	/**
	 * @param string $type
	 * @return int
	 */
	public function getBumpIndex( string $type ): int {
		return $this->versionBumper->getBumpIndex( $type );
	}

	/**
	 * @param RevisionRecord $revisionRecord
	 * @param Authority $actor
	 * @return void
	 * @throws NoVersionOnRevisionException
	 * @throws PermissionsError
	 */
	public function deleteVersion( RevisionRecord $revisionRecord, Authority $actor ): void {
		$this->assertActorCan( 'remove', $revisionRecord, $actor );
		$version = $this->store->getVersionForRevision( $revisionRecord );
		if ( !$version ) {
			throw new NoVersionOnRevisionException( $revisionRecord );
		}
		$this->store->deleteVersion( $version );

		$this->hookContainer->run( 'PageVersionDeleted', [ $version, $actor ] );
		$this->logger->logDeleteVersion( $actor->getUser(), $version );
	}

	/**
	 * @param RevisionRecord $revision
	 * @return void
	 * @throws RevisionUnavailableException
	 */
	private function assertAvailable( RevisionRecord $revision ): void {
		if ( !$this->store->revisionAvailable( $revision->getId(), $revision->getPageId() ) ) {
			throw new RevisionUnavailableException( $revision );
		}
	}

	/**
	 * @param string $action
	 * @param RevisionRecord $revision
	 * @param Authority $actor
	 * @return void
	 * @throws PermissionsError
	 */
	private function assertActorCan( string $action, RevisionRecord $revision, Authority $actor ): void {
		if ( $actor->isSystemuser() ) {
			return;
		}
		$can = false;
		switch ( $action ) {
			case 'create':
			case 'remove':
				$can = $this->permissionManager->userCan( 'edit', $actor->getUser(), $revision->getPage() );
				break;
		}

		if ( !$can ) {
			throw new PermissionsError( $action );
		}
	}
}
