<?php


namespace App\ValueObjects;


interface IOrderBook
{
    public function add(Order $order): void;

    public function getTop(): ?Order;

    public function count(): int;

    public function remove(Order $order): bool;
}
