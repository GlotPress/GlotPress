<<<<<<< HEAD
![GlotPress](.github/banner.png)

[![Build Status](https://travis-ci.org/GlotPress/GlotPress-WP.svg?branch=develop)](https://travis-ci.org/GlotPress/GlotPress-WP) [![codecov.io](https://codecov.io/github/GlotPress/GlotPress-WP/coverage.svg?branch=develop)](https://codecov.io/github/GlotPress/GlotPress-WP?branch=develop) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/GlotPress/GlotPress-WP/badges/quality-score.png?b=develop)](https://scrutinizer-ci.com/g/GlotPress/GlotPress-WP/?branch=develop)

# GlotPress

GlotPress is a WordPress plugin to let you set up your own collaborative, web-based software translation tool.

## Contributing

Many open source projects span regions, countries and dialects and need to support a variety of translations, GlotPress is here to help you collaborate online with your translators to ensure your users see your software in their native language.

GlotPress has two versions, a standalone version and this WordPress plugin version.  At this time these two versions are functionally similar, but the plugin version is likely to start moving away from the standalone version in future versions.  For the rest of this document, any reference to "GlotPress" should be taken as the plugin.

For more information about GlotPress, feel free to visit the channels listed below in the "Communication" section.

So who should use GlotPress?

Any developer of software that uses [gettext](http://www.gnu.org/software/gettext/), like WordPress theme or plugin authors.  But that's just the start, anyone who uses a gettext-based system can use GlotPress to help their translators collaborate.

This plugin wouldn't be possible without all the hard work that has gone in to the standalone version of GlotPress and we'd like to thank all those who contribute to it.

## Installation

Search for "GlotPress" in the WordPress.org plugin directory and install it.

After activating the plugin, GlotPress can be accessed via `<home_url>/glotpress/`

## Manual Installation
```bash
$ cd /your/wordpress/folder/wp-content/plugins/
$ git clone git@github.com:GlotPress/GlotPress-WP.git glotpress
```

After activating the plugin, GlotPress can be accessed via `<home_url>/glotpress/`


## Communication

* [GitHub Home](https://github.com/GlotPress/GlotPress-WP)
* [Blog](https://glotpress.blog)
* [WordPress Slack](https://chat.wordpress.org/): #glotpress (for development only)

## More Info

More information can be found on the [GlotPress Wiki](https://github.com/GlotPress/GlotPress-WP/wiki/) or [the manual](https://glotpress.blog/the-manual/).
=======
# GlotDict
[![License](https://img.shields.io/badge/License-GPL%20v2-blue.svg)](https://img.shields.io/badge/License-GPL%20v2-blue.svg) [![Codacy Badge](https://api.codacy.com/project/badge/Grade/e9107b200511490a961560efcf7c5d1c)](https://www.codacy.com/app/mte90net/GlotDict?utm_source=github.com&amp;utm_medium=referral&amp;utm_content=Mte90/GlotDict&amp;utm_campaign=Badge_Grade)  

[https://translate.wordpress.org](https://translate.wordpress.org) enable to the users to translate plugin, themes and WordPress itself.  
This extension implements many new feature to improve the quality of translation and speed up that process!

PS: If you are using NoScript or Privacy Badger enable the domain wordpress.org else the extension will not work!.

# Features

* Daily update of the list of locales
* Remove the loading of social buttons on the website footer (only for Firefox)
* Click on the terms with glossary open the consistency tool
* Add link to the Translation Status Overview with a button to scroll to the language in use
* More warning on translations
  * Validation for final "...", ".", ":"
  * Validation for final ;.!:、。؟？！
  * First character is not uppercase on translation
  * Missing term translated using the locale glossary
* Review mode with a button
* New column with fast Approve/Reject/Fuzzy for strings
* Bulk Actions also on footer
* Mark old string (6 months) with a black border
* Highlight non-breaking-space
* Many hotkeys and shortcut

## Hotkeys

* Shortcut on Ctrl+Shift+Z to click "Cancel"
* Shortcut on Ctrl+Shift+A to click "Approve"
* Shortcut on Ctrl+Shift+R to click "Reject"
* Shortcut on Ctrl+Shift+F to click "Fuzzy"
* Shortcut on Ctrl+Enter to click "Suggest new translation" or "Add translation"
* Shortcut on Page Down to open the previous string to translate
* Shortcut on Page Up to open the next string to translate
* Shortcut on Ctrl+Shift+B to "Copy from original"
* Shortcut on Ctrl+Shift+F to add non-breaking spaces near symbols
* Shortcut on Ctrl+Alt+R to reset all the GlotDict settings
* Right click of the mouse on the term with a dashed line and the translation will be added in the translation area

# Download

* Firefox [Instruction](https://support.mozilla.org/en-US/kb/find-and-install-add-ons-add-features-to-firefox): [Download](https://addons.mozilla.org/it/firefox/addon/glotdict/)
* Chrome [Instructions](https://support.google.com/chrome_webstore/answer/2664769?hl=en): [Download](https://chrome.google.com/webstore/detail/glotdict/jfdkihdmokdigeobcmnjmgigcgckljgl)

# Update times and release

When the developer of the extension think that a new release is ready and tested they create a new release and publish on Firefox and Chrome addons store.  
After that step we have to wait few hours for Chrome and for Firefox and all the installations will be updated automatically.

# Contributors

* [Daniele Scasciafratte](https://github.com/Mte90) - The developer [Donate](https://www.paypal.me/mte90)
* [Olegs Belousovs](https://github.com/sgelob) - The ideator
* Pascal Casier - For the help with the glossaries and hotkeys
* Garrett Hyder - For all the tickets
>>>>>>> 6dd8789dd8beec71ed492afec00aed9077ecfb0a
