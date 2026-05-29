<?php

namespace Tests\Unit;

use App\Support\SabreFareBrandPresenter;
use PHPUnit\Framework\TestCase;

class SabreFareBrandPresenterTest extends TestCase
{
    public function test_formats_inline_brand_with_airline_name(): void
    {
        $label = SabreFareBrandPresenter::fromPricingBlock([
            'fare' => [
                'validatingCarrierCode' => 'EK',
                'passengerInfoList' => [
                    [
                        'passengerInfo' => [
                            'fareComponents' => [
                                ['brandName' => 'ECO SAVER'],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $this->assertSame('Emirates Saver', $label);
    }

    public function test_resolves_brand_from_fare_component_desc_reference(): void
    {
        $label = SabreFareBrandPresenter::fromPricingBlock(
            [
                'fare' => [
                    'validatingCarrierCode' => 'EK',
                    'passengerInfoList' => [
                        [
                            'passengerInfo' => [
                                'fareComponents' => [
                                    ['ref' => 8],
                                    ['ref' => 20],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'fareComponentDescs' => [
                    ['id' => 8, 'brand' => ['brandName' => 'ECO SAVER']],
                    ['id' => 20, 'brand' => ['brandName' => 'ECO SAVER']],
                ],
            ],
        );

        $this->assertSame('Emirates Saver', $label);
    }

    public function test_resolves_brand_from_brand_desc_reference(): void
    {
        $label = SabreFareBrandPresenter::fromPricingBlock(
            [
                'fare' => [
                    'validatingCarrierCode' => 'EK',
                    'passengerInfoList' => [
                        [
                            'passengerInfo' => [
                                'fareComponents' => [
                                    ['brand' => ['ref' => 3]],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'brandDescs' => [
                    ['id' => 3, 'brandName' => 'ECO FLEX PLUS'],
                ],
            ],
        );

        $this->assertSame('Emirates Flex Plus', $label);
    }

    public function test_returns_null_when_brand_data_missing(): void
    {
        $label = SabreFareBrandPresenter::fromPricingBlock([
            'fare' => [
                'validatingCarrierCode' => 'EK',
                'passengerInfoList' => [
                    [
                        'passengerInfo' => [
                            'fareComponents' => [
                                ['segments' => [['segment' => ['bookingCode' => 'K']]]],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $this->assertNull($label);
    }
}
