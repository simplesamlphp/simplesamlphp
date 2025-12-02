# SimpleSAMLphp modules

<!-- 
	This file is written in Markdown syntax. 
	For more information about how to use the Markdown syntax, read here:
	http://daringfireball.net/projects/markdown/syntax
-->

[TOC]

This document describes how the module system in SimpleSAMLphp
works. It describes what types of modules there are, how they are
configured, and how to write new modules.

## Overview

There are currently three parts of SimpleSAMLphp which can be stored in
modules - authentication sources, authentication processing filters and
themes. There is also support for defining hooks - functions run at
specific times. More than one thing can be stored in a single module.
There is also support for storing supporting files, such as templates
and dictionaries, in modules.

The different functionalities which can be created as modules will be
described in more detail in the following sections; what follows is a
short introduction to what you can do with them:

- Authentication sources implement different methods for
  authenticating users, for example simple login forms which
  authenticate against a database backend, or login methods which use
  client-side certificates.
- Authentication processing filters perform various tasks after the
  user is authenticated and has a set of attributes. They can add,
  remove and modify attributes, do additional authentication checks,
  ask questions of the user, +++.
- Themes allow you to package custom templates for multiple modules
  into a single module.

## Module layout

Each SimpleSAMLphp module is stored in a directory under the
`modules`-directory. The module directory contains the following
directories and files:

locales
:   This directory contains dictionaries which belong to this
    module. To use a dictionary stored in a module, the extended tag
    names can be used:
    `{<module name>:<dictionary name>:<tag name>}` For
    example, `{example:login:hello}` will look up `hello` in
    `modules/example/locales/<lang>/login.po`.

:   It is also possible to specify
    `<module name>:<dictionary name>` as the default
    dictionary when instantiating the `\SimpleSAML\XHTML\Template`
    class.

hooks
:   This directory contains hook functions for this module. Each
    file in this directory represents a single function. See the
    hook-section in the documentation for more information.

src
:   This directory contains classes which belong to this module.
    All classes must be named in the following pattern:
    `\SimpleSAML\Module\<module name>\<class name>` When looking up the filename of
    a class, SimpleSAMLphp will search for `<class name>` in the `src`
    directory. Underscores in the class name will be translated into
    slashes.

:   Thus, if SimpleSAMLphp needs to load a class named
    `\SimpleSAML\Module\example\Auth\Source\Example`, it will load the file named
    `modules/example/src/Auth/Source/Example.php`.

templates
:   These are module-specific templates. To use one of these
    templates, specify `<module name>:<template file>.twig`
    as the template file in the constructor of
    `\SimpleSAML\XHTML\Template`. For example, `example:login-form.twig`
    is translated to the file
    `modules/example/templates/default/login-form.twig`. Note that
    `default` in the previous example is defined by the `theme.use`
    configuration option.

themes
:   This directory contains themes the module defines. A single
    module can define multiple themes, and these themes may override
    all templates in all modules. Each subdirectory of `themes` defines
    a theme. The theme directory contains a subdirectory for each
    module. The templates stored under `simplesamlphp/templates` can be
    overridden by a directory named `default`.

:   To use a theme provided by a module, the `theme.use`
    configuration option should be set to
    `<module name>:<theme name>`.

:   When using the theme `example:blue`, the template
    `templates/default/login.twig` will be overridden by
    `modules/example/themes/blue/default/login.twig`, while the template
    `modules/core/templates/default/loginuserpass.twig` will be
    overridden by
    `modules/example/themes/blue/core/loginuserpass.twig`.

public
:   All files stored in this directory will be available by
    accessing the URL
    `https://.../simplesamlphp/module.php/<module name>/<file name>`.
    For example, if a script named `login.php` is stored in
    `modules/example/public/`, it can be accessed by the URL
    `https://.../simplesamlphp/module.php/example/login.php`.

:   To retrieve this URL, the
    `SimpleSAML\Module::getModuleURL($resource)`-function can be used.
    This function takes in a resource on the form `<module>/<file>`.
    This function will then return a URL to the given file in the
    `public`-directory of `module`.

## Authentication sources

An authentication source is used to authenticate a user and receive a
set of attributes belonging to this user. In a single-signon setup, the
authentication source will only be called once, and the attributes
belonging to the user will be cached until the user logs out.

Authentication sources are defined in `config/authsources.php`. This
file contains an array of `name => configuration` pairs. The name is
used to refer to the authentication source in metadata. When
configuring an IdP to authenticate against an authentication source,
\the `auth` option should be set to this name. The configuration for an
authentication source is an array. The first element in the array
identifies the class which implements the authentication source. The
remaining elements in the array are configuration entries for the
authentication source.

A typical configuration entry for an authentication source looks like
this:

    'example-static' => [
        /* This maps to modules/exampleauth/src/Auth/Source/Static.php */
        'exampleauth:StaticSource',
    
        /* The following is configuration which is passed on to
         * the exampleauth:StaticSource authentication source. */
        'uid' => 'testuser',
        'eduPersonAffiliation' => ['member', 'employee'],
        'cn' => ['Test User'],
    ],

To use this authentication source in a SAML 2.0 IdP, set the
`auth`-option of the IdP to `'example-static'`:

    'https://example.org/saml-idp' => [
        'host' => '__DEFAULT__',
        'privatekey' => 'example.org.pem',
        'certificate' => 'example.org.crt',
        'auth' => 'example-static',
    ],

### Creating authentication sources

This is described in a separate document:

- [Creating authentication sources](simplesamlphp-authsource)

## Authentication processing filters

*Authentication processing filters* is explained in a separate document:

