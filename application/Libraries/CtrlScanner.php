<?php
declare(strict_types=1);

namespace App\Libraries;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use ReflectionMethod;
use stdClass;

class CtrlScanner
{    /**
     * Scan all controllers and return object:
     *   { FQCN => [method1, method2, ...] }
     * 
     * @param string|null $dir Directory to scan
     * @param string $ns Namespace of controllers
     * @param string $visibility Access level: 'public', 'protected', 'private' or 'all'
     * @return stdClass List of controllers and methods
     */
    public static function scan($dir = null, $ns = 'App\\Controllers', $visibility = 'public')
    {
        if ($dir === null) {
            $dir = defined('PATH_APP') ? PATH_APP . 'Controllers' : dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Controllers';
        }
        $dir = rtrim($dir, DIRECTORY_SEPARATOR);
        $ns  = trim($ns, '\\');
        $out = new stdClass();

        // Determine filter flag for ReflectionMethod
        $filter = 0;
        switch (strtolower($visibility)) {
            case 'public':
                $filter = ReflectionMethod::IS_PUBLIC;
                break;
            case 'protected':
                $filter = ReflectionMethod::IS_PROTECTED;
                break;
            case 'private':
                $filter = ReflectionMethod::IS_PRIVATE;
                break;
            case 'all':
                $filter = ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_PROTECTED | ReflectionMethod::IS_PRIVATE;
                break;
            default:
                $filter = ReflectionMethod::IS_PUBLIC;
                break;
        }

        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($it as $f) {
            if ($f->getExtension() !== 'php') continue;

            // map file → class according to PSR-4
            $rel   = str_replace([$dir, '/', '.php'], ['', '\\', ''], $f->getRealPath());
            $class = $ns . $rel;
            
            if (!class_exists($class)) {
                try {
                    require_once $f->getRealPath();
                } catch (\Throwable $e) {
                    // Skip files that cannot be loaded
                    continue;
                }
            }
            if (!class_exists($class)) continue;
            
            $ref = new ReflectionClass($class);
            if ($ref->isAbstract()) continue;
            
            // Only include classes that are controllers - check by name suffix or base class
            if (!str_ends_with($class, 'Controller') && !$ref->isSubclassOf('System\\Libraries\\Controller')) {
                continue;
            }

            $methods = [];
            foreach ($ref->getMethods($filter) as $m) {
                if (
                    $m->getDeclaringClass()->getName() === $class &&
                    !$m->isStatic() && !$m->isConstructor() &&
                    !$m->isDestructor() && !str_starts_with($m->getName(), '__')
                ) {
                    $methods[] = $m->getName();
                }
            }
            if ($methods) {
                $out->{$class} = $methods;
            }
        }
        return $out;
    }    /**
     * Auto scan all controllers in default directory
     * 
     * @param string|null $controllerDir Controller directory, if null will use default path
     * @param string $namespace Namespace of controllers
     * @param string $visibility Access level: 'public', 'protected', 'private' or 'all'
     * @return stdClass List of controllers and methods
     */
    public static function controllerScan($controllerDir = null, $namespace = 'App\\Controllers', $visibility = 'public')
    {
        if ($controllerDir === null) {
            // Use default path to controllers directory
            // Make sure PATH_APP is defined as a global constant
            $controllerDir = defined('PATH_APP') ? PATH_APP . 'Controllers' : dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Controllers';
        }
        
        return self::scan($controllerDir, $namespace, $visibility);
    }
}
