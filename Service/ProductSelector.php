<?php

namespace Microdesign\ExcelProductUpdate\Service;

use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;

class ProductSelector
{
    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var array
     */
    private $attributes;

    /**
     * @var \Magento\Eav\Api\AttributeRepositoryInterface
     */
    private $attributeRepository;

    /**
     * @var array
     */
    private $selectAttributes;

    /**
     * @var \Microdesign\ExcelProductUpdate\Service\AttributeSelector
     */
    private $attributeSelector;

    /**
     * ProductSelector constructor.
     *
     * @param \Magento\Catalog\Api\ProductRepositoryInterface           $productRepository
     * @param \Magento\Framework\Api\SearchCriteriaBuilder              $searchCriteriaBuilder
     * @param \Magento\Eav\Api\AttributeRepositoryInterface             $attributeRepository
     * @param \Microdesign\ExcelProductUpdate\Service\AttributeSelector $attributeSelector
     */
    public function __construct(
        ProductRepositoryInterface $productRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        AttributeRepositoryInterface $attributeRepository,
        AttributeSelector $attributeSelector
    ) {
        $this->productRepository     = $productRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->attributeRepository   = $attributeRepository;
        $this->attributeSelector     = $attributeSelector;
    }

    /**
     * @param array $attributes
     * @param bool  $onlyConfigurable
     *
     * @return array
     */
    public function getProducts(array $attributes, bool $onlyConfigurable)
    {
        $this->attributes = $attributes;
        sort($this->attributes);
        $this->selectAttributes = $this->attributeSelector->getSelectAttributes();
        $products               = $this->productRepository->getList($this->getSearch($onlyConfigurable));

        if (!$products->getItems()) {
            return [];
        }

        return $this->formatProducts(
            $products->getItems()
        );
    }

    private function formatProducts(array $getItems)
    {
        return array_map(function ($product) {
            /** @var \Magento\Catalog\Model\Product $product */
            $values['sku']  = $product->getSku();
            $values['name'] = $product->getName();
            foreach ($this->attributes as $attribute) {
                if (in_array($attribute, $this->selectAttributes)) {
                    $value              = $product->getData($attribute) ? $product->getAttributeText($attribute) : null;
                    $values[$attribute] = is_object($value) ? $product->getData($attribute) : $value;
                } else {
                    $values[$attribute] = $product->getData($attribute);
                }
            }

            return $values;
        }, $getItems);
    }

    /**
     * @param bool $onlyConfigurable
     *
     * @return \Magento\Framework\Api\SearchCriteria
     */
    private function getSearch(bool $onlyConfigurable)
    {
        $search = $this->searchCriteriaBuilder;

        if ($onlyConfigurable) {
            $search = $search->addFilter('type_id', 'configurable');
        }

        return $search->create();
    }

}
