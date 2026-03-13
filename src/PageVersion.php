<?php

namespace MediaWiki\Extension\PageVersions;

use DateTime;
use MediaWiki\Permissions\Authority;
use MediaWiki\Revision\RevisionRecord;

readonly class PageVersion implements \JsonSerializable {

	public function __construct(
		private RevisionRecord $revision,
		private string $version,
		private bool $isLatest,
		private Authority $author,
		private DateTime $timestamp,
		private string $comment,
		private string $wikiId
	) {
	}

	/**
	 * @return RevisionRecord
	 */
	public function getRevision(): RevisionRecord {
		return $this->revision;
	}

	/**
	 * @return string
	 */
	public function getVersion(): string {
		return $this->version;
	}

	/**
	 * @return DateTime
	 */
	public function getTimestamp(): DateTime {
		return $this->timestamp;
	}

	/**
	 * @return bool
	 */
	public function isLatest(): bool {
		return $this->isLatest;
	}

	/**
	 * @return Authority
	 */
	public function getAuthor(): Authority {
		return $this->author;
	}

	/**
	 * @return string
	 */
	public function getComment(): string {
		return $this->comment;
	}

	/**
	 * @return string
	 */
	public function getWikiId(): string {
		return $this->wikiId;
	}

	public function jsonSerialize(): mixed {
		return [
			'revisionId' => $this->revision->getId(),
			'version' => $this->version,
			'isLatest' => $this->isLatest,
			'timestamp' => $this->timestamp->format( 'YmdHis' ),
			'author' => [
				'id' => $this->author->getUser()->getId(),
				'name' => $this->author->getUser()->getName()
			],
			'comment' => $this->comment,
			'wikiId' => $this->wikiId
		];
	}
}
