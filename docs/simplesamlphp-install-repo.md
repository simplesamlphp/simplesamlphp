Installing SimpleSAMLphp from the repository
============================================

These are some notes about running SimpleSAMLphp from the repository.

Installing from git
-------------------

Go to the directory where you want to install SimpleSAMLphp:

```bash
cd /var
```

Then do a git clone:

```bash
git clone git@github.com:simplesamlphp/simplesamlphp.git simplesamlphp
```

Initialize configuration and metadata:

```bash
cd /var/simplesamlphp
cp config/config.php.dist config/config.php
cp config/authsources.php.dist config/authsources.php
cp metadata/saml20-idp-hosted.php.dist metadata/saml20-idp-hosted.php
cp metadata/saml20-idp-remote.php.dist metadata/saml20-idp-remote.php
cp metadata/saml20-sp-remote.php.dist metadata/saml20-sp-remote.php
```

Install the external dependencies with Composer (you can refer to
[getcomposer.org](https://getcomposer.org/) to get detailed
instructions on how to install Composer itself):

```bash
php composer.phar install
```

Upgrading
---------

Go to the root directory of your SimpleSAMLphp installation:

```bash
cd /var/simplesamlphp
```

Ask git to update to the latest version:

```bash
git fetch origin
git pull origin master
```

Install or upgrade the external dependencies with Composer:

```bash
php composer.phar install
```
