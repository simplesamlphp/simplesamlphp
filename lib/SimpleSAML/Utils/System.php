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
}