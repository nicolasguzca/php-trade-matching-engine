<?php


namespace App\Services;


use App\ValueObjects\IOrderBook;
use App\ValueObjects\Order;
use App\ValueObjects\MemoryOrderBook;
use App\ValueObjects\RedisOrderBook;
use Decimal\Decimal;
use Illuminate\Support\Facades\Cache;

class TradeEngine
{
    private IOrderBook $sellOrderBook;
    private IOrderBook $buyOrderBook;
    private string $source;
    private string $destination;

    const DRIVER_REDIS = 'REDIS';
    const DRIVER_MEMORY = 'MEMORY';


    /**
     * TradeEngine constructor.
     * @param string $source
     * @param string $destination
     */
    public function __construct(string $driver, string $source, string $destination)
    {
        $this->source = $source;
        $this->destination = $destination;

        //TODO we should add restore orderbook theory here i think
        if ($driver == self::DRIVER_MEMORY) {
            $this->sellOrderBook = new MemoryOrderBook(Order::TYPE_SELL);
            $this->buyOrderBook = new MemoryOrderBook(Order::TYPE_BUY);
        } else {
            $this->sellOrderBook = new RedisOrderBook($source, $destination, Order::TYPE_SELL);
            $this->buyOrderBook = new RedisOrderBook($source, $destination, Order::TYPE_BUY);
        }
    }

    public static function save($engine)
    {
        Cache::put('engine', $engine);
    }

    public static function load()
    {
        return Cache::get('engine');
    }

    public function addOrder(Order $order)
    {
        //Insert Order to orderbooks
        //$start = microtime(true);
        $this->insertOrderToOrderBooks($order);
        //$inserted = microtime(true);

        //Match orders
        $this->match();
        //$matched = microtime(true);
    }

    public function cancelOrder(Order $order)
    {
        if ($order->getType() == Order::TYPE_SELL) {
            $this->sellOrderBook->remove($order);
        }
        if ($order->getType() == Order::TYPE_BUY) {
            $this->buyOrderBook->remove($order);
        }
    }

    public function getOrderBookReport()
    {
        return [
            'sell-order-book-count' => $this->sellOrderBook->count(),
            'buy-order-book-count' => $this->buyOrderBook->count(),
            'top-sell-price' => $this->sellOrderBook->getTop() ? $this->sellOrderBook->getTop()->getPriceFee()->toString() : null,
            'top-buy-price' => $this->buyOrderBook->getTop() ? $this->buyOrderBook->getTop()->getPriceFee()->toString() : null
        ];
    }


    public function getOrderBooks()
    {
        return [
            $this->sellOrderBook,
            $this->buyOrderBook,
        ];
    }

    private function match()
    {
        //TODO we should handle market orders for production!

        do {
            $topSellOrder = $this->sellOrderBook->getTop();

            if (!$topSellOrder) {
                break;
            }

            $topBuyOrder = $this->buyOrderBook->getTop();

            if (!$topBuyOrder) {
                break;
            }

            //TODO handle market for production
            if ($topSellOrder->getPriceFee() > $topBuyOrder->getPriceFee()) {
                //echo "{$topSellOrder->getPriceFee()->toString()} > {$topBuyOrder->getPriceFee()->toString()} => False".PHP_EOL;
                break;
            }
            //echo "{$topSellOrder->getPriceFee()->toString()} > {$topBuyOrder->getPriceFee()->toString()} => True".PHP_EOL;

            /** @var Decimal $dealAmount */
            $dealAmount = min($topBuyOrder->getRemainingAmount(), $topSellOrder->getRemainingAmount());


            $topSellOrder->addDealtAmount($dealAmount);
            $topBuyOrder->addDealtAmount($dealAmount);

            if ($topSellOrder->isDealt()) {
                //Totaly dealt
                $this->sellOrderBook->remove($topSellOrder);

            } else {
                //partially dealt
                $this->sellOrderBook->add($topSellOrder);

                //update deal!
            }
            if ($topBuyOrder->isDealt()) {
                //Totaly dealt
                $this->buyOrderBook->remove($topBuyOrder);
            } else {
                //partially dealt
                $this->buyOrderBook->add($topBuyOrder);
            }

            //dd($topSellOrder,$topBuyOrder,$dealAmount,$this->buyOrderBook->getTop(),$this->sellOrderBook->getTop());
        } while (true); //TODO change true to could handle atleast on order!

    }

    private function insertOrderToOrderBooks(Order $order)
    {
        if ($order->getType() == Order::TYPE_BUY) {
            $this->buyOrderBook->add($order);
        }
        if ($order->getType() == Order::TYPE_SELL) {
            $this->sellOrderBook->add($order);
        }
    }


}
