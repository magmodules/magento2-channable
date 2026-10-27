<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magmodules\Channable\Plugin\Diagnostic;

use Magento\Quote\Model\Quote\Item as QuoteItem;
use Magmodules\Channable\Api\Log\RepositoryInterface as LogRepository;

/**
 * Refuse a quote item that is about to become its own parent, and record who tried.
 *
 * Quote::getAllVisibleItems() skips every item that carries a parent item, which is how a child of
 * a configurable stays out of the order in its own right. An item pointing at itself is no such
 * child, but it is skipped all the same, and the quote then reaches submit with nothing visible in
 * it: an order that is never created and never reported as failed. setParentItem() ignores null, so
 * this cannot be undone afterwards - it has to be refused as it happens. The parent lives in memory
 * only, which is why nothing in the database shows afterwards that this occurred.
 */
class LogParentItem
{
    private LogRepository $logger;

    public function __construct(LogRepository $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param QuoteItem $subject
     * @param QuoteItem|null $parentItem
     * @return array
     */
    public function aroundSetParentItem(QuoteItem $subject, callable $proceed, $parentItem = null)
    {
        if ($parentItem === null || $parentItem !== $subject) {
            return $proceed($parentItem);
        }

        $callers = [];
        foreach (array_slice(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 25), 1) as $frame) {
            if (!empty($frame['class']) && str_contains($frame['class'], 'Interceptor')) {
                continue;
            }
            $callers[] = sprintf(
                '%s%s%s:%s',
                $frame['class'] ?? '',
                $frame['type'] ?? '',
                $frame['function'] ?? '',
                $frame['line'] ?? '?'
            );
        }

        $this->logger->addErrorLog('SelfParent', sprintf(
            'Item for product %s was about to become its own parent, which would have kept it out '
            . 'of the order. Refused. Set via %s',
            (string)$subject->getProductId(),
            implode(' <- ', array_slice($callers, 0, 12))
        ));

        // Not proceeding is the fix: an item that parents itself is skipped by
        // getAllVisibleItems(), and setParentItem(null) cannot undo it afterwards.
        return $subject;
    }
}
