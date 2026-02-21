<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $entityManager
    ): Response {
        // Règle UX : Si l'utilisateur est déjà connecté, on bloque l'accès à l'inscription
        if ($this->getUser()) {
            return $this->redirectToRoute('app_user_profile');
        }

        // Si le formulaire a été soumis en POST
        if ($request->isMethod('POST')) {
            // 1. Récupération du payload HTTP (les données brutes du formulaire)
            $email = $request->request->get('email');
            $firstname = $request->request->get('firstname');
            $lastname = $request->request->get('lastname');
            $plainPassword = $request->request->get('password');

            // 2. Instanciation et hydratation de l'entité User
            $user = new User();
            $user->setEmail($email);
            $user->setFirstname($firstname);
            $user->setLastname($lastname);

            // 3. Sécurité critique : Hachage du mot de passe avec l'algorithme auto (Argon2id)
            $hashedPassword = $userPasswordHasher->hashPassword(
                $user,
                $plainPassword
            );
            $user->setPassword($hashedPassword);

            // Note architecturale : Pas besoin de forcer setRoles(),
            // la méthode getRoles() de l'entité assigne déjà 'ROLE_USER' par défaut.

            // 4. Unité de travail Doctrine : Persistance et Commit
            $entityManager->persist($user);
            $entityManager->flush();

            // 5. Feedback utilisateur et redirection vers la page de connexion
            $this->addFlash('success', 'Votre compte a bien été créé ! Vous pouvez maintenant vous connecter.');
            return $this->redirectToRoute('app_login');
        }

        // Si c'est une requête GET, on affiche simplement la vue
        return $this->render('register/register.html.twig');
    }
}
