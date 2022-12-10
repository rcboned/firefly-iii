<?php

namespace FireflyIII\Console\Commands\Transaction;

use Carbon\Carbon;
use FireflyIII\Models\Account;
use FireflyIII\Services\Transactions\FindTransaction;
use FireflyIII\User;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CreateTransactionFromRequest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'transaction:create {amount} {description} {--date=} {--email=} {--type=withdrawal}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(FindTransaction $findTransaction)
    {
        $amount = (float) $this->argument('amount');
        $transactionDescription = $this->argument('description');
        $email = $this->option('email') ?? env('USER_EMAIL');
        $dateString = $this->option('date') ?? Carbon::now()->toDateTimeString();
        $date = Carbon::createFromFormat('Y-m-d H:i:s', $dateString);
        $transactionType = (string) $this->option('type');

        if (!in_array($transactionType, ['withdrawal', 'deposit'])) {
            return 0;
        }

        $sourceAccountName = '';
        $endAccountName = '';
        if ($transactionType === 'withdrawal') {
            $endAccountName = env('EXPENSE_ACCOUNT');
            $sourceAccountName = env('BANK_ACCOUNT');
        } elseif ($transactionType === 'deposit') {
            $sourceAccountName =  env('REVENUE_ACCOUNT');
            $endAccountName = env('BANK_ACCOUNT');
        }

        $sourceAccount = Account::whereName($sourceAccountName)->first(); // where user where active
        $endAccount = Account::whereName($endAccountName)->first();

        if (!$sourceAccount || !$endAccount) {
            return 0;
        }

        $transaction = $findTransaction->find($transactionDescription, $amount, $dateString);

        $transactionExists = $transaction != null;

        if ($transactionExists) {
            $this->info('Skipped...');
            return 0;
        }

        $this->authenticateUser($email);

        $params = [
            'transactions' => [
                [
                    "type" => $transactionType,
                    "date" => $date->format('Y-m-d'),
                    "amount" => sprintf("%.2f", $amount),
                    "description" => $transactionDescription,
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
                ],
            ]
        ];

        $request = Request::create('api/v1/transactions', 'POST', $params);

        $response = app()->handle($request);

//        $responseBody = json_decode($response->getContent(), true);

        return 0;
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
}
