<?php

namespace Postroyka\AppBundle\Form;

use EWZ\Bundle\RecaptchaBundle\Form\Type\EWZRecaptchaType;
use Gregwar\CaptchaBundle\Type\CaptchaType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;
use EWZ\Bundle\RecaptchaBundle\Validator\Constraints\IsTrue as RecaptchaTrue;

class DirectorForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class, [
                'required' => false,
                'label' => 'director.name.title',
                'attr' => [
                    'placeholder' => 'director.name.placeholder',
                ]
            ])
            ->add('phone', TextType::class, [
                'required' => false,
                'label' => 'director.phone.title',
                'attr' => [
                    'placeholder' => 'director.phone.placeholder',
                ]
            ])
            ->add('email', EmailType::class, [
                'required' => false,
                'label' => 'director.email.title',
                'attr' => [
                    'placeholder' => 'director.email.placeholder',
                ]
            ])
            ->add('message', TextareaType::class, [
                'required' => true,
                'label' => 'director.message.title',
            ])
            ->add('recaptcha', EWZRecaptchaType::class, [
                'label' => false,
                'required' => false,
                'constraints' => [
                    new RecaptchaTrue()
                ],
            ])
            ->add('file1', FileType::class, [
                'label' => false,
                'required' => false,
                'constraints' => [
                    new Assert\File([
                        'maxSize' => '5Mi',
                    ])
                ]
            ]);
        /* ->add('file2', FileType::class, [
                'label' => false,
                'required' => false,
                'constraints' => [
                    new Assert\File([
                        'maxSize' => '5Mi',
                    ])
                ]
            ])
            ->add('file3', FileType::class, [
                'label' => false,
                'required' => false,
                'constraints' => [
                    new Assert\File([
                        'maxSize' => '5Mi',
                    ])
                ]
            ])
            ->add('captcha', CaptchaType::class, [
                'label' => 'director.captcha.title',
                'required' => true,
            ])*/
            ;
    }
}
