<?php

namespace MediaWiki\Extension\PageVersions\Tests\Doubles;

use ArrayObject;
use MediaWiki\Extension\PageVersions\PageVersion;
use MediaWiki\Extension\PageVersions\PageVersionStore;
use MediaWiki\Revision\RevisionLookup;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\User\UserFactory;
use Wikimedia\Rdbms\ILoadBalancer;

readonly class PageVersionStoreDouble extends PageVersionStore {

	public function __construct(
		ILoadBalancer $lb,
		RevisionLookup $revisionLookup,
		UserFactory $userFactory,
		private ArrayObject $state
	) {
		parent::__construct( $lb, $revisionLookup, $userFactory );
	}

	public function getCurrentPageVersion( int $pageId ): ?string {
		$this->state['getCurrentPageVersionCalls'][] = $pageId;

		return $this->state['currentPageVersion'];
	}

	public function storePageVersion( PageVersion $version ): void {
		$this->state['storedVersions'][] = $version;
	}

	public function getVersionForRevision( RevisionRecord $revision ): ?PageVersion {
		$this->state['getVersionForRevisionCalls'][] = $revision;

		return $this->state['versionForRevision'];
	}

	public function deleteVersion( PageVersion $version ): void {
		$this->state['deletedVersions'][] = $version;
	}

	public function revisionAvailable( int $requestedRev, int $pageId ): bool {
		$this->state['revisionAvailableCalls'][] = [ $requestedRev, $pageId ];

		return $this->state['revisionAvailable'];
	}
}
