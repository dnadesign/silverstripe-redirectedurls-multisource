<?php

declare(strict_types=1);

namespace DNADesign\RedirectedURLsMultiSource\Model;

use DNADesign\RedirectedURLsMultiSource\Support\FromURLParser;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\ReadonlyField;
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

        // Added from its redirect's Source aliases tab, which sets the redirect, so it's shown rather than picked
        $label = $this->getRedirectLabel();
        if ($label === '') {
            $fields->removeByName('RedirectedURLID');
        } else {
            $fields->replaceField('RedirectedURLID', ReadonlyField::create('RedirectLabel', 'Redirect', $label));
        }

        $queryField = $fields->dataFieldByName('FromQuerystring');
        if ($queryField !== null) {
            $queryField
                ->setTitle('From URL query parameters')
                ->setDescription('Enter query parameters only (e.g. page=1&num=5). Do not include a leading ?');
        }

        return $fields;
    }

    /**
     * The redirect this source belongs to, as its source URLs and where it goes, since a redirect has no title of its own
     */
    public function getRedirectLabel(): string
    {
        $redirect = $this->RedirectedURL();
        if (!$redirect->exists()) {
            return '';
        }

        $sources = array_filter([
            $redirect->FromBase ? FromURLParser::formatFromURL($redirect->FromBase, $redirect->FromQuerystring ?: null) : '',
            $redirect->getSourceAliasesSummary(),
        ]);

        $page = $redirect->LinkTo();
        $file = $redirect->LinkToAsset();
        $target = match ($redirect->RedirectionType) {
            'Internal' => $page->exists() ? $page->Title : '',
            'Asset' => $file->exists() ? $file->Name : '',
            default => $redirect->To,
        };

        return trim(implode(', ', $sources) . ' → ' . (string) $target);
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
