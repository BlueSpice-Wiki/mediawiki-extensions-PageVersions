<?php

namespace MediaWiki\Extension\PageVersions\Rest;

use MediaWiki\Context\RequestContext;
use MediaWiki\Extension\PageVersions\Exception\RevisionUnavailableException;
use MediaWiki\Extension\PageVersions\PageVersionManager;
use MediaWiki\Rest\HttpException;
use MediaWiki\Rest\Response;
use MediaWiki\Rest\SimpleHandler;
use MediaWiki\Revision\RevisionLookup;
use PermissionsError;
use Wikimedia\ParamValidator\ParamValidator;

class AddVersionHandler extends SimpleHandler {

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
	 * @throws RevisionUnavailableException
	 * @throws PermissionsError
	 */
	public function execute() {
		$params = $this->getValidatedBody();
		$revision = $this->revisionLookup->getRevisionById( $params['revision'] );
		if ( !$revision ) {
			throw new HttpException( 'Invalid revision ID' );
		}
		$actor = RequestContext::getMain()->getUser();
		$version = $this->versionManager->createNewVersion( $revision, $actor, $params['type'], $params['comment'] );
		return $this->getResponseFactory()->createJson( [ 'success' => true, 'version' => $version ] );
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
			],
			'type' => [
				static::PARAM_SOURCE => 'body',
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => true,
			],
			'comment' => [
				static::PARAM_SOURCE => 'body',
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => false,
				ParamValidator::PARAM_DEFAULT => ''
			]
		];
	}
}
