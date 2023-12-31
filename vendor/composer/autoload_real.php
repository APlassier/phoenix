<?php

// autoload_real.php @generated by Composer

class ComposerAutoloaderInitddd8efe37fb6f8f691bb50334b3f9fe4
{
    private static $loader;

    public static function loadClassLoader($class)
    {
        if ('Composer\Autoload\ClassLoader' === $class) {
            require __DIR__ . '/ClassLoader.php';
        }
    }

    /**
     * @return \Composer\Autoload\ClassLoader
     */
    public static function getLoader()
    {
        if (null !== self::$loader) {
            return self::$loader;
        }

        require __DIR__ . '/platform_check.php';

        spl_autoload_register(array('ComposerAutoloaderInitddd8efe37fb6f8f691bb50334b3f9fe4', 'loadClassLoader'), true, true);
        self::$loader = $loader = new \Composer\Autoload\ClassLoader(\dirname(__DIR__));
        spl_autoload_unregister(array('ComposerAutoloaderInitddd8efe37fb6f8f691bb50334b3f9fe4', 'loadClassLoader'));

        require __DIR__ . '/autoload_static.php';
        call_user_func(\Composer\Autoload\ComposerStaticInitddd8efe37fb6f8f691bb50334b3f9fe4::getInitializer($loader));

        $loader->register(true);

        return $loader;
    }
}
