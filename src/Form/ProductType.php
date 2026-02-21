<?php

namespace App\Form;

use App\Entity\Category;
use App\Entity\Product;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Définition de variables pour éviter de répéter les classes Tailwind
        $defaultClasses = 'w-full px-4 py-2 rounded border border-slate-200 focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all text-sm';
        $labelClasses = 'block text-xs font-bold uppercase tracking-widest text-slate-500 mb-1.5';

        $builder
            ->add('name', TextType::class, [
                'label' => 'Product Name',
                'attr' => ['class' => $defaultClasses, 'placeholder' => 'Ex: Gaming Laptop...'],
                'label_attr' => ['class' => $labelClasses],
            ])
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'name', // C'est ce champ qui sera affiché dans le <select> HTML
                'label' => 'Category',
                'attr' => ['class' => $defaultClasses],
                'label_attr' => ['class' => $labelClasses],
            ])
            ->add('price', IntegerType::class, [
                'label' => 'Price (in cents)',
                'attr' => ['class' => $defaultClasses, 'placeholder' => 'Ex: 150000 for 1500.00 €'],
                'label_attr' => ['class' => $labelClasses],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'attr' => ['class' => $defaultClasses, 'rows' => 4],
                'label_attr' => ['class' => $labelClasses],
            ])
            ->add('isTop', CheckboxType::class, [
                'label' => 'Display in "Top Products"?',
                'required' => false, // Obligatoire pour les checkbox, sinon HTML5 bloque la soumission si décoché
                'attr' => ['class' => 'w-5 h-5 text-primary border-slate-300 rounded focus:ring-primary'],
                'label_attr' => ['class' => 'ml-2 text-sm font-bold text-slate-700 cursor-pointer'],
                // row_attr permet de styliser la div englobant le label et l'input
                'row_attr' => ['class' => 'flex items-center bg-slate-50 p-4 rounded-lg border border-slate-100 mt-4']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
        ]);
    }
}
