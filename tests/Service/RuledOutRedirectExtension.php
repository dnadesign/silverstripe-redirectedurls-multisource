<?php

declare(strict_types=1);

namespace DNADesign\RedirectedURLsMultiSource\Tests\Service;

use SilverStripe\Core\Extension;
use SilverStripe\Dev\TestOnly;
use SilverStripe\RedirectedURLs\Model\RedirectedURL;

/**
 * Rules out any redirect whose target mentions "ruled-out", as the core subsite extension does for other subsites
 *
 * @extends Extension<(RedirectedURL&static)>
 */
class RuledOutRedirectExtension extends Extension implements TestOnly
{
    public function filterBestRedirectedURLMatch(): ?bool
    {
        return str_contains((string) $this->owner->To, 'ruled-out') ? false : null;
    }
}
