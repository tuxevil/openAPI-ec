<?php

namespace App\Providers\Accounting\Support;

use Carbon\Carbon;

class ContificoNormalizer
{
    public static function contact(array $contact): array
    {
        return [
            'externalId' => (string) ($contact['id'] ?? $contact['id_integracion'] ?? ''),
            'name' => $contact['razon_social'] ?? '',
            'commercialName' => $contact['nombre_comercial'] ?? null,
            'identification' => [
                'type' => self::identificationType($contact),
                'value' => $contact['ruc'] ?? $contact['cedula'] ?? $contact['placa'] ?? null,
            ],
            'email' => $contact['email'] ?? null,
            'phone' => $contact['telefonos'] ?? null,
            'address' => $contact['direccion'] ?? null,
            'isCustomer' => (bool) ($contact['es_cliente'] ?? false),
            'isSupplier' => (bool) ($contact['es_proveedor'] ?? false),
            'status' => $contact['estado'] ?? 'A',
        ];
    }

    public static function contactPayload(array $data): array
    {
        $identificationType = $data['identification']['type'];
        $identificationValue = (string) $data['identification']['value'];

        $payload = [
            'tipo' => $identificationType === 'RUC' ? 'J' : 'N',
            'razon_social' => $data['name'],
            'es_cliente' => (bool) $data['isCustomer'],
            'es_proveedor' => (bool) $data['isSupplier'],
            'cedula' => in_array($identificationType, ['CEDULA', 'PASAPORTE'], true)
                ? $identificationValue
                : substr($identificationValue, 0, 10),
            'es_extranjero' => $identificationType === 'PASAPORTE',
        ];

        if ($identificationType === 'RUC') {
            $payload['ruc'] = $identificationValue;
        }

        if (isset($data['commercialName'])) {
            $payload['nombre_comercial'] = $data['commercialName'];
        }

        if (isset($data['email'])) {
            $payload['email'] = $data['email'];
        }

        if (isset($data['phone'])) {
            $payload['telefonos'] = $data['phone'];
        }

        if (isset($data['address'])) {
            $payload['direccion'] = $data['address'];
        }

        return $payload;
    }

    public static function product(array $product): array
    {
        return [
            'externalId' => (string) ($product['id'] ?? $product['id_integracion'] ?? ''),
            'code' => $product['codigo'] ?? null,
            'name' => $product['nombre'] ?? '',
            'type' => $product['tipo'] ?? 'PRO',
            'price' => (float) ($product['pvp1'] ?? 0),
            'taxRate' => (float) ($product['porcentaje_iva'] ?? 0),
            'stock' => isset($product['cantidad_stock']) ? (float) $product['cantidad_stock'] : null,
            'status' => $product['estado'] ?? 'A',
        ];
    }

    public static function productPayload(array $data): array
    {
        return [
            'nombre' => $data['name'],
            'codigo' => $data['code'],
            'estado' => $data['status'],
            'pvp1' => (float) $data['price'],
            'minimo' => (float) ($data['stock'] ?? 0),
            'tipo' => $data['type'],
            'tipo_producto' => 'SIM',
            'porcentaje_iva' => (float) ($data['taxRate'] ?? 15),
        ];
    }

    public static function stock(array $stock): array
    {
        return [
            'warehouseId' => $stock['bodega_id'] ?? null,
            'warehouseName' => $stock['bodega_nombre'] ?? null,
            'quantity' => (float) ($stock['cantidad'] ?? 0),
        ];
    }

    public static function invoice(array $invoice): array
    {
        $items = array_map(function (array $item): array {
            return [
                'productExternalId' => $item['producto_id'] ?? null,
                'quantity' => (float) ($item['cantidad'] ?? 0),
                'unitPrice' => (float) ($item['precio'] ?? 0),
                'taxRate' => (float) ($item['porcentaje_iva'] ?? 0),
                'discountPercentage' => (float) ($item['porcentaje_descuento'] ?? 0),
            ];
        }, $invoice['detalles'] ?? []);

        return [
            'externalId' => (string) ($invoice['id'] ?? $invoice['id_integracion'] ?? ''),
            'number' => $invoice['documento'] ?? null,
            'issuedAt' => self::normalizeDate($invoice['fecha_emision'] ?? null),
            'customer' => [
                'name' => data_get($invoice, 'cliente.razon_social', $invoice['razon_social'] ?? null),
                'identification' => [
                    'type' => self::identificationType($invoice['cliente'] ?? $invoice),
                    'value' => data_get($invoice, 'cliente.ruc') ?? data_get($invoice, 'cliente.cedula') ?? $invoice['ruc'] ?? $invoice['cedula'] ?? null,
                ],
            ],
            'items' => $items,
            'subtotal' => (float) (($invoice['subtotal_0'] ?? 0) + ($invoice['subtotal_12'] ?? 0)),
            'taxTotal' => (float) ($invoice['iva'] ?? 0),
            'total' => (float) ($invoice['total'] ?? 0),
            'status' => $invoice['estado'] ?? null,
            'authorization' => $invoice['autorizacion'] ?? null,
        ];
    }

    public static function invoiceStatus(array $status): array
    {
        return [
            'externalId' => (string) ($status['documento_id'] ?? ''),
            'status' => $status['estado'] ?? null,
        ];
    }

    public static function payment(array $payment, string $invoiceId): array
    {
        return [
            'externalId' => (string) ($payment['id'] ?? ''),
            'invoiceExternalId' => $invoiceId,
            'method' => $payment['forma_cobro'] ?? null,
            'amount' => (float) ($payment['monto'] ?? 0),
            'paidAt' => self::normalizeDate($payment['fecha'] ?? null),
            'reference' => $payment['numero_comprobante'] ?? null,
            'bankAccountId' => $payment['cuenta_bancaria_id'] ?? null,
        ];
    }

    public static function paymentPayload(array $data): array
    {
        return array_filter([
            'forma_cobro' => $data['method'],
            'monto' => (float) $data['amount'],
            'fecha' => isset($data['paidAt']) ? Carbon::parse($data['paidAt'])->format('d/m/Y') : null,
            'tipo_ping' => $data['cardProcessor'] ?? null,
            'numero_cheque' => $data['checkNumber'] ?? null,
            'cuenta_bancaria_id' => $data['bankAccountId'] ?? null,
            'numero_comprobante' => $data['reference'] ?? null,
        ], static fn (mixed $value): bool => $value !== null);
    }

    protected static function identificationType(array $data): string
    {
        $value = $data['ruc'] ?? $data['cedula'] ?? null;

        if ($value === '9999999999999') {
            return 'CONSUMIDOR_FINAL';
        }

        if (! empty($data['ruc'])) {
            return 'RUC';
        }

        if (! empty($data['es_extranjero'])) {
            return 'PASAPORTE';
        }

        return 'CEDULA';
    }

    protected static function normalizeDate(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return Carbon::createFromFormat('d/m/Y', $value)->toDateString();
    }
}
