<?php

declare(strict_types=1);

namespace SimpleSAML\Error;

use Error as BuiltinError;
use Exception as BuiltinException;
use SimpleSAML\Module;
use Throwable;

use function class_exists;

/**
 * SimpleSAMLphp's custom exception handler.
 *
 * @package SimpleSAMLphp
 */
class ExceptionHandler
{
   /**
    * @param \Throwable $exception
    * @return void
    */
    public function customExceptionHandler(Throwable $exception): void
    {
        Module::callHooks('exception_handler', $exception);

        if ($exception instanceof Error) {
            $exception->show();
        } elseif ($exception instanceof BuiltinException) {
            $e = new Error('UNHANDLEDEXCEPTION', $exception);
            $e->show();
        } elseif (class_exists('Error') && $exception instanceof BuiltinError) {
            $e = new Error('UNHANDLEDEXCEPTION', $exception);
            $e->show();
        }
    }
}
