# SimpleSAMLphp developer information

<!-- 
	This file is written in Markdown syntax. 
	For more information about how to use the Markdown syntax, read here:
	http://daringfireball.net/projects/markdown/syntax
-->

[TOC]

This document is intended to provide an overview of the code for developers.
It should be readable to new developers and developers who contribute as
time allows and may have forgotten some details over time.

## Overview

This is a living document and various sections and additions are being made
as my knowledge of the SSP code and environment expands. Hopefully this can help
others find their way around the code a bit quicker.

The `master` branch is where active development of the next release is
taking place. If you would like to contribute an update to and
existing release please checkout the branch for that release, for
example to make a contribution to the v2.1.1 release you would
checkout the [simplesamlphp-2.1
branch](https://github.com/simplesamlphp/simplesamlphp/tree/simplesamlphp-2.1).

## Libraries that SimpleSAMLphp uses and planned changes

Many dependencies are listed in the require clause of the composer.json such as Symfony, Twig, and simplesamlphp/saml2.

As at early 2024 there is a plan to move from using robrichards/xmlseclibs to using the newly written [xml-security
library](https://github.com/simplesamlphp/xml-security). The integration of xml-security started in the v5 of the saml2 library.
The saml2 library is already a dependency of SimpleSAMLphp and is brought in with composer as a [dependency here.](https://github.com/simplesamlphp/simplesamlphp/blob/15019f97eb1b4041859582b8b6f39fe432b603af/composer.json#L66C21-L66C29).

## Build process

There are two main release targets for each release: slim and full.
This is done by the
[build-release.yml](https://github.com/simplesamlphp/simplesamlphp/blob/master/.github/workflows/build-release.yml)
script. The full version also contains some [simplesamlphp
modules](https://github.com/simplesamlphp/simplesamlphp/blob/master/.github/build/full.json).
The build will also include some files in the vendor directory in the
full build that are not in the slim build.

If the SimpleSAMLphp code relies on other repositories on the [simplesamlphp](https://github.com/simplesamlphp) site then
they are brought in using composer as shown for example for the [saml2 library](https://github.com/simplesamlphp/simplesamlphp/blob/435ce92874a789101495504cd6c4da600fb01334/composer.json#L73).

## Code checks

The github actions perform some linting and checks on the php code.
The linting is done with super-linter and the php checks with phpcs.
You can run the phpcs checks locally by executing `phpcs` in the root
of the git repository. If you would like your simpler issues to be
solved for you execute `phpcbf` which will update the code to remedy
as many issues as it can.

## CSS and common asset setup

The common assets such as CSS in SimpleSAMLphp, for example, that
stored in public/assets/base/css/stylesheet.css comes from the
[simplesamlphp-assets-base](https://github.com/simplesamlphp/simplesamlphp-assets-base)
package.

The dependencies are updated using github actions in
simplesamlphp-assets-base. Select a recent branch such as release-2.2
and dig into the .github directory for details.

## Following a simple login

The `SimpleSAML\Auth\Simple` class takes the authentication_source
name and can be used to find a login URL with `getLoginURL()`. The
getLoginURL method takes the return URL as it's only parameter. The
URL returned from `getLoginURL()` will be a request to module.php and
include the return URL information.

The module.php code `Module::process`. The `process` method uses
Symfony to dispatch the request. This may result in a call to
modules/core/src/Controller/Login.php in `Login::loginuserpass()`. The
code flows through `handleLogin()` which may call
`UserPassBase::handleLogin(authstateid, username, password)`. The
`handleLogin` method looks up the `$as = Auth\Source` and passes the
login credentials to the `$as->login( username, password )` method.

For an SQL based login this would be in
modules/sqlauth/src/Auth/Source/SQL.php and the `SQL::login` method.
This `login` method either returns the user attributes on success or
throws an exception on login failure.

## Documentation

The core of the simplesamlphp.org website is taken from
[simplesamlphp.github.io](https://github.com/simplesamlphp/simplesamlphp.github.io).
The [docs subdirectory](https://simplesamlphp.org/docs/) is built from
the [docs
subdirectory](https://github.com/simplesamlphp/simplesamlphp/tree/master/docs)
of the main repository on github.

### Documentation updates

There are two main repositories for documentation. The website itself
comes from one place and everything that is under the "Documentation"
menu uses another process
<https://simplesamlphp.org/docs/stable/index.html>.

The website lives in <https://github.com/simplesamlphp/simplesamlphp.github.io>

That only has a "release" branch to commit to, which is the website as
it is shown. There you'd commit to change the pages on the website,
e.g. to the page /contrib/

The "docs" repo (as described in the readme of the repo) only contains
the scripts that generate the docs website. In order to improve the
content of the documentation themselves, you commit using the same branches used
for code contributions at
<https://github.com/simplesamlphp/simplesamlphp>.

You can address documentation updates to master
<https://github.com/simplesamlphp/simplesamlphp/tree/master/docs>.
Though it makes sense to backport them to supported releases, so each
version under <https://simplesamlphp.org/docs/VERSION/> will show the
change. In other words, if a documentation change relates to 2.1.3 you
might like to make the pull request against the simplesamlphp-2.1
branch and leave it to the team to also apply it to master and other
branches in the same way that code updates work.

Some docs offered under the `docs` directory on the web site come from modules.
For example the [saml module](https://simplesamlphp.org/docs/2.3/saml/sp.html)
file comes from the file `./modules/saml/docs/sp.md` in the git repository.

### Documentation linting

The CI system has some linting for markdown files in place. This uses
`github-action-markdown-cli` to perform the work which itself uses
`markdownlint-cli`. You can install the latter with npm install and
then setup the rc file from github-action-markdown-cli from
<https://github.com/nosborn/github-action-markdown-cli/blob/master/.markdownlintrc>
to run the linter locally.

For example, with the markdownlintrc from above in a local file you
could use the following if you have not globally installed
markdownlint. The `--fix` option will have markdownlint fix simpler
issues for you so you can concentrate on the document and not the fine
details of the syntax formatting.

```bash
npm install markdownlint-cli
cd ./node_modules/.bin
# copy the markdown lint file to markdownlintrc
./markdownlint -c markdownlintrc /tmp/simplesamlphp-developer-information.md
./markdownlint -c markdownlintrc --fix /tmp/simplesamlphp-developer-information.md
```

You will probably want to make a script or alias to the above command
and apply it before pushing documentation changes to github.

## Making a release

The release process is documented on the wiki
<https://github.com/simplesamlphp/simplesamlphp/wiki/Release-process>.

## Dependbot

The dependbot
<https://docs.github.com/en/code-security/dependabot/dependabot-security-updates/configuring-dependabot-security-updates> runs on the master branch
and creates pull requests with recommended updates.

The release branches are updated automatically as part of the release process.
