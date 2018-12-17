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

namespace Alma\MonthlyPayments\Gateway\Config;

class Config extends \Magento\Payment\Gateway\Config\Config {
    public function get($field, $default = null, $storeId = null)
    {
        $value = parent::getValue($field, $storeId);

        if ($value === null) {
            $value = $default;
        }

        return $value;
    }

    public function canLog()
    {
        return (bool)(int)$this->get('debug', false);
    }

    public function getActiveMode()
    {
        return  $this->get('api_mode', 'test');
    }

    public function getActiveAPIKey() {
        $mode = $this->getActiveMode();

        switch ($mode) {
            case 'live':
                $apiKeyType = 'live_api_key';
                break;
            default:
                $apiKeyType = 'test_api_key';
        }

        return $this->get($apiKeyType);
    }

    public function getLiveKey()
    {
        return $this->get('live_api_key', '');
    }

    public function getTestKey()
    {
        return $this->get('test_api_key', '');
    }

    public function needsAPIKeys()
    {
        return empty(trim($this->getLiveKey())) || empty(trim($this->getTestKey()));
    }

    public function getEligibilityMessage()
    {
        return $this->get('eligibility_message');
    }

    public function getNonEligibilityMessage()
    {
        return $this->get('non_eligibility_message');
    }

    public function showEligibilityMessage()
    {
        return (bool)(int)$this->get('show_eligibility_message');
    }

    public function getPaymentButtonTitle()
    {
        return $this->get('title');
    }

    public function getPaymentButtonDescription()
    {
        return $this->get('description');
    }

    public function isFullyConfigured()
    {
        return !$this->needsAPIKeys() && (bool)(int)$this->get('fully_configured', false);
    }
}
