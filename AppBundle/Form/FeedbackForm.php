<?php

namespace Postroyka\AppBundle\Form;

use EWZ\Bundle\RecaptchaBundle\Form\Type\EWZRecaptchaType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;
use EWZ\Bundle\RecaptchaBundle\Validator\Constraints\IsTrue as RecaptchaTrue;

class FeedbackForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class, [
                'required' => false,
                'label' => 'feedback.name.title',
                'attr' => [
                    'placeholder' => 'feedback.name.placeholder',
                ]
            ])
            ->add('phone', TextType::class, [
                'required' => false,
                'label' => 'feedback.phone.title',
                'attr' => [
                    'placeholder' => 'feedback.phone.placeholder',
                    'data-type' => 'phone'
                ]
            ])
            ->add('email', EmailType::class, [
                'required' => false,
                'label' => 'feedback.email.title',
                'attr' => [
                    'placeholder' => 'feedback.email.placeholder',
                ]
            ])
            ->add('message', TextareaType::class, [
                'required' => true,
                'label' => 'feedback.message.title',
            ])
//            ->add('file', FileType::class, [
//                'required' => false,
//                'label' => false,
//                'constraints' => [
//                    new Assert\File([
//                        'maxSize' => '4Mi'
//                    ])
//                ]
//            ])
            ->add('captcha', EWZRecaptchaType::class, [
                'label' => false,
                'required' => false,
                'constraints' => [
                    new RecaptchaTrue()
                ],
            ]);
    }
}