<?php


namespace App\ValueObjects;

use Decimal\Decimal;

class MemoryOrderGroup
{
    private array $orders = [];
    private Decimal $priceFee;

    /**
     * OrderGroup constructor.
     * @param Order $firstOrder
     */
    public function __construct(Order $firstOrder)
    {
        $this->add($firstOrder);
        $this->priceFee = $firstOrder->getPriceFee();
    }

    public function isEmpty()
    {
        return empty($this->orders);
    }

    public function add(Order $order)
    {
        $this->orders[$order->getIdentifier()] = $order;
    }

    public function remove(Order $order): bool
    {
        if (isset($this->orders[$order->getIdentifier()])) {
            unset($this->orders[$order->getIdentifier()]);
            return true;
        }
        return false;
    }

    public function getTop()
    {
        return reset($this->orders);
    }

    /**
     * @return Decimal
     */
    public function getPriceFee(): Decimal
    {
        return $this->priceFee;
    }


}