- [Authentication processing filters](simplesamlphp-authproc)

## Themes

This feature allows you to collect all your custom templates in one
place. The directory structure is like this:
`modules/<thememodule>/themes/<theme>/<module>/<template>`
`thememodule` is the module where you store your theme, while `theme`
is the name of the theme. A theme is activated by setting the
`theme.use` configuration option to `<thememodule>:<theme>`. `module`
is the module the template belongs to, and `template` is the template
in that module.

For example, `modules/example/themes/test/core/loginuserpass.php`
replaces `modules/core/templates/default/loginuserpass.php`.
`modules/example/themes/test/default/frontpage.php` replaces
`templates/default/frontpage.php`. This theme can be activated by
setting `theme.use` to `example:test`.

## Event listeners (new Hooks)

In SimpleSAMLphp 2.5 the hook interface was updated to use PSR-14
events [PSR-14 events](https://www.php-fig.org/psr/psr-14) as well as
the legacy custom "hook" interface. When hooks are called in 2.5 both
the new PSR-14 event listeners are called followed by the older custom
hook. You are likely better to create new event listeners in new
configurations and might like to consider moving your hooks to event
listeners over time.

If you are using an older version of SimpleSAMLphp please see the
below section for information on how to use the older interface.

This section discusses how to make new event listeners with an
eye toward moving an existing hook to become an event listener.

The PSR events that can be used are defined in your module's
src/Event subdirectory. For example, for the admin module you can
see modules/admin/src/Event/ConfigPageEvent.php which defines
a class ConfigPageEvent in the `SimpleSAML\Module\admin\Event` namespace.

That event takes some state in the constructor and likely has one or
more access methods to later retrieve that state.

The event might be listened to by the same or another module by
defining a `Listener` class. In the running concrete example the class
in modules/cron/src/Event/Listener/ConfigPageListener.php defines a
`ConfigPageListener` class in the
`SimpleSAML\Module\cron\Event\Listener` namespace. That listener class
has an `__invoke` method that will be passed the `ConfigPageEvent`
object and has no return value.

The `ConfigPageEvent` is a reasonable starting point for testing your
own listener. If you create a new listener for ConfigPageEvent you can
use `Logger::error` to check that you get called and use the
admin/config page through the admin module to trigger this event
fairly easily. You might like to base your new listener on the
ConfigPageListener for the cron module.

To update a hook from a 2.4 release or below you will need to move
your hook class from the `hooks` directory to the `src/Event/Listener`
subdirectory. Considering the config page example again, the
`hooks/hook_configpage.php` file should move to `src/Event/Listener`.
That hook defined a single top level function with a specific funciton
name `cron_hook_configpage`. In the event listener the new
`src/Event/Listener/ConfigPageListener.php` file will need to be in
the `SimpleSAML\Module\cron\Event\Listener` namespace and define a
class `ConfigPageListener`. The single top level function is moved
into a member function `__invoke` taking a single parameter
`ConfigPageEvent`.

The old hook function took a reference `Template &$template` directly
as the single argument. The new PSR class takes a `ConfigPageEvent`
object. The ConfigPageEvent object provides access to the template
using the `getTemplate` method on the event.

The old hook funciton in `hooks` might look like the following.

    // old hook interface
    function cron_hook_configpage(Template &$template): void
    {
      $template->data['links'][] = [
          'href' => Module::getModuleURL('cron/info'),
          'text' => Translate::noop('Cron module information page'),
      ];
      ...
    }

The new Listener version in `src/Event/Listener` is shown below.

```php
class ConfigPageListener
{
    public function __invoke(ConfigPageEvent $event): void
    {
        $template = $event->getTemplate();
        
        $template->data['links'][] = [
            'href' => \SimpleSAML\Module::getModuleURL('cron/info'),
            'text' => \SimpleSAML\Locale\Translate::noop('Cron module information page'),
        ];        
        ...
    }
}
```

That `ConfigPageEvent` class is defined in
`modules/admin/src/Event/ConfigPageEvent.php`.

```php
class ConfigPageEvent
{
    public function __construct(
        private readonly XHTML\Template $template,
    )
    {}

    public function getTemplate(): XHTML\Template
    {
        return $this->template;
    }
}
```

In summary to move a hook to the new PSR-14 events you have to move
the hook file to your module's src/Event/Listener directory and have
it take a new Event object that passes the function argument to it.
The event will be a simple class defined in your `src/Event`
directory.

When you wish to call event listeners in your code you can use code
like the following. The event dispatcher is passed an event which
contains all of the state you wish to make available to the event
listeners. You can then get the state which may have been updated by
one or more listeners using a getter method and do something with the
result.

```php
$eventDispatcher = ModuleEventDispatcherFactory::getInstance();
$event = $eventDispatcher->dispatch(new ConfigPageEvent($t));
$t = $event->getTemplate();
```

## Hook interface (SimpleSAMLphp 2.4 and below)

Releases 2.4 and below use a custom hook interface to allow code to
run when specific things of interest are happening. If you are using
SimpleSAMLphp 2.5 or above please see the above section on Event listeners instead.

The hook interface allows you to call a hook function in all enabled
modules which define that hook. Hook functions are stored in a
directory called 'hooks' in each module directory. Each hook is
stored in a file named `hook_<hook name>.php`, and each file defines a
function named `<module name>_hook_<hook name>`.

Each hook function accepts a single argument. This argument will be
passed by reference, which allows each hook to update that argument.

For an example of hook usage, see the cron module, which adds a link
to its information page in the Configuration section of the admin
module, through the file `modules/cron/hooks/hook_configpage.php`.
