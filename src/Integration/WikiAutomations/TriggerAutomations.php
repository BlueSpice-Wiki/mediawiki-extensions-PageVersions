<?php

namespace MediaWiki\Extension\PageVersions\Integration\WikiAutomations;

use MediaWiki\Extension\PageVersions\Hook\PageVersionCreatedHook;
use MediaWiki\Extension\PageVersions\PageVersion;
use MediaWiki\Extension\WikiAutomations\AutomationRunner;

readonly class TriggerAutomations implements PageVersionCreatedHook {

	/**
	 * @param AutomationRunner $automationRunner
	 */
	public function __construct(
		private AutomationRunner $automationRunner
	) {
	}

	/**
	 * @inheritDoc
	 */
	public function onPageVersionCreated( PageVersion $version, string $versionType ): void {
		$this->automationRunner->scheduleTrigger( 'page_version_created', [
			$version->getRevision()->getPage()
		], null, [
			'version_type' => $versionType,
			'author' => $version->getAuthor()->getUser()->getName(),
			'revision' => $version->getRevision()->getId(),
			'comment' => $version->getRevision()->getComment()
		] );
	}
}
