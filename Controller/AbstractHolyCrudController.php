<?php

namespace NetMafia\HolyCrudBundle\Controller;

use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

/**
 * Class AbstractAutoCrudController
 * @package NetMafia\HolyCrudBundle\Controller
 */
abstract class AbstractHolyCrudController extends Controller
{

    protected $routes;

    /**
     * User-facing endpoint to add new elements
     * @Route("/new")
     */
    public function newAction()
    {
        $request = $this->getRequest();
        $entityManager = $this->getDoctrine()->getManager();
        $form = $this->getForm();

        if ($request->isMethod('POST')) {

            $form->handleRequest($request);
            $entity = $form->getData();

            if ($form->isValid()) {
                $entityManager->persist($entity);
                $entityManager->flush();

                return $this->redirect(
                    $this->generateUrl(
                        $this->getRoute('edit'),
                        [
                            'id' => $entity->getId()
                        ]
                    )
                );
            }
        }

        return $this->render(
            $this->getTemplate(__FUNCTION__),
            [
                'form' => $form->createView(),
            ]
        );

    }

    /**
     * User-facing listing of all elements
     */
    public function listAction()
    {
        $entityRepository = $this->getRepository();
        $entities = $entityRepository->findAll();

        return $this->render(
            $this->getTemplate(__FUNCTION__),
            [
                'entities' => $entities,
            ]
        );

    }

    /**
     * User-facing endpoint to edit existing elements
     * @Route("/{id}/edit")
     * @param $id
     * @return Response
     */
    public function editAction($id)
    {
        $request = $this->getRequest();
        $entityManager = $this->getDoctrine()->getManager();
        $entity = $this->getEntity($id);
        $form = $this->getForm($entity);

        if ($request->isMethod('POST')) {

            $form->handleRequest($request);
            if ($form->isValid()) {
                $entityManager->flush();
            }

            return $this->redirect(
                $this->getRoute('list')
            );
        }

        return $this->render(
            $this->getTemplate(__FUNCTION__),
            [
                'form' => $form->createView(),
                'entity' => $entity,
            ]
        );

    }

    /**
     * User-facing endpoint to remove elements
     *
     * @Route("/{id}/remove")
     * @param $id
     * @return RedirectResponse
     */
    public function removeAction($id)
    {
        $request = $this->getRequest();
        $entityManager = $this->getDoctrine()->getManager();
        $entity = $this->getEntity($id);
        $form = $this->getForm($entity);
        $form->handleRequest($request);
        $entityManager->remove($entity);
        $entityManager->flush();

        return $this->redirect(
            $this->getRoute('list')
        );

    }

    /* Auto-mapping functions */

    protected function getRoutes()
    {
        if (is_null($this->routes)) {
            // If there isn't a list of routes, generate it
            $this->routes = $this->get('netmafia_holycrud.helper')->getRoutes(get_class($this));
        }

        return $this->routes;
    }

    /**
     * Returns the route name for a given action name on this controller.
     *
     * @return string|null
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
        return $this->get('netmafia_holycrud.helper')
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
        return $this->get('netmafia_holycrud.helper')
            ->getEntity(get_class($this), $id);
    }

    /**
     * Try to guess the template name of a given action of this controller
     * based on the name. Supports any combination of request mime type and
     * templating engine.
     *
     * @return string
     * @param $method
     */
    protected function getTemplate($method)
    {
        return $this->get('netmafia_holycrud.helper')
            ->getTemplate(get_class($this), $method);
    }

    /**
     * Retrieves a form for the entity managed by this controller. This method
     * will attempt different approaches for this.
     *
     * @param $entity
     * @return FormInterface
     */
    protected function getForm($entity = null)
    {
        return $this->get('netmafia_holycrud.helper')
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
}
