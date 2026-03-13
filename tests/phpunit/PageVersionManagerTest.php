<?php

namespace MediaWiki\Extension\PageVersions\Tests;

use DateTime;
use MediaWiki\Extension\PageVersions\Exception\NoVersionOnRevisionException;
use MediaWiki\Extension\PageVersions\Exception\RevisionUnavailableException;
use MediaWiki\Extension\PageVersions\PageVersion;
use MediaWiki\Extension\PageVersions\PageVersionManager;
use MediaWiki\Extension\PageVersions\Tests\Doubles\AuthorityDouble;
use MediaWiki\Extension\PageVersions\Tests\Doubles\PageVersionLoggerDouble;
use MediaWiki\Extension\PageVersions\Tests\Doubles\PageVersionStoreDouble;
use MediaWiki\Extension\PageVersions\Util\VersionBumper;
use MediaWiki\HookContainer\HookContainer;
use MediaWiki\Permissions\Authority;
use MediaWiki\Permissions\PermissionManager;
use MediaWiki\Revision\RevisionLookup;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Title\Title;
use MediaWiki\User\User;
use MediaWiki\User\UserFactory;
use MediaWiki\User\UserIdentity;
use PermissionsError;
use PHPUnit\Framework\TestCase;
use Wikimedia\Rdbms\ILoadBalancer;

class PageVersionManagerTest extends TestCase {

	/**
	 * @covers \MediaWiki\Extension\PageVersions\PageVersionManager::createNewVersion
	 */
	public function testCreateNewVersionStoresVersionAndRunsSideEffects(): void {
		$page = $this->createConfiguredMock( Title::class, [ 'getId' => 55 ] );
		$revision = $this->newRevision( 77, 55, $page );
		$user = $this->createConfiguredMock( User::class, [ 'getId' => 14, 'getName' => 'Alice' ] );
		$actor = $this->newAuthority( $user, false );
		$storeState = $this->newStoreState( [ 'currentPageVersion' => '1.2.3' ] );
		$store = $this->newStore( $storeState );
		$permissionManager = $this->createMock( PermissionManager::class );
		$versionBumper = $this->createMock( VersionBumper::class );
		$hookContainer = $this->createMock( HookContainer::class );
		$loggerState = $this->newLoggerState();
		$logger = $this->newLogger( $loggerState );

		$versionBumper->expects( $this->once() )
			->method( 'getBumpIndex' )
			->with( VersionBumper::VERSION_MINOR )
			->willReturn( VersionBumper::MINOR_BUMP );
		$permissionManager->expects( $this->once() )
			->method( 'userCan' )
			->with( 'edit', $user, $page )
			->willReturn( true );
		$versionBumper->expects( $this->once() )
			->method( 'bump' )
			->with( '1.2.3', VersionBumper::MINOR_BUMP )
			->willReturn( '1.3.0' );
		$hookContainer->expects( $this->once() )
			->method( 'run' )
			->with(
				'PageVersionCreated',
				$this->callback( static function ( array $args ): bool {
					return count( $args ) === 2 &&
						$args[0] instanceof PageVersion &&
						$args[1] === VersionBumper::VERSION_MINOR;
				} )
			);
		$manager = new PageVersionManager(
			$store,
			$permissionManager,
			$versionBumper,
			$hookContainer,
			$logger
		);

		$this->assertSame(
			'1.3.0',
			$manager->createNewVersion( $revision, $actor, VersionBumper::VERSION_MINOR, 'Release notes' )
		);
		$this->assertSame( [ [ 77, 55 ] ], $storeState['revisionAvailableCalls'] );
		$this->assertSame( [ 55 ], $storeState['getCurrentPageVersionCalls'] );
		$this->assertCount( 1, $storeState['storedVersions'] );
		$this->assertTrue( $storeState['storedVersions'][0] instanceof PageVersion );
		$this->assertSame( $revision, $storeState['storedVersions'][0]->getRevision() );
		$this->assertSame( '1.3.0', $storeState['storedVersions'][0]->getVersion() );
		$this->assertTrue( $storeState['storedVersions'][0]->isLatest() );
		$this->assertSame( $actor, $storeState['storedVersions'][0]->getAuthor() );
		$this->assertSame( 'Release notes', $storeState['storedVersions'][0]->getComment() );
		$this->assertCount( 1, $loggerState['newVersionLogs'] );
		$this->assertSame( $user, $loggerState['newVersionLogs'][0][0] );
		$this->assertSame( $storeState['storedVersions'][0], $loggerState['newVersionLogs'][0][1] );
	}

