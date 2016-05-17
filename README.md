# GlotDict

[https://translate.wordpress.org](https://translate.wordpress.org) enable to the users to translate plugin, themes and WordPress itself.  
The problem is that every community have their dictionaries to maintain consistency of the terms.
The goal of this browser extension is add that dictionaries when the user is translating.

# Hotkeys

* Shortcut on Ctrl+Shift+Z to click "Cancel"
* Shortcut on Ctrl+Shift+A to click "Approve"
* Shortcut on Ctrl+Shift+R to click "Reject"
* Shortcut on Ctrl+Enter to click "Suggest new translation" or "Add translation"
* Shortcut on Page Down to open the previous string to translate
* Shortcut on Page Up to open the next string to translate
* Shortcut on Ctrl+Shift+B to "Copy from original"
* Shortcut on Ctrl+Shift+F to add non-breaking spaces near symbols (for fr_FR)

# Download

* Firefox [Instruction](https://support.mozilla.org/en-US/kb/find-and-install-add-ons-add-features-to-firefox): [Download](https://addons.mozilla.org/it/firefox/addon/glotdict/)
* Chrome [Instructions](https://support.google.com/chrome_webstore/answer/2664769?hl=en): [Download](https://chrome.google.com/webstore/detail/glotdict/jfdkihdmokdigeobcmnjmgigcgckljgl)

## New Glossaries
With the script on [https://github.com/Mte90/GlotDictJSON](https://github.com/Mte90/GlotDictJSON) you can have an updated of all the glossaries.

# Update times and release

When the developer of the extension think that a new release is ready and tested they create a new release and publish on Firefox and Chrome addons store.  
After that step we have to wait 1 day for Chrome and 2 or 3 for Firefox and all the installation will be updated automatically.

# Add a new glossary for a non-developer

* Create/Sign in into github account if you don't have one
* Create an issues with the information about the lang code  
* Next someone add that file and the support in the extension
* Choose a glossary from `dictionaries` folder - https://github.com/Mte90/GlotDict/tree/master/dictionaries
* Tap the pencil icon - near the history button - at the top of ther file
* Replace the content with your valid JSON (with identation!)
* Now you need to create a new Pull Request, go to https://github.com/Mte90/GlotDict/pulls
* Click on New Pull Request
* Click on Compare across forks
* On Head fork search your fork (YourNickOnGitHub/GlotDict/)
* Click on create Pull Request
* Done!

# Contributors

* [Olegs Belousovs](https://github.com/sgelob) - The ideator
* [Daniele Scasciafratte](https://github.com/Mte90) - The developer
* Pascal Casier - For the help with the glossaries
