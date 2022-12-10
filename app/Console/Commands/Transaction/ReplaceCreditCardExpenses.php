<?php

namespace FireflyIII\Console\Commands\Transaction;

use Carbon\Carbon;
use FireflyIII\Models\Account;
use FireflyIII\Models\Transaction;
use FireflyIII\User;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use function app;
use function dump;
use function env;

class ReplaceCreditCardExpenses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'transaction:file:importCredit {filename} {--amountMargin=} {--showTransactions=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Imports and replaces credit card by actual operations';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $fileName = (string) $this->argument('filename');
        $email = env('USER_EMAIL');

        $amountMargin = $this->option('amountMargin');
        $showTransactions = $this->option('showTransactions');

        $transactionsArray = $this->getTransactionsFromFile($fileName);

        $total = $this->getTotalAmount($transactionsArray);

        $transactions = Transaction::whereHas('transactionJournal', function(Builder $query) {
            $query->where('description', 'like', '%visa%');
        });

        if ($amountMargin === null) {
            $transactions = $transactions->where('amount', $total);
        } else {
            $amountMargin = (float) $amountMargin;

            $transactions = $transactions->where('amount', '>', $total - $amountMargin)
                ->where('amount', '<', $total + $amountMargin);
        }

        $transactions = $transactions->get();

        if (count($transactions) !== 1) {
            $this->error(count($transactions)  . ' records found');
            if (!is_null($showTransactions)) {
                dump($transactions->toArray());
            }

            return 0;
        }

        // ask
        $transaction = $transactions->first();
        $journal = $transaction->transactionJournal;

        $question = $journal->description . " with amount " . $total . ' will be deleted and replaced by ' . count($transactionsArray) . ' transactions';

        if ($this->confirm($question)) {
            $this->authenticateUser($email);

            $request = Request::create('api/v1/transactions/' . $journal->transaction_group_id, 'DELETE');

            $response = app()->handle($request);

            foreach ($transactionsArray as $transaction) {
                if ($transaction['date']->lt(Carbon::create(2022, 01, 01, 00, 00, 00))) {
                    continue;
                }

                $transactionType = 'deposit';
                if ($transaction['amount'] < 0) {
                    $transactionType = 'withdrawal';
                }

                $transaction['amount'] = $transaction['amount'] * -1;

                [$sourceAccount, $endAccount] = $this->getAccounts($transactionType);

                $params = [
                    'transactions' => [
                        [
                            "type" => $transactionType,
                            "date" => $transaction['date']->format('Y-m-d'),
                            "amount" => sprintf("%.2f", $transaction['amount']),
                            "description" => $transaction['description'],
                            "source_id" => (string) $sourceAccount->id,
                            "source_name" => $sourceAccount->name,
                            "destination_id" =>  (string) $endAccount->id,
                            "destination_name" => $endAccount->name,
                            "category_name" => '',
                            "interest_date" => '',
                            "book_date" => '',
                            "process_date" => '',
                            "due_date" => '',
                            "payment_date" => '',
                            "invoice_date" => '',
                            "internal_reference" => '',
                            "notes" => '',
                            "external_url" => '',
                        ]
                    ]
                ];

                $this->info(
                    $transactionType . ' ' .
                    $transaction['date']->format('Y-m-d') . ' ' .
                    sprintf("%.2f", $transaction['amount']) . ' '.
                    $transaction['description'] . ' ' .
                    ' sending...'
                );

                $request = Request::create('api/v1/transactions', 'POST', $params);

                $response = app()->handle($request);
            }
        }

        return 0;
    }

    /**
     * @param array $transactions
     * @return float|mixed
     */
    public function getTotalAmount(array $transactions): mixed
    {
        $total = 0.0;
        foreach ($transactions as $transaction) {
            $total += $transaction['amount'];
        }
        return $total;
    }

    public function getTransactionsFromFile(string $fileName): array
    {
        $handle = fopen("storage/upload/" . $fileName, "r");
        if ($handle) {
            $transactions = [];
            while (($line = fgets($handle)) !== false) {
                [$date, $time, $description, $city, $amount] = explode(';', $line);

                $transactions[] = [
                    'date' => Carbon::createFromFormat('d/m/Y H:i', $date . ' ' . $time),
                    'description' => $description,
                    'city' => $city,
                    'amount' => floatval(trim(str_replace(',', '.', $amount))),
                ];
            }

            fclose($handle);
        }
        return $transactions;
    }

    public function authenticateUser(mixed $email): void
    {
        $user = User::whereEmail($email)->first();

        $loggedUser = Auth::user();

        // hack so we can send this command several times, we just login once
        if ($loggedUser === null) {
            Auth::login($user, true);
        }
    }

    public function getAccounts(string $transactionType): array
    {
        $sourceAccountName = '';
        $endAccountName = '';
        if ($transactionType === 'withdrawal') {
            $endAccountName = env('EXPENSE_ACCOUNT');
            $sourceAccountName = env('BANK_ACCOUNT');
        } elseif ($transactionType === 'deposit') {
            $sourceAccountName = env('REVENUE_ACCOUNT');
            $endAccountName = env('BANK_ACCOUNT');
        }

        $sourceAccount = Account::whereName($sourceAccountName)->first(); // where user where active
        $endAccount = Account::whereName($endAccountName)->first();

        return [$sourceAccount, $endAccount];
    }
}
