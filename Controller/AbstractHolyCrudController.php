<?php

namespace NetMafia\HolyCrudBundle\Controller;

use Doctrine\ORM\EntityRepository;
use NetMafia\HolyCrudBundle\Controller\Traits\HolyCrudAutomappingTrait;
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

    use HolyCrudAutomappingTrait;

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
}
