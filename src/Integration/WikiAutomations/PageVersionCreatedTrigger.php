<?php

namespace MediaWiki\Extension\PageVersions\Integration\WikiAutomations;

use MediaWiki\Extension\PageVersions\Util\VersionBumper;
use MediaWiki\Extension\WikiAutomations\Trigger\PageEventTrigger;
use MediaWiki\Message\Message;
use MediaWiki\Page\PageIdentity;
use MWStake\MediaWiki\Component\FormEngine\IFormSpecification;
use MWStake\MediaWiki\Component\FormEngine\StandaloneFormSpecification;

class PageVersionCreatedTrigger extends PageEventTrigger {

	/**
	 * @param VersionBumper $versionBumper
	 */
	public function __construct(
		private readonly VersionBumper $versionBumper
	) {
	}

	/**
	 * @return IFormSpecification|null
	 */
	public function getLayout(): ?IFormSpecification {
		$availableBumps = $this->versionBumper->getAvailable();
		$spec = new StandaloneFormSpecification();

		$items = [
			[
				'type' => 'label',
				'widget_label' => Message::newFromKey( 'pageversions-automation-trigger-type-header' )->text()
			]
		];

		foreach ( $availableBumps as $availableBump ) {
			// pageversions-workflow-version-type-label-patch
			// pageversions-workflow-version-type-label-minor
			// pageversions-workflow-version-type-label-major
			$items[] = [
				'type' => 'checkbox',
				'name' => $availableBump,
				'label' => Message::newFromKey( "pageversions-workflow-version-type-label-$availableBump" )->text()
			];
		}
		$spec->setItems( $items );
		return $spec;
	}

	/**
	 * @return array
	 */
	public function getDisplayData(): array {
		$data = $this->getData();
		$versionTypes = [];
		if ( $data['patch'] ) {
			$versionTypes[] = Message::newFromKey( 'pageversions-workflow-version-type-label-patch' )->text();
		}
		if ( $data['minor'] ) {
			$versionTypes[] = Message::newFromKey( 'pageversions-workflow-version-type-label-minor' )->text();
		}
		if ( $data['major'] ) {
			$versionTypes[] = Message::newFromKey( 'pageversions-workflow-version-type-label-major' )->text();
		}
		return [ [
			'key' => Message::newFromKey( 'pageversions-automation-trigger-type-header' )->text(),
			'value' => implode( ', ', $versionTypes )
		] ];
	}

	/**
	 * @param array $triggerData
	 * @return array|PageIdentity[]
	 */
	public function providePages( array $triggerData = [] ): array {
		$versionType = $triggerData['version_type'] ?? '';
		$data = $this->getData();

		$finalPages = [];
		foreach ( $this->pages as $page ) {
			if (
				!$data[VersionBumper::VERSION_PATCH] &&
				!$data[VersionBumper::VERSION_MINOR] &&
				!$data[VersionBumper::VERSION_MAJOR]
			) {
				// Nothing selected, apply to all
				$finalPages[] = $page;
				continue;
			}
			if ( $versionType === VersionBumper::VERSION_PATCH && $data[VersionBumper::VERSION_PATCH] ) {
				$finalPages[] = $page;
			} elseif ( $versionType === VersionBumper::VERSION_MINOR && $data[VersionBumper::VERSION_MINOR] ) {
				$finalPages[] = $page;
			} elseif ( $versionType === VersionBumper::VERSION_MAJOR && $data[VersionBumper::VERSION_MAJOR] ) {
				$finalPages[] = $page;
			}
		}
		return $finalPages;
	}
}
