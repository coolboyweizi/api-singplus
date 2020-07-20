<?php

namespace SingPlus\SMS;

class Balance
{
  /**
   * @var string transport
   */
  protected $transport;

  /**
   * @var string
   */
  protected $balance;

  /**
   * @var string
   */
  protected $currency;

  public function __construct(string $transport, string $balance, string $currency)
  {
    $this->transport = $transport;
    $this->balance = $balance;
    $this->currency = strtoupper($currency);
  }

  /**
   * @return string       account balance
   */
  public function getBalance() : string
  {
    return $this->balance;
  }

  /**
   * @return string
   */
  public function getCurrency() : string
  {
    return $this->currency;
  }

  /**
   * @return string
   */
  public function getTransport() : string
  {
    return $this->transport;
  }
}
