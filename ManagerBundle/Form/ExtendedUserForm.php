<?php

namespace Postroyka\ManagerBundle\Form;

use Doctrine\ORM\EntityRepository;
use Postroyka\AccountBundle\Entity\ExtendedUser;
use Postroyka\AppBundle\Service\OrderCalculator;
use Submarine\CoreBundle\Entity\Options\Option;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ExtendedUserForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('firstName', TextType::class, [
                'required' => true,
                'label' => 'user.first_name',
                'attr' => [
                    'placeholder' => 'user.first_name_description'
                ]
            ])
            ->add('secondName', TextType::class, [
                'required' => true,
                'label' => 'user.second_name',
                'attr' => [
                    'placeholder' => 'user.second_name_description'
                ]
            ])
            ->add('company', TextType::class, [
                'required' => false,
                'label' => 'user.company',
                'attr' => [
                    'placeholder' => 'user.company_description'
                ]
            ])
            ->add('email', EmailType::class, [
                'required' => true,
                'label' => 'user.email',
                'attr' => [
                    'placeholder' => 'user.email_description'
                ]
            ])
            ->add('phone', TextType::class, [
                'required' => false,
                'label' => 'user.phone',
                'attr' => [
                    'placeholder' => 'user.phone_description'
                ]
            ])
            ->add('discountCard', EntityType::class, [
                'label' => 'user.discount_card',
                'required' => false,
                'class' => Option::class,
                'choice_label' => 'title',
                'placeholder' => 'user.discount_description',
                'query_builder' => function (EntityRepository $er) {
                    return $er
                        ->createQueryBuilder('o')
                        ->where('o.name IN (:options)')
                        ->setParameter('options', [OrderCalculator::SILVER_CARD, OrderCalculator::GOLD_CARD]);
                },
            ])
            ->add('discountCardNumber', IntegerType::class, [
                'label' => 'user.discount_card_number',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => ExtendedUser::class,
        ]);
    }
}