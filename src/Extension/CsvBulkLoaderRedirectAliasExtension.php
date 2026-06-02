<?php

declare(strict_types=1);

namespace DNADesign\RedirectedURLsMultiSource\Extension;

use DNADesign\RedirectedURLsMultiSource\Model\RedirectSourceAlias;
use DNADesign\RedirectedURLsMultiSource\Support\FromURLParser;
use SilverStripe\Core\Extension;
use SilverStripe\Dev\BulkLoader_Result;
use SilverStripe\Dev\CsvBulkLoader;
use SilverStripe\RedirectedURLs\Model\RedirectedURL;

/**
 * @extends Extension<CsvBulkLoader&static>
 */
class CsvBulkLoaderRedirectAliasExtension extends Extension
{
    /**
     * Convert imported redirects to aliases when they target the same destination as an existing redirect.
     * This keeps the import UX unchanged while storing multi-source redirects in the alias model.
     */
    public function onAfterProcessAll(BulkLoader_Result $result, bool $preview): void
    {
        if ($preview) {
            return;
        }

        if ($this->owner->objectClass !== RedirectedURL::class) {
            return;
        }

        foreach ($result->Created() as $created) {
            if (!$created instanceof RedirectedURL || !$created->exists()) {
                continue;
            }

            $canonical = $this->findCanonicalRedirect($created);
            if (!$canonical || !$canonical->exists()) {
                continue;
            }

            $this->createAliasIfNeeded($canonical, $created);
            $created->delete();
        }
    }

    private function findCanonicalRedirect(RedirectedURL $created): ?RedirectedURL
    {
        $filter = [
            'RedirectionType' => $created->RedirectionType,
        ];

        if ($created->RedirectionType === 'External') {
            $filter['To'] = $created->To;
        } elseif ($created->RedirectionType === 'Internal') {
            $filter['LinkToID'] = (int) $created->LinkToID;
        } elseif ($created->RedirectionType === 'Asset') {
            $filter['LinkToAssetID'] = (int) $created->LinkToAssetID;
        }

        return RedirectedURL::get()
            ->filter($filter)
            ->exclude(['ID' => (int) $created->ID])
            ->sort('ID ASC')
            ->first();
    }

    private function createAliasIfNeeded(RedirectedURL $canonical, RedirectedURL $created): void
    {
        $base = FromURLParser::normaliseBase((string) $created->FromBase);
        $query = FromURLParser::normaliseQuerystring($created->FromQuerystring) ?? '';

        if ($base === '') {
            return;
        }

        $existingAlias = RedirectSourceAlias::get()
            ->filter([
                'FromBase' => $base,
                'FromQuerystring' => $query,
            ])
            ->first();

        if ($existingAlias && $existingAlias->exists()) {
            return;
        }

        RedirectSourceAlias::create([
            'FromBase' => $base,
            'FromQuerystring' => $query,
            'RedirectedURLID' => (int) $canonical->ID,
        ])->write();
    }
}
