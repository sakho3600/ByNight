<?php

namespace TBN\MainBundle\Form\Extension;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;

/**
 * 
 * 
 * @author Guillaume Sainthillier
 */
class CollectionExtension extends AbstractTypeExtension
{
    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options) {
        if ($form->getConfig()->hasAttribute('prototype')) {
            $view->vars['prototype'] = $form->getConfig()->getAttribute('prototype')->createView($view);
        }

	$view->vars['group_class']              = $options['group_class'];
        $view->vars['base_class']               = $options['base_class'];
        $view->vars['widget_class']             = $options['widget_class'];
    }

    /**
     * {@inheritDoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver) {

        $resolver->setDefaults(array(
            'allow_add' => true,
            'group_class' => null,
            'allow_delete' => true,
            'prototype' => true,
            'prototype_name' => '__name__',
            'widget_class' => 'widget_collection',
            'base_class' => null,
            'by_reference' => false, //GARANTIE D'APPEL de addXXX sur l'objet parent de la collection
        ));

        $resolver->setNormalizers(array(
            'options' => function (Options $options, $value) {
                $value['block_name'] = 'entry';

                return $value;
            }
        ));
    }


    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return 'collection';
    }
}