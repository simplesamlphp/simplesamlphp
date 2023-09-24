<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Auth\ProcessingFilter;

use Exception;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Auth\ProcessingFilter as AuthProcFilter;
use SimpleSAML\Module\core\Auth\Process\AttributeAlter;

/**
 * Test for the ProccessingFilter.
 *
 * @covers \SimpleSAML\Auth\ProcessingFilter
 */
class AttributeAlterTest extends TestCase
{
    /**
     * Helper function to run the filter with a given configuration.
     *
     * @param array $config  The filter configuration.
     * @param array $request  The request state.
     * @return \SimpleSAML\Auth\ProcessingFilter
     */
    private static function processFilter(array $config, array $request): AuthProcFilter
    {
        return new AttributeAlter($config, null);
    }


    /**
     * Test that a filter without precondition will run.
     */
    public function testWithoutPrecondition(): void
    {
        $config = [
            'subject' => 'test',
            'pattern' => '/wrong/',
            'replacement' => 'right',
        ];

        $request = [
            'Attributes' => [
                 'test' => ['somethingiswrong'],
             ],
        ];

        $filter = self::processFilter($config, $request);
        $this->assertTrue($filter->checkPrecondition($request));
    }


    /**
     * Test that a filter with a precondition evaluating to true will run.
     */
    public function testWithPreconditionTrue(): void
    {
        $config = [
            '%precondition' => 'return true;',
            'subject' => 'test',
            'pattern' => '/wrong/',
            'replacement' => 'right',
        ];

        $request = [
            'Attributes' => [
                 'test' => ['somethingiswrong'],
             ],
        ];

        $filter = self::processFilter($config, $request);
        $this->assertTrue($filter->checkPrecondition($request));
    }


    /**
     * Test that a filter with a precondition evaluating to false will not run.
     */
    public function testWithPreconditionFalse(): void
    {
        $config = [
            '%precondition' => 'return false;',
            'subject' => 'test',
            'pattern' => '/wrong/',
            'replacement' => 'right',
        ];

        $request = [
            'Attributes' => [
                 'test' => ['somethingiswrong'],
             ],
        ];

        $filter = self::processFilter($config, $request);
        $this->assertFalse($filter->checkPrecondition($request));
    }
}
