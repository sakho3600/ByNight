<?php

namespace AppBundle\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use AppBundle\Social\Social;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/social/{service}", requirements={"service": "facebook|twitter|google"})
 */
class SocialController extends Controller
{
    /**
     * @Route("/connexion", name="tbn_administration_connect_site")
     */
    public function connectInfoAction($service)
    {
        $session = $this->container->get('session');
        $session->set('connect_site', true);

        $url = $this->get('router')->generate('hwi_oauth_service_redirect', ['service' => 'facebook' === $service ? 'facebook_admin' : $service]);

        return $this->redirect($url);
    }

    /**
     * @Route("/deconnexion", name="tbn_administration_disconnect_service")
     */
    public function disconnectSiteAction($service)
    {
        $serviceName = 'tbn.social.' . ('facebook' === $service ? 'facebook_events' : $service);

        /** @var Social $social */
        $social = $this->container->get($serviceName);
        $social->disconnectSite();

        $em = $this->getDoctrine()->getManager();
        $em->persist($this->get('app.social_manager')->getSiteInfo());
        $em->flush();

        return new JsonResponse(['success' => true]);
    }

    /**
     * @Route("/deconnexion/confirmation", name="tbn_administration_disconnect_service_confirm")
     */
    public function disconnectConfirmAction($service)
    {
        return $this->render('Social/confirm.html.twig', [
            'service' => $service,
            'url'     => $this->generateUrl('tbn_administration_disconnect_service', ['service' => $service]),
        ]);
    }
}
