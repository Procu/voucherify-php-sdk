<?php

namespace Voucherify;

/**
 * Class VoucherBuilder
 *
 * @package Voucherify
 */
class VoucherBuilder
{
    private $voucher;

    public function __construct()
    {
        $this->voucher = (object)[];
    }

    public function setCode($code)
    {
        $this->voucher->code = $code;

        return $this;
    }

    public function setCampaign($campaign)
    {
        $this->voucher->campaign = $campaign;

        return $this;
    }

    public function setCategory($category)
    {
        $this->voucher->category = $category;

        return $this;
    }

    public function setAmountDiscount($amountOff)
    {
        $this->voucher->discount = (object)[
            "type"       => "AMOUNT",
            "amount_off" => $amountOff * 100,
        ];

        return $this;
    }

    public function setPercentDiscount($percentOff)
    {
        $this->voucher->discount = (object)[
            "type"        => "PERCENT",
            "percent_off" => $percentOff,
        ];

        return $this;
    }

    public function setUnitDiscount($unitOff, $unitType)
    {
        $this->voucher->discount = (object)[
            "type"      => "UNIT",
            "unit_off"  => $unitOff,
            "unit_type" => $unitType,
        ];

        return $this;
    }

    public function setStartDate($startDate)
    {
        if ($startDate instanceof \DateTime) {
            $startDate = $startDate->format(\DateTime::ISO8601);
        }
        $this->voucher->start_date = $startDate;

        return $this;
    }

    public function setExpirationDate($expirationDate)
    {
        if ($expirationDate instanceof \DateTime) {
            $expirationDate = $expirationDate->format(\DateTime::ISO8601);
        }
        $this->voucher->expiration_date = $expirationDate;

        return $this;
    }

    public function setRedemptionLimit($redemptionLimit)
    {
        $this->voucher->redemption = (object)[
            "quantity" => $redemptionLimit,
        ];

        return $this;
    }

    public function setActive($active)
    {
        $this->voucher->active = $active;
    }

    public function build()
    {
        return $this->voucher;
    }
}
