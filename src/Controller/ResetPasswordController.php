<?php

namespace App\Controller;

use App\Entity\ResetPasswordToken;
use App\Repository\ResetPasswordTokenRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ResetPasswordController extends AbstractController
{
    #[Route('/forgot-password', name: 'app_forgot_password')]
    public function request(Request $request, UserRepository $userRepository, EntityManagerInterface $entityManager): Response
    {
        // Vérification si la requête HTTP entrante est de type POST (soumission du formulaire).
        // L'objet Request injecté par le conteneur de services contient toutes les superglobales ($_POST, $_GET, etc.).
        if ($request->isMethod('POST')) {
            // Extraction de l'email depuis le payload de la requête HTTP.
            $email = $request->request->get('email');

            // Requête vers la base de données via le pattern Repository pour trouver l'utilisateur correspondant.
            $user = $userRepository->findOneBy(['email' => $email]);

            // Si l'utilisateur existe dans notre système, on enclenche le processus de génération de token.
            if ($user) {
                // Utilisation de random_bytes() (un CSPRNG - Cryptographically Secure Pseudo-Random Number Generator)
                // pour générer 32 octets aléatoires, puis conversion en chaîne hexadécimale.
                // C'est vital pour éviter les attaques par force brute ou la prédiction de tokens.
                $tokenString = bin2hex(random_bytes(32));

                // Instanciation d'une nouvelle entité ResetPasswordToken pour tracer cette demande en base.
                $resetToken = new ResetPasswordToken();
                $resetToken->setUser($user);
                $resetToken->setToken($tokenString);

                // Sécurité temporelle (TTL) : le token expire strictement dans 1 heure.
                // On utilise DateTimeImmutable pour éviter tout effet de bord indésirable sur l'objet Date lors de manipulations futures.
                $resetToken->setExpiresAt((new \DateTimeImmutable())->modify('+1 hour'));

                // L'EntityManager (le gestionnaire d'état de Doctrine) prépare la requête d'insertion (persist)
                // puis l'exécute physiquement dans MySQL (flush).
                $entityManager->persist($resetToken);
                $entityManager->flush();

                // Génération du lien absolu (avec le domaine complet, ex: http://localhost/...) qui sera envoyé par email.
                // On utilise le composant de routing pour garantir que l'URL soit toujours valide même si la structure change.
                $resetLink = $this->generateUrl('app_reset_password', ['token' => $tokenString], UrlGeneratorInterface::ABSOLUTE_URL);

                // --- DEVELOPMENT MODE ---
                // dd("Simulation d'envoi d'email ! Voici le lien secret à cliquer : " . $resetLink);
            }

            // CONCEPT CLÉ DE CYBERSÉCURITÉ : L'anti-énumération (Prevention of User Enumeration).
            // Même si l'utilisateur n'existe PAS en base de données, on renvoie EXACTEMENT le même message de succès.
            // Ainsi, un attaquant ou un bot ne peut pas utiliser ce formulaire pour scanner et deviner quels emails sont inscrits chez nous.
            $this->addFlash('success', 'If your account exists, an email has been sent with reset instructions.');

            // Redirection pattern (PRG: Post/Redirect/Get) pour éviter la double soumission du formulaire en cas de rafraîchissement (F5).
            return $this->redirectToRoute('app_login');
        }

        // Fallback en méthode GET : on affiche la vue Twig contenant le formulaire HTML.
        return $this->render('reset_password/request.html.twig');
    }



    #[Route('/reset-password/{token}', name: 'app_reset_password')]
    public function reset(
        string $token,
        Request $request,
        ResetPasswordTokenRepository $tokenRepository,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager
    ): Response {
        // 1. Recherche du token de réinitialisation soumis dans l'URL directement dans la base de données.
        $resetToken = $tokenRepository->findOneBy(['token' => $token]);

        // 2. Première barrière de sécurité : Validation de l'existence du token.
        // Si le token est introuvable (faux token généré par un attaquant, ou déjà consommé), on bloque l'accès.
        if (!$resetToken) {
            $this->addFlash('error', 'This reset link is invalid or has already been used.');
            return $this->redirectToRoute('app_login');
        }

        // 3. Deuxième barrière de sécurité : Validation de la fenêtre de temps (Time-To-Live).
        // On compare la date d'expiration stockée en base avec le timestamp actuel.
        if ($resetToken->getExpiresAt() < new \DateTimeImmutable()) {
            // Opération de nettoyage (Garbage collection manuelle) : le token est périmé, on assainit la DB en le supprimant.
            $entityManager->remove($resetToken);
            $entityManager->flush();

            $this->addFlash('error', 'This link has expired. Please make a new request.');
            return $this->redirectToRoute('app_forgot_password');
        }

        // 4. Traitement de la soumission du nouveau mot de passe (Méthode POST).
        if ($request->isMethod('POST')) {
            // Extraction du mot de passe en clair envoyé via le formulaire sécurisé.
            $newPassword = $request->request->get('password');

            // Récupération de l'objet User rattaché à ce token validé.
            $user = $resetToken->getUser();

            // On utilise le service de hachage de Symfony (qui implémente l'algorithme Argon2id ou Bcrypt selon le serveur)
            // pour crypter le nouveau mot de passe.
            $hashedPassword = $passwordHasher->hashPassword($user, $newPassword);

            // Mise à jour de la propriété password de l'entité User.
            $user->setPassword($hashedPassword);

            // Troisième règle de sécurité absolue : Un token est à usage UNIQUE.
            // Dès que le mot de passe est réécrit, on détruit le token pour contrer les attaques par rejeu (Replay attacks).
            $entityManager->remove($resetToken);

            // Commit final (Transaction de base de données) : on sauvegarde l'utilisateur modifié et on supprime le token de façon atomique.
            $entityManager->flush();

            // Feedback utilisateur et redirection de fin de processus.
            $this->addFlash('success', 'Your password has been successfully reset. You can now log in.');
            return $this->redirectToRoute('app_login');
        }

        // 5. Affichage du formulaire de définition du nouveau mot de passe (Méthode GET).
        return $this->render('reset_password/reset.html.twig');
    }
}
