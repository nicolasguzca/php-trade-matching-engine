<?php


namespace App\ValueObjects;


use Illuminate\Support\Facades\Redis;

class RedisOrderBook implements IOrderBook
{
    private string $orderType;

    private string $key;

    private function storeOrder(Order $order)
    {
        Redis::set($this->getOrderKey($order->getIdentifier()), Order::serialize($order));
    }

    private function getOrder(string $orderIdentifier)
    {
        $value = Redis::get($this->getOrderKey($orderIdentifier));

        if (empty($value)) {
            throw new \Exception('tes');
            dd($orderIdentifier, $value);
        }
        return Order::unserialize($value);
    }

    private function getOrderKey(string $orderIdentifier)
    {
        return $this->key . '::' . $orderIdentifier;
    }

    /**
     * OrderBook constructor.
     * @param string $source
     * @param string $destination
     * @param string $orderType
     */
    public function __construct(string $source, string $destination, string $orderType)
    {
        $this->orderType = $orderType;
        $this->key = 'orderbooks::' . $source . '-' . $destination . '::' . $orderType;
    }

    public function add(Order $order): void
    {
        $this->storeOrder($order);
        $this->storeOrderInSet($order);
    }

    private function storeOrderInSet(Order $order)
    {
        Redis::command('zadd', [$this->key, $order->getPriceFee()->toString(), $order->getIdentifier()]);
    }

    public function getTop(): ?Order
    {
        $command = 'zpopmin';
        if ($this->orderType == Order::TYPE_BUY)
            $command = 'zpopmax';
        $value = Redis::command($command, [$this->key]);
        if (!$value)
            return null;


        $keys = array_keys($value);
        $orderIdentifier = reset($keys);

        if (!is_int($orderIdentifier)) {
            dd($keys, $orderIdentifier, $value);
        }
        $order = $this->getOrder($orderIdentifier);

        if ($order) {
            $this->storeOrderInSet($order);
            return $order;
        }
        return null;
    }

    public function count(): int
    {
        return (int)Redis::command('zcount', [$this->key, '-inf', '+inf']);
    }

    public function remove(Order $order): bool
    {
        $count = Redis::command('zrem', [$this->key, $order->getIdentifier()]);
        Redis::del($this->getOrderKey($order->getIdentifier()));
        return $count == 1;
    }

}
