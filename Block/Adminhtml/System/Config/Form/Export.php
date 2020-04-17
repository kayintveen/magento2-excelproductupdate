<?php
namespace Microdesign\ExcelProductUpdate\Block\Adminhtml\System\Config\Form;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class Export extends Field
{
    /**
     * @var string
     */
    protected $_template = 'Microdesign_ExcelProductUpdate::system/config/button/export.phtml';
    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    private $request;

    /**
     * Checker constructor.
     *
     * @param Context $context
     * @param array   $data
     */
    public function __construct(
        Context $context,
        array $data = []
    ) {
        $this->request = $context->getRequest();
        parent::__construct($context, $data);
    }

    /**
     * @param AbstractElement $element
     *
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();

        return parent::render($element);
    }

    /**
     * @param AbstractElement $element
     *
     * @return string
     */
    public function _getElementHtml(AbstractElement $element)
    {
        return $this->_toHtml();
    }

    /**
     * @return string
     */
    public function getAjaxUrl()
    {
        return $this->getUrl('microdesign/export/product');
    }

    /**
     * @return mixed
     */
    public function getButtonHtml()
    {
        try {
            $buttonData = ['id' => 'product_export', 'label' => __('Export products to CSV')];
            $button = $this->getLayout()->createBlock('Magento\Backend\Block\Widget\Button')->setData($buttonData);
            return $button->toHtml();
        } catch (\Exception $e) {
            return '';
        }
    }
}
