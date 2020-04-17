<?php

namespace Microdesign\ExcelProductUpdate\Model\Source;

use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\Data\OptionSourceInterface;

class Attributes implements OptionSourceInterface
{
    /**
     * @var \Magento\Eav\Api\AttributeRepositoryInterface
     */
    private $attributeRepository;
    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;
    /**
     * @var \Magento\Framework\Api\SortOrderBuilder
     */
    private $orderBuilder;

    /**
     * Attributes constructor.
     *
     * @param \Magento\Eav\Api\AttributeRepositoryInterface $attributeRepository
     * @param \Magento\Framework\Api\SearchCriteriaBuilder  $searchCriteriaBuilder
     * @param \Magento\Framework\Api\SortOrderBuilder       $orderBuilder
     */
    public function __construct(
        AttributeRepositoryInterface $attributeRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        SortOrderBuilder $orderBuilder
    ) {
        $this->attributeRepository   = $attributeRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->orderBuilder          = $orderBuilder;
    }

    /**
     * Return array of options as value-label pairs
     *
     * @return array Format: array(array('value' => '<value>', 'label' => '<label>'), ...)
     */
    public function toOptionArray()
    {
        return array_map(function ($attribute) {
            /** @var $attribute \Magento\Eav\Model\Attribute */
            return [
                'value' => $attribute->getAttributeCode(),
                'label' => $attribute->getDefaultFrontendLabel()
            ];
        }, $this->getAttributesArray());
    }

    /**
     * Gets array of all attributes existing
     *
     * @return array
     */
    private function getAttributesArray(): array
    {
        $order = $this->orderBuilder
            ->setField('attribute_code')
            ->create();

        $searchCriteria = $this->searchCriteriaBuilder
            ->addSortOrder($order)
            ->create();

        $attributes = $this->attributeRepository->getList(
            ProductAttributeInterface::ENTITY_TYPE_CODE,
            $searchCriteria
        );

        return $attributes->getTotalCount() ? $attributes->getItems() : [];
    }
}
