Testing
=======

Testing your code is crucial to have a stable and good quality product.
We are therefore slowly increasing the amount of tests we perform, and
as a rule of thumb **all new code should have associated tests**. If you
want to contribute to the project with a pull request, make sure to
**include tests covering your code**. We won't accept pull requests
without tests or getting the code coverage down, except in very specific
situations.

All the tests reside in the `tests` directory. The directory structure
there replicates the main structure of the code. Each class is tested by
a class named with the same name and `Test` appended, having the same
directory structure as the original, but inside the `tests` directory.
We also use namespaces, with `SimpleSAML\Test` as the root for standard
classes, and `SimpleSAML\Test\Module\modulename` for classes located in
modules.

For example, if you want to test the `SimpleSAML\Utils\HTTP` class
located in `lib/SimpleSAML/Utils/HTTP.php`, the tests must be in a class
named `HTTPTest` implemented in
`tests/lib/SimpleSAML/Utils/HTTPTest.php`, with the following namespace
definition:

```php
namespace SimpleSAML\Test\Utils;
```

The test classes need to extend `PHPUnit_Framework_TestCase`, and inside
you can implement as many methods as you want. `phpunit` will only run
the ones prefixed with "test".

You will usually make use of the `assert*()` methods provided by
`PHPUnit_Framework_TestCase`, but you can also tell `phpunit` to expect
an exception to be thrown using *phpdoc*. For example, if you want to 
ensure that the `SimpleSAML\Utils\HTTP::addURLParameters()` method
throws an exception in a specific situation:

```php
  /**
    * Test SimpleSAML\Utils\HTTP::addURLParameters().
    *
    * @expectedException \InvalidArgumentException
    */
  public function testAddURLParametersInvalidParameters() {
```

Refer to [the `phpunit 4.8` documentation](https://phpunit.de/manual/4.8/en/installation.html)
for more information on how to write tests. We currently use the `phpunit 4.8` 
since it is the last version to support php 5.3.

Once you have implemented your tests, you can run them locally. First,
make sure the `config` directory is **not** in the root of your
SimpleSAMLphp installation, as the tests cannot use that. Make sure
you have `phpunit` installed and run:

```sh
phpunit -c tools/phpunit/phpunit.xml
```

If your default version of `phpunit` is more recent than 4.8, you can run
the old version installed by composer

```sh
./vendor/bin/phpunit -c tools/phpunit/phpunit.xml
```

All the tests are run by our *continuous integration* platform, 
[travis](https://travis-ci.org/simplesamlphp/simplesamlphp). If you are
submitting a pull request, Travis will run your tests and notify whether
your code builds or not according to them.
