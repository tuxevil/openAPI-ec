<?php

namespace App\Providers\Accounting\Contifico;

use App\Contracts\AccountingProvider;
use App\Exceptions\ProviderException;
use App\Providers\Accounting\Support\ContificoInvoicePayload;
use App\Providers\Accounting\Support\ContificoNormalizer;
use App\Support\OperationResult;

class ContificoProvider implements AccountingProvider
{
    public function __construct(protected ContificoClient $client) {}

    public function listContacts(array $filters): OperationResult
    {
        $response = $this->client->get('/api/v2/persona/', array_filter([
            'search' => $filters['search'] ?? null,
            'estado' => $filters['status'] ?? null,
            'tipo' => $filters['type'] ?? null,
            'page' => $filters['page'] ?? null,
            'es_cliente' => '1',
        ]));

        return new OperationResult('contifico', 'contacts.list', null, 'success', [
            'items' => array_map(ContificoNormalizer::contact(...), $response),
        ], $response);
    }

    public function getContact(string $id): OperationResult
    {
        $response = $this->client->get("/api/v2/persona/{$id}/");

        return new OperationResult('contifico', 'contacts.get', (string) ($response['id'] ?? $id), 'success', ContificoNormalizer::contact($response), $response);
    }

    public function createContact(array $data): OperationResult
    {
        $payload = ContificoNormalizer::contactPayload($data);
        $response = $this->client->post('/api/v2/persona/', $payload, $this->requiredPosQuery());

        return new OperationResult('contifico', 'contacts.create', (string) ($response['id'] ?? $response['id_integracion'] ?? null), 'success', ContificoNormalizer::contact($response), $response);
    }

    public function updateContact(string $id, array $data): OperationResult
    {
        $payload = ContificoNormalizer::contactPayload($data);
        $response = $this->client->put("/api/v2/persona/{$id}/", $payload, $this->requiredPosQuery());

        return new OperationResult('contifico', 'contacts.update', (string) ($response['id'] ?? $id), 'success', ContificoNormalizer::contact($response), $response);
    }

    public function listProducts(array $filters): OperationResult
    {
        $response = $this->client->get('/api/v2/producto/', array_filter([
            'filtro' => $filters['search'] ?? null,
            'estado' => $filters['status'] ?? null,
            'page' => $filters['page'] ?? null,
        ]));

        return new OperationResult('contifico', 'products.list', null, 'success', [
            'items' => array_map(ContificoNormalizer::product(...), $response),
        ], $response);
    }

    public function getProduct(string $id): OperationResult
    {
        $response = $this->client->get("/api/v2/producto/{$id}");

        return new OperationResult('contifico', 'products.get', (string) ($response['id'] ?? $id), 'success', ContificoNormalizer::product($response), $response);
    }

    public function createProduct(array $data): OperationResult
    {
        $response = $this->client->post('/api/v2/producto/', ContificoNormalizer::productPayload($data));

        return new OperationResult('contifico', 'products.create', (string) ($response['id'] ?? $response['id_integracion'] ?? null), 'success', ContificoNormalizer::product($response), $response);
    }

    public function updateProduct(string $id, array $data): OperationResult
    {
        $response = $this->client->put("/api/v2/producto/{$id}", ContificoNormalizer::productPayload($data));

        return new OperationResult('contifico', 'products.update', (string) ($response['id'] ?? $id), 'success', ContificoNormalizer::product($response), $response);
    }

    public function getProductStock(string $id): OperationResult
    {
        $response = $this->client->get("/api/v2/producto/{$id}/stock/");

        return new OperationResult('contifico', 'products.stock', $id, 'success', [
            'items' => array_map(ContificoNormalizer::stock(...), $response),
        ], $response);
    }

    public function listInvoices(array $filters): OperationResult
    {
        $response = $this->client->get('/api/v2/documento/', array_filter([
            'tipo_registro' => 'CLI',
            'tipo' => 'FAC',
            'persona_identificacion' => $filters['customerIdentification'] ?? null,
            'fecha_inicial' => $filters['issuedFrom'] ?? null,
            'fecha_final' => $filters['issuedTo'] ?? null,
            'result_page' => $filters['page'] ?? null,
        ]));

        return new OperationResult('contifico', 'invoices.list', null, 'success', [
            'items' => array_map(ContificoNormalizer::invoice(...), $response),
        ], $response);
    }

    public function getInvoice(string $id): OperationResult
    {
        $response = $this->client->get("/api/v2/documento/{$id}");

        return new OperationResult('contifico', 'invoices.get', (string) ($response['id'] ?? $id), 'success', ContificoNormalizer::invoice($response), $response);
    }

    public function createInvoice(array $data): OperationResult
    {
        $response = $this->client->post('/api/v2/documento/', ContificoInvoicePayload::fromNormalized($data, $this->client->posQuery()['pos'] ?? null));

        return new OperationResult('contifico', 'invoices.create', (string) ($response['id'] ?? $response['id_integracion'] ?? null), 'success', ContificoNormalizer::invoice($response), $response);
    }

    public function getInvoiceStatus(string $id): OperationResult
    {
        $response = $this->client->get("/api/v2/documento/estado/{$id}");

        return new OperationResult('contifico', 'invoices.status', $id, 'success', ContificoNormalizer::invoiceStatus($response), $response);
    }

    public function listInvoicePayments(string $invoiceId): OperationResult
    {
        $response = $this->client->get("/api/v2/documento/{$invoiceId}/cobro/");

        return new OperationResult('contifico', 'payments.list', $invoiceId, 'success', [
            'items' => array_map(fn (array $payment) => ContificoNormalizer::payment($payment, $invoiceId), $response),
        ], $response);
    }

    public function createInvoicePayment(string $invoiceId, array $data): OperationResult
    {
        $response = $this->client->post("/api/v2/documento/{$invoiceId}/cobro/", ContificoNormalizer::paymentPayload($data));

        return new OperationResult('contifico', 'payments.create', (string) ($response['id'] ?? $invoiceId), 'success', ContificoNormalizer::payment($response, $invoiceId), $response);
    }

    protected function requiredPosQuery(): array
    {
        $query = $this->client->posQuery();

        if ($query === []) {
            throw new ProviderException(
                message: 'Contifico posToken is required for this operation.',
                httpStatus: 422,
                apiCode: 'provider_credentials_invalid',
                details: ['field' => 'credentials.posToken'],
                provider: 'contifico',
            );
        }

        return $query;
    }
}
