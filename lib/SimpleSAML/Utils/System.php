<?php


/**
 * System-related utility classes.
 *
 * @package SimpleSAMLphp
 */
class SimpleSAML_Utils_System
{

    const WINDOWS = 1;
    const LINUX = 2;
    const OSX = 3;
    const HPUX = 4;
    const UNIX = 5;
    const BSD = 6;
    const IRIX = 7;
    const SUNOS = 8;


    /**
     * This function returns the Operating System we are running on.
     *
     * @return mixed A predefined constant identifying the OS we are running on. False if we are unable to determine it.
     *
     * @author Jaime Perez, UNINETT AS <jaime.perez@uninett.no>
     */
    public static function getOS()
    {
        if (stristr(PHP_OS, 'LINUX')) {
            return self::LINUX;
        }
        if (stristr(PHP_OS, 'WIN')) {
            return self::WINDOWS;
        }
        if (stristr(PHP_OS, 'DARWIN')) {
            return self::OSX;
        }
        if (stristr(PHP_OS, 'BSD')) {
            return self::BSD;
        }
        if (stristr(PHP_OS, 'UNIX')) {
            return self::UNIX;
        }
        if (stristr(PHP_OS, 'HP-UX')) {
            return self::HPUX;
        }
        if (stristr(PHP_OS, 'IRIX')) {
            return self::IRIX;
        }
        if (stristr(PHP_OS, 'SUNOS')) {
            return self::SUNOS;
        }
        return false;
    }

    /**
     * This function retrieves the path to a directory where temporary files can be saved.
     *
     * @return string Path to a temporary directory, without a trailing directory separator.
     * @throws SimpleSAML_Error_Exception If the temporary directory cannot be created or it exists and does not belong
     * to the current user.
     */
    public static function getTempDir()
    {
        $globalConfig = SimpleSAML_Configuration::getInstance();

        $tempDir = rtrim($globalConfig->getString('tempdir', sys_get_temp_dir().DIRECTORY_SEPARATOR.'simplesaml'),
            DIRECTORY_SEPARATOR);

        if (!is_dir($tempDir)) {
            if (!mkdir($tempDir, 0700, true)) {
                throw new SimpleSAML_Error_Exception('Error creating temporary directory "'.$tempDir.
                    '": '.SimpleSAML_Utilities::getLastError());
            }
        } elseif (function_exists('posix_getuid')) {
            // check that the owner of the temp directory is the current user
            $stat = lstat($tempDir);
            if ($stat['uid'] !== posix_getuid()) {
                throw new SimpleSAML_Error_Exception('Temporary directory "'.$tempDir.
                    '" does not belong to the current user.');
            }
        }

        return $tempDir;
    }
}