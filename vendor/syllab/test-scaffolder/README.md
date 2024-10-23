# test-scaffolder

A php functional test scaffolder for webdriver and phpunit using maker.

## Installation

Installation is possible using Composer.

If you don't already use Composer, you can download the `composer.phar` binary:

```bash
    curl -sS https://getcomposer.org/installer | php
```

Then install the library adding the following entries in your `composer.json` :

```json
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/labasse/test-scaffolder"
        }
    ],
    "require-dev": {
        "syllab/test-scaffolder": "dev-main"
    }
```

Then run the following command:

```bash
    php composer.phar install
    php composer.phar dump-autoload
```

## Usage

Here are the commands available:

- `php vendor/bin/console.php webtest:init`: Initialize a new test project

You can get this list by running `php vendor/bin/console.php list` in your terminal or get help on each command using `php vendor/bin/console.php (command) --help`.
