<?php

namespace App\Contracts;

interface AccountingProvider extends ContactsProvider, ProductsProvider, InvoicesProvider, PaymentsProvider
{
}
