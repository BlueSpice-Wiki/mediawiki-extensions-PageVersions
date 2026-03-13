<?php

namespace MediaWiki\Extension\PageVersions\Util;

use Config;

class VersionBumper {

	public const VERSION_PATCH = 'patch';
	public const VERSION_MINOR = 'minor';
	public const VERSION_MAJOR = 'major';

	public const PATCH_BUMP = 2;
	public const MINOR_BUMP = 1;
	public const MAJOR_BUMP = 0;

	/** @var int[] */
	private $typeMap = [
		'patch' => self::PATCH_BUMP,
		'minor' => self::MINOR_BUMP,
		'major' => self::MAJOR_BUMP
	];

	/**
	 * @param Config $config
	 */
	public function __construct(
		private readonly Config $config
	) {
	}

	/**
	 * @param string $type
	 * @return int
	 */
	public function getBumpIndex( string $type ): int {
		$available = $this->getAvailable();
		if ( !in_array( $type, $available ) || !isset( $this->typeMap[$type] ) ) {
			throw new \InvalidArgumentException( "Invalid version type: $type" );
		}
		return $this->typeMap[$type];
	}

	/**
	 * @return string[]
	 */
	public function getAvailable(): array {
		return array_intersect( array_keys( $this->typeMap ), $this->config->get( 'PageVersionsLevels' ) ?? [] );
	}

	/**
	 * @param string $old
	 * @return string
	 */
	public function bumpPatch( string $old = '' ): string {
		return $this->bump( $old, 2 );
	}

	/**
	 * @param string $old
	 * @return string
	 */
	public function bumpMinor( string $old = '' ): string {
		return $this->bump( $old, 1 );
	}

	/**
	 * @param string $old
	 * @return string
	 */
	public function bumpMajor( string $old = '' ): string {
		return $this->bump( $old, 0 );
	}

	/**
	 * @param string $old
	 * @param int $index
	 * @return string
	 */
	public function bump( string $old, int $index ): string {
		// Split version into parts. Every version has `X.Y.Z` format
		$parts = explode( '.', $old );
		$major = (int)$parts[0] ?? 0;
		$minor = (int)$parts[1] ?? 0;
		$patch = (int)$parts[2] ?? 0;

		// Bump the requested part and reset the parts to the right of it
		switch ( $index ) {
			case 0:
				$major++;
				$minor = 0;
				$patch = 0;
				break;
			case 1:
				$minor++;
				$patch = 0;
				break;
			case 2:
				$patch++;
				break;
		}

		return "$major.$minor.$patch";
	}
}
