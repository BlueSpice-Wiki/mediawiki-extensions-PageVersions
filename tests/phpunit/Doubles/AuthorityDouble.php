<?php

namespace MediaWiki\Extension\PageVersions\Tests\Doubles;

use MediaWiki\Permissions\SimpleAuthority;
use MediaWiki\User\UserIdentity;

class AuthorityDouble extends SimpleAuthority {

	public function __construct(
		UserIdentity $actor,
		array $permissions,
		private bool $isSystemUser
	) {
		parent::__construct( $actor, $permissions );
	}

	public function isSystemuser(): bool {
		return $this->isSystemUser;
	}
}
