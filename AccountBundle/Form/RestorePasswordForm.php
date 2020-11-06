<?php

namespace Postroyka\AccountBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

class RestorePasswordForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('email', EmailType::class, array(
                'required' => true,
                'label' => 'user.email',
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Email()
                ]
            ))
            ->add('password', RepeatedType::class, [
                'type' => PasswordType::class,
                'required' => true,
                'first_options' => ['label' => 'user.password_new'],
                'second_options' => ['label' => 'user.repeat_password'],
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(['min' => 6])
                ]
            ]);
    }
}