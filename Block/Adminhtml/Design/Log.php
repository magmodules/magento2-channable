<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Block\Adminhtml\Design;

use Magento\Config\Model\Config\CommentInterface;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\View\Element\AbstractBlock;
use Magento\Framework\View\Element\Context;

/**
 * Log comment block class
 */
class Log extends AbstractBlock implements CommentInterface
{

    /**
     *
     */
    const LOG_FILE = '%s/log/channable/order.log';
    /**
     *
     */
    const LOG_CONTENT = 'data:text/plain;base64,%s';

    /**
     * @var DirectoryList
     */
    private $dir;

    /**
     * @var File
     */
    private $file;

    /**
     * Log constructor.
     * @param DirectoryList $dir
     * @param File $file
     * @param Context $context
     * @param array $data
     */
    public function __construct(
        DirectoryList $dir,
        File $file,
        Context $context,
        array $data = []
    ) {
        $this->dir = $dir;
        $this->file = $file;
        parent::__construct($context, $data);
    }

    /**
     * @param string $elementValue
     * @return string
     * @throws FileSystemException
     */
    public function getCommentText($elementValue): string
    {
        $text = "Write errors and orders to the Channable log file located in /var/log/channable/order.log<br>";
        if ($this->isLogExists()) {
            $text .= "Or download the last 100 log lines <a href='" . $this->prepareLogText() . "'>here</a>";
        }
        return $text;
    }

    /**
     * @return bool
     * @throws FileSystemException
     */
    private function isLogExists(): bool
    {
        $logFile = sprintf(self::LOG_FILE, $this->dir->getPath('var'));
        return $this->file->isExists($logFile);
    }

    /**
     * @return string
     * @throws FileSystemException
     */
    private function prepareLogText()
    {
        $logFile = sprintf(self::LOG_FILE, $this->dir->getPath('var'));
        $fileContent = file($logFile);
        if (count($fileContent) > 100) {
            $fileContent = array_slice($fileContent, -100, 100, true);
            return sprintf(
                self::LOG_CONTENT,
                base64_encode(implode("\n", $fileContent))
            );
        } else {
            return sprintf(
                self::LOG_CONTENT,
                base64_encode(implode("\n", $fileContent))
            );
        }
    }
}
