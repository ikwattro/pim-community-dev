<?php
namespace Pim\Bundle\ProductBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Pim\Bundle\TranslationBundle\Entity\AbstractTranslation;

/**
 * Export profile translation entity
 *
 * @author    Romain Monceau <romain@akeneo.com>
 * @copyright 2012 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *
 * @ORM\Entity()
 * @ORM\Table(name="pim_export_profile_translation")
 *
 */
class ExportProfileTranslation extends AbstractTranslation
{
    /**
     * All required columns are mapped through inherited superclass
     */

    /**
     * Change foreign key to add constraint and work with basic entity
     *
     * @ORM\ManyToOne(targetEntity="ExportProfile", inversedBy="translations")
     * @ORM\JoinColumn(name="foreign_key", referencedColumnName="id")
     */
    protected $foreignKey;


    /**
     * @var string $name
     *
     * @ORM\Column(name="label", type="string", length=100)
     */
    protected $name;

    /**
     * Set name
     *
     * @param string $name
     *
     * @return ProductAttribute
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get the name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
}
