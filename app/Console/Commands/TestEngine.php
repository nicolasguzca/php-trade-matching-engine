<?php

namespace App\Console\Commands;

use App\Services\TradeEngine;
use App\ValueObjects\Order;
use App\ValueObjects\MemoryOrderBook;
use Decimal\Decimal;
use Illuminate\Console\Command;

class TestEngine extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:engine';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $drivers = [
            TradeEngine::DRIVER_MEMORY,
            TradeEngine::DRIVER_REDIS,
        ];
        $count = 1000;


        foreach ($drivers as $driver) {
            $engine = new TradeEngine($driver, 'btc', 'toman');

            $last = microtime(true);
            $startInsert = $last;
            $orders = [];
            for ($i = 1; $i <= $count; $i++) {
                $orders[] = new Order(
                    $i,
                    $i % 2 !== 0 ? Order::TYPE_BUY : Order::TYPE_SELL,
                    new Decimal(1000 + $i%1000),
                    new Decimal(1000 + $i%1000),
                );
            }
            $current = microtime(true);
            $this->output->text('generated ' . count($orders) . ' orders  in : ' . round(($current - $startInsert) * 1000, 6) . ' ms)');

            $this->output->section('Start matching in ' . $driver);
            $startMatch = microtime(true);

            foreach ($orders as $order) {
                $engine->addOrder($order);
            }

            $current = microtime(true);
            $this->output->text('matched ' . count($orders) . ' in : ' . round(($current - $startMatch) * 1000, 6) . ' ms)');

//        $startSnapShot = microtime(true);
//
//        $startRestore = microtime(true);
//        $engine = TradeEngine::load();
//        $current = microtime(true);
//
////        $this->output->text('snapshot engine in: ' . round(($startRestore - $startSnapShot) * 1000, 6) . ' ms)');
//        $this->output->text('restore snapshot engine in: ' . round(($current- $startRestore ) * 1000, 6) . ' ms)');
            //list($sell, $buy) = $engine->getOrderBooks();
            /** @var MemoryOrderBook $sell */
            //dd($sell->getTop()->getPriceFee()->toString(), $buy->getTop()->getPriceFee()->toString());

            $string = '';
            foreach ($engine->getOrderBookReport() as $key => $value) {
                $string .= "{$key}: {$value}" . PHP_EOL;
            }
            $this->info($string);
        }

        return 0;
    }
}
