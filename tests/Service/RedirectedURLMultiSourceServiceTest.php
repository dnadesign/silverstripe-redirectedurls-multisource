<?php

declare(strict_types=1);

namespace DNADesign\RedirectedURLsMultiSource\Tests\Service;

use DNADesign\RedirectedURLsMultiSource\Model\RedirectSourceAlias;
use DNADesign\RedirectedURLsMultiSource\Service\RedirectedURLMultiSourceService;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\RedirectedURLs\Model\RedirectedURL;

class RedirectedURLMultiSourceServiceTest extends SapphireTest
{
    protected $usesDatabase = true;

    protected static $required_extensions = [
        RedirectedURL::class => [
            RuledOutRedirectExtension::class,
        ],
    ];

    public function testMatchesByPrimaryAndAdditionalSource(): void
    {
        $redirect = $this->createRedirect('/old', 'https://example.com/new');
        $this->createAlias($redirect, '/older');

        $this->assertSame('https://example.com/new', $this->findTarget('old'));
        $this->assertSame('https://example.com/new', $this->findTarget('older'));
    }

    public function testRedirectRuledOutByAnExtensionIsNotMatched(): void
    {
        $redirect = $this->createRedirect('/old', 'https://example.com/ruled-out');
        $this->createAlias($redirect, '/older');

        $this->assertNull($this->findTarget('old'));
        $this->assertNull($this->findTarget('older'));
    }

    public function testRuledOutRedirectLetsAWildcardMatchInstead(): void
    {
        $this->createRedirect('/section/page', 'https://example.com/ruled-out');
        $this->createRedirect('/section/*', 'https://example.com/section');

        $this->assertSame('https://example.com/section', $this->findTarget('section/page'));
    }

    public function testRuledOutWildcardIsNotMatched(): void
    {
        $this->createRedirect('/section/*', 'https://example.com/ruled-out/*');

        $this->assertNull($this->findTarget('section/page'));
    }

    public function testSourceTakenByAnotherRedirectsAliasIsRejected(): void
    {
        $this->createAlias($this->createRedirect('/owner', 'https://example.com/owner'), '/taken');

        $result = RedirectedURL::create(['FromBase' => '/taken', 'To' => 'https://example.com/new'])->validate();

        $this->assertFalse($result->isValid());
    }

    public function testSourceTakenByARuledOutRedirectsAliasIsAllowed(): void
    {
        $this->createAlias($this->createRedirect('/owner', 'https://example.com/ruled-out'), '/taken');

        $result = RedirectedURL::create(['FromBase' => '/taken', 'To' => 'https://example.com/new'])->validate();

        $this->assertTrue($result->isValid());
    }

    private function findTarget(string $url): ?string
    {
        $match = RedirectedURLMultiSourceService::create()->findBestRedirectedURLMatch(new HTTPRequest('GET', $url));

        return $match?->To;
    }

    private function createRedirect(string $from, string $to): RedirectedURL
    {
        $redirect = RedirectedURL::create([
            'FromBase' => $from,
            'FromQuerystring' => '',
            'RedirectionType' => 'External',
            'To' => $to,
        ]);
        $redirect->write();

        return $redirect;
    }

    private function createAlias(RedirectedURL $redirect, string $from): void
    {
        RedirectSourceAlias::create([
            'FromBase' => $from,
            'FromQuerystring' => '',
            'RedirectedURLID' => (int) $redirect->ID,
        ])->write();
    }
}
