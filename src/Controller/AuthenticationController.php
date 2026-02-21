<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class AuthenticationController extends AbstractController
{
    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // Vérifie si l'utilisateur possède déjà une session active (est authentifié).
        // Si c'est le cas, on court-circuite le formulaire de connexion et on le redirige
        // directement vers la route protégée du catalogue.
        if ($this->getUser()) {
            return $this->redirectToRoute('app_products');
        }

        // Récupère la dernière erreur d'authentification (ex: mauvais identifiants, mot de passe incorrect).
        // Ce processus est géré et injecté automatiquement par le pare-feu (firewall) de sécurité de Symfony.
        $error = $authenticationUtils->getLastAuthenticationError();

        // Récupère le dernier email (username) saisi par l'utilisateur.
        // Cela améliore l'expérience utilisateur (UX) en pré-remplissant le champ email après une tentative échouée.
        $lastUsername = $authenticationUtils->getLastUsername();

        // Fait le rendu de la vue Twig en lui injectant l'erreur potentielle et le dernier email utilisé.
        return $this->render('authentication/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        // Cette méthode reste intentionnellement vide.
        // Le composant de sécurité de Symfony intercepte la requête '/logout' grâce à la configuration
        // du pare-feu dans security.yaml et gère la destruction de la session automatiquement
        // avant même que cette méthode ne soit exécutée.
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
