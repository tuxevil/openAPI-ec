<?php

namespace App\Contracts;

interface AccountingProvider extends ContactsProvider, InvoicesProvider, PaymentsProvider, ProductsProvider {}
