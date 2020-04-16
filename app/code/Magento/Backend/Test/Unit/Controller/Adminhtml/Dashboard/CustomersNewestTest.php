<?php declare(strict_types=1);
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Backend\Test\Unit\Controller\Adminhtml\Dashboard;

use Magento\Backend\Block\Dashboard\Tab\Customers\Newest;
use Magento\Backend\Controller\Adminhtml\Dashboard\CustomersNewest;

/**
 * Test for \Magento\Backend\Controller\Adminhtml\Dashboard\CustomersNewest
 */
class CustomersNewestTest extends AbstractTestCase
{
    public function testExecute()
    {
        $this->assertExecute(
            CustomersNewest::class,
            Newest::class
        );
    }
}
