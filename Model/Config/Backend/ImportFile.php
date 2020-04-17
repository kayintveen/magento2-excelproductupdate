<?php

namespace Microdesign\ExcelProductUpdate\Model\Config\Backend;

use Magento\Config\Model\Config\Backend\File;
use Magento\Config\Model\Config\Backend\File\RequestData\RequestDataInterface;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Filesystem;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\MediaStorage\Model\File\UploaderFactory;
use Microdesign\ExcelProductUpdate\Service\ParseCsv;

/**
 * Class ImportFile
 */
class ImportFile extends File
{
    /**
     * @var ParseCsv
     */
    protected $csvImport;


    /**
     * @var ManagerInterface
     */
    protected $messageManager;


    /**
     * ImportFile constructor.
     *
     * @param Context              $context
     * @param Registry             $registry
     * @param ScopeConfigInterface $config
     * @param TypeListInterface    $cacheTypeList
     * @param UploaderFactory      $uploaderFactory
     * @param RequestDataInterface $requestData
     * @param Filesystem           $filesystem
     * @param AbstractResource     $resource
     * @param AbstractDb           $resourceCollection
     * @param ParseCsv             $csvImport
     * @param ManagerInterface     $messageManager
     * @param array                $data
     *
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        UploaderFactory $uploaderFactory,
        RequestDataInterface $requestData,
        Filesystem $filesystem,
        AbstractResource $resource,
        AbstractDb $resourceCollection,
        ParseCsv $csvImport,
        ManagerInterface $messageManager,
        array $data = []
    ) {
        parent::__construct($context, $registry, $config, $cacheTypeList, $uploaderFactory, $requestData, $filesystem, $resource, $resourceCollection, $data);
        $this->_mediaDirectory = $filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
        $this->csvImport = $csvImport;
        $this->messageManager = $messageManager;
    }

    /**
     * @return string[]
     */
    public function getAllowedExtensions()
    {
        return ['csv'];
    }


    /**
     * @return $this|\Magento\Config\Model\Config\Backend\File
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function beforeSave()
    {
        $value = $this->getValue();
        $file = $this->getFileData();

        if (!empty($file)) {

            try {
                $result         = $this->uploadFile($file);
                $this->processFile($result);
                $this->messageManager->addSuccessMessage('Succesfully imported');
            } catch (\Exception $e) {
                throw new \Magento\Framework\Exception\LocalizedException(__('%1', $e->getMessage()));
            }

            $filename = $result['file'];
            if ($filename) {
                if ($this->_addWhetherScopeInfo()) {
                    $filename = $this->_prependScopeInfo($filename);
                }

                $this->setValue($filename);
            }
        } else {
            if (is_array($value) && !empty($value['delete'])) {
                $this->setValue('');
            }
        }

        return $this;
    }


    /**
     * @param $result
     */
    protected function processFile($result)
    {
        $filePath = $result['path'] . $result['file'];
        $this->csvImport->execute($filePath);
    }

    /**
     * @param array $file
     *
     * @return array
     * @throws \Exception
     */
    private function uploadFile(array $file)
    {
        $uploadDir = $this->_filesystem->getDirectoryRead(DirectoryList::VAR_DIR)->getAbsolutePath();
        $uploader = $this->_uploaderFactory->create(['fileId' => $file]);
        $uploader->setAllowedExtensions($this->_getAllowedExtensions());
        $uploader->setAllowRenameFiles(true);
        $uploader->addValidateCallback('size', $this, 'validateMaxSize');
        return $uploader->save($uploadDir);
    }

}
