# Theming the user interface in SimpleSAMLphp

[TOC]

In SimpleSAMLphp every part that needs to interact with the user by using a web page, uses templates to present the HTML. SimpleSAMLphp comes with a default set of templates that presents an anonymous look.

You may create your own theme, where you add one or more template files that will override the default ones. This document explains how to achieve that.

## How themes work

If you want to customize the UI, the right way to do that is to create a new **theme**. A theme is a set of templates that can be configured to override the default templates. Themes are a special type of SimpleSAMLphp module.

### Configuring which theme to use

In `config.php` there is a configuration option that controls theming. You need to set that option and also add the module that contains the theme to the list of enabled modules. Here is an example:

```php
'module.enable' => [
    ...
    'mymodule' => true,
],

'theme.use' => 'mymodule:fancytheme',
```

The `theme.use` parameter points to which theme that will be used. If some functionality in SimpleSAMLphp needs to present UI in example with the `logout.twig` template, it will first look for `logout.twig` in the `theme.use` theme, and if not found it will all fallback to look for the base templates.

### Override only specific templates

The SimpleSAMLphp templates are derived from a base template and include other templates as building blocks. You only need to override the templates or building blocks needed for your change.
SimpleSAMLphp allows themes to override the included templates files only, if needed. That means you can create a new theme `fancytheme` that includes only a header and footer template. These templates may refer to your own CSS files, which means that a simple way of making a new look on SimpleSAMLphp is to create a new theme, and copy the existing header, but point to your own CSS instead of the default CSS. This means that for many theme requirements, you only need to specify a new header and footer template, and leave all other templates to SimpleSAMLphp's base versions.

## Creating your first theme

The first thing you need to do is having a SimpleSAMLphp module to place your theme in. If you do not have a module already, create a new one:

```bash
cd modules
mkdir mymodule
```

Then within this module, you can create a new theme named `fancytheme`.

```bash
cd modules/mymodule
mkdir -p themes/fancytheme/default/
```

Now, in `config.php`, add the module to the list of enabled modules, and configure SimpleSAMLphp to actually use your new theme:

```php
'module.enable' => [
    ...
    'mymodule' => true,
],
'theme.use' => 'mymodule:fancytheme',
```

Next, we copy the header file from the base theme:

```bash
cp templates/_header.twig modules/mymodule/themes/fancytheme/default/
```

In the `modules/mymodule/themes/fancytheme/default/_header.twig` file, type in something and go to the SimpleSAMLphp front page to see that your new theme is in use.

## Adding resource files

You can put resource files within the `public/assets` folder of your module, to make your module completely independent with included css, icons etc.

```bash
modules
└───mymodule
    └───src
    └───themes
    └───public
        └───assets
            └───logo.svg
            └───style.css
```

Reference these resources in your custom templates under `themes/fancytheme` by using a generator for the URL.
Example for a custom CSS stylesheet file:

```twig
{% block preload %}
<link rel="stylesheet" href="{{ asset('style.css', 'mymodule') }}">
{% endblock %}
```

## A custom theme controller

If you have very specific requirements for your theme, you can define a custom theme controller
in `config.php`:

```php
'theme.controller' => '\SimpleSAML\Module\mymodule\FancyThemeController',
```

This requires you to implement `\SimpleSAML\XHTML\TemplateControllerInterface.php` in your module's `src`-directory.
The class can then modify the Twig Environment and the variables passed to the theme's templates. In short, this allows you to set additional global variables and to write your own Twig filters and functions.

An example to put in `src/FancyThemeController.php`:

```php
<?php

namespace SimpleSAML\Module\mymodule;

use Twig\Environment;
use SimpleSAML\XHTML\TemplateControllerInterface;

class FancyThemeController implements TemplateControllerInterface
{
    /**
     * Modify the twig environment after its initialization (e.g. add filters or extensions).
     *
     * @param \Twig\Environment $twig The current twig environment.
     * @return void
     */
    public function setUpTwig(Environment &$twig): void
    {
    }

    /**
     * Add, delete or modify the data passed to the template.
     *
     * This method will be called right before displaying the template.
     *
     * @param array $data The current data used by the template.
     * @return void
     */
    public function display(array &$data): void
    {
        $data['extra_info'] = 'Extra information to use in your template';
    }
}
```

See the [Twig documentation](https://twig.symfony.com/doc/2.x/templates.html) for more information on using variables and expressions in Twig templates, and the SimpleSAMLphp wiki for [our conventions](https://github.com/simplesamlphp/simplesamlphp/wiki/Twig-conventions).

## Migrating to Twig templates

For existing themes that have been created before SimpleSAMLphp 2.0, you may need to upgrade them to the Twig
templating engine to be compatible with SimpleSAMLphp 2.0.

Twig works by extending a base template, which can itself include other partial templates. Some of the content of the old `includes/header.php` template is now located in a separate `_header.twig` file. This can be customized by copying it from the base template:

```bash
cp templates/_header.twig modules/mymodule/themes/fancytheme/default/
```

If you need to make more extensive customizations to the base template, you should copy it from the base theme:

```bash
cp templates/base.twig modules/mymodule/themes/fancytheme/default/
```

Any references to `$this->data['baseurlpath']` in old-style templates can be replaced with `{{baseurlpath}}` in Twig templates. Likewise, references to `\SimpleSAML\Module::getModuleURL()` can be replaced with `{{baseurlpath}}module.php/mymodule/...` or the `asset()` function like shown above.
If you want to use the `asset()` function, you need to move the asserts from `public/` to `public/assets/`.

Within templates each module is defined as a separate namespace matching the module name. This allows one template to reference templates from other modules using Twig's `@namespace_name/template_path` notation. For instance, a template in `mymodule` can include the widget template from the `yourmodule` module using the notation `@yourmodule/widget.twig`. A special namespace, `__parent__`, exists to allow theme developers to more easily extend a module's stock template.

The wiki also includes some information on [migrating translations](https://github.com/simplesamlphp/simplesamlphp/wiki/Migrating-translation-in-Twig) and [migrating templates](https://github.com/simplesamlphp/simplesamlphp/wiki/Twig:-Migrating-templates).
