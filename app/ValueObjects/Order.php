<?php


namespace App\ValueObjects;


use Decimal\Decimal;

class Order
{
    const TYPE_SELL = 'SELL';
    const TYPE_BUY = 'BUY';
    private string $type;
    private Decimal $amount;
    private Decimal $dealtAmount;
    private Decimal $priceFee;
    private string $identifier;

    /**
     * Order constructor.
     * @param string $identifier
     * @param string $type
     * @param Decimal $amount
     * @param Decimal $priceFee
     * @param Decimal|null $dealtAmount
     */
    public function __construct(
        string $identifier,
        string $type,
        Decimal $amount,
        Decimal $priceFee,
        Decimal $dealtAmount = null
    )
    {
        $this->type = $type;
        $this->amount = $amount;
        $this->priceFee = $priceFee;
        $this->identifier = $identifier;
        $this->dealtAmount = $dealtAmount ?? new Decimal(0);
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return Decimal
     */
    public function getAmount(): Decimal
    {
        return $this->amount;
    }

    /**
     * @return Decimal
     */
    public function getPriceFee(): Decimal
    {
        return $this->priceFee;
    }

    /**
     * @return string
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    /**
     * @return Decimal
     */
    public function getDealtAmount(): Decimal
    {
        return $this->dealtAmount;
    }

    public function addDealtAmount(Decimal $dealtAmount)
    {
        if ($this->getRemainingAmount() < $dealtAmount)
            throw new \Exception('setting dealt amount is more that remaining price');
        $this->dealtAmount += $dealtAmount;
    }

    public function getRemainingAmount(): Decimal
    {
        return $this->amount - $this->dealtAmount;
    }

    public function isDealt(): bool
    {
        if ($this->getRemainingAmount()->isZero())
            return true;
        return false;
    }

    public function isOpen(): bool
    {
        return !$this->isDealt();
    }

//    public function serialize(){
//
//    }

    public static function serialize(Order $order){
        return "{$order->getIdentifier()}-{$order->getType()}-{$order->getAmount()->toString()}-{$order->getPriceFee()->toString()}-{$order->getDealtAmount()->toString()}";
    }

    public static function unserialize($value)
    {
        $values=explode('-',$value);
        if(count($values)!=5)
            throw new \Exception('tes');
        return new Order(
            $values[0],
            $values[1],
            new Decimal($values[2]),
            new Decimal($values[3]),
            new Decimal($values[4]),
        );
    }
}
