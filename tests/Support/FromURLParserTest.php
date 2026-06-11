<?php

declare(strict_types=1);

namespace DNADesign\RedirectedURLsMultiSource\Tests\Support;

use DNADesign\RedirectedURLsMultiSource\Support\FromURLParser;
use PHPUnit\Framework\TestCase;

class FromURLParserTest extends TestCase
{
    public function testParseFromURLWithQuerystring(): void
    {
        [$base, $query] = FromURLParser::parseFromURL('/old-path?page=1');

        $this->assertSame('/old-path', $base);
        $this->assertSame('page=1', $query);
    }

    public function testParseFromURLNormalisesBase(): void
    {
        [$base, $query] = FromURLParser::parseFromURL('legacy/path/');

        $this->assertSame('/legacy/path', $base);
        $this->assertNull($query);
    }

    public function testFormatFromURL(): void
    {
        $this->assertSame('/legacy?id=10', FromURLParser::formatFromURL('/legacy', 'id=10'));
    }
}
