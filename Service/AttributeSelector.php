<?php
namespace Microdesign\ExcelProductUpdate\Service;

use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;

class AttributeSelector
{
    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var \Magento\Eav\Api\AttributeRepositoryInterface
     */
    private $attributeRepository;

    /**
     * @var array
     */
    private $attributes;

    public function __construct(
        SearchCriteriaBuilder $searchCriteriaBuilder,
        AttributeRepositoryInterface $attributeRepository
    ) {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->attributeRepository = $attributeRepository;
        $this->attributes = [];
    }

    /**
     * @return array
     */
    public function getAttributes(): array
    {
        if (!$this->attributes || empty($this->attributes) || !is_array($this->attributes) || !count($this->attributes)) {
            $this->attributes = $this->fetchAttributes();
        }

        return array_map(function ($attribute) {
            /** @var \Magento\Eav\Model\Attribute $attribute */
            return $attribute->getAttributeCode();
        }, $this->attributes);
    }

    /**
     * @return array
     */
    public function getSelectAttributes(): array
    {
        if (!$this->attributes || empty($this->attributes) || !is_array($this->attributes) || !count($this->attributes)) {
            $this->attributes = $this->fetchAttributes();
        }

        $attributes = array_filter($this->attributes, function ($product) {
             /* @var \Magento\Catalog\Model\Product $product */
             return $product->getData('frontend_input') == 'select';
        });

        return array_map(function ($attribute) {
            /** @var \Magento\Eav\Model\Attribute $attribute */
            return $attribute->getAttributeCode();
        }, $attributes);
    }

    /**
     * @return array|\Magento\Eav\Api\Data\AttributeSearchResultsInterface
     */
    private function fetchAttributes()
    {
        $searchCriteria = $this->searchCriteriaBuilder
            //->addFilter('frontend_input', 'select', 'eq')
            ->setCurrentPage(1)
            ->create();

        $attributes = $this->attributeRepository->getList(
            ProductAttributeInterface::ENTITY_TYPE_CODE,
            $searchCriteria
        );

        if (!$attributes->getTotalCount()) {
            return [];
        }

        return $attributes->getItems();
    }
}
