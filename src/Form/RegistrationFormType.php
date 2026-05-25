<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Mutualisation des classes Tailwind pour un code "Clean"
        $defaultClasses = 'w-full px-4 py-3 rounded border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all placeholder:text-slate-400 text-sm';
        $labelClasses = 'block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5';

        $builder
            ->add('firstname', TextType::class, [
                'attr' => ['class' => $defaultClasses],
                'label_attr' => ['class' => $labelClasses],
            ])
            ->add('lastname', TextType::class, [
                'attr' => ['class' => $defaultClasses],
                'label_attr' => ['class' => $labelClasses],
            ])
            ->add('email', EmailType::class, [
                'attr' => ['class' => $defaultClasses, 'placeholder' => 'name@company.com'],
                'label_attr' => ['class' => $labelClasses],
            ])
            ->add('password', RepeatedType::class, [
                'type' => PasswordType::class,
                // Le RepeatedType gère automatiquement la vérification d'égalité des deux champs !
                'invalid_message' => 'The password fields must match.',
                'required' => true,
                'first_options'  => [
                    'label' => 'Password',
                    'attr' => ['class' => $defaultClasses, 'placeholder' => '••••••••'],
                    'label_attr' => ['class' => $labelClasses]
                ],
                'second_options' => [
                    'label' => 'Confirm Password',
                    'attr' => ['class' => $defaultClasses, 'placeholder' => '••••••••'],
                    'label_attr' => ['class' => $labelClasses . ' mt-4']
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
