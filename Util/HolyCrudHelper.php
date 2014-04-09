<?php

namespace NetMafia\HolyCrudBundle\Util;

use Doctrine\ORM\EntityManager;
use NetMafia\HolyCrudBundle\Entity\MinimalEntityInterface;
use NetMafia\HolyCrudBundle\Exception\HolyCrudGeneratorException;
use Symfony\Bundle\FrameworkBundle\Controller\ControllerNameParser;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;

class HolyCrudHelper
{
    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var EngineInterface
     */
    private $templatingEngine;

    /**
     * @var array
     */
    private $templatingEngines;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var \Symfony\Bundle\FrameworkBundle\Controller\ControllerNameParser
     */
    private $controllerNameParser;

    /**
     * @var \Symfony\Component\Form\FormFactoryInterface
     */
    private $formFactory;

    public function __construct(
        RouterInterface $router,
        EntityManager $entityManager,
        EngineInterface $templatingEngine,
        Request $request,
        ControllerNameParser $controllerNameParser,
        FormFactoryInterface $formFactory,
        array $templatingEngines
    ) {
        $this->router = $router;
        $this->entityManager = $entityManager;
        $this->templatingEngine = $templatingEngine;
        $this->templatingEngines = $templatingEngines;
        $this->request = $request;
        $this->controllerNameParser = $controllerNameParser;
        $this->formFactory = $formFactory;
    }

    /**
     * Returns the route name for a given method on this controller.
     *
     * @return string|null
     * @param $class
     * @param $method
     */
    public function getRoute($class, $method)
    {
        // Get a collection of all the routes in the application
        /** @var $routes RouteCollection */
        $routes = $this->router->getRouteCollection();

        // Find a route that matches the current controller and passed action
        /** @var $route Route */
        foreach ($routes as $name => $route) {
            if ($route->getDefault('_controller')
                == $class . '::' . $method
            ) {
                return $name;
            }
        }

        // If none found, return null
        return null;
    }

    /**
     * Returns an array actionName => routeName for all actions in the given controller $class
     *
     * @param $class
     * @return array
     */
    public function getRoutes($class)
    {
        $allRoutes = $this->router->getRouteCollection();
        $routes = [];

        /** @var $route Route */
        foreach ($allRoutes as $routeName => $route) {
            if (preg_match('/^' . preg_quote($class) . '::(.+)Action$/', $route->getDefault('_controller'), $matches)) {
                $actionName = $matches[1];
                $routes[$actionName] = $routeName;
            }
        }

        return $routes;
    }

    /**
     * Attempts to get an entity repository based on this controller's
     * name.

     *
*@param $class
     *
*@throws HolyCrudGeneratorException
     * @return EntityRepository
     */
    public function getRepository($class)
    {
        $repositoryClass = $this->getGuessedClassName($class, 'Entity');

        if (class_exists($repositoryClass)) {
            return $this->entityManager
                ->getRepository($repositoryClass);
        }

        throw new HolyCrudGeneratorException('Could not find the entity repository automatically. You must
            override this method to return the repository of entities managed by this controller');

    }

    /**
     * Attempts to get an entity from the repository by $id. In case of
     * no $id provided, will return a new entity.

     *
*@param $class
     * @param null $id
     *
*@throws HolyCrudGeneratorException
     * @return MinimalEntityInterface
     */
    public function getEntity($class, $id = null)
    {
        $entityRepository = $this->getRepository($class);

        // If a ID is given, try to get it from the repository
        if (!is_null($id)) {
            $entity = $entityRepository->find($id);

            return $entity;
        }

        // Otherwise, create a new one
        $entityClass = $entityRepository->getClassName();
        if (class_exists($entityClass)) {
            return new $entityClass;
        }

        // In case the entity doesn't exist, fail
        throw new HolyCrudGeneratorException('Could not create a new entity.');

    }

    /**
     * Try to guess the template name of a given action of this controller
     * based on the name. Supports any combination of request mime type and
     * templating engine.

     *
*@return string
     *
*@param $class
     * @param $method
     *
     * @throws HolyCrudGeneratorException
     */
    public function getTemplate($class, $method)
    {
        $templateName = $this->getMethodShortNotation($class, $method);
        $templating = $this->templatingEngine;
        $format = $this->request->getRequestFormat();
        foreach ($this->templatingEngines as $engine) {
            $template = "$templateName.$format.$engine";
            if ($templating->exists($template)) {
                return $template;
            }
        }

        throw new HolyCrudGeneratorException("Could not find the template for method $method automatically.");

    }

    /**
     * Retrieves a form for the entity managed by this controller. This method
     * will attempt different approaches for this.

     *
*@param $class
     * @param $entity
     *
     * @throws HolyCrudGeneratorException
     * @return FormInterface
     */
    public function getForm($class, $entity)
    {
        // Attempt to find a form type by name
        $form = $this->autoFindForm($class, $entity);
        if (!is_null($form)) {
            return $form;
        }

        // Attempt to autogenerate the form from the entity object
        $form = $this->autoGenerateForm($class, $entity);
        if (!is_null($form)) {
            return $form;
        }

        throw new HolyCrudGeneratorException('There was no way to autogenerate the CRUD
         form for the entity. You might need to define it manually by
         overriding this method.');

    }

    /**
     * Builds the Symfony short notation a:b:c from a given method of this controller
     *
     * @param $class
     * @param $method
     * @return string
     */
    public function getMethodShortNotation($class, $method)
    {
        return $this->controllerNameParser->build($class . "::" . $method);
    }

    /**
     * Builds a full qualified class name based on the current bundle and controller
     * name, for a given sub$directory and $suffix

     *
*@param $class
     * @param $directory
     * @param string $suffix
     *
     * @throws HolyCrudGeneratorException
     * @return string
     */
    public function getGuessedClassName($class, $directory, $suffix = "")
    {
        $regex = '/(.*)\\\Controller\\\(.+)Controller/';
        if (!preg_match($regex, $class, $matches)) {
            throw new HolyCrudGeneratorException('This controller has a non-standard name or namespace. The
                autoguesser features are not available');
        }

        return "$matches[1]\\$directory\\$matches[2]$suffix";
    }

    /**
     * Tries to find a form type based on this controller's name
     *
     * @param $class
     * @param $entity
     * @return null|FormInterface
     */
    public function autoFindForm($class, $entity)
    {
        $typeClass = $this->getGuessedClassName($class, 'Form', 'Type');

        if (class_exists($typeClass)) {
            return $this->formFactory->create(new $typeClass, $entity);
        }

        return null;
    }

    /**
     * Autogenerates a form from the entity characteristics
     *
     * @return null|FormInterface
     * @param $class
     * @param $entity
     */
    public function autoGenerateForm($class, $entity)
    {
        // TODO: Import this functionality from the lemon crud bundle
        return null;

    }

}
