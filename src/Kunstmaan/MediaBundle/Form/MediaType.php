<?php

namespace Kunstmaan\KMediaBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;

/**
 * class to define the form to upload a picture
 *
 */
class MediaType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options)
    {
        $builder
            ->add('media', 'file')
        ;
    }

    public function getName()
    {
        return 'kunstmaan_kmediabundle_filetype';
    }
}

?>