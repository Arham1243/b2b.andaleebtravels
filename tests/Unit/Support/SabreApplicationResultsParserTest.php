<?php

namespace Tests\Unit\Support;

use App\Support\SabreApplicationResultsParser;
use PHPUnit\Framework\TestCase;

class SabreApplicationResultsParserTest extends TestCase
{
    public function test_extracts_nested_sabre_messages(): void
    {
        $response = [
            'CreatePassengerNameRecordRS' => [
                'ApplicationResults' => [
                    'status' => 'NotProcessed',
                    'Error' => [
                        [
                            'SystemSpecificResults' => [
                                ['Message' => 'INVALID DATE'],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->assertSame(['INVALID DATE'], SabreApplicationResultsParser::messages($response));
    }

    public function test_ignores_logger_placeholder_messages(): void
    {
        $response = [
            'CreatePassengerNameRecordRS' => [
                'ApplicationResults' => [
                    'Error' => [
                        [
                            'SystemSpecificResults' => [
                                ['Message' => 'Over 9 levels deep, aborting normalization'],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->assertSame([], SabreApplicationResultsParser::messages($response));
    }
}
