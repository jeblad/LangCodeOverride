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
git clone git@github.com:jeblad/LangCodeOverride.git
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

Make sure the `$wgDBname` has an entry in the sites table.

A Vagrant box will have a `$wgDBname` of `wiki`, so create a site entry

```bash
php maintenance/addSite.php wiki wiki
```
