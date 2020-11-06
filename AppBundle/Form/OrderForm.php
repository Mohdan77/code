<?php

namespace Postroyka\AppBundle\Form;

use Postroyka\AppBundle\Service\OrderCalculator;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class OrderForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('purchase', ChoiceType::class, [
                'required' => true,
                'label' => 'order.purchase.title',
                'choices' => [
                    OrderCalculator::ORDER_PURCHASE_PICKUP => OrderCalculator::ORDER_PURCHASE_PICKUP,
                    OrderCalculator::ORDER_PURCHASE_DELIVERY => OrderCalculator::ORDER_PURCHASE_DELIVERY,
//                    OrderCalculator::ORDER_PURCHASE_EXPRESS => OrderCalculator::ORDER_PURCHASE_EXPRESS,
                ],
                'choice_attr' => [
                    OrderCalculator::ORDER_PURCHASE_PICKUP => [
                        'data-factor' => 0,
                    ],
                    OrderCalculator::ORDER_PURCHASE_DELIVERY => [
                        'data-factor' => 1,
                    ],
//                    OrderCalculator::ORDER_PURCHASE_EXPRESS => [
//                        'data-factor' => $options['express_delivery_factor']
//                    ],
                ],
            ])
            ->add('address', TextType::class, [
                'required' => false,
                'label' => 'order.address.title',
            ])
            ->add('date', TextType::class, [
                'required' => false,
                'label' => 'order.date.title',
                'attr' => [
                    'data-weekend' => $options['weekend_dates'],
                    'autocomplete' => 'off',
                    'readonly' => 'readonly',
                    'inputmode' => 'none'
                ]
            ])
            ->add('delivery', ChoiceType::class, [
                'required' => false,
                'label' => 'order.delivery.title',
                'choices' => $this->getChoices($options),
                'choice_attr' => function ($choices, $key, $value) use ($options) {
                    $zoneNumber = substr($value, -2, 2);
                    $zoneNumber = (int)str_replace('_', '', $zoneNumber);

                    return ['data-price' => $options['delivery_zone_' . $zoneNumber]];
                },
                'placeholder' => 'order.delivery.placeholder',
            ])
            ->add('unloading', ChoiceType::class, [
                'required' => true,
                'label' => 'order.unloading.title',
                'choices' => [
                    OrderCalculator::ORDER_UNLOADING_NONE => OrderCalculator::ORDER_UNLOADING_NONE,
                    OrderCalculator::ORDER_UNLOADING_ROOM => OrderCalculator::ORDER_UNLOADING_ROOM,
//                    OrderCalculator::ORDER_UNLOADING_CAR => OrderCalculator::ORDER_UNLOADING_CAR,
                ],
            ])
            ->add('floor', TextType::class, [
                'required' => false,
                'label' => 'order.floor.title',
                'constraints' => [
                    new Assert\Range(['min' => 1, 'max' => 50]),
                ]
            ])
            ->add('extraFloor', CheckboxType::class, [
                'required' => false,
                'label' => 'order.extraFloor.title'
            ])
            ->add('elevator', CheckboxType::class, [
                'required' => false,
                'label' => 'order.elevator.title'
            ])
            ->add('phone', TextType::class, [
                'required' => true,
                'label' => 'order.phone.title',
                'attr' => [
                    'placeholder' => 'order.phone.placeholder',
                    'data-type' => 'phone'
                ],
                'constraints' => [
                    new Assert\NotBlank(),
                ]
            ])
            ->add('email', EmailType::class, [
                'required' => true,
                'label' => 'order.email.title',
                'attr' => [
                    'placeholder' => 'order.email.placeholder',
                ],
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Email(),
                ]
            ])
            ->add('comment', TextareaType::class, [
                'required' => false,
                'label' => 'order.comment.title',
                'attr' => [
                    'placeholder' => 'order.comment.placeholder',
                    'rows' => '3'
                ]
            ])
            ->add('card_payment', CheckboxType::class, [
                'required' => false,
                'mapped' => false,
                'label' => 'order.card_payment.label'
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
                'delivery_zone_1' => 0,
                'delivery_zone_2' => 0,
                'delivery_zone_3' => 0,
                'delivery_zone_4' => 0,
                'delivery_zone_5' => 0,
                'delivery_zone_6' => 0,
                'delivery_zone_7' => 0,
                'delivery_zone_8' => 0,
                'delivery_zone_9' => 0,
                'delivery_zone_10' => 0,
                'delivery_zone_11' => 0,
                'delivery_zone_12' => 0,
                'delivery_zone_13' => 0,
                'delivery_zone_14' => 0,
                'delivery_zone_15' => 0,
                'delivery_zone_16' => 0,
                'delivery_zone_17' => 0,
                'delivery_zone_18' => 0,
                'express_delivery_factor' => 1,
                'translator' => null,
                'weekend_dates' => '',
            ]
        );
    }

    public function getBlockPrefix()
    {
        return '';
    }

    /**
     * Зоны доставки
     * @param $options
     * @return array
     */
    private function getChoices($options)
    {
        $zones = [];
        for ($i = 1; $i <= OrderCalculator::DELIVERY_ZONES_NUMBER; $i++) {
            $zone = constant(OrderCalculator::class . '::DELIVERY_ZONE_' . $i);
            $partZone = explode('.', $zone)[1];
            $price = $options[$partZone];
            $price = $price ? number_format($price, 0, ',', ' ') . ' руб.' : 'Бесплатно';
            $title = $options['translator']->trans($zone) . ': ' . $price;
            $zones[$title] = $zone;
        }

        return $zones;
    }
}
