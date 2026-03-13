<?php

namespace MediaWiki\Extension\PageVersions\Hook;

use MediaWiki\Extension\PageVersions\PageVersion;

interface PageVersionDeletedHook {

	/**
	 * @param PageVersion $version
	 * @return void
	 */
	public function onPageVersionDeleted( PageVersion $version ): void;
}
