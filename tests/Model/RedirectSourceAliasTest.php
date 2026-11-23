<?php

declare(strict_types=1);

namespace DNADesign\RedirectedURLsMultiSource\Tests\Model;

use DNADesign\RedirectedURLsMultiSource\Model\RedirectSourceAlias;
use SilverStripe\Dev\SapphireTest;

class RedirectSourceAliasTest extends SapphireTest
{
    public function testFormHasNoRedirectPicker(): void
    {
        $fields = RedirectSourceAlias::create()->getCMSFields();

        $this->assertNull($fields->dataFieldByName('RedirectedURLID'));
        $this->assertNotNull($fields->dataFieldByName('FromBase'));
    }
}
