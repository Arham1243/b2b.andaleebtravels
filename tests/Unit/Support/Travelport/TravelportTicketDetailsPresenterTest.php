<?php

namespace Tests\Unit\Support\Travelport;

use App\Models\B2bFlightBooking;
use App\Support\Travelport\TravelportAirPricePresenter;
use App\Support\Travelport\TravelportTicketDetailsPresenter;
use PHPUnit\Framework\TestCase;

class TravelportTicketDetailsPresenterTest extends TestCase
{
    public function test_maps_etr_ticket_coupons_and_passenger(): void
    {
        $booking = new B2bFlightBooking([
            'sabre_record_locator' => 'ABC123',
        ]);

        $parsed = [
            'Body' => [
                'AirRetrieveDocumentRsp' => [
                    'ETR' => [
                        '@attributes' => [
                            'PlatingCarrier' => 'EK',
                            'ProviderLocatorCode' => 'ABC123',
                            'IssuedDate' => '2026-06-08T10:00:00.000+04:00',
                            'Refundable' => 'false',
                            'Taxes' => 'AED325',
                        ],
                        'BookingTraveler' => [
                            'BookingTravelerName' => [
                                '@attributes' => [
                                    'First' => 'JOHN',
                                    'Last' => 'DOE',
                                ],
                            ],
                        ],
                        'AirPricingInfo' => [
                            '@attributes' => [
                                'TotalPrice' => 'AED525',
                                'BasePrice' => 'AED200',
                                'Taxes' => 'AED325',
                            ],
                            'FareInfo' => [
                                '@attributes' => ['FareBasis' => 'LLEOPAE1'],
                            ],
                        ],
                        'Ticket' => [
                            '@attributes' => [
                                'TicketNumber' => '1761234567890',
                                'TicketStatus' => 'N',
                            ],
                            'Coupon' => [
                                '@attributes' => [
                                    'CouponNumber' => '1',
                                    'MarketingCarrier' => 'EK',
                                    'MarketingFlightNumber' => '600',
                                    'Origin' => 'DXB',
                                    'Destination' => 'KHI',
                                    'DepartureTime' => '2026-06-18T07:40:00.000+04:00',
                                    'BookingClass' => 'L',
                                    'FareBasis' => 'LLEOPAE1',
                                    'Status' => 'O',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $tickets = TravelportTicketDetailsPresenter::fromRetrieveDocument($parsed, $booking);

        $this->assertCount(1, $tickets);
        $this->assertSame('1761234567890', $tickets[0]['ticket_number']);
        $this->assertSame('JOHN DOE', $tickets[0]['passenger_name']);
        $this->assertSame('EK', $tickets[0]['plating_carrier']);
        $this->assertSame('AED 525.00', $tickets[0]['total_price']);
        $this->assertCount(1, $tickets[0]['coupons']);
        $this->assertSame('EK 600', $tickets[0]['coupons'][0]['flight']);
        $this->assertSame('DXB → KHI', $tickets[0]['coupons'][0]['route']);
    }

    public function test_air_price_fare_tags_default_to_gds_for_gfb_host_token(): void
    {
        $parsed = [
            'Body' => [
                'AirPriceRsp' => [
                    'AirPriceResult' => [
                        'AirPricingSolution' => [
                            '@attributes' => ['Key' => 's1', 'TotalPrice' => 'AED525'],
                            'AirPricingInfo' => [
                                '@attributes' => ['BasePrice' => 'AED200', 'Taxes' => 'AED325', 'PlatingCarrier' => 'EK'],
                                'FareInfo' => [
                                    '@attributes' => ['FareBasis' => 'LLEOPAE1'],
                                    'Brand' => ['@attributes' => ['Name' => 'ECO SAVER']],
                                ],
                            ],
                            'HostToken' => 'GFB10101ADT01OW01LLEOPAE1',
                        ],
                    ],
                ],
            ],
        ];

        $options = TravelportAirPricePresenter::toFareOptions($parsed, ['onward_cabin_class' => 'Economy'], []);

        $this->assertSame(['published', 'gds'], $options[0]['fare_tags'] ?? []);
    }
}
