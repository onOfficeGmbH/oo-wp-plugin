# onOffice plugin for WordPress
![Unit tests](https://github.com/onOfficeGmbH/oo-wp-plugin/workflows/Unit%20tests/badge.svg?branch=master)

Integrate real estates, contact forms and contact persons from the onOffice Software into your WordPress website.

## Installation

### For Development

* Clone this repository recursively: `git clone --recursive https://github.com/onOfficeGmbH/oo-wp-plugin.git`.
* Install the development dependencies: `composer install`.

### Building a Release

The included Makefile can be used to generate a release. This strips down configuration files for several development tools, as well as unit tests.
Composer is still required.

```
git clone --recursive https://github.com/onOfficeGmbH/oo-wp-plugin.git
PREFIX=/tmp make release
```

This will generate the directory /tmp/onoffice with the plugin data. If you need a zip file for testing, execute `make test-zip`.

## Getting Started

1. Move the plugin directory into a new subdirectory inside the WordPress plugins directory (`wp-content/plugins/`)
2. Create a new plugin folder called `onoffice-personalized` or create a new folder inside your theme called `onoffice-theme`.
3. Copy the folder `templates.dist` to `onoffice-personalized/templates` or `onoffice-theme/templates`. This is where the newly created individual templates will go.
4. Login into your WordPress page as an administrator and go to the plugins list by navigating to `Plugins` » `Installed Plugins`. You should be able to see and activate the onOffice for WP-Websites plugin. If no API token or secret have been saved so far, a notification will show up at the top. Clicking the link will bring you to the appropriate configuration page.
5. Start editing inside the new `onoffice-personalized` or `onoffice-theme` folder.

**IMPORTANT**: Although it is safe to disable the plugin, DELETING IT WILL WIPE ALL PLUGIN-RELATED DATA FROM THE DATABASE. WE DO NOT PROVIDE ANY WARRANTY FOR DATA LOSS!

### Getting API Access

Request your own [onOffice trial version](https://onoffice.com/)

Contact us by phone (+49 241 446860) or email (support@onoffice.com) for questions concerning onOffice enterprise edition.
Please fill out this [form](https://wpplugindoc.onoffice.de/support-request/?lang=en) in regards to questions about our API or plugin development workflow.

Proceed to the next step once you have got an API token and secret.

### Configuration Basics

In comparison to other real estate WordPress-plugins, this one does not use any file transfer via FTP but from the onOffice API.
This also means, you need to enter your API credentials before configuring anything else.

#### A First Example: Creating a New Estate List
In order to create a new estate list, go to "onOffice" » "Estates" and press "Add New". **Give the new list a name**, pick the desired settings and click "Save Changes" at the bottom of the page. Going back to the estate list overview will show you the shortcode (i.e. `[oo_estate view="my new view"]`. Paste this into a new page and open the preview. The new list of estates should be embedded.

An extensive documentation can be found at [wpplugindoc.onoffice.de](https://wpplugindoc.onoffice.de/?lang=en).

## License

This project is licensed under both GNU AGPLv3 and GNU GPLv3:
 - the plugin itself is licensed under GNU AGPLv3. See LICENSE-agpl-3.0.txt.
 - config files and default templates are licensed under GNU GPLv3. See LICENSE-gpl.txt.


## Contact

onOffice GmbH\
Charlottenburger Allee 5\
52068 Aachen\
Germany

[support@onoffice.com](mailto://support@onoffice.com)\
+49 241 446860
