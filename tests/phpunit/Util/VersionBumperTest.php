<?php

namespace MediaWiki\Extension\PageVersions\Tests\Util;

use Config;
use MediaWiki\Extension\PageVersions\Util\VersionBumper;
use PHPUnit\Framework\TestCase;

class VersionBumperTest extends TestCase {

	public static function provideBumpCases(): array {
		return [
			'major bump resets lower parts' => [ '1.2.3', VersionBumper::MAJOR_BUMP, '2.0.0' ],
			'minor bump resets patch' => [ '1.2.3', VersionBumper::MINOR_BUMP, '1.3.0' ],
			'patch bump increments patch only' => [ '1.2.3', VersionBumper::PATCH_BUMP, '1.2.4' ],
			'patch bump handles larger patch numbers' => [ '5.0.9', VersionBumper::PATCH_BUMP, '5.0.10' ],
		];
	}

	/**
	 * @dataProvider provideBumpCases
	 * @covers \MediaWiki\Extension\PageVersions\Util\VersionBumper::bump
	 */
	public function testBumpReturnsExpectedVersion( string $old, int $index, string $expected ): void {
		$bumper = $this->newVersionBumper();

		$this->assertSame( $expected, $bumper->bump( $old, $index ) );
	}

	/**
	 * @covers \MediaWiki\Extension\PageVersions\Util\VersionBumper::bumpMajor
	 * @covers \MediaWiki\Extension\PageVersions\Util\VersionBumper::bumpMinor
	 * @covers \MediaWiki\Extension\PageVersions\Util\VersionBumper::bumpPatch
	 */
	public function testConvenienceBumpMethodsUseMatchingIndices(): void {
		$bumper = $this->newVersionBumper();

		$this->assertSame( '2.0.0', $bumper->bumpMajor( '1.2.3' ) );
		$this->assertSame( '1.3.0', $bumper->bumpMinor( '1.2.3' ) );
		$this->assertSame( '1.2.4', $bumper->bumpPatch( '1.2.3' ) );
	}

	/**
	 * @covers \MediaWiki\Extension\PageVersions\Util\VersionBumper::getAvailable
	 */
	public function testGetAvailableReturnsConfiguredVersionTypesOnly(): void {
		$bumper = $this->newVersionBumper( [ VersionBumper::VERSION_MINOR, VersionBumper::VERSION_PATCH ] );

		$this->assertSame(
			[ VersionBumper::VERSION_PATCH, VersionBumper::VERSION_MINOR ],
			array_values( $bumper->getAvailable() )
		);
	}

	/**
	 * @covers \MediaWiki\Extension\PageVersions\Util\VersionBumper::getBumpIndex
	 */
	public function testGetBumpIndexReturnsConfiguredIndex(): void {
		$bumper = $this->newVersionBumper();

		$this->assertSame( VersionBumper::MAJOR_BUMP, $bumper->getBumpIndex( VersionBumper::VERSION_MAJOR ) );
		$this->assertSame( VersionBumper::MINOR_BUMP, $bumper->getBumpIndex( VersionBumper::VERSION_MINOR ) );
		$this->assertSame( VersionBumper::PATCH_BUMP, $bumper->getBumpIndex( VersionBumper::VERSION_PATCH ) );
	}

	/**
	 * @covers \MediaWiki\Extension\PageVersions\Util\VersionBumper::getBumpIndex
	 */
	public function testGetBumpIndexRejectsUnavailableVersionType(): void {
		$bumper = $this->newVersionBumper( [ VersionBumper::VERSION_MINOR ] );

		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Invalid version type: patch' );

		$bumper->getBumpIndex( VersionBumper::VERSION_PATCH );
	}

	private function newVersionBumper( array $levels = [
		VersionBumper::VERSION_MAJOR,
		VersionBumper::VERSION_MINOR,
		VersionBumper::VERSION_PATCH
	] ): VersionBumper {
		$config = $this->createMock( Config::class );
		$config->method( 'get' )
			->with( 'PageVersionsLevels' )
			->willReturn( $levels );

		return new VersionBumper( $config );
	}
}
