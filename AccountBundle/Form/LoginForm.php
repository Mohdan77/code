<?php

namespace Postroyka\AccountBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

class LoginForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('_email', EmailType::class, [
                'required' => true,
                'label' => 'login.email',
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Email()
                ]
            ])
            ->add('_password', PasswordType::class, [
                'required' => true,
                'label' => 'login.password',
                'constraints' => [
                    new Assert\NotBlank(),
                ]
            ])
            ->add('_remember_me', CheckboxType::class, [
                'required' => false,
                'label' => 'login.remember_me'
            ]);
    }

    public function getBlockPrefix()
    {
        return '';
    }
}