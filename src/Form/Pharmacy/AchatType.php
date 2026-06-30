/*
 * Copyright (c) 2025 Hamda Chaouch.
 *
 * Licensed under the Apache License, Version 2.0 (the License);
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an AS IS BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */


<?php

namespace App\Form\Pharmacy;

use App\Entity\Pharmacy\Achat;
use App\Form\Pharmacy\AchatItemType;
use App\Entity\Pharmacy\Fournisseur;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class AchatType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('reference',TextType::class,[
                'required' => false,
                'label' => 'Reference',
            ]) // manual for now
            ->add('date_achat', DateTimeType::class, [
                'widget' => 'single_text',
                'label' => 'Date Achat',
            ])
            ->add('fournisseur', EntityType::class, [
                'class' => Fournisseur::class,
                'choice_label' => 'raisonSocial',
                'placeholder' => 'Select a supplier',
                'label' => 'Fournisseur',
            ])
/*            ->add('tva_pourcentage', NumberType::class, [
                'label' => 'TVA (%)',
                'required' => false,
                'empty_data' => 0,
            ])
*/
            ->add('AchatItems', CollectionType::class, [
                'entry_type' => AchatItemType::class,
                'entry_options' => ['label' => false],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'prototype' => true,  // allows adding new items with JavaScript
                'label' => 'Produits Ajoutés',
            ])
           // ->add('total_ht', NumberType::class)
           // ->add('total_tva', NumberType::class)
           // ->add('total_ttc', NumberType::class)
            ->add('etat', ChoiceType::class, [
                'choices' => [
                    'Brouillon' => 'draft',
                    'Confirmee' => 'confirmed',
                    'Partiellement recu' => 'partially_received',
                    'Recu' => 'received',
                    'Annulee' => 'cancelled',
                             ],
                'placeholder' => 'Select status',
                'label' => 'Etat', 
                    ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Achat::class,
        ]);
    }
}
