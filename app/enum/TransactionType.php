<?php

namespace App\enum;

enum TransactionType: string
{
    case income = 'income';
    case expense = 'expense';
}
