<?php

declare(strict_types=1);

namespace DNADesign\RedirectedURLsMultiSource\Service;

use DNADesign\RedirectedURLsMultiSource\Model\RedirectSourceAlias;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Convert;
use SilverStripe\Model\List\ArrayList;
use SilverStripe\RedirectedURLs\Model\RedirectedURL;
use SilverStripe\RedirectedURLs\Service\RedirectedURLService;
use SilverStripe\RedirectedURLs\Support\Arr;

class RedirectedURLMultiSourceService extends RedirectedURLService
{
    public function findBestRedirectedURLMatch(HTTPRequest $request): ?RedirectedURL
    {
        $base = strtolower($request->getURL());
        $getVars = Arr::toLowercase($request->getVars());

        $listPotentials = $this->buildPotentialList($base);

        if ($listPotentials->count() === 0) {
            return null;
        }

        foreach ($listPotentials as $potential) {
            /** @var RedirectedURL $potential */
            $allVarsMatch = true;

            if ($potential->FromQuerystring) {
                $reqVars = [];
                parse_str($potential->FromQuerystring, $reqVars);

                foreach ($reqVars as $key => $value) {
                    if (!$value) {
                        continue;
                    }

                    if (!isset($getVars[$key]) || (string) $value !== (string) $getVars[$key]) {
                        $allVarsMatch = false;
                        break;
                    }
                }
            }

            if ($allVarsMatch) {
                return $potential;
            }
        }

        return null;
    }

    /**
     * @return ArrayList<RedirectedURL>
     */
    private function buildPotentialList(string $base): ArrayList
    {
        $sqlBase = Convert::raw2sql(rtrim($base, '/'));

        /** @var ArrayList<RedirectedURL> $potentials */
        $potentials = ArrayList::create();
        $seen = [];

        foreach ($this->getCoreCandidates('/' . $sqlBase) as $candidate) {
            $this->pushIfUnique($potentials, $candidate, $seen);
        }

        foreach ($this->getAliasCandidates('/' . $sqlBase) as $candidate) {
            $this->pushIfUnique($potentials, $candidate, $seen);
        }

        $baseParts = explode('/', $base);
        for ($pos = count($baseParts) - 1; $pos >= 0; $pos--) {
            $baseStr = implode('/', array_slice($baseParts, 0, $pos));
            $wildcardBase = '/' . Convert::raw2sql($baseStr . '/*');

            foreach ($this->getCoreCandidates($wildcardBase) as $candidate) {
                $this->applyWildcardToIfNeeded($candidate, $base, $baseStr);
                $this->pushIfUnique($potentials, $candidate, $seen);
            }

            foreach ($this->getAliasCandidates($wildcardBase) as $candidate) {
                $this->applyWildcardToIfNeeded($candidate, $base, $baseStr);
                $this->pushIfUnique($potentials, $candidate, $seen);
            }
        }

        return $potentials;
    }

    /**
     * @return iterable<RedirectedURL>
     */
    private function getCoreCandidates(string $fromBase): iterable
    {
        return RedirectedURL::get()->filter(['FromBase' => $fromBase])->sort('FromQuerystring DESC');
    }

    /**
     * @return iterable<RedirectedURL>
     */
    private function getAliasCandidates(string $fromBase): iterable
    {
        $aliases = RedirectSourceAlias::get()
            ->filter(['FromBase' => $fromBase])
            ->sort('FromQuerystring DESC');

        foreach ($aliases as $alias) {
            $target = $alias->RedirectedURL();
            if (!$target->exists()) {
                continue;
            }

            $candidate = clone $target;
            $candidate->FromBase = $alias->FromBase;
            $candidate->FromQuerystring = $alias->FromQuerystring;

            yield $candidate;
        }
    }

    /**
     * @param array<string, bool> $seen
     */
    private function pushIfUnique(ArrayList $potentials, RedirectedURL $candidate, array &$seen): void
    {
        $key = sprintf(
            '%d|%s|%s',
            (int) $candidate->ID,
            (string) $candidate->FromBase,
            (string) $candidate->FromQuerystring,
        );

        if (isset($seen[$key])) {
            return;
        }

        $potentials->push($candidate);
        $seen[$key] = true;
    }

    private function applyWildcardToIfNeeded(RedirectedURL $candidate, string $base, string $baseStr): void
    {
        if ($candidate->RedirectionType !== 'External') {
            return;
        }

        if (!str_ends_with((string) $candidate->To, '/*')) {
            return;
        }

        $candidate->To = substr((string) $candidate->To, 0, -2) . substr($base, strlen($baseStr));
    }
}
