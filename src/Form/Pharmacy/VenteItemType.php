<?php

namespace App\Form\Pharmacy;

use App\Entity\Pharmacy\Stock;

use App\Entity\Pharmacy\VenteItem;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\NumberType;

class VenteItemType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('quantite', NumberType::class)
            ->add('prix_unitaire_ht', NumberType::class)

            ->add('produit', EntityType::class, [
                'class' => Stock::class,
                'choice_label' => 'Designation',
                'placeholder' => 'Choisit un produit',
                //'attr' => ['class' => 'product-select'], // optional for JavaScript
                'required' => true,
                'choice_attr' => function(Stock $product) {
                    return [
                        'data-price' => $product->getPrixDetail(),
                        'data-tva' => $product->getTvaPourcentage() ?? 0,

                            ];
                },
            ])
            ->add('tva_pourcentage',NumberType::class,[
                'required' => false,
                'empty_data' => 0,
                'help' => 'Taux de TVA applicable (laisser vide pour utiliser celui du produit)',
                'label' => 'TVA (%)',
                'attr' => ['readonly' => true, 'class' => 'form-control-plaintext'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => VenteItem::class,
        ]);
    }
}
