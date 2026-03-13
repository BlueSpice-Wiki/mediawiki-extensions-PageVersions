<?php

namespace MediaWiki\Extension\PageVersions\Rest;

use MediaWiki\Context\RequestContext;
use MediaWiki\Extension\PageVersions\Exception\NoVersionOnRevisionException;
use MediaWiki\Extension\PageVersions\PageVersionManager;
use MediaWiki\Rest\HttpException;
use MediaWiki\Rest\Response;
use MediaWiki\Rest\SimpleHandler;
use MediaWiki\Revision\RevisionLookup;
use PermissionsError;
use Wikimedia\ParamValidator\ParamValidator;

class DeleteVersionHandler extends SimpleHandler {

	/**
	 * @param PageVersionManager $versionManager
	 * @param RevisionLookup $revisionLookup
	 */
	public function __construct(
		private readonly PageVersionManager $versionManager,
		private readonly RevisionLookup $revisionLookup
	) {
	}

	/**
	 * @return Response
	 * @throws HttpException
	 * @throws PermissionsError
	 * @throws NoVersionOnRevisionException
	 */
	public function execute() {
		$params = $this->getValidatedBody();
		$revision = $this->revisionLookup->getRevisionById( $params['revision'] );
		if ( !$revision ) {
			throw new HttpException( 'Invalid revision ID' );
		}
		$actor = RequestContext::getMain()->getUser();

		$this->versionManager->deleteVersion( $revision, $actor );
		return $this->getResponseFactory()->createJson( [ 'success' => true ] );
	}

	/**
	 * @return array[]
	 */
	public function getBodyParamSettings(): array {
		return [
			'revision' => [
				static::PARAM_SOURCE => 'body',
				ParamValidator::PARAM_TYPE => 'integer',
				ParamValidator::PARAM_REQUIRED => true,
			]
		];
	}
}
