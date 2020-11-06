<?php

namespace Postroyka\AppBundle\Form;

use Submarine\ReviewsBundle\Entity\Review;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReviewForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('username', Type\TextType::class, [
                'label' => 'review.username.title',
                'required' => true,
                'attr' => [
                    'placeholder' => 'review.username.placeholder',
                ]
            ])
            ->add('rating', Type\IntegerType::class, [
                'label' => 'review.rating.title',
                'required' => true,
            ])
            ->add('comment', Type\TextareaType::class, [
                'label' => 'review.comment.title',
                'required' => true,
                'attr' => [
                    'placeholder' => 'review.comment.placeholder',
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Review::class,
        ]);
    }
}
