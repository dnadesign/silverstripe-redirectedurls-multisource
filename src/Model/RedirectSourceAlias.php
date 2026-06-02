<?php

declare(strict_types=1);

namespace DNADesign\RedirectedURLsMultiSource\Model;

use DNADesign\RedirectedURLsMultiSource\Support\FromURLParser;
use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DataObject;
use SilverStripe\RedirectedURLs\Model\RedirectedURL;

/**
 * @property string $FromBase
 * @property string $FromQuerystring
 * @property int $RedirectedURLID
 * @method RedirectedURL RedirectedURL()
 */
class RedirectSourceAlias extends DataObject
{
    private static string $table_name = 'RedirectSourceAlias';

    private static array $db = [
        'FromBase' => 'Varchar(255)',
        'FromQuerystring' => 'Varchar(255)',
    ];

    private static array $has_one = [
        'RedirectedURL' => RedirectedURL::class,
    ];

    private static array $indexes = [
        'UniqueFrom' => [
            'type' => 'unique',
            'columns' => [
                'FromBase',
                'FromQuerystring',
            ],
        ],
    ];

    private static array $summary_fields = [
        'FromBase' => 'From URL base',
        'FromQuerystring' => 'From URL query parameters',
    ];

    public function getCMSFields(): FieldList
    {
        $fields = parent::getCMSFields();

        $queryField = $fields->dataFieldByName('FromQuerystring');
        if ($queryField !== null) {
            $queryField
                ->setTitle('From URL query parameters')
                ->setDescription('Enter query parameters only (e.g. page=1&num=5). Do not include a leading ?');
        }

        return $fields;
    }

    protected function onBeforeWrite(): void
    {
        parent::onBeforeWrite();

        $this->FromBase = FromURLParser::normaliseBase((string) $this->FromBase);
        $this->FromQuerystring = FromURLParser::normaliseQuerystring($this->FromQuerystring) ?? '';
    }

    public function getFrom(): string
    {
        return FromURLParser::formatFromURL($this->FromBase, $this->FromQuerystring ?: null);
    }
}
