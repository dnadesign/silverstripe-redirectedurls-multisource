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

## Notes

- Newline separated input is preferred to avoid ambiguity with commas in URLs.
- The module keeps source URL base and querystring paired per line.
