<?php

namespace Microdesign\ExcelProductUpdate\Service;

use BigBridge\ProductImport\Api\Data\ConfigurableProduct;
use BigBridge\ProductImport\Api\Data\Product;
use BigBridge\ProductImport\Api\Data\SimpleProduct;
use BigBridge\ProductImport\Api\ImportConfig;
use BigBridge\ProductImport\Api\Importer;
use BigBridge\ProductImport\Api\ImporterFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Eav\Model\Attribute;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\ManagerInterface;

class ImportProduct
{
    const DEFAULT_ATTRIBUTES = ["short_description", "description"];

    const SKIP_ATTRIBUTES = ["name"];

    const XML_MICRODESIGN_ATTRIBUTE = "microdesign/import/key_attribute";

    /**
     * @var \BigBridge\ProductImport\Api\ImporterFactory
     */
    private $importerFactory;

    /**
     * @var \BigBridge\ProductImport\Api\ImportConfig
     */
    private $importConfig;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    private $messageManager;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var \Microdesign\ExcelProductUpdate\Service\AttributeSelector
     */
    private $attributeSelector;

    /**
     * @var array
     */
    private $attributes;

    /**
     * @var array
     */
    private $selectAttributes;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * ImportProduct constructor.
     *
     * @param \BigBridge\ProductImport\Api\ImporterFactory              $importerFactory
     * @param \BigBridge\ProductImport\Api\ImportConfig                 $importConfig
     * @param \Magento\Framework\Message\ManagerInterface               $messageManager
     * @param \Magento\Catalog\Api\ProductRepositoryInterface           $productRepository
     * @param \Microdesign\ExcelProductUpdate\Service\AttributeSelector $attributeSelector
     * @param \Magento\Framework\App\Config\ScopeConfigInterface        $scopeConfig
     * @param \Magento\Framework\Api\SearchCriteriaBuilder              $searchCriteriaBuilder
     */
    public function __construct(
        ImporterFactory $importerFactory,
        ImportConfig $importConfig,
        ManagerInterface $messageManager,
        ProductRepositoryInterface $productRepository,
        AttributeSelector $attributeSelector,
        ScopeConfigInterface $scopeConfig,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->importerFactory                          = $importerFactory;
        $this->importConfig                             = $importConfig;
        $this->messageManager                           = $messageManager;
        $this->productRepository                        = $productRepository;
        $this->attributeSelector                        = $attributeSelector;
        $this->scopeConfig                              = $scopeConfig;
        $this->searchCriteriaBuilder                    = $searchCriteriaBuilder;
        $this->attributes                               = $this->attributeSelector->getAttributes();
        $this->selectAttributes                         = $this->attributeSelector->getSelectAttributes();
        $this->importConfig->autoCreateOptionAttributes = array_values($this->selectAttributes);

        // a callback function to postprocess imported products
        $this->importConfig->resultCallback = function (Product $product) {
            if ($product->isOk()) {
                $this->messageManager->addSuccessMessage(
                    "Imported successfully " . $product->getSku()
                );
            } else {
                $this->messageManager
                    ->addErrorMessage(
                        "Imported failed " . $product->getSku() . " Errors:" . implode(',',
                            $product->getErrors())
                    );
            }
        };
    }

    /**
     * @param array $data
     *
     * @return bool
     * @throws \Exception
     */
    public function importProducts(array $data)
    {
        $importer = $this->importerFactory->createImporter($this->importConfig);


        $keyAttribute = $this->scopeConfig->getValue(self::XML_MICRODESIGN_ATTRIBUTE);
        if ($keyAttribute && $keyAttribute != 'sku') {
            $data = $this->addSkuToData($data, $keyAttribute);
        }

        if (!$this->validateAttributes($data)) {
            return false;
        }

        // Check if key is sku if true skip

        // if != sku get sku from attribute

        foreach ($data as $product) {
            if (!isset($product['sku'])) {
                $this->messageManager
                    ->addErrorMessage(
                        "Product without sku found, product data: " . implode(', ', $product)
                    );
            }
            $importer = $this->importProduct($importer, $product);
        }

        // process any remaining products in the pipeline
        $importer->flush();
        return true;
    }

