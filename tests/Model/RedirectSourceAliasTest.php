<?php

declare(strict_types=1);

namespace DNADesign\RedirectedURLsMultiSource\Tests\Model;

use DNADesign\RedirectedURLsMultiSource\Model\RedirectSourceAlias;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\RedirectedURLs\Model\RedirectedURL;

class RedirectSourceAliasTest extends SapphireTest
{
    protected $usesDatabase = true;

    public function testFormShowsItsRedirectInsteadOfAPicker(): void
    {
        $redirect = RedirectedURL::create(['RedirectionType' => 'External', 'To' => '/heart-age']);
        $redirect->write();
        RedirectSourceAlias::create(['FromBase' => '/old-one', 'RedirectedURLID' => (int) $redirect->ID])->write();
        $alias = RedirectSourceAlias::create(['FromBase' => '/old-two', 'RedirectedURLID' => (int) $redirect->ID]);
        $alias->write();

        $fields = $alias->getCMSFields();
        $label = $fields->dataFieldByName('RedirectLabel');

        $this->assertNull($fields->dataFieldByName('RedirectedURLID'));
        $this->assertInstanceOf(ReadonlyField::class, $label);
        $this->assertSame('/old-one, /old-two → /heart-age', $label->getValue());
    }

    public function testFormWithoutARedirectShowsNeither(): void
    {
        $fields = RedirectSourceAlias::create()->getCMSFields();

        $this->assertNull($fields->dataFieldByName('RedirectedURLID'));
        $this->assertNull($fields->dataFieldByName('RedirectLabel'));
    }
}
