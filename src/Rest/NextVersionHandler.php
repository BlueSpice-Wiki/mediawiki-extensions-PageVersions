<?php

namespace MediaWiki\Extension\PageVersions\Rest;

use MediaWiki\Extension\PageVersions\PageVersionStore;
use MediaWiki\Extension\PageVersions\Util\VersionBumper;
use MediaWiki\Rest\Response;
use MediaWiki\Rest\SimpleHandler;
use Wikimedia\ParamValidator\ParamValidator;

class NextVersionHandler extends SimpleHandler {

	/**
	 * @param PageVersionStore $versionStore
	 * @param VersionBumper $bumper
	 */
	public function __construct(
		private readonly PageVersionStore $versionStore,
		private readonly VersionBumper $bumper
	) {
	}

	/**
	 * @return Response
	 */
	public function execute() {
		$currentLatest = $this->versionStore->getCurrentPageVersion( $this->getValidatedParams()['pageId'] ) ?? '';

		$data = [
			'current' => $currentLatest,
		];
		$available = $this->bumper->getAvailable();
		if ( in_array( VersionBumper::VERSION_PATCH, $available ) ) {
			$data[VersionBumper::VERSION_PATCH] = $this->bumper->bumpPatch( $currentLatest );
		}
		if ( in_array( VersionBumper::VERSION_MINOR, $available ) ) {
			$data[VersionBumper::VERSION_MINOR] = $this->bumper->bumpMinor( $currentLatest );
		}
		if ( in_array( VersionBumper::VERSION_MAJOR, $available ) ) {
			$data[VersionBumper::VERSION_MAJOR] = $this->bumper->bumpMajor( $currentLatest );
		}

		return $this->getResponseFactory()->createJson( $data );
	}

	/**
	 * @return array[]
	 */
	public function getParamSettings(): array {
		return [
			'pageId' => [
				static::PARAM_SOURCE => 'path',
				ParamValidator::PARAM_TYPE => 'integer',
				ParamValidator::PARAM_REQUIRED => true,
			]
		];
	}
}
