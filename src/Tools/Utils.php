<?php

namespace Knuckles\Scribe\Tools;

use Closure;
use Exception;
use Illuminate\Routing\Route;
use Knuckles\Scribe\Tools\ConsoleOutputUtils as c;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use ReflectionFunctionAbstract;

class Utils
{
    public static function getFullUrl(Route $route, array $urlParameters = []): string
    {
        $uri = $route->uri();

        return self::replaceUrlParameterPlaceholdersWithValues($uri, $urlParameters);
    }

    public static function getRouteClassAndMethodNames(Route $route): array
    {
        $action = $route->getAction();

        $uses = $action['uses'];

        if ($uses !== null) {
            if (is_array($uses)) {
                return $uses;
            } elseif (is_string($uses)) {
                return explode('@', $uses);
            } elseif (static::isInvokableObject($uses)) {
                return [$uses, '__invoke'];
            }
        }
        if (array_key_exists(0, $action) && array_key_exists(1, $action)) {
            return [
                0 => $action[0],
                1 => $action[1],
            ];
        }

        throw new Exception("Couldn't get class and method names for route ". c::getRouteRepresentation($route).'.');
    }

    /**
     * Transform parameters in URLs into real values (/users/{user} -> /users/2).
     * Uses @urlParam values specified by caller, otherwise just uses '1'.
     *
     * @param string $uri
     * @param array $urlParameters Dictionary of url params and example values
     *
     * @return mixed
     */
    public static function replaceUrlParameterPlaceholdersWithValues(string $uri, array $urlParameters)
    {
        $matches = preg_match_all('/{.+?}/i', $uri, $parameterPaths);
        if (!$matches) {
            return $uri;
        }

        foreach ($parameterPaths[0] as $parameterPath) {
            $key = trim($parameterPath, '{?}');
            if (isset($urlParameters[$key])) {
                $example = $urlParameters[$key];
                $uri = str_replace($parameterPath, $example, $uri);
            }
        }
        // Remove unbound optional parameters with nothing
        $uri = preg_replace('#{([^/]+\?)}#', '', $uri);
        // Replace any unbound non-optional parameters with '1'
        $uri = preg_replace('#{([^/]+)}#', '1', $uri);

        return $uri;
    }

    public static function deleteDirectoryAndContents($dir, $base = null)
    {
        $dir = ltrim($dir, '/');
        $adapter = new Local($base ?: realpath(__DIR__ . '/../../'));
        $fs = new Filesystem($adapter);
        $fs->deleteDir($dir);
    }

    /**
     * @param mixed $value
     *
     * @return bool
     */
    public static function isInvokableObject($value): bool
    {
        return is_object($value) && method_exists($value, '__invoke');
    }

    /**
     * Returns the route method or closure as an instance of ReflectionMethod or ReflectionFunction
     *
     * @param array $routeControllerAndMethod
     *
     * @throws ReflectionException
     *
     * @return ReflectionFunctionAbstract
     */
    public static function getReflectedRouteMethod(array $routeControllerAndMethod): ReflectionFunctionAbstract
    {
        [$class, $method] = $routeControllerAndMethod;

        if ($class instanceof Closure) {
            return new ReflectionFunction($class);
        }

        return (new ReflectionClass($class))->getMethod($method);
    }

    /**
     * @param string $tag
     * @return string
     */
    public static function parseTransformerTagForIncludes(string $tag)
    {
        if ($transformerFullClass = self::transformerTagToClass($tag)) {
            try {
                if (class_exists($transformerFullClass)) {
                    $transformerInstance = new $transformerFullClass();
                    $availableIncludes = $transformerInstance->getAvailableIncludes();
                    $availableIncludesArray = array_map(
                        function ($availableInclude) {
                            return '<code>' . $availableInclude . '</code>';
                        },
                        $availableIncludes
                    );
                    $relationshipString = implode(', ', $availableIncludesArray);
                    $output = preg_replace('#<transformer(.*?)>(.*?)</transformer>#is', $relationshipString, $tag);
                    $transformerUsed = str_replace('Transformer', ' Transformer', self::transformerTagToName($tag));
                    $output .= ' (Uses the ' . $transformerUsed . ').';

                    return $output;
                }
            } catch (\Exception $exception) {
                // Class does not exist
            }
        }

        return $tag;
    }

    /**
     * @param string $tag
     * @return mixed|null
     */
    public static function transformerTagToClass(string $tag): ?string
    {
        if ($transformerName = self::transformerTagToName($tag)) {
            return 'App\Transformers\\' . $transformerName;
        }

        return null;
    }

    /**
     * @param string $tag
     * @return string|null
     */
    public static function transformerTagToName(string $tag): ?string
    {
        $regex = '#<\s*?transformer\b[^>]*>(.*?)</transformer\b[^>]*>#s';
        preg_match($regex, $tag, $matches);
        return $matches[1] ?? null;
    }
}
