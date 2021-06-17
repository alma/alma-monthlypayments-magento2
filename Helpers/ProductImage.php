<?php
/**
 * 2018-2019 Alma SAS
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
 * @copyright 2018-2019 Alma SAS
 * @license   https://opensource.org/licenses/MIT The MIT License
 */

namespace Alma\MonthlyPayments\Helpers;

use Magento\Catalog\Block\Product\ListProduct;
use Magento\Catalog\Helper\ImageFactory;
use Magento\Framework\App\Area;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\App\Emulation;
use Magento\Store\Model\StoreManagerInterface;

class ProductImage
{
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var Emulation
     */
    private $appEmulation;
    /**
     * @var ImageFactory
     */
    private $imageFactory;

    /**
     * ProductImage constructor.
     * @param StoreManagerInterface $storeManager
     * @param Emulation $appEmulation
     * @param ImageFactory $imageFactory
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        Emulation $appEmulation,
        ImageFactory $imageFactory
    )
    {
        $this->storeManager = $storeManager;
        $this->appEmulation = $appEmulation;
        $this->imageFactory = $imageFactory;
    }

    /**
     * @param $product
     * @param string $imageType
     * @param array $attributes
     * @return mixed
     */
    public function getImageUrl($product, $imageType = 'product_page_image_small', $attributes = [])
    {
        try {
            $storeId = $this->storeManager->getStore()->getId();
        } catch (NoSuchEntityException $e) {
            return null;
        }
        $this->appEmulation->startEnvironmentEmulation($storeId, Area::AREA_FRONTEND, true);
        $imageUrl = $this->imageFactory->create()->init($product, $imageType, $attributes)->getUrl();
        $this->appEmulation->stopEnvironmentEmulation();
        return $imageUrl;
    }

}
