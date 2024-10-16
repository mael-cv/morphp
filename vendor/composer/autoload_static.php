<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit7db9fe0780e2543b2e90b2000f48033f
{
    public static $prefixLengthsPsr4 = array (
        'M' => 
        array (
            'Maelcv\\Morpion\\' => 15,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Maelcv\\Morpion\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit7db9fe0780e2543b2e90b2000f48033f::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit7db9fe0780e2543b2e90b2000f48033f::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit7db9fe0780e2543b2e90b2000f48033f::$classMap;

        }, null, ClassLoader::class);
    }
}