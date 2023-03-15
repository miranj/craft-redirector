# Redirector

A [Craft CMS][craft] plugin for handling legacy paths and URLs by preventing 404s and redirecting to the new entry's URL.

[craft]: https://craftcms.com/

## Contents

- [Usage](#usage)
- [Installation](#installation)
- [Requirements](#requirements)
- [Changelog](./CHANGELOG.md)
- [License](./LICENSE)

## Usage

In order to handle legacy paths and URLs, we need to create a field to capture them.
We can make use of [Craft's built-in URL field][urlfield] for this purpose.

1.  Create a new field of type URL. Set the _Allowed URL Types_ to _Web Page_.
2.  Add this field to all sections where entries have URLs of their own.
3.  Create a config file for this plugin `config/redirector.php` and add the
    handle of the field created in the first step.

    ```php
    <?php

    return [
      'redirectField' => 'legacyUrl',
    ];
    ```

[urlfield]: https://craftcms.com/docs/4.x/url-fields.html

## Installation

You can install this plugin <!-- from the [Plugin Store][ps] or --> with Composer.

[ps]: https://plugins.craftcms.com/redirector

<!-- #### From the Plugin Store

Go to the Plugin Store in your project’s Control Panel and search for “Redirector”.
Then click on the “Install” button in its modal window.

#### Using Composer -->

Open your terminal and run the following commands:

    # go to the project directory
    cd /path/to/project

    # tell composer to use the plugin
    composer require miranj/craft-redirector

    # tell Craft to install the plugin
    ./craft install/plugin redirector

## Requirements

This plugin requires Craft CMS 3 or 4.

---

Brought to you by [Miranj](https://miranj.in/)
