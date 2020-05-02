<?php

namespace Microdesign\ExcelProductUpdate\Service;

use BigBridge\ProductImport\Api\Data\ConfigurableProduct;
use BigBridge\ProductImport\Api\Data\Product;
use BigBridge\ProductImport\Api\Data\SimpleProduct;
use BigBridge\ProductImport\Api\ImportConfig;
use BigBridge\ProductImport\Api\Importer;
use BigBridge\ProductImport\Api\ImporterFactory;
use Exception;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\ManagerInterface;

class ParseCsv
{
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
     * @var \Microdesign\ExcelProductUpdate\Service\ImportProduct
     */
    private $importProduct;

    /**
     * ParseCsv constructor.
     *
     * @param \BigBridge\ProductImport\Api\ImporterFactory          $importerFactory
     * @param \BigBridge\ProductImport\Api\ImportConfig             $importConfig
     * @param \Magento\Framework\Message\ManagerInterface           $messageManager
     * @param \Microdesign\ExcelProductUpdate\Service\ImportProduct $importProduct
     */
    public function __construct(
        ImporterFactory $importerFactory,
        ImportConfig $importConfig,
        ManagerInterface $messageManager,
        ImportProduct $importProduct
    ) {
        $this->importerFactory = $importerFactory;
        $this->importConfig    = $importConfig;
        $this->messageManager  = $messageManager;
        $this->importProduct = $importProduct;
    }

    /**
     * @param string $filePath
     *
     * @throws \Exception
     */
    public function execute(string $filePath): void
    {
        $data = $this->parseCsvFile($filePath);
        $this->importProduct->importProducts($data);
    }

    /**
     * @param string $filePath
     *
     * @return array
     * @throws \Exception
     */
    private function parseCsvFile(string $filePath): array
    {
        if (!file_exists($filePath)) {
            throw new Exception("CSV not found", 500, null);
        }

        $handler   = fopen($filePath, 'r');
        $result    = [];
        $delimiter = $this->detectDelimiter($handler);
        $header    = fgetcsv($handler, 0, $delimiter);

        while ($data = fgetcsv($handler, 0, $delimiter)) {
            $row = [];
            foreach ($header as $i => $key) {
                $key = strtolower($key);
                // Skip empty columns
                if ($key == '') {
                    continue;
                }
                $row[$key] = $data[$i];
            }
            $result[] = $row;
        }

        return $result;
    }

    /**
     * @param $fh
     *
     * @return mixed
     */
    private function detectDelimiter($fh)
    {
        $delimiters = ["\t", ";", "|", ","];
        $data_2     = [];
        $delimiter  = $delimiters[0];
        foreach ($delimiters as $d) {
            $data_1 = fgetcsv($fh, 4096, $d);
            if (count($data_1) > count($data_2)) {
                $delimiter = $d;
                $data_2    = $data_1;
            }
            rewind($fh);
        }

        return $delimiter;
    }

}
