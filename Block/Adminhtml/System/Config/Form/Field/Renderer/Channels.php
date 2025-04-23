<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Block\Adminhtml\System\Config\Form\Field\Renderer;

use Magento\Framework\View\Element\Context;
use Magento\Framework\View\Element\Html\Select;
use Magmodules\Channable\Model\Config\Source\Channels as ChannelsSource;

class Channels extends Select
{

    private ChannelsSource $channels;

    public function __construct(
        Context $context,
        ChannelsSource $channels,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->channels = $channels;
    }

    /**
     * Render block HTML.
     *
     * @return string
     */
    protected function _toHtml(): string
    {
        if (empty($this->getOptions())) {
            $this->addOption(null, '-- select --');
            foreach ($this->channels->toOptionArray() as $channel) {
                $this->addOption($channel['value'], $channel['label']);
            }
        }

        return parent::_toHtml();
    }

    /**
     * Sets the name for the input element.
     *
     * @param string $value
     * @return $this
     */
    public function setInputName(string $value): self
    {
        $this->setData('name', $value);
        return $this;
    }
}
