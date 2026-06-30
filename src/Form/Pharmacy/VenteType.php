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

use App\Entity\Pharmacy\Customer;
use App\Entity\Pharmacy\Vente;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Entity\Pharmacy\VenteItem;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class VenteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Add event listener to handle date field
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $form = $event->getForm();
            $vente = $event->getData(); // get the Vente entity (or null)


            // If entity is new (no ID), set current date
            //if ($vente && !$vente->getId()) {
            $form->add('date_vente', DateType::class, [
                    'widget' => 'single_text',
                    'disabled' => true, // makes it read‑only, but value is still submitted
                    'data' => new \DateTimeImmutable(), // sets current date (time will be 00:00:00)
                    'label' => 'Date de la vente',
                    'html5' => true,
                    'attr' => ['readonly' => true, 'class' => 'form-control-plaintext'], //alternative
                ]);
            //} else {
              if (!$vente && $vente->getId()) {
                $form->add('date_vente', DateType::class, [
                    'widget' => 'single_text',
                    'disabled' => true,
                    'label' => 'Date de la vente',
                ]);
            }
        });

            $builder
            ->add('reference', TextType::class, [
                'required' => true,
                'label' => 'Numero de la Facture',
            ]);

/*            ->add('total_ht')
            ->add('total_tva')
            ->add('total_ttc') */

            if (!$options['is_edit']) {

               $builder->add('etat', ChoiceType::class, [
                    'choices' => [
                        'Brouillon' => 'draft',
                        'Confirmee' => 'confirmed',
                        'Livree' => 'delivered',
                        'Annulee' => 'cancelled',
                    ],
                    'placeholder' => 'Sélectionner un statut',
                ]);
            }
/*
            ->add('tva_pourcentage', NumberType::class, [
                'required' => false, 
                'empty_data' => 20
            ])

            ->add('client', EntityType::class, [
                'class' => Customer::class,
                'choice_label' => 'raisonSocial',
                'placeholder' => 'Select a client',
            ])
*/
            $builder->add('venteItems', CollectionType::class, [
                'entry_type' => VenteItemType::class,
                'entry_options' => ['label' => false],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'prototype' => true,
                'label' => 'Articles de la Vente',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Vente::class,
            'is_edit' => false,
        ]);
    }
}
