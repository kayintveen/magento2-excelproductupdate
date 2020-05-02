<?php

namespace Microdesign\ExcelProductUpdate\Controller\Adminhtml\Export;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Microdesign\ExcelProductUpdate\Service\CsvGenerator;
use Microdesign\ExcelProductUpdate\Service\ProductSelector;

class Product extends Action
{
    const XML_MICRODESIGN_ATTRIBUTES = 'microdesign/export/attributes';
    const XML_MICRODESIGN_ONLY_CONFIGURABLE = 'microdesign/export/configurable';

    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    private $resultJsonFactory;
    /**
     * @var \Microdesign\ExcelProductUpdate\Service\ProductSelector
     */
    private $productSelector;
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var \Microdesign\ExcelProductUpdate\Service\CsvGenerator
     */
    private $csvGenerator;

    /**
     * Feed constructor.
     *
     * @param \Magento\Framework\Controller\Result\JsonFactory        $jsonFactory
     * @param \Microdesign\ExcelProductUpdate\Service\ProductSelector $productSelector
     * @param \Magento\Framework\App\Config\ScopeConfigInterface      $scopeConfig
     * @param \Microdesign\ExcelProductUpdate\Service\CsvGenerator    $csvGenerator
     * @param Context                                                 $context
     */
    public function __construct(
        JsonFactory $jsonFactory,
        ProductSelector $productSelector,
        ScopeConfigInterface $scopeConfig,
        CsvGenerator $csvGenerator,
        Context $context
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $jsonFactory;
        $this->productSelector = $productSelector;
        $this->scopeConfig = $scopeConfig;
        $this->csvGenerator = $csvGenerator;
    }


    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function execute()
    {
        $products = $this->productSelector->getProducts(
            explode(',',$this->scopeConfig->getValue(self::XML_MICRODESIGN_ATTRIBUTES)),
            $this->scopeConfig->getValue(self::XML_MICRODESIGN_ONLY_CONFIGURABLE)
        );

        return $this->csvGenerator->setContent($products)->create();
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Microdesign_ExcelProductUpdate::config');
    }
}
