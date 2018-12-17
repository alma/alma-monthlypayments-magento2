<?php
/**
 * 2018 Alma / Nabla SAS
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
 * @author    Alma / Nabla SAS <contact@getalma.eu>
 * @copyright 2018 Alma / Nabla SAS
 * @license   https://opensource.org/licenses/MIT The MIT License
 *
 */

namespace Alma\MonthlyPayments\Observer\Admin;

use Alma\MonthlyPayments\Helpers\Availability;
use Alma\MonthlyPayments\Model\Ui\ConfigProvider;
use Magento\Config\Model\ResourceModel\Config as ResourceConfig;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class ApiKeyObserver implements ObserverInterface
{
    /**
     * @var Availability
     */
    private $availabilityHelper;
    /**
     * @var ResourceConfig
     */
    private $resourceConfig;

    public function __construct(
        Availability $availabilityHelper,
        ResourceConfig $resourceConfig
    ) {
        $this->availabilityHelper = $availabilityHelper;
        $this->resourceConfig = $resourceConfig;
    }

    public function execute(Observer $observer)
    {
        $map = [
            'live' => 'test',
            'test' => 'live'
        ];

        $modeToTest = $map[$observer->getData('mode')];

        $configPath = 'payment/' . ConfigProvider::CODE  . '/fully_configured';
        if ($this->availabilityHelper->canConnectToAlma($modeToTest)) {
            $this->resourceConfig->saveConfig($configPath, 1);
        } else {
            $this->resourceConfig->saveConfig($configPath, 0);
        }
    }
}
