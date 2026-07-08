<?php

namespace MediaWiki\Extension\PageVersions\Integration\Galaxy;

use BlueSpice\GalaxyDistributionConnector\NamespaceSettings\INamespaceSetting;
use MediaWiki\Message\Message;

class NamespaceSetting implements INamespaceSetting {

	/**
	 * @return Message
	 */
	public function getLabel(): Message {
		return Message::newFromKey( 'page-versions-ns-setting-label' );
	}

	/**
	 * @return Message
	 */
	public function getDescription(): Message {
		return Message::newFromKey( 'page-versions-ns-setting-desc' );
	}

	/**
	 * @param int $namespace
	 * @param mixed $value
	 * @return void
	 */
	public function apply( int $namespace, mixed $value ): void {
		$GLOBALS['wgPageVersionsEnabledNamespaces'] = $GLOBALS['wgPageVersionsEnabledNamespaces'] ?? [];
		if ( !$value && in_array( $namespace, $GLOBALS['wgPageVersionsEnabledNamespaces'] ) ) {
			$GLOBALS['wgPageVersionsEnabledNamespaces'] = array_diff(
				$GLOBALS['wgPageVersionsEnabledNamespaces'],
				[ $namespace ]
			);
		} elseif ( $value && !in_array( $namespace, $GLOBALS['wgPageVersionsEnabledNamespaces'] ) ) {
			$GLOBALS['wgPageVersionsEnabledNamespaces'][] = $namespace;
		}
	}
}
