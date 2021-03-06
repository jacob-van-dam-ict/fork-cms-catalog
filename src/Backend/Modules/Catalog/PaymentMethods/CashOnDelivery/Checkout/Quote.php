<?php

namespace Backend\Modules\Catalog\PaymentMethods\CashOnDelivery\Checkout;

use Backend\Modules\Catalog\PaymentMethods\Base\Checkout\Quote as BaseQuote;

class Quote extends BaseQuote
{
    /**
     * {@inheritdoc}
     */
    public function getQuote(): array
    {
        if (!$this->isAllowedPaymentMethod()) {
            return [];
        }

        $name = $this->getSetting('name');

        if ($this->getSetting('price')) {
            $name .= ' (&euro; '. number_format($this->getSetting('price'), 2, ',', '.') .')';
        }

        return [
            $this->name => [
                'label' =>  $this->getSetting('name'),
                'name' => $name,
                'price' => (float) $this->getSetting('price'),
            ]
        ];
    }
}
