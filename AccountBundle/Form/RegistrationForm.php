<?php

namespace Postroyka\AccountBundle\Form;

use EWZ\Bundle\RecaptchaBundle\Form\Type\EWZRecaptchaType;
use Postroyka\AccountBundle\Entity\RegistrationUser;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use EWZ\Bundle\RecaptchaBundle\Validator\Constraints\IsTrue as RecaptchaTrue;

class RegistrationForm extends AbstractType
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
                    'placeholder' => 'user.email_description'
                ]
            ])
            ->add('password', RepeatedType::class, [
                'type' => PasswordType::class,
                'required' => true,
                'first_options' => ['label' => 'user.password'],
                'second_options' => ['label' => 'user.repeat_password'],
            ])
            ->add('captcha', EWZRecaptchaType::class, [
                'label' => false,
                'required' => false,
                'mapped' => false,
                'constraints' => [
                    new RecaptchaTrue()
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => RegistrationUser::class,
        ]);
    }
}