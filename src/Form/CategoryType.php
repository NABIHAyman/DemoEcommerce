<?php

namespace App\Form;

use App\Entity\Category;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CategoryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Category Name',
                'attr' => [
                    'placeholder' => 'Ex: Men, Women, Accessories...',
                    // On peut injecter des classes Tailwind directement depuis le PHP !
                    'class' => 'w-full px-4 py-2 rounded border border-slate-200 focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all'
                ],
                'label_attr' => [
                    'class' => 'block text-sm font-bold text-slate-700 mb-2'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Category::class,
        ]);
    }
}
