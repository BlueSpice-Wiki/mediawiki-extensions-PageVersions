<?php

namespace MediaWiki\Extension\PageVersions\Tests;

use DateTime;
use MediaWiki\Extension\PageVersions\PageVersion;
use MediaWiki\Permissions\Authority;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\User\UserIdentity;
use PHPUnit\Framework\TestCase;

class PageVersionTest extends TestCase {

	/**
	 * @covers \MediaWiki\Extension\PageVersions\PageVersion::getRevision
	 * @covers \MediaWiki\Extension\PageVersions\PageVersion::getVersion
	 * @covers \MediaWiki\Extension\PageVersions\PageVersion::isLatest
	 * @covers \MediaWiki\Extension\PageVersions\PageVersion::getAuthor
	 * @covers \MediaWiki\Extension\PageVersions\PageVersion::getTimestamp
	 * @covers \MediaWiki\Extension\PageVersions\PageVersion::getComment
	 * @covers \MediaWiki\Extension\PageVersions\PageVersion::getWikiId
	 */
	public function testGettersReturnConstructorValues(): void {
		$revision = $this->createMock( RevisionRecord::class );
		$author = $this->newAuthority( 12, 'Alice Example' );
		$timestamp = new DateTime( '2024-01-02 03:04:05' );

		$pageVersion = new PageVersion(
			$revision,
			'1.2.3',
			true,
			$author,
			$timestamp,
			'Version comment',
			'testwiki'
		);

		$this->assertSame( $revision, $pageVersion->getRevision() );
		$this->assertSame( '1.2.3', $pageVersion->getVersion() );
		$this->assertTrue( $pageVersion->isLatest() );
		$this->assertSame( $author, $pageVersion->getAuthor() );
		$this->assertSame( $timestamp, $pageVersion->getTimestamp() );
		$this->assertSame( 'Version comment', $pageVersion->getComment() );
		$this->assertSame( 'testwiki', $pageVersion->getWikiId() );
	}

	/**
	 * @covers \MediaWiki\Extension\PageVersions\PageVersion::jsonSerialize
	 */
	public function testJsonSerializeReturnsExpectedPayload(): void {
		$revision = $this->createMock( RevisionRecord::class );
		$revision->method( 'getId' )->willReturn( 99 );
		$author = $this->newAuthority( 7, 'Bob Example' );
		$timestamp = new DateTime( '2024-07-08 09:10:11' );

		$pageVersion = new PageVersion(
			$revision,
			'2.3.4',
			false,
			$author,
			$timestamp,
			'Release candidate',
			'enwiki'
		);

		$this->assertSame(
			[
				'revisionId' => 99,
				'version' => '2.3.4',
				'isLatest' => false,
				'timestamp' => '20240708091011',
				'author' => [
					'id' => 7,
					'name' => 'Bob Example',
				],
				'comment' => 'Release candidate',
				'wikiId' => 'enwiki',
			],
			$pageVersion->jsonSerialize()
		);
	}

	private function newAuthority( int $userId, string $userName ): Authority {
		$user = $this->createMock( UserIdentity::class );
		$user->method( 'getId' )->willReturn( $userId );
		$user->method( 'getName' )->willReturn( $userName );

		$authority = $this->createMock( Authority::class );
		$authority->method( 'getUser' )->willReturn( $user );

		return $authority;
	}
}
