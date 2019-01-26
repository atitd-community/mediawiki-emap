This is a MediaWiki extension that adds a `{{#tag:emap}}` tag that creates an interactive map of Egypt.

Installation
============

  * Download and place the files in a directory called `EMap` in your `extensions/` folder.
  * Add the following code at the bottom of your LocalSettings.php:

```php
wfLoadExtension( 'EMap' );
```

  * **Done** — Navigate to Special:Version on your wiki to verify that the extension is successfully installed.

Note
----

The extension is currently configured to pull the static images from `https://static.atitd.wiki/`. This URL is hardcoded into `modules/ext.emap.js` on `line 221` and may need to be changed depending on server settings.