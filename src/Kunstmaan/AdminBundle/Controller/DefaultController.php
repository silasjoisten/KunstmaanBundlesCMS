<?php

namespace Kunstmaan\AdminBundle\Controller;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Kunstmaan\AdminBundle\Entity\DashboardConfiguration;
use Kunstmaan\AdminBundle\FlashMessages\FlashTypes;
use Kunstmaan\AdminBundle\Form\DashboardConfigurationType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * The default controller is used to render the main screen the users see when they log in to the admin
 */
final class DefaultController extends AbstractController
{
    /** @var ParameterBagInterface */
    private $parameterBag;
    /** @var ManagerRegistry */
    private $managerRegistry;

    public function __construct(ParameterBagInterface $parameterBag, ManagerRegistry $managerRegistry)
    {
        $this->parameterBag = $parameterBag;
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * The index action will render the main screen the users see when they log in in to the admin
     *
     * @Route("/", name="KunstmaanAdminBundle_homepage")
     * @Template("@KunstmaanAdmin/Default/index.html.twig")
     *
     * @return array
     */
    public function indexAction()
    {
        if ($this->parameterBag->has('kunstmaan_admin.dashboard_route')) {
            return $this->redirect($this->generateUrl($this->getParameter('kunstmaan_admin.dashboard_route')));
        }

        /* @var DashboardConfiguration $dashboardConfiguration */
        $dashboardConfiguration = $this->managerRegistry
            ->getManager()
            ->getRepository(DashboardConfiguration::class)
            ->findOneBy([]);

        return ['dashboardConfiguration' => $dashboardConfiguration];
    }

    /**
     * The admin of the index page
     *
     * @Route("/adminindex", name="KunstmaanAdminBundle_homepage_admin")
     * @Template("@KunstmaanAdmin/Default/editIndex.html.twig")
     *
     * @return array
     */
    public function editIndexAction(Request $request)
    {
        /* @var $em EntityManager */
        $em = $this->managerRegistry->getManager();

        /* @var DashboardConfiguration $dashboardConfiguration */
        $dashboardConfiguration = $em
            ->getRepository(DashboardConfiguration::class)
            ->findOneBy([]);

        if (\is_null($dashboardConfiguration)) {
            $dashboardConfiguration = new DashboardConfiguration();
        }
        $form = $this->createForm(DashboardConfigurationType::class, $dashboardConfiguration);

        if ($request->isMethod('POST')) {
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $em->persist($dashboardConfiguration);
                $em->flush($dashboardConfiguration);

                $this->addFlash(
                    FlashTypes::SUCCESS,
                    $this->get('translator')->trans('kuma_admin.edit.flash.success')
                );

                return new RedirectResponse($this->generateUrl('KunstmaanAdminBundle_homepage'));
            }
        }

        return [
            'form' => $form->createView(),
            'dashboardConfiguration' => $dashboardConfiguration,
        ];
    }
}
