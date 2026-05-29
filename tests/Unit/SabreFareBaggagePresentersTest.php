<?php

namespace Tests\Unit;

use App\Support\SabreBaggagePresenter;
use App\Support\SabreFareRulesPresenter;
use PHPUnit\Framework\TestCase;

class SabreFareBaggagePresentersTest extends TestCase
{
    public function test_baggage_presenter_formats_checked_allowance_by_route(): void
    {
        $result = SabreBaggagePresenter::fromPricingBlock(
            [
                'fare' => [
                    'passengerInfoList' => [[
                        'passengerInfo' => [
                            'fareComponents' => [
                                ['beginAirport' => 'DXB', 'endAirport' => 'KHI'],
                                ['beginAirport' => 'KHI', 'endAirport' => 'DXB'],
                            ],
                            'baggageInformation' => [
                                [
                                    'provisionType' => 'A',
                                    'airlineCode' => 'EK',
                                    'segments' => [['id' => 0]],
                                    'allowance' => ['ref' => 4],
                                ],
                                [
                                    'provisionType' => 'A',
                                    'airlineCode' => 'EK',
                                    'segments' => [['id' => 1]],
                                    'allowance' => ['ref' => 4],
                                ],
                            ],
                        ],
                    ]],
                ],
            ],
            [
                'baggageAllowanceDescs' => [
                    ['id' => 4, 'weight' => 25, 'unit' => 'kg'],
                ],
            ],
        );

        $this->assertSame('25 kg checked', $result['summary']);
        $this->assertCount(2, $result['checked']);
        $this->assertSame('DXB → KHI', $result['checked'][0]['route']);
        $this->assertSame('25 kg', $result['checked'][0]['allowance']);
    }

    public function test_fare_rules_presenter_builds_component_and_notes(): void
    {
        $result = SabreFareRulesPresenter::fromPricingBlock(
            [
                'fare' => [
                    'validatingCarrierCode' => 'EK',
                    'eTicketable' => true,
                    'lastTicketDate' => '2026-06-01',
                    'lastTicketTime' => '19:20',
                    'passengerInfoList' => [[
                        'passengerInfo' => [
                            'passengerType' => 'ADT',
                            'nonRefundable' => false,
                            'fareComponents' => [
                                ['ref' => 8, 'beginAirport' => 'DXB', 'endAirport' => 'KHI'],
                            ],
                        ],
                    ]],
                ],
            ],
            [
                'fareComponentDescs' => [[
                    'id' => 8,
                    'fareBasisCode' => 'LLXEPAE1',
                    'fareRule' => 'AET5',
                    'cabinCode' => 'Y',
                    'notValidBefore' => '2026-06-01',
                    'notValidAfter' => '2026-06-01',
                    'brand' => ['brandName' => 'ECO SAVER'],
                ]],
            ],
        );

        $this->assertTrue($result['refundable']);
        $this->assertSame('Refundable', $result['refund_label']);
        $this->assertSame('EK', $result['validating_carrier']);
        $this->assertSame('LLXEPAE1', $result['components'][0]['fare_basis']);
        $this->assertSame('AET5', $result['components'][0]['fare_rule']);
        $this->assertStringContainsString('Ticket must be issued by 2026-06-01 19:20', $result['notes'][1]);
    }
}
