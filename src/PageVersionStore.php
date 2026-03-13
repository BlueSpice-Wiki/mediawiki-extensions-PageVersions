<?php

namespace MediaWiki\Extension\PageVersions;

use MediaWiki\Revision\RevisionLookup;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\User\UserFactory;
use MediaWiki\WikiMap\WikiMap;
use stdClass;
use Wikimedia\Rdbms\ILoadBalancer;

readonly class PageVersionStore {

	/**
	 * @param ILoadBalancer $lb
	 * @param RevisionLookup $revisionLookup
	 * @param UserFactory $userFactory
	 */
	public function __construct(
		private ILoadBalancer $lb,
		private RevisionLookup $revisionLookup,
		private UserFactory $userFactory
	) {
	}

	/**
	 * @param RevisionRecord $revision
	 * @return PageVersion|null
	 */
	public function getVersionForRevision( RevisionRecord $revision ): ?PageVersion {
		return $this->getVersionForRevisionId( $revision->getId() );
	}

	/**
	 * @param int $revId
	 * @return PageVersion|null
	 */
	public function getVersionForRevisionId( int $revId ): ?PageVersion {
		$dbr = $this->lb->getConnectionRef( DB_REPLICA );
		$row = $dbr->newSelectQueryBuilder()
			->select( [ 'pv_rev', 'pv_version', 'pv_wiki_id', 'pv_page', 'pv_timestamp', 'pv_actor', 'pv_comment' ] )
			->from( 'page_version' )
			->where( [
				'pv_rev' => $revId,
				'pv_wiki_id' => WikiMap::getCurrentWikiId(),
			] )
			->fetchRow();

		if ( !$row ) {
			return null;
		}

		return $this->versionFromRow( $row );
	}

	/**
	 * @param string $version
	 * @return RevisionRecord|null
	 */
	public function getRevisionForVersion( string $version ): ?RevisionRecord {
		$rev = $this->lb->getConnection( DB_REPLICA )
			->newSelectQueryBuilder()
			->select( [ 'pv_rev' ] )
			->from( 'page_version' )
			->where( [
				'pv_version' => $version,
				'pv_wiki_id' => WikiMap::getCurrentWikiId(),
			] )
			->fetchField();
		if ( !$rev ) {
			return null;
		}
		return $this->revisionLookup->getRevisionById( $rev );
	}

	/**
	 * @param int $pageId
	 * @return string|null
	 */
	public function getCurrentPageVersion( int $pageId ): ?string {
		// Get latest version for this page, if any
		$dbr = $this->lb->getConnectionRef( DB_REPLICA );
		$row = $dbr->newSelectQueryBuilder()
			->select( [ 'pv_version' ] )
			->from( 'page_version' )
			->where( [
				'pv_wiki_id' => WikiMap::getCurrentWikiId(),
				'pv_page' => $pageId
			] )
			->orderBy( [ 'pv_rev' ], 'DESC' )
			->fetchRow();

		return $row ? $row->pv_version : null;
	}

	/**
	 * @param PageVersion $version
	 * @return void
	 */
	public function storePageVersion( PageVersion $version ): void {
		$dbw = $this->lb->getConnectionRef( DB_PRIMARY );
		$dbw->newInsertQueryBuilder()
			->table( 'page_version' )
			->row( [
				'pv_rev' => $version->getRevision()->getId(),
				'pv_version' => $version->getVersion(),
				'pv_wiki_id' => WikiMap::getCurrentWikiId(),
				'pv_page' => $version->getRevision()->getPageId(),
				'pv_timestamp' => $dbw->timestamp(),
				'pv_actor' => $version->getAuthor()->getUser()->getId(),
				'pv_comment' => $version->getComment()
			] )
			->caller( __METHOD__ )
			->execute();
	}

	/**
	 * @param PageVersion $version
	 * @return void
	 */
	public function deleteVersion( PageVersion $version ): void {
		$dbw = $this->lb->getConnectionRef( DB_PRIMARY );
		$dbw->newDeleteQueryBuilder()
			->deleteFrom( 'page_version' )
			->where( [
				'pv_rev' => $version->getRevision()->getId(),
				'pv_wiki_id' => WikiMap::getCurrentWikiId(),
			] )
			->caller( __METHOD__ )
			->execute();
	}

	/**
	 * @param int $requestedRev
	 * @param int $pageId
	 * @return bool
	 */
	public function revisionAvailable( int $requestedRev, int $pageId ): bool {
		// Make sure there is no version for this revision and that this page has no version for a later revision
		$versions = $this->getPageVersionsRaw( $pageId );
		if ( isset( $versions[$requestedRev] ) ) {
			return false;
		}
		foreach ( $versions as $revId => $version ) {
			if ( $revId > $requestedRev ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * @param string $version
	 * @param int $pageId
	 * @return bool
	 */
	public function isLatestVersion( string $version, int $pageId ): bool {
		$versions = $this->getPageVersionsRaw( $pageId );
		$flipped = array_flip( $versions );
		if ( !isset( $flipped[$version] ) ) {
			return false;
		}
		// If not first in the list, then it's not the latest
		return array_key_first( $flipped ) === $version;
	}

	/**
	 * @param int $pageId
	 * @return array [ rev_id => version ]
	 */
	private function getPageVersionsRaw( int $pageId ): array {
		$dbr = $this->lb->getConnectionRef( DB_REPLICA );
		$res = $dbr->newSelectQueryBuilder()
			->select( [ 'pv_rev', 'pv_version', 'pv_page' ] )
			->from( 'page_version' )
			->where( [
				'pv_wiki_id' => WikiMap::getCurrentWikiId(),
				'pv_page' => $pageId
			] )
			->orderBy( [ 'pv_rev' ], 'DESC' )
			->fetchResultSet();

		$versions = [];
		foreach ( $res as $row ) {
			$versions[(int)$row->pv_rev] = $row->pv_version;
		}
		return $versions;
	}

	/**
	 * @param stdClass $row
	 * @return PageVersion
	 */
	private function versionFromRow( stdClass $row ): PageVersion {
		$revision = $this->revisionLookup->getRevisionById( $row->pv_rev );
		if ( !$revision ) {
			throw new \UnexpectedValueException( "Revision with ID {$row->pv_rev} not found" );
		}
		$actor = $this->userFactory->newFromActorId( $row->pv_actor );
		return new PageVersion(
			$revision,
			$row->pv_version,
			$this->isLatestVersion( $row->pv_version, (int)$row->pv_page ),
			$actor,
			\DateTime::createFromFormat( 'YmdHis', $row->pv_timestamp ),
			$row->pv_comment,
			$row->pv_wiki_id
		);
	}

}

/**
 * pv_rev INT UNSIGNED NOT NULL,
 * pv_version VARCHAR(255) NOT NULL,
 * pv_wiki_id VARCHAR(255) NOT NULL,
 * pv_page VARCHAR(255) NOT NULL,
 * pv_timestamp BINARY(14) NOT NULL,
 * pv_actor INT UNSIGNED DEFAULT NULL,
 * pv_comment VARCHAR(255) DEFAULT NULL,
 */
