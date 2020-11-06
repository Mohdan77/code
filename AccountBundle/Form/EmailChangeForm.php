<?php

namespace Postroyka\AccountBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

class EmailChangeForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('password', PasswordType::class, array(
                'required' => true,
                'label' => 'user.password_current',
                'constraints' => [
                    new Assert\NotBlank(),
                ]
            ))
            ->add('email', EmailType::class, array(
                'required' => true,
                'label' => 'user.email_new',
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Email()
                ]
            ));
    }
}