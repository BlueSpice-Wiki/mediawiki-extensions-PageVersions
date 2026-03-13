<?php

namespace MediaWiki\Extension\PageVersions\Integration;

use MediaWiki\Extension\PageCheckout\Entity\CheckoutEntity;
use MediaWiki\Extension\PageCheckout\IPageCheckoutPlugin;
use MediaWiki\Extension\PageVersions\PageVersionManager;
use MediaWiki\Extension\PageVersions\PageVersionStore;
use MediaWiki\Extension\PageVersions\Util\PageVersionLogger;
use MediaWiki\Extension\PageVersions\Util\VersionBumper;
use MediaWiki\Message\Message;
use MediaWiki\Page\PageIdentity;
use MediaWiki\Revision\RevisionLookup;
use MediaWiki\User\UserIdentity;
use MWStake\MediaWiki\Component\FormEngine\IFormSpecification;
use MWStake\MediaWiki\Component\FormEngine\StandaloneFormSpecification;
use Throwable;

readonly class PageCheckoutPlugin implements IPageCheckoutPlugin {

	/**
	 * @param PageVersionStore $store
	 * @param RevisionLookup $revisionLookup
	 * @param PageVersionManager $versionManager
	 * @param PageVersionLogger $logger
	 * @param VersionBumper $versionBumper
	 */
	public function __construct(
		private PageVersionStore $store,
		private RevisionLookup $revisionLookup,
		private PageVersionManager $versionManager,
		private PageVersionLogger $logger,
		private VersionBumper $versionBumper
	) {
	}

	/**
	 * @inheritDoc
	 */
	public function getCheckInLayout( PageIdentity $forPage, UserIdentity $forUser ): ?IFormSpecification {
		$latestRevision = $this->revisionLookup->getRevisionByTitle( $forPage );
		if ( $latestRevision && $this->store->getVersionForRevision( $latestRevision ) !== null ) {
			// Revision already has a version
			return null;
		}
		$currentLatest = $this->store->getCurrentPageVersion( $forPage->getId() ) ?? '';

		$items = [
			[
				'type' => 'section_label',
				'title' => Message::newFromKey( 'pageversions-checkin-form-header' )->text()
			],
		];
		if ( $currentLatest ) {
			$msg = Message::newFromKey( 'page-versions-next-versions-current', $currentLatest )->text();
			$items[] = [
				'type' => 'label',
				'widget_label' => $msg
			];
		}
		$items[] = [
			'type' => 'hr',
			'noLayout' => true
		];
		$options = [
			[
				'data' => -1,
				'label' => Message::newFromKey( 'pageversions-ui-version-type-label-none' )->text()
			],
		];
		$available = $this->versionBumper->getAvailable();
		foreach ( $available as $bumpType ) {
			$index = $this->versionBumper->getBumpIndex( $bumpType );
			$newVersion = $this->versionBumper->bump( $currentLatest, $index );
			// pageversions-ui-version-type-label-patch
			// pageversions-ui-version-type-label-minor
			// pageversions-ui-version-type-label-major
			$options[] = [
				'data' => $bumpType,
				'label' => Message::newFromKey(
					'pageversions-ui-version-type-label-' . $bumpType, $newVersion
				)->text()
			];
		}
		$items[] = [
			'type' => 'radio_multiselect',
			'name' => 'version_type',
			'noLayout' => true,
			'options' => $options
		];

		$form = new StandaloneFormSpecification();
		$form->setItems( $items );
		return $form;
	}

	/**
	 * @inheritDoc
	 */
	public function onCheckout( CheckoutEntity $checkoutEntity ): void {
		// NOOP
	}

	/**
	 * @inheritDoc
	 */
	public function onCheckIn( CheckoutEntity $checkoutEntity, array $data ): void {
		$type = $data['version_type'] ?? null;
		$revision = $this->revisionLookup->getRevisionByTitle( $checkoutEntity->getTitle() );
		if ( !$revision ) {
			return;
		}
		try {
			$this->versionManager->createNewVersion( $revision, $checkoutEntity->getUser(), $type );
		} catch ( Throwable $e ) {
			$this->logger->logError( 'Failed to create page version on check-in for page {page}: {error}', [
				'page' => $checkoutEntity->getTitle()->getPrefixedText(),
				'error' => $e->getMessage()
			] );
		}
	}
}
