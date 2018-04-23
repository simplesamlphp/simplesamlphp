SimpleSAMLphp Installation and Configuration
============================================

<!-- 
	This file is written in Markdown syntax. 
	For more information about how to use the Markdown syntax, read here:
	http://daringfireball.net/projects/markdown/syntax
-->



<!-- {{TOC}} -->

SimpleSAMLphp news and documentation
------------------------------------

This document is part of the SimpleSAMLphp documentation suite.

 * [List of all SimpleSAMLphp documentation](https://simplesamlphp.org/docs)
 * [SimpleSAMLphp homepage](https://simplesamlphp.org)


Development version
--------------------

This document is about the latest stable version of SimpleSAMLphp.
If you want to install the development version, look at the instructions for [installing SimpleSAMLphp from the repository](simplesamlphp-install-repo).


Prerequisites
-------------

 * Some webserver capable of executing PHP scripts.
 * PHP version >= 5.4.0.
 * Support for the following PHP extensions:
   * Always required: `date`, `dom`, `hash`, `libxml`, `openssl`, `pcre`, `SPL`, `zlib`, `json`, `mbstring`
   * When automatically checking for latest versions, and used by some modules: `cURL`
   * When authenticating against LDAP server: `ldap`
   * When authenticating against RADIUS server: `radius`
   * When using native PHP session handler: `session`
   * When saving session information to a memcache server: `memcache`
   * When using databases:
     * Always: `PDO`
     * Database driver: (`mysql`, `pgsql`, ...)
 * Support for the following PHP packages:
   * When saving session information to a Redis server: `predis`

What actual packages are required for the various extensions varies between different platforms and distributions.


Download and install SimpleSAMLphp
----------------------------------

The most recent release of SimpleSAMLphp is found at [https://simplesamlphp.org/download](https://simplesamlphp.org/download).

Go to the directory where you want to install SimpleSAMLphp, and extract the archive file you just downloaded:

    cd /var
    tar xzf simplesamlphp-1.x.y.tar.gz
    mv simplesamlphp-1.x.y simplesamlphp

## Upgrading from a previous version of SimpleSAMLphp

Extract the new version:

    cd /var
    tar xzf simplesamlphp-1.x.y.tar.gz

Copy the configuration files from the previous version (in case the configuration directory is inside SimpleSAMLphp,
keep reading for other alternatives):

    cd /var/simplesamlphp-1.x.y
    rm -rf config metadata
    cp -rv ../simplesamlphp/config config
    cp -rv ../simplesamlphp/metadata metadata

Replace the old version with the new version:

    cd /var
    mv simplesamlphp simplesamlphp.old
    mv simplesamlphp-1.x.y simplesamlphp


If the format of the config files or metadata has changed from your previous version of SimpleSAMLphp (check the revision log), you may have to update your configuration and metadata after updating the SimpleSAMLphp code:


### Upgrading configuration files

A good approach is to run a `diff` between your previous `config.php` file and the new `config.php` file located in `config-templates/config.php`, and apply relevant modifications to the new template.
This will ensure that all new entries in the latest version of config.php are included, as well as preserve your local modifications.


### Upgrading metadata files

Most likely the metadata format is backwards compatible. If not, you should receive a very clear error message at startup indicating how and what you need to update. You should look through the metadata in the metadata-templates directory after the upgrade to see whether recommended defaults have been changed.

### Alternative location for configuration files

By default, SimpleSAMLphp looks for its configuration in the `config` directory in the root of its own directory. This
has some drawbacks, like making it harder to use SimpleSAMLphp as a composer dependency, or to package it for different
operating systems.

However, it is now possible to specify an alternate location for the configuration directory by setting an environment
variable with this location. This way, the configuration directory doesn't need to be inside the library's directory,
making it easier to manage and to update. The simplest way to set this environment variable is to set it in your web
server's configuration. See the next section for more information.


Configuring Apache
------------------

Examples below assume that SimpleSAMLphp is installed in the default location, `/var/simplesamlphp`. You may choose another location, but this requires a path update in a few files. See Appendix for details ‹Installing SimpleSAMLphp in alternative locations›.

The only subdirectory of `SimpleSAMLphp` that needs to be accessible from the web is `www`. There are several ways of exposing SimpleSAMLphp depending on the way web sites are structured on your Apache web server. The following is just one possible configuration.

Find the Apache configuration file for the virtual hosts where you want to run SimpleSAMLphp. The configuration may look like this:

    <VirtualHost *>
            ServerName service.example.com
            DocumentRoot /var/www/service.example.com

            SetEnv SIMPLESAMLPHP_CONFIG_DIR /var/simplesamlphp/config

            Alias /simplesaml /var/simplesamlphp/www

            <Directory /var/simplesamlphp/www>
                <IfModule !mod_authz_core.c>
                # For Apache 2.2:
                Order allow,deny
                Allow from all
                </IfModule>
                <IfModule mod_authz_core.c>
                # For Apache 2.4:
                Require all granted
                </IfModule>
            </Directory>
    </VirtualHost>

Note the `Alias` directive, which gives control to SimpleSAMLphp for all urls matching `http(s)://service.example.com/simplesaml/*`. SimpleSAMLphp makes several SAML interfaces available on the web; all of them are included in the `www` subdirectory of your SimpleSAMLphp installation. You can name the alias whatever you want, but the name must be specified in the `config.php` file of SimpleSAMLphp as described in [the section called “SimpleSAMLphp configuration: config.php”](#sect.config "SimpleSAMLphp configuration: config.php"). Here is an example of how this configuration may look like in `config.php`:

    $config = array (
    [...]
            'baseurlpath'                   => 'simplesaml/',

Note also the `SetEnv` directive. It sets the `SIMPLESAMLPHP_CONFIG_DIR` environment variable, in this case, to the
default location for the configuration directory. You can omit this environment variable, and SimpleSAMLphp will
then look for the `config` directory inside its own directory. If you need to move your configuration to a different
location, you can use this environment variable to tell SimpleSAMLphp where to look for configuration files.
This works only for the `config` directory. If you need your metadata to be in a different directory too, use the
`metadatadir` configuration option to specify the location.

This is just the basic configuration to get things working. For a checklist
further completing your documentation, please see
[Maintenance and configuration: Apache](simplesamlphp-maintenance#section_4).


SimpleSAMLphp configuration: config.php
---------------------------------------

There is a few steps that you should edit in the main configuration
file, `config.php`, right away:

-  Set a administrator password. This is needed to access some of the pages in your SimpleSAMLphp installation web interface.

		'auth.adminpassword'        => 'setnewpasswordhere',

   Hashed passwords can also be used here. See the [`authcrypt`](./authcrypt:authcrypt) documentation for more information.

-  Set a secret salt. This should be a random string. Some parts of the SimpleSAMLphp needs this salt to generate cryptographically secure hashes. SimpleSAMLphp will give an error if the salt is not changed from the default value. The command below can help you to generated a random string on (some) unix systems:

		tr -c -d '0123456789abcdefghijklmnopqrstuvwxyz' </dev/urandom | dd bs=32 count=1 2>/dev/null;echo

    Here is an example of the config option:

		'secretsalt' => 'randombytesinsertedhere',

-  
    Set technical contact information. This information will be
    available in the generated metadata. The e-mail address will also
    be used for receiving error reports sent automatically by
    SimpleSAMLphp. Here is an example:

		'technicalcontact_name'     => 'John Smith',
		'technicalcontact_email'    => 'john.smith@example.com',

-  
    If you use SimpleSAMLphp in a country where English is not
    widespread, you may want to change the default language from
    English to something else:

		'language.default'      => 'no',

-  
    Set the timezone which you use:

        'timezone' => 'Europe/Oslo',

    * [List of Supported Timezones at php.net](http://php.net/manual/en/timezones.php)


Configuring PHP
---------------

### Sending e-mails from PHP

Some parts of SimpleSAMLphp will allow you to send e-mails. In example sending error reports to technical admin, as well as sending in metadata to the federation administrators. If you want to make use of this functionality, you should make sure your PHP installation is configured to be able to send e-mails. It's a common problem that PHP is not configured to send e-mails properly. The configuration differs from system to system. On UNIX, PHP is using sendmail, on Windows SMTP.


Enable modules
--------------

If you want to enable some of the modules that are installed with SimpleSAMLphp, but are disabled by default, you should create an empty file in the module directory named `enable`.

    # Enabling the consent module
    cd modules
    ls -l
    cd consent
    touch enable

If you later want to disable the module, rename the `enable` file
to `disable`.

    cd modules/consent
    mv enable disable



The SimpleSAMLphp installation webpage
--------------------------------------

After installing SimpleSAMLphp, you can access the homepage of your installation, which contains some information and a few links to the test services. The URL of an installation can be e.g.:

	https://service.example.org/simplesaml/

The exact link depends on how you set it up with Apache, and of course on your hostname.

### Warning

Don't click on any of the links yet, because they require you to
either have setup SimpleSAMLphp as an Service Provider or as an
Identity Provider.

Here is an example screenshot of what the SimpleSAMLphp page looks
like:

![Screenshot of the SimpleSAMLphp installation page.](resources/simplesamlphp-install/screenshot-installationpage.png)

### Check your PHP environment

At the bottom of the installation page are some green lights. simpleSAML runs some tests to see whether required and recommended prerequisites are met. If any of the lights are red, you may have to add some extensions or modules to PHP, e.g. you need the PHP LDAP extension to use the LDAP authentication module.

## Next steps

You have now successfully installed SimpleSAMLphp, and the next steps depends on whether you want to setup a service provider, to protect a website by authentication or if you want to setup an identity provider and connect it to a user catalog. Documentation on bridging between federation protocols is found in a separate document.

 * [Using SimpleSAMLphp as a SAML Service Provider](simplesamlphp-sp)
  * [Hosted SP Configuration Reference](./saml:sp)
  * [IdP remote reference](simplesamlphp-reference-idp-remote)
  * [Connecting SimpleSAMLphp as a SP to UK Access Federation or InCommon](simplesamlphp-ukaccess)
  * [Upgrading - migration to use the SAML authentication source](simplesamlphp-sp-migration)
 * [Identity Provider QuickStart](simplesamlphp-idp)
  * [IdP hosted reference](simplesamlphp-reference-idp-hosted)
  * [SP remote reference](simplesamlphp-reference-sp-remote)
  * [Use case: Setting up an IdP for G Suite (Google Apps)](simplesamlphp-googleapps)
  * [Identity Provider Advanced Topics](simplesamlphp-idp-more)
 * [Automated Metadata Management](simplesamlphp-automated_metadata)
 * [Maintenance and configuration](simplesamlphp-maintenance)


Support
-------

If you need help to make this work, or want to discuss SimpleSAMLphp with other users of the software, you are fortunate: Around SimpleSAMLphp there is a great Open source community, and you are welcome to join! The forums are open for you to ask questions, contribute answers other further questions, request improvements or contribute with code or plugins of your own.

-  [SimpleSAMLphp homepage](https://simplesamlphp.org)
-  [List of all available SimpleSAMLphp documentation](https://simplesamlphp.org/docs/)
-  [Join the SimpleSAMLphp user's mailing list](https://simplesamlphp.org/lists)




Installing SimpleSAMLphp in alternative locations
-------------------------------------------------

There may be several reasons why you want to install SimpleSAMLphp
in an alternative way.

1.	You are installing SimpleSAMLphp in a hosted environment where you
	do not have root access, and cannot change Apache configuration.
	Still you can install SimpleSAMLphp - keep on reading.

2.	You have full permissions to the server, but cannot edit Apache
	configuration for some reason, politics, policy or whatever.


The SimpleSAMLphp code contains one folder named `simplesamlphp`. In this folder there are a lot of subfolders for library, metadata, configuration and much more. One of these folders is named `www`. This and *only this* folder should be exposed on the web. The recommended configuration is to put the whole `simplesamlphp` folder outside the webroot, and then link in the `www` folder by using the `Alias` directive, as described in [the section called “Configuring Apache”](#sect.apacheconfig "Configuring Apache"). But this is not the only possible way.

As an example, let's see how you can install SimpleSAMLphp in your home directory on a shared hosting server.

Extract the SimpleSAMLphp archive in your home directory:

    cd ~
    tar xzf simplesamlphp-1.x.y.tar.gz
    mv simplesamlphp-1.x.y simplesamlphp

Then you can try to make a symlink into the `public\_html` directory.

    cd ~/public_html
    ln -s ../simplesamlphp/www simplesaml

Next, you need to update the configuration of paths in `simplesamlphp/config/config.php`:

And, then we need to set the `baseurlpath` parameter to match the base path of the URLs to the content of your `www` folder:

    'baseurlpath' => '/simplesaml/',

Now, you can go to the URL of your installation and check if things work:

    http://yourcompany.com/simplesaml/


### Tip

Symlinking may fail, because some Apache configurations do not allow you to link in files from outside the public\_html folder. If so, move the folder instead of symlinking:

    cd ~/public_html
    mv ../simplesamlphp/www simplesaml

Now you have the following directory structure.

-   `~/simplesamlphp`

-  
    `~/public_html/simplesaml` where `simplesaml` is the `www`
    directory from the `simplesamlphp` installation directory, either
    moved or a symlink.


Now, we need to make a few configuration changes. First, let's edit
`~/public_html/simplesaml/_include.php`:

Change the two lines from:

    require_once(dirname(dirname(__FILE__)) . '/lib/_autoload.php');

to something like:

    require_once('/var/www/simplesamlphp/lib/_autoload.php');

And then at the end of the file, you need to change another line
from:

    $configdir = dirname(dirname(__FILE__)) . '/config';

to:

    $configdir = '/var/www/simplesamlphp/config';



### Note

In a future version of SimpleSAMLphp we'll make this a bit easier, and let you only change the path one place, instead of three as described above.
