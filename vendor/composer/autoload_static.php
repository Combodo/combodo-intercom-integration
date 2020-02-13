<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitfe626b7edf4c50f84f22dfa0d3a90ed8
{
    public static $prefixLengthsPsr4 = array (
        'C' => 
        array (
            'Combodo\\iTop\\Extension\\IntercomIntegration\\' => 43,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Combodo\\iTop\\Extension\\IntercomIntegration\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src',
        ),
    );

    public static $classMap = array (
        'Combodo\\iTop\\Extension\\IntercomIntegration\\Extension\\ConsoleUIExtension' => __DIR__ . '/../..' . '/src/Hook/ConsoleUIExtension.php',
        'Combodo\\iTop\\Extension\\IntercomIntegration\\Extension\\PortalUIExtension' => __DIR__ . '/../..' . '/src/Hook/PortalUIExtension.php',
        'Combodo\\iTop\\Extension\\IntercomIntegration\\Helper\\ConfigHelper' => __DIR__ . '/../..' . '/src/Helper/ConfigHelper.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitfe626b7edf4c50f84f22dfa0d3a90ed8::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitfe626b7edf4c50f84f22dfa0d3a90ed8::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInitfe626b7edf4c50f84f22dfa0d3a90ed8::$classMap;

        }, null, ClassLoader::class);
    }
}
