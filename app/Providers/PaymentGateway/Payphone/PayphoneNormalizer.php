<?php

namespace App\Providers\PaymentGateway\Payphone;

class PayphoneNormalizer
{
    public static function salePayload(array $data): array
    {
        return self::filter([
            'phoneNumber' => $data['phoneNumber'] ?? null,
            'countryCode' => $data['countryCode'] ?? null,
            'clientUserId' => $data['clientUserId'] ?? null,
            'documentId' => $data['documentId'] ?? null,
            'email' => $data['email'] ?? null,
            'reference' => $data['reference'] ?? null,
            'responseUrl' => $data['responseUrl'] ?? null,
            'amount' => $data['amount'] ?? null,
            'amountWithTax' => $data['amountWithTax'] ?? null,
            'amountWithoutTax' => $data['amountWithoutTax'] ?? null,
            'tax' => $data['tax'] ?? null,
            'clientTransactionId' => $data['clientTransactionId'] ?? null,
            'storeId' => $data['storeId'] ?? null,
            'terminalId' => $data['terminalId'] ?? null,
        ]);
    }

    public static function linkPayload(array $data): array
    {
        return self::filter([
            'amount' => $data['amount'] ?? null,
            'amountWithTax' => $data['amountWithTax'] ?? null,
            'amountWithoutTax' => $data['amountWithoutTax'] ?? null,
            'tax' => $data['tax'] ?? null,
            'reference' => $data['reference'] ?? null,
            'clientTransactionId' => $data['clientTransactionId'] ?? null,
            'expireIn' => $data['expireIn'] ?? null,
            'notifyUrl' => $data['notifyUrl'] ?? null,
            'storeId' => $data['storeId'] ?? null,
            'terminalId' => $data['terminalId'] ?? null,
            'documentId' => $data['documentId'] ?? null,
            'email' => $data['email'] ?? null,
        ]);
    }

    public static function reversalPayload(array $data): array
    {
        return self::filter([
            'transactionId' => $data['transactionId'] ?? null,
        ]);
    }

    public static function sale(array $response, array $request): array
    {
        return self::filter([
            'transactionId' => $response['transactionId'] ?? null,
            'clientTransactionId' => $request['clientTransactionId'] ?? null,
            'reference' => $request['reference'] ?? null,
            'amount' => $request['amount'] ?? null,
            'amountWithTax' => $request['amountWithTax'] ?? null,
            'amountWithoutTax' => $request['amountWithoutTax'] ?? null,
            'tax' => $request['tax'] ?? null,
            'phoneNumber' => $request['phoneNumber'] ?? null,
            'countryCode' => $request['countryCode'] ?? null,
            'documentId' => $request['documentId'] ?? null,
            'email' => $request['email'] ?? null,
            'responseUrl' => $request['responseUrl'] ?? null,
            'storeId' => $request['storeId'] ?? null,
            'terminalId' => $request['terminalId'] ?? null,
        ]);
    }

    public static function link(array $response, array $request): array
    {
        return self::filter([
            'url' => $response['url'] ?? null,
            'clientTransactionId' => $request['clientTransactionId'] ?? null,
            'reference' => $request['reference'] ?? null,
            'amount' => $request['amount'] ?? null,
            'amountWithTax' => $request['amountWithTax'] ?? null,
            'amountWithoutTax' => $request['amountWithoutTax'] ?? null,
            'tax' => $request['tax'] ?? null,
            'notifyUrl' => $request['notifyUrl'] ?? null,
            'storeId' => $request['storeId'] ?? null,
            'terminalId' => $request['terminalId'] ?? null,
            'documentId' => $request['documentId'] ?? null,
            'email' => $request['email'] ?? null,
            'expireIn' => $request['expireIn'] ?? null,
        ]);
    }

    public static function transaction(array $response): array
    {
        return self::filter([
            'transactionId' => $response['transactionId'] ?? null,
            'clientTransactionId' => $response['clientTransactionId'] ?? null,
            'transactionStatus' => $response['transactionStatus'] ?? null,
            'amount' => $response['amount'] ?? null,
            'currency' => $response['currency'] ?? null,
            'authorizationCode' => $response['authorizationCode'] ?? null,
            'message' => $response['message'] ?? null,
            'date' => $response['date'] ?? null,
            'documentId' => $response['documentId'] ?? null,
            'phoneNumber' => $response['phoneNumber'] ?? null,
            'email' => $response['email'] ?? null,
            'storeId' => $response['storeId'] ?? null,
            'terminalId' => $response['terminalId'] ?? null,
            'bin' => $response['bin'] ?? null,
            'lastDigits' => $response['lastDigits'] ?? null,
            'cardType' => $response['cardType'] ?? null,
            'cardBrand' => $response['cardBrand'] ?? null,
        ]);
    }

    public static function reversal(array $response, array $request): array
    {
        return self::filter([
            'transactionId' => $request['transactionId'] ?? null,
            'clientTransactionId' => $request['clientTransactionId'] ?? null,
            'status' => $response['status'] ?? null,
            'message' => $response['message'] ?? null,
        ]);
    }

    public static function operationStatusFromTransaction(string $transactionStatus): string
    {
        return match ($transactionStatus) {
            'Approved' => 'success',
            'Pending' => 'pending',
            'Canceled', 'Rejected' => 'error',
            default => 'error',
        };
    }

    public static function operationStatusFromReverse(array $response): string
    {
        return strcasecmp((string) ($response['status'] ?? ''), 'Success') === 0 ? 'success' : 'error';
    }

    protected static function filter(array $data): array
    {
        return array_filter($data, static fn ($value) => $value !== null);
    }
}
