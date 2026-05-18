<?php

namespace App\Providers\Accounting\Support;

use Carbon\Carbon;

class ContificoInvoicePayload
{
    public static function fromNormalized(array $data, ?string $posToken): array
    {
        $items = array_map([self::class, 'item'], $data['items']);

        $subtotalZero = 0.0;
        $subtotalTaxable = 0.0;
        $taxTotal = 0.0;

        foreach ($items as $item) {
            $subtotalZero += $item['base_cero'];
            $subtotalTaxable += $item['base_gravable'];
            $taxTotal += $item['tax_amount'];
        }

        return [
            'pos' => $posToken,
            'fecha_emision' => Carbon::parse($data['issuedAt'])->format('d/m/Y'),
            'tipo_documento' => 'FAC',
            'tipo_registro' => 'CLI',
            'documento' => $data['number'],
            'autorizacion' => (string) ($data['authorization'] ?? ''),
            'descripcion' => $data['description'] ?? sprintf('Factura %s', $data['number']),
            'referencia' => $data['reference'] ?? $data['number'],
            'subtotal_0' => round($subtotalZero, 2),
            'subtotal_12' => round($subtotalTaxable, 2),
            'iva' => round($taxTotal, 2),
            'ice' => 0,
            'total' => round($subtotalZero + $subtotalTaxable + $taxTotal, 2),
            'estado' => $data['status'] ?? 'P',
            'cliente' => self::customer($data['customer']),
            'detalles' => array_map(function (array $item): array {
                unset($item['tax_amount']);

                return $item;
            }, $items),
        ];
    }

    protected static function item(array $item): array
    {
        $quantity = (float) $item['quantity'];
        $unitPrice = (float) $item['unitPrice'];
        $taxRate = (float) ($item['taxRate'] ?? 15);
        $discount = (float) ($item['discountPercentage'] ?? 0);
        $gross = $quantity * $unitPrice;
        $discountAmount = $gross * ($discount / 100);
        $net = round($gross - $discountAmount, 2);
        $taxed = $taxRate > 0;
        $taxAmount = $taxed ? round($net * ($taxRate / 100), 2) : 0.0;

        return [
            'producto_id' => $item['productExternalId'],
            'cantidad' => $quantity,
            'precio' => $unitPrice,
            'porcentaje_iva' => $taxRate,
            'porcentaje_descuento' => $discount,
            'base_cero' => $taxed ? 0.0 : $net,
            'base_gravable' => $taxed ? $net : 0.0,
            'base_no_gravable' => 0.0,
            'tax_amount' => $taxAmount,
        ];
    }

    protected static function customer(array $customer): array
    {
        $type = $customer['identification']['type'];
        $value = (string) $customer['identification']['value'];

        return array_filter([
            'razon_social' => $customer['name'],
            'email' => $customer['email'] ?? null,
            'telefonos' => $customer['phone'] ?? null,
            'direccion' => $customer['address'] ?? null,
            'tipo' => $type === 'RUC' ? 'J' : 'N',
            'ruc' => $type === 'RUC' ? $value : null,
            'cedula' => $type === 'RUC' ? substr($value, 0, 10) : $value,
            'es_extranjero' => $type === 'PASAPORTE',
        ], static fn (mixed $value): bool => $value !== null);
    }
}
