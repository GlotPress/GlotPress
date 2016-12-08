#1.1.15
* Sanitize the values inside
* Support for plurals out-the-box

#1.1.14
* Disable the GlotPress official hotkeys

#1.1.13
* Fix legend

#1.1.12
* Fix: On Chrome in few cases the library for hotkeys is not loaded in the right order
* Fix: Improvement on the css selector for the blue legend
* Improvement: The glossary update in few cases was updated only with a refresh

#1.1.11
* Fix: final space on Copy from original
* Improvement: New RegExp to detect better the words
* Feature: Rows with GlotDict terms are now marked in the list

#1.1.10
* Fix: bugs on words inside ()
* Fix: other sanitizations
* Improvement: Now check if the content is different to avoid many DOM operations
* Improvement: Now validate the HTML to avoid broken strings
* Improvement: Now there is a file for tests the extension

#1.1.9
* Fix: bugs on  single words

#1.1.8
* Fix: bugs on multiple cases on french and german

#1.1.7
* Fix: Bug on multiple terms near each other

#1.1.6
* Fix: Bug on Germany fixed
* Fix: Bug for consistency link with language like Japan

#1.1.5
* Fix: The date on glossary update
* Add link to Consistency tool on terms
* On right click on the term with glossary is appended in the text in translation
* Text for new insttallation to look on the readme
* Improved Readme

#1.1.4 
* Improvement: Show the date of the last update
* Improvement: ctrl+alt+r reset all the GlotDict settings
* Fix: Problems with terms with parenthesis (happen in ES)
* Fix: For new user the alert system was not working

#1.1.3
* Improvement: The FB, Twitter and Google+ button are not loaded


#1.1.2
* Improvement: If there is only one string is opened automatically
* Fix: Remove the ' on comments to avoid problems
* Added: da_DK language in the code

#1.1.1
* Little fix to force the glossary download in few cases

#1.1.0
* Improvement: Auto update glossary system!
* Improvement: The FB, Twitter and Google+ button in the bottom of WP.org are removed to speed up the loading of the page
* Improvement: Added link to Home page about project to translate of the language choosen
* Improvement: Added link Translation Global Status
* Improvement: Added button to scroll to the language configured in Translation Global Status page
* Improvement: Better code naming, organizations, improvements
* Fixed: Hotkeys duplicated
* Removed: sk_SK because the glotpress glossary it is not complete

#1.0.9
* Fix: New internal system to detect shortcut
* Feature: Shortcut on Ctrl+Shift+B copy the original by ePascalC

#1.0.8
* Fix: Better regular expression to ignore nested terms
* Fix: Ignore browser hotkey shortcut
* Fix: Support multiple definitions for the same term
* Enhancement: Alert with hotkeys when the string is not visible
* Enhancement: New glossaries: ast
* Feature: Shortcut on Ctrl+Shift+F to replace space near symbols by ePascalC

#1.0.7
* Glossaries generated with GLotDictJSON now contain also the `pos` value
* Enhancement: New glossaries: tr_TR
* Feature: Shortcut on Ctrl+Shift+Z to click "Cancel"
* Feature: Shortcut on Ctrl+Shift+A to click "Approve"
* Feature: Shortcut on Ctrl+Shift+R to click "Reject"

#1.0.6
* Fix for terms detection near <>() symbols
* Fix alignment of toolbar for PTE
* Enhancement: New UI on first use
* Enhancement: No default language on first use
* Feature: Shortcut on Ctrl+Enter to click "Suggest new translation" or "Add translation"
* Feature: Shortcut on Page Down to open the previous string to translate
* Feature: Shortcut on Page Up to open the next string to translate
* Enhancement: New glossaries: he_IL, ro_RO, th, en_AU, en_CA
* New icon by CrowdedTent

#1.0.5
* Fix remember the language chosen now is working at 100%
* New glossaries: bg,ja,es,fi,hi,ja,lt,sk,sv

#1.0.4
* New glossaries for German, French, Dutch
* Updated Italian glossary
* Fix for missing translation
* Improved accessibility

#1.0.3
* Fix for PTE users on translate.wordpress.org

#1.0.2
* Fix for Firefox for the JSON load
* Force save of locale on dropdown
* Improvement on detection in case of different case letter of the terms

#1.0.1
* Few fixes

#1.0.0
* First Release