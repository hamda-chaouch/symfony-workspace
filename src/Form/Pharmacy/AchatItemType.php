<?php

namespace App\Form\Pharmacy;

use App\Entity\Pharmacy\Achat;
use App\Entity\Pharmacy\AchatItem;
use App\Entity\Pharmacy\Stock;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\NumberType;

class AchatItemType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('produit', EntityType::class, [
                'class' => Stock::class,
                'choice_label' => 'designation',
                'placeholder' => 'Choisit un Produit',
                'choice_attr' => function(Stock $product) {
                    return [
                        'data-price' => $product->getPrixAchat(),
                        'data-tva' => $product->getTvaPourcentage() ?? 0,
                    ];
                },
                'required' => true,
            ])
            ->add('quantite', NumberType::class)
            ->add('prix_unitaire_ht', NumberType::class)
            ->add('tva_pourcentage',NumberType::class,[
                'required' => false,
                'empty_data' => 0,
        'help' => 'Taux de TVA applicable (laisser vide pour utiliser celui du produit)',
        'label' => 'TVA (%)',
            ]);
            // add('total_ligne_ht')
            //->add('achat', EntityType::class, [ //this make no sense
            //    'class' => Achat::class,
            //    'choice_label' => 'id',
            //]) ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AchatItem::class,
        ]);
    }
}
