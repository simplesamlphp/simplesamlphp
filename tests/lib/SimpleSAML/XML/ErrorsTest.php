<?php
/**
 * Tests for the SQL store.
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source
 * code.
 *
 * @author Sergio Gómez <sergio@uco.es>
 * @package simplesamlphp/simplesamlphp
 */


namespace SimpleSAML\Test\XML;

use SimpleSAML\XML\Errors;

class ErrorsTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers \SimpleSAML\XML\Errors::begin
     * @covers \SimpleSAML\XML\Errors::addErrors
     * @covers \SimpleSAML\XML\Errors::end
     * @test
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
     */
    public function formatErrors()
    {
        $error = new \LibXMLError();
        $error->level = 'level';
        $error->code = 'code';
        $error->line = 'line';
        $error->column = 'col';
        $error->message = ' msg ';

        $errors = Errors::formatErrors(array($error, $error));

        $this->assertEquals(
            "level=level,code=code,line=line,col=col,msg=msg\nlevel=level,code=code,line=line,col=col,msg=msg\n",
            $errors
        );
    }
}
