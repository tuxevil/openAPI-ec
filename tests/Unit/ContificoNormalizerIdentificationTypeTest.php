<?php

namespace Tests\Unit;

use App\Providers\Accounting\Support\ContificoNormalizer;
use PHPUnit\Framework\TestCase;

class ContificoNormalizerIdentificationTypeTest extends TestCase
{
    public function test_consumidor_final_value_9999999999999(): void
    {
        $this->assertSame('CONSUMIDOR_FINAL', $this->invoke(['ruc' => '9999999999999']));
    }

    public function test_ruc_present(): void
    {
        $this->assertSame('RUC', $this->invoke(['ruc' => '1790011679001']));
    }

    public function test_pasaporte_with_boolean_true(): void
    {
        $this->assertSame('PASAPORTE', $this->invoke(['es_extranjero' => true]));
    }

    public function test_pasaporte_with_string_s(): void
    {
        $this->assertSame('PASAPORTE', $this->invoke(['es_extranjero' => 'S']));
    }

    public function test_pasaporte_with_string_si(): void
    {
        $this->assertSame('PASAPORTE', $this->invoke(['es_extranjero' => 'Si']));
    }

    public function test_pasaporte_with_string_yes(): void
    {
        $this->assertSame('PASAPORTE', $this->invoke(['es_extranjero' => 'YES']));
    }

    public function test_pasaporte_with_int_one(): void
    {
        $this->assertSame('PASAPORTE', $this->invoke(['es_extranjero' => 1]));
    }

    public function test_cedula_default_when_es_extranjero_is_n(): void
    {
        $this->assertSame('CEDULA', $this->invoke(['es_extranjero' => 'N', 'cedula' => '0912345678']));
    }

    public function test_cedula_default_when_es_extranjero_is_false(): void
    {
        $this->assertSame('CEDULA', $this->invoke(['es_extranjero' => false, 'cedula' => '0912345678']));
    }

    public function test_cedula_default_when_es_extranjero_is_zero(): void
    {
        $this->assertSame('CEDULA', $this->invoke(['es_extranjero' => 0, 'cedula' => '0912345678']));
    }

    public function test_cedula_default_when_no_flags(): void
    {
        $this->assertSame('CEDULA', $this->invoke(['cedula' => '0912345678']));
    }

    private function invoke(array $data): string
    {
        $reflection = new \ReflectionMethod(ContificoNormalizer::class, 'identificationType');
        $reflection->setAccessible(true);

        return $reflection->invoke(null, $data);
    }
}