	/**
	 * @covers \MediaWiki\Extension\PageVersions\PageVersionManager::createNewVersion
	 */
	public function testCreateNewVersionThrowsWhenRevisionIsUnavailable(): void {
		$page = $this->createConfiguredMock( Title::class, [ 'getId' => 5 ] );
		$revision = $this->newRevision( 11, 5, $page );
		$actor = $this->newAuthority( $this->createMock( UserIdentity::class ), false );
		$storeState = $this->newStoreState( [ 'revisionAvailable' => false ] );
		$store = $this->newStore( $storeState );
		$permissionManager = $this->createMock( PermissionManager::class );
		$versionBumper = $this->createMock( VersionBumper::class );
		$hookContainer = $this->createMock( HookContainer::class );
		$logger = $this->newLogger();

		$versionBumper->method( 'getBumpIndex' )->willReturn( VersionBumper::PATCH_BUMP );
		$permissionManager->expects( $this->never() )->method( 'userCan' );

		$manager = new PageVersionManager(
			$store,
			$permissionManager,
			$versionBumper,
			$hookContainer,
			$logger
		);

		$this->expectException( RevisionUnavailableException::class );

		$manager->createNewVersion( $revision, $actor, VersionBumper::VERSION_PATCH );
	}

	/**
	 * @covers \MediaWiki\Extension\PageVersions\PageVersionManager::createNewVersion
	 */
	public function testCreateNewVersionThrowsWhenActorLacksPermission(): void {
		$page = $this->createConfiguredMock( Title::class, [ 'getId' => 8 ] );
		$revision = $this->newRevision( 12, 8, $page );
		$user = $this->createConfiguredMock( User::class, [ 'getId' => 21, 'getName' => 'Bob' ] );
		$actor = $this->newAuthority( $user, false );
		$storeState = $this->newStoreState();
		$store = $this->newStore( $storeState );
		$permissionManager = $this->createMock( PermissionManager::class );
		$versionBumper = $this->createMock( VersionBumper::class );
		$hookContainer = $this->createMock( HookContainer::class );
		$logger = $this->newLogger();

		$versionBumper->method( 'getBumpIndex' )->willReturn( VersionBumper::PATCH_BUMP );
		$permissionManager->expects( $this->once() )
			->method( 'userCan' )
			->with( 'edit', $user, $page )
			->willReturn( false );

		$manager = new PageVersionManager(
			$store,
			$permissionManager,
			$versionBumper,
			$hookContainer,
			$logger
		);

		$this->expectException( PermissionsError::class );

		$manager->createNewVersion( $revision, $actor, VersionBumper::VERSION_PATCH );
		$this->assertSame( [], $storeState['storedVersions'] );
	}

	/**
	 * @covers \MediaWiki\Extension\PageVersions\PageVersionManager::createNewVersion
	 */
	public function testCreateNewVersionSkipsPermissionChecksForSystemUser(): void {
		$page = $this->createConfiguredMock( Title::class, [ 'getId' => 13 ] );
		$revision = $this->newRevision( 19, 13, $page );
		$user = $this->createConfiguredMock( User::class, [ 'getId' => 4, 'getName' => 'System' ] );
		$actor = $this->newAuthority( $user, true );
		$storeState = $this->newStoreState();
		$store = $this->newStore( $storeState );
		$permissionManager = $this->createMock( PermissionManager::class );
		$versionBumper = $this->createMock( VersionBumper::class );
		$hookContainer = $this->createMock( HookContainer::class );
		$loggerState = $this->newLoggerState();
		$logger = $this->newLogger( $loggerState );

		$versionBumper->method( 'getBumpIndex' )->willReturn( VersionBumper::PATCH_BUMP );
		$versionBumper->method( 'bump' )->with( '', VersionBumper::PATCH_BUMP )->willReturn( '0.0.1' );
		$permissionManager->expects( $this->never() )->method( 'userCan' );
		$hookContainer->expects( $this->once() )->method( 'run' );
		$manager = new PageVersionManager(
			$store,
			$permissionManager,
			$versionBumper,
			$hookContainer,
			$logger
		);

		$this->assertSame(
			'0.0.1',
			$manager->createNewVersion( $revision, $actor, VersionBumper::VERSION_PATCH )
		);
		$this->assertCount( 1, $storeState['storedVersions'] );
		$this->assertCount( 1, $loggerState['newVersionLogs'] );
	}

