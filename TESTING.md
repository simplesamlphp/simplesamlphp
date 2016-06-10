Testing
=======

All tests should be in "tests/". The directory structure there replicates the
main structure of the code. Each class Whatever is tested by a class named
WhateverTest.php, following the same path as the original.

The test classes (WhateverTest) need to extend PHPUnit_Framework_TestCase, and
inside you can implement how many methods you want. phpunit will only run the
ones prefixed with "test".

You'd usually use the $this->assertSomething() methods provided by
PHPUnit_Framework_TestCase, but you can also tell phpunit to expect an
exception to be thrown using phpdoc:

```
/**
    * Test SimpleSAML\Utils\HTTP::addURLParameters().
    *
    * @expectedException \InvalidArgumentException
*/
```

Run the tests locally by hiding the config-directory in the root (as the tests cannot use that) and running:

```
phpunit -c tools/phpunit/phpunit.xml
```

After you've pushed a change, Travis-CI will run your test in the CI platform.

Todo
----

The tests should use namespaces.
