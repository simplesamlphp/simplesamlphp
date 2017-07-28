# Contribution guidelines

**SimpleSAMLphp welcomes all contributions**. It is impossible to make a product like this without the efforts of many
people, so please don't be shy and share your help with us. Even the tiniest contribution can make a difference!

This guidelines briefly explain how to contribute to SimpleSAMLphp in an effective manner, making sure to keep high
quality standards and making it easier for your contributions to make through.

<!-- {{TOC}} -->

## Team members

Currently, the core team members are:

* Jaime Pérez Crespo, *main developer and release manager*, UNINETT <jaime.perez@uninett.no>
* Olav Morken, *main developer*, UNINETT <olav.morken@uninett.no>
* Andreas Åkre Solberg, *architect and original developer*, UNINETT <andreas.solberg@uninett.no>
* Hanne Moa, *developer*, UNINETT <hanne.moa@uninett.no>

We've been lucky to have the help of many people through the years. SimpleSAMLphp wouldn't have reached so far without
them, and we want to thank them from here. Unfortunately, they are so many it is nearly impossible to mention all of
them. [Github can offer a good summary on who has contributed to the project](https://github.com/simplesamlphp/simplesamlphp/graphs/contributors?from=2007-09-09&to=2015-09-06&type=c).
Big thanks to you all!

## First things first

Before embarking yourself in a contribution, please make sure you are familiar with the way SimpleSAMLphp is written,
the way it works, and what is required or not.

* Make sure to read [the documentation](https://simplesamlphp.org/docs/stable/). If you use the search engine in the
website, please verify that you are reading the latest stable version. If you want to develop something, check [the
development branch of the documentation](https://simplesamlphp.org/docs/development/).
* If you have a question about **using SimpleSAMLphp**, please use [the mailing list](http://groups.google.com/group/simplesamlphp).
* If you have a question about **developing SimpleSAMLphp**, please ask in the [development mailing list](http://groups.google.com/group/simplesamlphp-dev).
* If you think you have discovered a bug, please check the [issue tracker](https://github.com/simplesamlphp/simplesamlphp/issues)
and the [pull requests](https://github.com/simplesamlphp/simplesamlphp/pulls) to verify it hasn't been reported before.

## Contributing code

New features are always welcome provided they will be useful to someone apart from yourself. Please take a look at the
[list of issues](https://github.com/simplesamlphp/simplesamlphp/issues) to see what people is demanding. Our
[roadmap](https://simplesamlphp.org/releaseplan) might also be a good place to start if you don't know exactly what
you can contribute with.

When contributing your code, please make sure to:

* Respect the coding standards. We try to comply with PHP's [PSR-2](http://www.php-fig.org/psr/psr-2/). Pay special
attention to:
   * Lines should not be longer than 80 characters.
   * Use **4 spaces** instead of tabs.
   * Keep the keywords in **lowercase**, including `true`, `false` and `null`.
   * Make sure your classes work with *autoloading*.
   * Never include a trailing `?>` in your files.
   * The first line of every file must be `<?php`.
   * Use namespaces if you are adding new files.
* Do not include many changes in every commit. Commits should be focused and address one single problem or feature. By
having **multiple, small commits** instead of few large ones, it is easier to track what you are doing, revert changes
in case of an error and help you out if needed.
* Try to write clean **commit messages**, largely based on the [seven rules](http://chris.beams.io/posts/git-commit/):
   * Write a **short** subject line, followed by a blank line and a longer explanation.
   * Prefix the subject line with the component(s) changed, e.g. "docs: Update foo", or "SAML: Don't log bar twice",
     or "tests: Add tests for quux".
   * Explain **what and why** in the commit message, not just _how_. Things obvious now might become quite confusing
     when rediscovered years later.
* **Be explicit**. Add comments. Use strict comparison operators like `===` and check for specific values when testing
conditions.
* **Keep things simple**. Avoid big functions, long nested loops or `if` statements.
* Include complete **phpdoc** documentation for every property and method you add. If you change a method or property,
make sure to update the existing *phpdoc* accordingly. Do not forget to document all parameters, returned values and
exceptions thrown.
* Try to keep **backwards-compatibility**. Code that breaks current configurations and installations is difficult to
deploy, and therefore we try to avoid it.
* Add **unit tests** to verify that your code not only works but also keeps working over time. When adding tests, keep
the same directory structure used for regular classes. Try to cover **all your code** with tests. The bigger the test
coverage, the more reliable and better our library is. Read our [guidelines](TESTING.md) to learn more about tests.
* Add proper **documentation** explaining your how to use your new feature or how your code changes things.
* Submit your code as a **pull request** in github, from a branch with a descriptive name in your own fork of the
repository. Add a meaningful, short title, and explain in detail what you did and why in the description of the *PR*.
Add instructions on how to test your code. We appreciate branch names like `feature/whatever-new-feature` for new
features and `bug/something-not-working` for bug fixes, but this is not carved in stone.

Sometimes it can take a long time until we are able to process your pull requests. Don't get discouraged, we'll
eventually reach up to you. And remember that following this guidelines you are making it easier for us to analyze your
request so the process will be smoother and faster. We really appreciate you helping us out, not only with your code,
but also by following this guidelines.

## Reporting bugs

Before reporting a bug, please make sure it is indeed a bug. Check [the documentation](https://simplesamlphp.org/docs/stable/)
to verify what the intended behaviour is. Review the [list of issues](https://github.com/simplesamlphp/simplesamlphp/issues)
and the [pull requests](https://github.com/simplesamlphp/simplesamlphp/pulls) to see if someone has already reported the
same issue.

Pull requests are definitely more appreciated than plain issue reports, as they are easier and faster to address, but
please, do not hesitate to open an issue if you don't have coding skills or just can't find the bug. It's better to have
just an issue report than nothing!

You can help us diagnose and fix bugs by asking and providing answers to the following questions:

* How can we reproduce the bug?
* Is it reproducible in other environments (for example, on different browsers or devices)?
* Are the steps to reproduce the bug clear? If not, can you describe how you might reproduce it?
* What tags should the bug have?
* How critical is this bug? Does it impact a large amount of users?
* Is this a security issue? If so, how severe is it? How can an attacker exploit it? Read more about security issues in
the next section.

## Reporting vulnerabilities

In case you find a vulnerability in SimpleSAMLphp, or you want to confirm a possible security issue in the software, please
get in touch with us through [UNINETT's CERT team](https://www.uninett.no/cert). Please use our PGP public key to encrypt
any possible sensitive data that you may need to submit. We will get back to you as soon as possible according to our
working hours in Central European Time.

When reporting a security issue, please add as much information as possible to help us identify, confirm, replicate and
fix the problem. In particular, remember to include the following information in your report:

* The version or versions of SimpleSAMLphp affected.
* An exact version that can be used to replicate the issue.
* Any module or modules involved in the issue.
* Any particular configuration details relevant to the setup affected.
* A detailed description and a clear and concise, step-by-step guide to allow us reproduce the issue.
* Screenshots, videos, or any other media that would help identify the issue.
* Pointers to the exact line or lines in the code where the vulnerability is supposed to be.
* Context on how you discovered the issue.
* Your own name and whether you want to be credited for the discovery or not.

Please **DO NOT** report security incidents related to systems that use SimpleSAMLphp, where this software is not the
cause of the incident. Issues related to the use (or misuse) of infrastructure, misconfiguration of the software,
malfunction of a particular system or user-related errors should not be reported either. If you are using SimpleSAMLphp
to authenticate or login to services, but you don't know what SimpleSAMLphp is or you are not sure about the nature of
the issue, please contact the organization running the service for you.

Finally, be reasonable. We'll do our best to resolve the issue according to our principles of security and transparency.
Every confirmed vulnerability will be published and resolved in a timely manner. All we ask in return is that you
contact us privately first in order to avoid any potential damage to those using the software.

You can find the list of security advisories we have published [here](https://simplesamlphp.org/security).

## Translations

SimpleSAMLphp is translated to many languages, though it needs constant updates from translators, as well as new
translations to other languages. For the moment, translations can be contributed as **pull requests**. We are looking
at better ways to translate the software that would make your life easier, so stay tuned! You can also join the
[translators mailing list](http://groups.google.com/group/simplesamlphp-translation) to keep up to date on the
latest news.

Before starting a new translation, decide what style you want to use, whether you want to address the user in a polite
manner or not, etc. Be coherent and keep that style through all your translations. If there is already a translation and
you want to complete it, make sure to keep the same style used by your fellow translators.

## Documentation

Did you find a typo in the documentation? Does something make no sense? Did we use very poor english? Tell us!

Documentation is included in our own repository in *markdown* format. You can submit pull requests with fixes. If you
encounter some feature that's not documented, or the documentation does not reflect the real behaviour of the library,
please do not hesitate to open an issue.

Good documentation is key to make things easier for our users!

## Community

You don't feel capable of contributing with your code, but are using SimpleSAMLphp and can share your knowledge and
experience? Please, do so! Join our [users mailing list](http://groups.google.com/group/simplesamlphp) and help other
users when you can. Your experience might be valuable for many!
