<?php

namespace TBN\UserBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PlaceType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder
            ->add('nom', 'text', [
                "required" => true,
                "label" => "Où ça ?",
                "attr" => [
                    "placeholder" => "Indiquez le nom du lieu"
                ]
            ])
           ->add('rue','text', [
                "required" => false,
            ])
            ->add('latitude','hidden', [
                "required" => false,
            ])
            ->add('longitude','hidden', [
                "required" => false,
            ])
            ->add('ville', 'text', [
                "label" => "Ville"
            ])
            ->add('codePostal','text', [
                "required" => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'TBN\AgendaBundle\Entity\Place'
        ]);
    }

    public function getName()
    {
        return 'tbn_place';
    }
}
