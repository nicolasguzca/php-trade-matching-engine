<?php


namespace App\ValueObjects;


class MemoryOrderBook implements IOrderBook
{
    private string $orderType;

    private array $items = [];

    /**
     * OrderBook constructor.
     * @param string $orderType
     */
    public function __construct(string $orderType)
    {
        $this->orderType = $orderType;
    }

    public function add(Order $order): void
    {

        if (isset($this->items[$order->getPriceFee()->toFloat()])) {
            /** @var MemoryOrderGroup $orderGroup */
            $orderGroup = $this->items[$order->getPriceFee()->toFloat()];
            $orderGroup->add($order);
        } else {
            $orderGroup = new MemoryOrderGroup($order);
            $this->items[$orderGroup->getPriceFee()->toFloat()] = $orderGroup;
            $this->sortItems();
        }
    }

    public function getTop(): ?Order
    {
        /** @var MemoryOrderGroup|bool $item */
        $item = reset($this->items);
        if ($item) {
            $order = $item->getTop();
            if ($order)
                return $order;
        }

        return null;
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function remove(Order $order): bool
    {
        $result = false;
        if (isset($this->items[$order->getPriceFee()->toFloat()])) {
            /** @var MemoryOrderGroup $orderGroup */
            $orderGroup = $this->items[$order->getPriceFee()->toFloat()];
            $result = $orderGroup->remove($order);

            if ($orderGroup->isEmpty()) {
                unset($this->items[$orderGroup->getPriceFee()->toFloat()]);
            }
        }
        return $result;
    }

    private function sortItems()
    {
        if ($this->orderType == Order::TYPE_SELL)
            ksort($this->items, SORT_NUMERIC);
        else
            krsort($this->items, SORT_NUMERIC);
    }
}
