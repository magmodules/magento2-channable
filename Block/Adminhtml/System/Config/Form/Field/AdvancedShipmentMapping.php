<?php
/*
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Block\Adminhtml\System\Config\Form\Field;

use Exception;
use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Framework\DataObject;
use Magento\Framework\View\Element\BlockInterface as ElementBlockInterface;
use Magmodules\Channable\Api\Log\RepositoryInterface as LogRepository;

/**
 * Represents a table for advanced shipping method mapping in the admin configuration
 */
class AdvancedShipmentMapping extends AbstractFieldArray
{
    public const OPTION_PATTERN = 'option_%s';
    public const SELECTED = 'selected="selected"';
    public const RENDERERS = [
        'channel' => Renderer\Channels::class,
        'method' => Renderer\ShippingMethods::class,
    ];

    private array $renderers = [];
    private LogRepository $logger;

    public const COLUMN_CHANNEL = 'channel';
    public const COLUMN_CHANNABLE_CARRIER = 'channable_carrier';
    public const COLUMN_METHOD = 'method';

    public function __construct(
        Context $context,
        LogRepository $logger,
        array $data = []
    ) {
        $this->logger = $logger;
        parent::__construct($context, $data);
    }

    /**
     * Prepare columns for rendering.
     *
     * @return void
     */
    protected function _prepareToRender()
    {
        $this->addColumn(self::COLUMN_CHANNEL, [
            'label' => (string)__('Channel'),
            'class' => 'required-entry',
            'renderer' => $this->getRenderer('channel'),
        ]);
        $this->addColumn(self::COLUMN_CHANNABLE_CARRIER, [
            'label' => (string)__('Carrier'),
            'class' => 'required-entry',
        ]);
        $this->addColumn(self::COLUMN_METHOD, [
            'label' => (string)__('Magento Method'),
            'class' => 'required-entry',
            'renderer' => $this->getRenderer('method'),
        ]);
        $this->_addAfter = false;
        $this->_addButtonLabel = (string)__('Add');
    }

    /**
     * Get renderer for a specific column type.
     *
     * @param string $type
     * @return ElementBlockInterface
     */
    public function getRenderer(string $type): ElementBlockInterface
    {
        if (!isset($this->renderers[$type])) {
            try {
                $this->renderers[$type] = $this->getLayout()->createBlock(
                    self::RENDERERS[$type],
                    '',
                    ['data' => ['is_render_to_js_template' => true]]
                );
            } catch (Exception $e) {
                $this->logger->addErrorLog('RendererCreationError', $e->getMessage());
            }
        }

        return $this->renderers[$type];
    }

    /**
     * Prepare extra attributes for array row rendering.
     *
     * @param DataObject $row
     * @return void
     */
    protected function _prepareArrayRow(DataObject $row)
    {
        $options = [];
        foreach (array_keys(self::RENDERERS) as $element) {
            if ($elementData = $row->getData($element)) {
                $renderer = $this->getRenderer($element);
                $options[sprintf(self::OPTION_PATTERN, $renderer->calcOptionHash($elementData))] = self::SELECTED;
            }
        }
        $row->setData('option_extra_attrs', $options);
    }
}