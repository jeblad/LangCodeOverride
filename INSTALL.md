# Install

The following should be sufficient to get a working Vagrant box with MediaWiki and the LangCodeOverride extension.

Run the following commands

```bash
mkdir lco-test # or some other dir of choice
cd lco-test
git clone --recursive https://gerrit.wikimedia.org/r/mediawiki/vagrant .
./setup.sh
vagrant up
cd mediawiki/extensions
git clone https://github.com/jeblad/LangCodeOverride.git
```

Add a line to `LocalSettings.php` for loading of the extension

```php
wfLoadExtension('LangCodeOverride');
```

Add the following optional lines to `LocalSettings.php`

```php
$wgGroupPermissions['sysop']['interwiki'] = true;
$wgDebugComments = true;
```

Populate the interwiki table by reusing the data from [English Wikipedia](https://en.wikipedia.org). The script is part of the [Extension:WikimediaMaintenance](https://www.mediawiki.org/wiki/Extension:WikimediaMaintenance).

```bash
vagrant ssh
php maintenance/populateInterwiki.php --source https://en.wikipedia.org/w/api.php
```

Populate the sites table by reusing the data from [Meta Wikimedia](https://meta.wikimedia.org/). The script is part of the [Extension:WikimediaSites](https://www.mediawiki.org/wiki/Extension:WikimediaSites).

```bash
vagrant ssh
php maintenance/populateSites.php --force-protocol https
```
