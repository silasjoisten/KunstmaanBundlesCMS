<?php

namespace Kunstmaan\FormBundle\Entity\FormSubmissionFieldTypes;

use Doctrine\ORM\Mapping as ORM;
use Kunstmaan\FormBundle\Entity\FormSubmissionField;
use Kunstmaan\FormBundle\Form\StringFormSubmissionType;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * The StringFormSubmissionField can be used to store string values to a FormSubmission
 *
 * @ORM\Entity
 * @ORM\Table(name="kuma_string_form_submission_fields")
 */
#[ORM\Entity]
#[ORM\Table(name: 'kuma_string_form_submission_fields')]
class StringFormSubmissionField extends FormSubmissionField
{
    /**
     * @ORM\Column(name="sfsf_value", type="string")
     * @Assert\Length(max=255)
     */
    #[ORM\Column(name: 'sfsf_value', type: 'string')]
    protected $value;

    /**
     * Returns the default form type for this FormSubmissionField
     *
     * @return string
     */
    public function getDefaultAdminType()
    {
        return StringFormSubmissionType::class;
    }

    /**
     * Return a string representation of this FormSubmissionField
     *
     * @return string
     */
    public function __toString()
    {
        $value = $this->getValue();

        return !empty($value) ? $value : '';
    }

    /**
     * Returns the current value of this field
     *
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Sets the current value of this field
     *
     * @param string $value
     *
     * @return StringFormSubmissionField
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }
}
