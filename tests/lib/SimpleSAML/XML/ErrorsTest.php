<?php

namespace SimpleSAML\Test\XML;

use PHPUnit\Framework\TestCase;
use SimpleSAML\XML\Errors;

/**
 * Tests for the SQL store.
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source
 * code.
 *
 * @author Sergio Gómez <sergio@uco.es>
 * @package simplesamlphp/simplesamlphp
 */
class ErrorsTest extends TestCase
{
    /**
     * @covers \SimpleSAML\XML\Errors::begin
     * @covers \SimpleSAML\XML\Errors::addErrors
     * @covers \SimpleSAML\XML\Errors::end
     * @test
     * @return void
     */
    public function loggingErrors()
    {
        Errors::begin();
        $xmlstr = "<Test>Test</test>";
        simplexml_load_string($xmlstr);
        $errors = Errors::end();
        $errors = Errors::formatErrors($errors);

        $this->assertEquals(
            "level=3,code=76,line=1,col=18,msg=Opening and ending tag mismatch: Test line 1 and test\n",
            $errors
        );
    }


    /**
     * @covers \SimpleSAML\XML\Errors::formatError
     * @covers \SimpleSAML\XML\Errors::formatErrors
     * @test
     * @return void
     */
    public function formatErrors()
    {
        $error = new \LibXMLError();
        $error->level = 3;
        $error->code = 76;
        $error->line = 1;
        $error->column = 18;
        $error->message = ' msg ';

        $errors = Errors::formatErrors([$error, $error]);

        $this->assertEquals(
            "level=3,code=76,line=1,col=18,msg=msg\nlevel=3,code=76,line=1,col=18,msg=msg\n",
            $errors
        );
    }
}
