<?php

namespace MediaWiki\Extension\PageVersions\Integration\Workflows;

use MediaWiki\Extension\PageVersions\Exception\RevisionUnavailableException;
use MediaWiki\Extension\PageVersions\PageVersionManager;
use MediaWiki\Extension\Workflows\Activity\ExecutionStatus;
use MediaWiki\Extension\Workflows\Activity\GenericActivity;
use MediaWiki\Extension\Workflows\Definition\ITask;
use MediaWiki\Extension\Workflows\Exception\WorkflowExecutionException;
use MediaWiki\Extension\Workflows\WorkflowContext;
use MediaWiki\Message\Message;
use MediaWiki\Revision\RevisionLookup;
use MediaWiki\Title\Title;
use MediaWiki\Title\TitleFactory;
use MediaWiki\User\User;

class NewPageVersionActivity extends GenericActivity {

	/**
	 * @param TitleFactory $titleFactory
	 * @param RevisionLookup $revisionLookup
	 * @param PageVersionManager $pageVersionManager
	 * @param ITask $task
	 */
	public function __construct(
		private readonly TitleFactory $titleFactory,
		private readonly RevisionLookup $revisionLookup,
		private readonly PageVersionManager $pageVersionManager,
		ITask $task
	) {
		parent::__construct( $task );
	}

	/**
	 * @inheritDoc
	 */
	public function execute( $data, WorkflowContext $context ): ExecutionStatus {
		$title = $this->getAffectedTitle( $data, $context );
		if ( !$title instanceof Title ) {
			throw new WorkflowExecutionException(
				Message::newFromKey( 'pageversions-workflow-exception-no-title' )->text(), $this->getTask()
			);
		}
		$revId = $data['revision'] ?? $title->getLatestRevID();
		$revision = $this->revisionLookup->getRevisionById( $revId );
		if ( !$revision ) {
			throw new WorkflowExecutionException(
				'pageversions-workflow-exception-no-revision', $this->getTask()
			);
		}
		$type = $data['version_type'] ?? null;

		try {
			$user = User::newSystemUser( 'MediaWiki default', [ 'steal' => true ] );
			$this->pageVersionManager->createNewVersion( $revision, $user, $type, $data['comment'] ?? '' );
			return new ExecutionStatus( static::STATUS_COMPLETE, $data );
		} catch ( \Throwable $ex ) {
			if ( $ex instanceof RevisionUnavailableException ) {
				return new ExecutionStatus( static::STATUS_COMPLETE, $data );
			}
			throw new WorkflowExecutionException( $ex->getMessage(), $this->getTask() );
		}
	}

	/**
	 * @param array $data
	 * @param WorkflowContext $context
	 * @return Title|null
	 */
	private function getAffectedTitle( $data, WorkflowContext $context ) {
		if ( !empty( $data['pageId'] ) ) {
			return $this->titleFactory->newFromID( $data['pageId'] );
		}
		if ( !empty( $data['pagename'] ) ) {
			return $this->titleFactory->newFromText( $data['pagename'] );
		}

		return $context->getContextPage();
	}
}
