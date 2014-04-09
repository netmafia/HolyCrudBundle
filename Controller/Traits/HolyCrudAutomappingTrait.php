<?php
namespace NetMafia\HolyCrudBundle\Controller\Traits;

use Doctrine\ORM\EntityRepository;
use NetMafia\HolyCrudBundle\Util\HolyCrudHelper;
use Symfony\Component\Form\FormInterface;

trait HolyCrudAutomappingTrait
{

    protected $routes;

    protected function getRoutes()
    {
        if (is_null($this->routes)) {
            // If there isn't a list of routes, generate it
            $this->routes = $this->getHelper()->getRoutes(get_class($this));
        }

        return $this->routes;
    }

    /**
     * Returns the route name for a given action name on this controller.
     *
     * @return string|null
     *
     * @param $actionName
     */
    protected function getRoute($actionName)
    {
        $routes = $this->getRoutes();

        if (isset($routes[$actionName])) {
            return $routes[$actionName];
        }

        return null;
    }

    /**
     * Attempts to get an entity repository based on this controller's
     * name.
     *
     * @return EntityRepository
     */
    protected function getRepository()
    {
        return $this->getHelper()
            ->getRepository(get_class($this));
    }

    /**
     * Attempts to get an entity from the repository by $id. In case of
     * no $id provided, will return a new entity.
     *
     * @param null $id
     */
    protected function getEntity($id = null)
    {
        return $this->getHelper()
            ->getEntity(get_class($this), $id);
    }

    /**
     * Try to guess the template name of a given action of this controller
     * based on the name. Supports any combination of request mime type and
     * templating engine.
     *
     * @return string
     *
     * @param $method
     */
    protected function getTemplate($method)
    {
        return $this->getHelper()
            ->getTemplate(get_class($this), $method);
    }

    /**
     * Retrieves a form for the entity managed by this controller. This method
     * will attempt different approaches for this.
     *
     * @param $entity
     *
     * @return FormInterface
     */
    protected function getForm($entity = null)
    {
        return $this->getHelper()
            ->getForm(get_class($this), $entity);
    }

    /**
     * Gets the proper name of the controller, i.e. called on SomeController
     * would return "Some"
     *
     * @return mixed
     */
    protected function getControllerName()
    {
        return preg_replace('/Controller$/', '', (new \ReflectionClass(get_class($this)))->getShortName());
    }

    /**
     * Get the HolyCrudHelper service
     *
     * @return HolyCrudHelper
     */
    protected function getHelper()
    {
        return $this->get('netmafia_holycrud.helper');
    }
}