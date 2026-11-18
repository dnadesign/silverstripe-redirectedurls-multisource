# dnadesign/silverstripe-redirectedurls-multisource

Adds multi-source redirect support to `silverstripe/redirectedurls`.

## Features

- Adds additional source URL entries per redirect record
- Preserves source base and querystring pairing per entry
- Supports one URL per line in CMS for additional sources
- Resolves redirects through either core source or alias source

## Requirements

- PHP ^8.3
- Silverstripe CMS ^6
- silverstripe/redirectedurls ^4

## Installation

```bash
composer require dnadesign/silverstripe-redirectedurls-multisource
```

Run a dev/build after install:

```bash
vendor/bin/sake dev/build flush=1
```

## Usage

1. Open Redirects in CMS.
2. Edit a redirect.
3. Add extra source URLs in `Additional source URLs`.
4. Use one source URL per line. Each line can include querystring.

Example:

```
/old-path
/old-path?page=1
/legacy/article?id=22
```

## Ruling out matches

The service replaces the core `RedirectedURLService`, and like the core one it runs the
`filterBestRedirectedURLMatch` hook on every candidate. A redirect is skipped when any extension
returns `false`. This covers primary sources, additional sources and wildcards.

An additional source is checked as its redirect, so it follows the same rules.

The same hook decides whether an additional source clashes with a redirect being saved. An alias the
hook rules out is not counted as a clash.

```php
class RedirectRegionExtension extends Extension
{
    public function filterBestRedirectedURLMatch(): ?bool
    {
        return $this->getOwner()->Region === Region::current() ? null : false;
    }
}
```

## Subsites

`silverstripe/redirectedurls` ships `RedirectedURLSubsiteExtension`, which adds a `SubsiteID` to redirects
and implements the hook, so redirects only match on their own subsite.

The unique indexes on `RedirectedURL` and `RedirectSourceAlias` don't include the subsite, so two subsites
can't use the same source URL. To allow that, add `SubsiteID` to both indexes in your project. The alias
table has no `SubsiteID` of its own, so add one and copy it from the alias's redirect on write.

```yaml
SilverStripe\RedirectedURLs\Model\RedirectedURL:
  indexes:
    From:
      columns:
        - SubsiteID

DNADesign\RedirectedURLsMultiSource\Model\RedirectSourceAlias:
  has_one:
    Subsite: SilverStripe\Subsites\Model\Subsite
  indexes:
    UniqueFrom:
      columns:
        - SubsiteID
```

## Notes

- Newline separated input is preferred to avoid ambiguity with commas in URLs.
- The module keeps source URL base and querystring paired per line.
