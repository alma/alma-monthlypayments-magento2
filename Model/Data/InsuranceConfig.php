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

namespace Alma\MonthlyPayments\Model\Data;

class InsuranceConfig
{
    private $pageActivated;
    private $activated;
    private $cartActivated;
    private $popupActivated;
    /**
     * @var string
     */
    private $arrayData;
    /**
     * @var bool
     */
    private $isAllowed;

    public function __construct(
        bool   $isAllowed,
        string $jsonData
    ) {
        $this->isAllowed = $isAllowed;
        $this->activated = false;
        $this->pageActivated = false;
        $this->cartActivated = false;
        $this->popupActivated = false;
        $this->arrayData = json_decode($jsonData, true);
        if ($this->arrayData) {
            foreach ($this->arrayData as $key => $value) {
                if (!is_bool($this->arrayData[$key])) {
                    continue;
                }
                switch ($key) {
                    case 'isInsuranceActivated':
                        $this->activated = $this->arrayData[$key];
                        break;
                    case 'isInsuranceOnProductPageActivated':
                        $this->pageActivated = $this->arrayData[$key];
                        break;
                    case 'isAddToCartPopupActivated':
                        $this->popupActivated = $this->arrayData[$key];
                        break;
                    case 'isInCartWidgetActivated':
                        $this->cartActivated = $this->arrayData[$key];

                        break;
                    default:
                        break;
                }
            }
        }
    }

    public function isActivated(): bool
    {
        return $this->activated;
    }

    public function isPageActivated(): bool
    {
        return $this->pageActivated;
    }

    public function isCartActivated(): bool
    {
        return $this->cartActivated;
    }

    public function isPopupActivated(): bool
    {
        return $this->popupActivated;
    }

    public function getArrayConfig(): array
    {
        return (array)$this->arrayData;
    }

    public function isAllowed(): bool
    {
        return $this->isAllowed;
    }
}
