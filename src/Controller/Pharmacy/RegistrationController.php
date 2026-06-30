<?php

namespace App\Controller\Pharmacy;

use App\Entity\User;
use App\Form\Pharmacy\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/pharmacy')]
class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
 
            /** @var string $plainPassword */
            $plainPassword = $form->get('plainPassword')->getData();

            // encode the plain password
            $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));

            $user->setUsername($form->get('username')->getData());
            //$user->setPassword($passwordHasher->hashPassword($user, $form->get('plainPassword')->getData()));
            $user->setRoles(['ROLE_EMPLOYEE']);   // default role
            //$user->setAgreeTerms(true);          // optional, only if you have that field
            // If you added a 'name' field (see point 2)
            if ($form->has('name')) {
                $user->setName($form->get('name')->getData());
            }

            $entityManager->persist($user);
            $entityManager->flush();

            // do anything else you need here, like send an email

            return $this->redirectToRoute('app_register_success');
        }

        return $this->render('pharmacy/registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }

    #[Route('/register/success', name: 'app_register_success')]
    public function registerSuccess(): Response
    {
        return $this->render('pharmacy/registration/success.html.twig');
    }
}
