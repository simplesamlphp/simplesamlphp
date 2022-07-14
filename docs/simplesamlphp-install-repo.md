Installing SimpleSAMLphp from the repository
============================================

These are some notes about running SimpleSAMLphp from the repository.

Prerequisites
-------------

* NodeJS version >= 10.0.

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
cp -r config-templates/* config/
cp -r metadata-templates/* metadata/
```

Install the external dependencies with Composer (you can refer to
[getcomposer.org](https://getcomposer.org/) to get detailed
instructions on how to install Composer itself) and npm:

```bash
php composer.phar install
npm install
```

Build the assets:

```bash
npm run build
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

Install or upgrade the external dependencies with Composer and npm:

```bash
php composer.phar install
npm install
npm run build
```
