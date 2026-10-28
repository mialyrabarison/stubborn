<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

class LoginSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator
    ) {
    }

    // Déterminer la page à afficher après une connexion réussie
    public function onAuthenticationSuccess(
        Request $request,
        TokenInterface $token
    ): RedirectResponse
    {
        // Récupérer les rôles de l'utilisateur connecté
        $roles = $token->getRoleNames();

        // Rediriger l'administrateur vers le Back-Office
        if (in_array('ROLE_ADMIN', $roles, true)) {
            return new RedirectResponse(
                $this->urlGenerator->generate('app_admin')
            );
        }

        // Rediriger les clients vers la page d'accueil
        return new RedirectResponse(
            $this->urlGenerator->generate('app_home')
        );
    }
}
