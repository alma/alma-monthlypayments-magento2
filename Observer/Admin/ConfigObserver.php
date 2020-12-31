<?php
/**
 * 2018-2020 Alma SAS
 *
 * THE MIT LICENSE
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated
 * documentation files (the "Software"), to deal in the Software without restriction, including without limitation
 * the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and
 * to permit persons to whom the Software is furnished to do so, subject to the following conditions:
 * The above copyright notice and this permission notice shall be included in all copies or substantial portions of the
 * Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE
 * WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF
 * CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * @author    Alma SAS <contact@getalma.eu>
 * @copyright 2018-2020 Alma SAS
 * @license   https://opensource.org/licenses/MIT The MIT License
 */


namespace Alma\MonthlyPayments\Observer\Admin;

use Alma\MonthlyPayments\Helpers\Availability;
use Alma\MonthlyPayments\Model\Ui\ConfigProvider;
use Magento\Config\Model\ResourceModel\Config as ResourceConfig;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Message\Manager as MessageManager;
use Magento\Framework\Message\MessageInterface;
use Psr\Log\LoggerInterface;

class ConfigObserver implements ObserverInterface
{
    /**
     * @var ResourceConfig
     */
    private $resourceConfig;
    /**
     * @var Availability
     */
    private $availabilityHelper;
    /**
     * @var MessageManager
     */
    private $messageManager;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        ResourceConfig $resourceConfig,
        Availability $availabilityHelper,
        MessageManager $messageManager,
        LoggerInterface $logger
    ) {
        $this->resourceConfig = $resourceConfig;
        $this->availabilityHelper = $availabilityHelper;
        $this->messageManager = $messageManager;
        $this->logger = $logger;
    }

    public function execute(Observer $observer)
    {
        // Update the fully_configured flag depending on whether we can correctly connect to Alma with provided API keys
        $configPath = 'payment/' . ConfigProvider::CODE  . '/fully_configured';
        $fully_configured = (int) $this->availabilityHelper->canConnectToAlma();
        $this->resourceConfig->saveConfig($configPath, $fully_configured, ScopeConfigInterface::SCOPE_TYPE_DEFAULT, 0);
    }
}
