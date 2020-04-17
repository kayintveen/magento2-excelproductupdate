<?php
namespace Microdesign\ExcelProductUpdate\Service;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\Filesystem;

class CsvGenerator
{
    const DEFAULT_FILENAME = 'export_products.csv';

    const DEFAULT_PATH = 'export';

    /**
     * @var \Magento\Framework\Filesystem
     */
    private $filesystem;

    /**
     * @var \Magento\Framework\App\Response\Http\FileFactory
     */
    private $fileFactory;

    /**
     * @var mixed
     */
    private $content;

    /**
     * @var string
     */
    private $fileName;


    public function __construct(
        Filesystem $filesystem,
        FileFactory $fileFactory
    ) {
        $this->filesystem = $filesystem;
        $this->fileFactory = $fileFactory;
        $this->fileName = self::DEFAULT_FILENAME;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface
     * @throws \Magento\Framework\Exception\FileSystemException
     * @throws \Exception
     */
    public function create()
    {
        $directory = $this->filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
        $directory->create('export');
        $filePath = self::DEFAULT_PATH . '/' . $this->fileName;
        $stream = $directory->openFile($filePath, 'w+');
        $stream->lock();

        $i=0;
        foreach ($this->content as $product)
        {
            $i++;
            if ($i == 1) {
                $stream->writeCsv(array_keys($product));
            }
            $stream->writeCsv($product);
        }

        $content = [];
        $content['type'] = 'filename'; // must keep filename
        $content['value'] = $filePath;
        $content['rm'] = '1'; //remove csv from var folder

        return $this->fileFactory->create(
            $this->fileName,
            $content,
            DirectoryList::VAR_DIR
        );
    }

    /**
     * @param array $content
     *
     * @return \Microdesign\ExcelProductUpdate\Service\CsvGenerator
     */
    public function setContent(array $content): CsvGenerator
    {
        $this->content = $content;
        return $this;
    }
}
