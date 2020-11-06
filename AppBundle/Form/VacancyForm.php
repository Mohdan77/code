<?php

namespace Postroyka\AppBundle\Form;

use EWZ\Bundle\RecaptchaBundle\Form\Type\EWZRecaptchaType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;
use EWZ\Bundle\RecaptchaBundle\Validator\Constraints\IsTrue as RecaptchaTrue;

class VacancyForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class, [
                'required' => true,
                'label' => 'vacancy.name.title',
                'attr' => [
                    'placeholder' => 'vacancy.name.placeholder',
                ]
            ])
            ->add('phone', TextType::class, [
                'required' => true,
                'label' => 'vacancy.phone.title',
                'attr' => [
                    'placeholder' => 'vacancy.phone.placeholder',
                ]
            ])
            ->add('job', TextType::class, [
                'required' => true,
                'label' => 'vacancy.job.title',
                'attr' => [
                    'placeholder' => 'vacancy.job.placeholder',
                ]
            ])
            ->add('message', TextareaType::class, [
                'required' => true,
                'label' => 'vacancy.message.title',
                'attr' => [
                    'placeholder' => 'vacancy.message.placeholder',
                ]
            ])
            ->add('file1', FileType::class, [
                'label' => false,
                'required' => false,
                'constraints' => [
                    new Assert\File([
                        'maxSize' => '5Mi',
                    ])
                ]
            ])
            ->add('recaptcha', EWZRecaptchaType::class, [
                'label' => false,
                'required' => false,
                'constraints' => [
                    new RecaptchaTrue()
                ],
            ]);
    }
}