	/**
	 * @covers \MediaWiki\Extension\PageVersions\PageVersionManager::deleteVersion
	 */
	public function testDeleteVersionDeletesStoredVersionAndRunsSideEffects(): void {
		$page = $this->createConfiguredMock( Title::class, [ 'getId' => 31 ] );
		$revision = $this->newRevision( 44, 31, $page );
		$user = $this->createConfiguredMock( User::class, [ 'getId' => 33, 'getName' => 'Charlie' ] );
		$actor = $this->newAuthority( $user, false );
		$storedVersion = new PageVersion(
			$revision,
			'3.1.4',
			true,
			$actor,
			new DateTime( '2024-01-02 03:04:05' ),
			'Cleanup',
			'testwiki'
		);
		$storeState = $this->newStoreState( [ 'versionForRevision' => $storedVersion ] );
		$store = $this->newStore( $storeState );
		$permissionManager = $this->createMock( PermissionManager::class );
		$versionBumper = $this->createMock( VersionBumper::class );
		$hookContainer = $this->createMock( HookContainer::class );
		$loggerState = $this->newLoggerState();
		$logger = $this->newLogger( $loggerState );

		$permissionManager->expects( $this->once() )
			->method( 'userCan' )
			->with( 'edit', $user, $page )
			->willReturn( true );
		$hookContainer->expects( $this->once() )
			->method( 'run' )
			->with( 'PageVersionDeleted', [ $storedVersion, $actor ] );
		$manager = new PageVersionManager(
			$store,
			$permissionManager,
			$versionBumper,
			$hookContainer,
			$logger
		);

		$manager->deleteVersion( $revision, $actor );
		$this->assertSame( [ $revision ], $storeState['getVersionForRevisionCalls'] );
		$this->assertSame( [ $storedVersion ], $storeState['deletedVersions'] );
		$this->assertCount( 1, $loggerState['deleteVersionLogs'] );
		$this->assertSame( [ $user, $storedVersion ], $loggerState['deleteVersionLogs'][0] );
	}

	/**
	 * @covers \MediaWiki\Extension\PageVersions\PageVersionManager::deleteVersion
	 */
	public function testDeleteVersionThrowsWhenRevisionHasNoVersion(): void {
		$page = $this->createConfiguredMock( Title::class, [ 'getId' => 62 ] );
		$revision = $this->newRevision( 73, 62, $page );
		$user = $this->createConfiguredMock( User::class, [ 'getId' => 41, 'getName' => 'Dana' ] );
		$actor = $this->newAuthority( $user, false );
		$storeState = $this->newStoreState( [ 'versionForRevision' => null ] );
		$store = $this->newStore( $storeState );
		$permissionManager = $this->createMock( PermissionManager::class );
		$versionBumper = $this->createMock( VersionBumper::class );
		$hookContainer = $this->createMock( HookContainer::class );
		$logger = $this->newLogger();

		$permissionManager->expects( $this->once() )
			->method( 'userCan' )
			->with( 'edit', $user, $page )
			->willReturn( true );

		$manager = new PageVersionManager(
			$store,
			$permissionManager,
			$versionBumper,
			$hookContainer,
			$logger
		);

		$this->expectException( NoVersionOnRevisionException::class );

		$manager->deleteVersion( $revision, $actor );
	}

	/**
	 * @covers \MediaWiki\Extension\PageVersions\PageVersionManager::getBumpIndex
	 */
	public function testGetBumpIndexDelegatesToVersionBumper(): void {
		$store = $this->newStore();
		$permissionManager = $this->createMock( PermissionManager::class );
		$versionBumper = $this->createMock( VersionBumper::class );
		$hookContainer = $this->createMock( HookContainer::class );
		$logger = $this->newLogger();

		$versionBumper->expects( $this->once() )
			->method( 'getBumpIndex' )
			->with( VersionBumper::VERSION_MAJOR )
			->willReturn( VersionBumper::MAJOR_BUMP );

		$manager = new PageVersionManager(
			$store,
			$permissionManager,
			$versionBumper,
			$hookContainer,
			$logger
		);

		$this->assertSame( VersionBumper::MAJOR_BUMP, $manager->getBumpIndex( VersionBumper::VERSION_MAJOR ) );
	}

	private function newRevision( int $revisionId, int $pageId, Title $page ): RevisionRecord {
		$revision = $this->createMock( RevisionRecord::class );
		$revision->method( 'getId' )->willReturn( $revisionId );
		$revision->method( 'getPageId' )->willReturn( $pageId );
		$revision->method( 'getPage' )->willReturn( $page );

		return $revision;
	}

	private function newAuthority( UserIdentity $user, bool $isSystemUser ): Authority {
		return new AuthorityDouble(
			$user,
			$isSystemUser ? [] : [ 'edit' ],
			$isSystemUser
		);
	}

	private function newStore( ?\ArrayObject $state = null ): PageVersionStoreDouble {
		return new PageVersionStoreDouble(
			$this->createMock( ILoadBalancer::class ),
			$this->createMock( RevisionLookup::class ),
			$this->createMock( UserFactory::class ),
			$state ?? $this->newStoreState()
		);
	}

	private function newStoreState( array $overrides = [] ): \ArrayObject {
		return new \ArrayObject( array_merge( [
			'revisionAvailable' => true,
			'currentPageVersion' => null,
			'versionForRevision' => null,
			'revisionAvailableCalls' => [],
			'getCurrentPageVersionCalls' => [],
			'getVersionForRevisionCalls' => [],
			'storedVersions' => [],
			'deletedVersions' => [],
		], $overrides ) );
	}

	private function newLogger( ?\ArrayObject $state = null ): PageVersionLoggerDouble {
		return new PageVersionLoggerDouble( $state ?? $this->newLoggerState() );
	}

	private function newLoggerState(): \ArrayObject {
		return new \ArrayObject( [
			'newVersionLogs' => [],
			'deleteVersionLogs' => [],
		] );
	}
}
