<?php

namespace Alma\MonthlyPayments\Helpers;

use Alma\MonthlyPayments\Gateway\Config\Config;

class WidgetConfigHelper extends Config
{
    const WIDGET_POSITION = 'widget_position';
    const WIDGET_ACTIVE = 'widget_active';
    const WIDGET_CONTAINER = 'widget_container_css_selector';
    const WIDGET_PRICE_USE_QTY = 'widget_price_use_qty';
    const CUSTOM_WIDGET_POSITION = 'catalog.product.view.custom.alma.widget';
    const WIDGET_CONTAINER_PREPEND = 'widget_container_prepend';

    private $widgetContainer;

    /**
     * @return bool
     */
    public function showProductWidget(): bool
    {
        return ((bool)(int)$this->get(self::WIDGET_ACTIVE) && $this->getIsActive());
    }


    /**
     * @return string
     */
    public function getWidgetContainerSelector(): string
    {
        if (!$this->widgetContainer) {
            $this->widgetContainer =
                $this->get(self::WIDGET_CONTAINER);
        }
        return $this->widgetContainer;
    }

    /**
     * @return string used by javascript in view.phtml
     */
    public function useQuantityForWidgetPrice(): string
    {
        return ((bool)(int)$this->get(self::WIDGET_PRICE_USE_QTY) ? 'true' : 'false');
    }

    /**
     * @return string used by javascript in view.phtml
     */
    public function prependWidgetInContainer(): string
    {
        return ((bool)(int)$this->get(self::WIDGET_CONTAINER_PREPEND) == 0 ? 'true' : 'false');
    }

    /**
     * @return string used by javascript in view.phtml
     */
    public function isCustomWidgetPosition(): string
    {
        return ($this->getWidgetPosition() ==
        self::CUSTOM_WIDGET_POSITION ? 'true' : 'false');
    }

    /**
     * @return string
     */
    public function getWidgetPosition(): string
    {
        return $this->get(self::WIDGET_POSITION);
    }
}
