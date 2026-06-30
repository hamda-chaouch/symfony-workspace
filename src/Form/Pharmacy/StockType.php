<?php

namespace App\Form\Pharmacy;

use App\Entity\Pharmacy\Category;
use App\Entity\Pharmacy\Fournisseur;
use App\Entity\Pharmacy\Stock;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\DateType;

class StockType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
    $builder
        // ========== Product Information ==========
        ->add('designation', null, [
            'label' => 'Désignation',
            'attr' => ['placeholder' => 'Nom du produit', 'class' => 'form-control'],
            'help' => 'Nom ou description courte du produit.',
        ])
        ->add('reference', null, [
            'label' => 'Référence / Code‑barre',
            'attr' => ['placeholder' => 'Code interne ou EAN', 'class' => 'form-control'],
            'help' => 'Identifiant unique pour le scanner.',
        ])

        // ========== Pricing ==========
        ->add('prix_achat', NumberType::class, [
            'label' => "Prix d'achat HT",
            'attr' => ['placeholder' => 'Prix d’achat unitaire', 'class' => 'form-control'],
            'help' => 'Prix payé au fournisseur.',
        ])
        ->add('prix_gros', NumberType::class, [
            'label' => 'Prix de gros (PVG)',
            'required' => false,
            'attr' => ['placeholder' => 'Prix vente en gros', 'class' => 'form-control'],
        ])
        //to be used to calculate the PVD automatically
        ->add('marge', NumberType::class, [
            'label' => 'Marge Beneficiaire',
            'mapped' => false,           // not stored in database
            'attr' => ['placeholder' => 'marge beneficiaire', 'class' => 'form-control'],
        ])
        //calculated automatically 
        ->add('prix_detail', NumberType::class, [
            'label' => 'Prix de détail (PVD)',
            //'disabled' => true,  // user cannot edit it manually //commented because disabled fields its value cannot reach the entity
            'attr' => ['readonly' => true, 'class' => 'form-control-plaintext'],
        ])
        ->add('tva_pourcentage', NumberType::class, [
            'label' => 'TVA (%)',
            'required' => false,
            'attr' => ['placeholder' => 'Ex: 20', 'class' => 'form-control', 'step' => '0.1'],
            'help' => 'Taux de TVA applicable à ce produit.',
        ])
        //an indicator field (calculated automatically dinamically)
        ->add('prix_ttc', NumberType::class, [
            'label' => 'Prix de vente TTC',
            'mapped' => false,           // not stored in database
            'disabled' => true,          // user cannot edit it manually
            'attr' => ['readonly' => true, 'class' => 'form-control-plaintext'],
        ])
        // ========== Inventory ==========
        ->add('quantite', IntegerType::class, [
            'label' => 'Stock actuel',
            'attr' => ['placeholder' => 'Quantité en main', 'class' => 'form-control'],
        ])
        ->add('quantite_min', IntegerType::class, [
            'label' => 'Stock minimum d’alerte',
            'required' => false,
            'attr' => ['placeholder' => 'Seuil d’alerte', 'class' => 'form-control'],
            'help' => 'En dessous → alerte.',
        ])
        ->add('quantite_max', IntegerType::class, [
            'label' => 'Stock maximum',
            'required' => false,
            'attr' => ['placeholder' => 'Capacité max', 'class' => 'form-control'],
            'help' => 'Dépassement bloqué lors des achats.',
        ])
        ->add('description', TextareaType::class, [
            'label' => 'Description',
            'required' => false,
            'attr' => ['rows' => 3, 'placeholder' => 'Informations complémentaires', 'class' => 'form-control'],
        ])

        // ========== Relations ==========
            ->add('fournisseur', EntityType::class, [
                'class' => Fournisseur::class,
                'choice_label' => 'raisonSocial',
            'placeholder' => '– Sélectionner un fournisseur –',
            'label' => 'Fournisseur',
            'attr' => ['class' => 'form-select'],
            ])
            ->add('categorie', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'nom',
            'placeholder' => '– Sélectionner une catégorie –',
            'label' => 'Catégorie',
            'attr' => ['class' => 'form-select'],
            ])
            ->add('date_expiration', DateType::class, [
                    'widget' => 'single_text',
                    'required' => false,
                    'attr' => ['class' => 'form-control'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Stock::class,
        ]);
    }
}
