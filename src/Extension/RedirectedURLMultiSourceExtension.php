<?php

declare(strict_types=1);

namespace DNADesign\RedirectedURLsMultiSource\Extension;

use DNADesign\RedirectedURLsMultiSource\Model\RedirectSourceAlias;
use DNADesign\RedirectedURLsMultiSource\Support\FromURLParser;
use SilverStripe\Core\Extension;
use SilverStripe\Core\Validation\ValidationResult;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RelationEditor;
use SilverStripe\Forms\TextareaField;
use SilverStripe\RedirectedURLs\Model\RedirectedURL;

/**
 * @extends Extension<(RedirectedURL&static)>
 */
class RedirectedURLMultiSourceExtension extends Extension
{
    private const SOURCE_ALIASES_PREVIEW_MAX_LENGTH = 120;

    private static array $db = [
        'RedirectComment' => 'Text',
    ];

    private static array $has_many = [
        'SourceAliases' => RedirectSourceAlias::class,
    ];

    protected function updateCMSFields(FieldList $fields): void
    {
        $fields->removeByName('FromBase');
        $fields->removeByName('FromQuerystring');

        $aliasesField = GridField::create(
            'SourceAliases',
            'Additional source URLs',
            $this->owner->SourceAliases(),
            GridFieldConfig_RelationEditor::create(),
        );

        $aliasesField->setDescription('Manage additional source URLs as individual rows.');

        $fields->addFieldToTab('Root.SourceAliases', $aliasesField);

        $fields->addFieldToTab(
            'Root.Main',
            TextareaField::create('RedirectComment', 'Redirect comment')
                ->setDescription('Optional notes to explain the purpose of this redirect.')
                ->setRows(4),
        );
    }

    public function updateValidate(ValidationResult $result): void
    {
        $primaryBase = FromURLParser::normaliseBase((string) $this->owner->FromBase);
        $primaryQuery = FromURLParser::normaliseQuerystring($this->owner->FromQuerystring) ?? '';

        if ($primaryBase === '') {
            return;
        }

        $aliasQueryFilter = $primaryQuery === '' ? ['', null] : $primaryQuery;

        $existingAlias = RedirectSourceAlias::get()
            ->filter([
                'FromBase' => $primaryBase,
                'FromQuerystring' => $aliasQueryFilter,
            ])
            ->exclude(['RedirectedURLID' => (int) $this->owner->ID])
            ->first();

        if (!$existingAlias || !$existingAlias->exists()) {
            return;
        }

        $result->addError(sprintf(
            'Primary source URL already exists as an additional source on another redirect: %s',
            FromURLParser::formatFromURL($primaryBase, $primaryQuery === '' ? null : $primaryQuery),
        ));
    }

    public function updateSummaryFields(array &$fields): void
    {
        $orderedFields = [
            'SourceAliasesSummary' => 'Source aliases',
        ];

        foreach ($fields as $name => $label) {
            if (in_array($name, ['FromBase', 'FromQuerystring', 'SourceAliasesSummary'], true)) {
                continue;
            }

            $orderedFields[$name] = $label;
        }

        $fields = $orderedFields;
    }

    public function getSourceAliasesSummary(): string
    {
        $aliases = [];
        foreach ($this->owner->SourceAliases()->sort('ID ASC') as $alias) {
            $aliases[] = $alias->getFrom();
        }

        if ($aliases === []) {
            return '';
        }

        $summary = implode(', ', $aliases);
        if (strlen($summary) <= self::SOURCE_ALIASES_PREVIEW_MAX_LENGTH) {
            return $summary;
        }

        $truncated = substr($summary, 0, self::SOURCE_ALIASES_PREVIEW_MAX_LENGTH - 3);
        $lastSeparatorPosition = strrpos($truncated, ', ');

        if ($lastSeparatorPosition !== false) {
            $truncated = substr($truncated, 0, $lastSeparatorPosition);
        }

        return rtrim($truncated, ', ') . '...';
    }
}
