<?php

namespace App\Contracts\Crawl;

/**
 * A single normalized record extracted from an external source before it is
 * persisted. Connectors should convert source-specific payloads into this
 * shape so that the sync layer does not care about the upstream format.
 */
class RawRecord
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public readonly string $externalId,
        public readonly string $recordType,
        public readonly ?string $name = null,
        public readonly ?string $provinceCode = null,
        public readonly ?string $kabupatenCode = null,
        public readonly ?string $kabupatenName = null,
        public readonly ?string $kecamatanCode = null,
        public readonly ?string $kecamatanName = null,
        public readonly ?string $desaCode = null,
        public readonly ?string $desaName = null,
        public readonly ?float $latitude = null,
        public readonly ?float $longitude = null,
        public readonly array $data = [],
        public readonly ?string $sourceUrl = null,
        public readonly ?\DateTimeInterface $sourceUpdatedAt = null,
    ) {}
}
