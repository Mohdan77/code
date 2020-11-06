<?php

namespace Postroyka\AccountBundle\Form;

use Postroyka\AccountBundle\Entity\ExtendedUser;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class ProfileForm extends AbstractType
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
            ->add('email', EmailType::class, [
                'required' => true,
                'label' => 'user.email',
                'attr' => [
                    'placeholder' => 'user.email_description',
                    'readonly' => true,
                ]
            ])
            ->add('phone', TextType::class, [
                'required' => false,
                'label' => 'user.phone',
                'attr' => [
                    'placeholder' => 'user.phone_description',
                    'data-type' => 'phone'
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => ExtendedUser::class,
        ]);
    }
}