    private function importProduct(Importer $importer, array $productArray): Importer
    {
        try {
            $magentoProduct = $this->productRepository->get($productArray['sku']);
        } catch (NoSuchEntityException $e) {
            $this->messageManager->addErrorMessage("Imported failed " . $productArray['sku'] . " Errors: Product not found in Magento");

            return $importer;
        }

        switch ($magentoProduct->getTypeId()):
            case ('configurable'):
                $importer->importConfigurableProduct(
                    $this->setupProduct(new ConfigurableProduct($magentoProduct->getSku()), $productArray)
                );
                break;
            case('simple'):
                $importer->importSimpleProduct(
                    $this->setupProduct(new SimpleProduct($magentoProduct->getSku()), $productArray)
                );
                break;
        endswitch;

        return $importer;
    }

    /**
     * @param ConfigurableProduct|SimpleProduct $product
     * @param array                             $productArray
     *
     * @return \BigBridge\ProductImport\Api\Data\ConfigurableProduct|\BigBridge\ProductImport\Api\Data\SimpleProduct
     */
    private function setupProduct($product, array $productArray)
    {
        $global = $product->global();

        foreach ($productArray as $attribute => $value) {
            if ($attribute == 'sku' || in_array($attribute, self::SKIP_ATTRIBUTES)) {
                continue;
            }

            // if SELECT attribute
            if (in_array($attribute, $this->selectAttributes)) {
                $global->setSelectAttribute($attribute, $value);

                // if CUSTOM attribute
            } elseif (in_array($attribute, $this->attributes)) {
                $global->setCustomAttribute($attribute, $value);

                // if DEFAULT attribute
            } else {
                // Explode potential storeCode values
                $attribute = explode('|', $attribute);

                if (in_array($attribute[0], self::DEFAULT_ATTRIBUTES)) {
                    if (isset($attribute[1]) && strlen($attribute[1]) > 1) {
                        // TODO: Check if storeCode exists
                        $product->storeView($attribute[1])->setCustomAttribute($attribute[0], $value);
                    } else {
                        $global->setCustomAttribute($attribute[0]);
                    }

                }
            }
        }

        return $product;
    }

    /**
     * @param array $data
     *
     * @return bool
     */
    private function validateAttributes(array $data)
    {
        if (!count($data)) {
            $this->messageManager
                ->addErrorMessage(
                    "Import has no data"
                );
            return false;
        }
        $wrongAttributes = [];
        foreach (array_keys(reset($data)) as $attribute) {
            $attribute = explode('|', $attribute)[0];
            if (!in_array($attribute, $this->attributes)) {
                $wrongAttributes[] = $attribute;
            }
        }

        if (count($wrongAttributes)) {
            $this->messageManager
                ->addErrorMessage(
                    "These attributes do not exist: " . implode(', ', $wrongAttributes)
                );
            return false;
        }

        $this->messageManager
            ->addSuccessMessage(
                "All attributes validated!"
            );
        return true;
    }

    private function addSkuToData(array $data, $keyAttribute)
    {
        $keyValues = array_map( function($array) use ($keyAttribute) {
            $keyAttribute = strtolower($keyAttribute);
            return isset($array[$keyAttribute]) ? $array[$keyAttribute] : null;
        }, $data);
        // Filter un-found attributes
        $keyValues = array_filter($keyValues, function($array) { return $array; });

        $searchProducts = $this->searchCriteriaBuilder
            ->addFilter($keyAttribute, array_values($keyValues), 'IN')
            ->create();

        $products = $this->productRepository->getList($searchProducts);
        $attributeSkuCombination = [];
        foreach ($products->getItems() as $product) {
            $attributeSkuCombination[$product->getData($keyAttribute)] = $product->getSku();
        }

        foreach($data as $key => $dataProduct) {
            if (isset($attributeSkuCombination[$dataProduct[$keyAttribute]])) {
                $data[$key]['sku'] = $attributeSkuCombination[$dataProduct[$keyAttribute]];
            } else {
                $this->messageManager
                    ->addErrorMessage(
                        "Product with attribute " . $keyAttribute . " and value ". $data[$key][$keyAttribute] . " not found"
                    );
                unset($data[$key]);
            }
        }

        return $data;
    }
}
