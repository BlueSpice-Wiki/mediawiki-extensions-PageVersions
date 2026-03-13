<?php

namespace MediaWiki\Extension\PageVersions\Hook;

use MediaWiki\Extension\PageVersions\PageVersion;

interface PageVersionCreatedHook {

	/**
	 * @param PageVersion $version
	 * @param string $versionType
	 * @return void
	 */
	public function onPageVersionCreated( PageVersion $version, string $versionType ): void;
}
