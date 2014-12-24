<?php

namespace Pim\Bundle\CatalogBundle\Updater\Setter;

use Pim\Bundle\CatalogBundle\Builder\ProductBuilderInterface;
use Pim\Bundle\CatalogBundle\Entity\Repository\AttributeOptionRepository;
use Pim\Bundle\CatalogBundle\Model\AttributeInterface;
use Pim\Bundle\CatalogBundle\Builder\ProductBuilder;
use Pim\Bundle\CatalogBundle\Model\ProductInterface;
use Pim\Bundle\CatalogBundle\Updater\InvalidArgumentException;
use Pim\Bundle\CatalogBundle\Validator\AttributeValidatorHelper;

/**
 * Sets a simple select value in many products
 *
 * @author    Olivier Soulet <olivier.soulet@akeneo.com>
 * @copyright 2014 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class SimpleSelectValueSetter extends AbstractValueSetter
{
    /** @var AttributeOptionRepository */
    protected $attrOptionRepository;

    /**
     * @param ProductBuilderInterface   $productBuilder
     * @param AttributeValidatorHelper  $attrValidatorHelper
     * @param AttributeOptionRepository $attrOptionRepository
     * @param array                     $supportedTypes
     */
    public function __construct(
        ProductBuilderInterface $productBuilder,
        AttributeValidatorHelper $attrValidatorHelper,
        AttributeOptionRepository $attrOptionRepository,
        array $supportedTypes
    ) {
        parent::__construct($productBuilder, $attrValidatorHelper);
        $this->attrOptionRepository = $attrOptionRepository;
        $this->supportedTypes       = $supportedTypes;
    }

    /**
     * {@inheritdoc}
     */
    public function setValue(array $products, AttributeInterface $attribute, $data, $locale = null, $scope = null)
    {
        $this->checkLocaleAndScope($attribute, $locale, $scope, 'simple select');
        $this->checkData($attribute, $data);

        $attributeOption = $this->attrOptionRepository
            ->findOneBy(['code' => $data['code'], 'attribute' => $attribute]);

        if (null === $attributeOption) {
            throw InvalidArgumentException::arrayInvalidKey(
                $attribute->getCode(),
                'code',
                sprintf('Option with code "%s" does not exist', $data['code']),
                'setter',
                'simple select',
                gettype($data)
            );
        }

        foreach ($products as $product) {
            $this->setOption($attribute, $product, $attributeOption, $locale, $scope);
        }
    }

    /**
     * Check if data are valid
     *
     * @param AttributeInterface $attribute
     * @param mixed              $data
     */
    protected function checkData(AttributeInterface $attribute, $data)
    {
        if (!is_array($data)) {
            throw InvalidArgumentException::arrayExpected(
                $attribute->getCode(),
                'setter',
                'simple select',
                gettype($data)
            );
        }

        if (!array_key_exists('attribute', $data)) {
            throw InvalidArgumentException::arrayKeyExpected(
                $attribute->getCode(),
                'attribute',
                'setter',
                'simple select',
                gettype($data)
            );
        }

        if (!array_key_exists('code', $data)) {
            throw InvalidArgumentException::arrayKeyExpected(
                $attribute->getCode(),
                'code',
                'setter',
                'simple select',
                gettype($data)
            );
        }
    }

    /**
     * Set option into the product value
     *
     * @param AttributeInterface $attribute
     * @param ProductInterface   $product
     * @param array              $attributeOption
     * @param string             $locale
     * @param string             $scope
     */
    protected function setOption(
        AttributeInterface $attribute,
        ProductInterface $product,
        $attributeOption,
        $locale,
        $scope
    ) {
        $value = $product->getValue($attribute->getCode(), $locale, $scope);
        if (null === $value) {
            $value = $this->productBuilder->addProductValue($product, $attribute, $locale, $scope);
        }
        $value->setOption($attributeOption);
    }
}
