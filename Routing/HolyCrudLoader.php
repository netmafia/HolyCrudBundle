<?php

namespace NetMafia\HolyCrudBundle\Routing;

use ReflectionParameter;
use Symfony\Bundle\FrameworkBundle\Controller\ControllerNameParser;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * This class implements a route loader that will generate a RouteCollection
 * that includes all the Routes to Actions on the controller passed as 'resource'
 * that inherit from AbstractHolyCrudController
 */
class HolyCrudLoader implements LoaderInterface
{

    /**
     * @var \Symfony\Bundle\FrameworkBundle\Controller\ControllerNameParser
     */
    private $controllerNameParser;

    public function __construct(ControllerNameParser $controllerNameParser)
    {

        $this->controllerNameParser = $controllerNameParser;
    }

    /**
     * Loads a resource.
     *
     * @param mixed $resource The resource
     * @param string $type     The resource type
     * @return RouteCollection
     */
    public function load($resource, $type = null)
    {
        return $this->buildRoutes($resource);
    }

    private function replacePathVariables($path, $arguments)
    {
        /** @var $argument ReflectionParameter */
        foreach ($arguments as $index => $argument) {
            $path = str_replace("{$index}", $argument->getName(), $path);
        }

        return $path;
    }

    private function generatePath($name, $arguments)
    {
        $specialPathNames = [
            'view' => '/{1}'
        ];

        // If the action name is not specifically defined, we generate a path on the form of
        // /{arg1}/{arg2}/{arg3}/{actionName}
        if (!in_array($name, array_keys($specialPathNames))) {
            $path = "";
            foreach ($arguments as $index => $argument) {
                $path .= "/\{$index\}";
            }
            $path .= '/' . $name;
        } else {
            $path = $specialPathNames[$name];
        }

        // Fill the arguments
        $path = $this->replacePathVariables($path, $arguments);

        return $path;
    }

    private function buildRoutes($class)
    {
        $controllerReflectionClass = new \ReflectionClass($class);
        $availableMethods = $controllerReflectionClass->getMethods();

        $routes = new RouteCollection();

        foreach ($availableMethods as $method) {
            $fullMethodName = $method->getName();
            if (preg_match('/(.+)Action$/', $fullMethodName, $matches) && $method->isPublic()) {
                $actionName = $matches[1];
                $path = $this->generatePath($actionName, $method->getParameters());
                $defaults = [
                    '_controller' => $this->controllerNameParser->build($class.'::'.$fullMethodName)
                ];

                $routes->add(
                    $this->buildRouteName($controllerReflectionClass, $actionName),
                    new Route(
                        $path,
                        $defaults
                    )
                );
            }
        }

        return $routes;
    }

    private function buildRouteName(\ReflectionClass $reflectionClass, $actionName)
    {
        // Namespace without the \Controller part and the Bundle part
        $namespace = $reflectionClass->getNamespaceName();
        $namespace = preg_replace('/Bundle\\\/', '\\', $namespace);
        $positionOfControllerNamespacePart = strpos($namespace, '\\Controller');
        if ($positionOfControllerNamespacePart !== false) {
            $namespace = substr($namespace, 0, $positionOfControllerNamespacePart);
        }

        // Class name without the Controller part
        $class = preg_replace('/Controller$/', '', $reflectionClass->getShortName());

        // All in lowercase, with underscores
        $route = strtolower(str_replace('\\', '_', $namespace).'_'.$class.'_'.$actionName);

        return $route;
    }

    /**
     * Returns true if this class supports the given resource.
     *
     * @param mixed $resource A resource
     * @param string $type     The resource type
     *
     * @return Boolean true if this class supports the given resource, false otherwise
     */
    public function supports($resource, $type = null)
    {
        return $type == 'holycrud';
    }

    /**
     * Gets the loader resolver.
     *
     * @return LoaderResolverInterface A LoaderResolverInterface instance
     */
    public function getResolver()
    {
        // TODO: Implement getResolver() method.
    }

    /**
     * Sets the loader resolver.
     *
     * @param LoaderResolverInterface $resolver A LoaderResolverInterface instance
     */
    public function setResolver(LoaderResolverInterface $resolver)
    {
        // TODO: Implement setResolver() method.
    }

}
