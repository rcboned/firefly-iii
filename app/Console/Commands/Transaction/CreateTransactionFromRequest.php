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
    protected $signature = 'transaction:create:withdrawal {amount} {description} {--date=} {--email=} {--bankName=}';

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
        $bankName = $this->option('bankName') ?? env('BANK_ACCOUNT');
        $dateString = $this->option('date') ?? Carbon::now()->toDateTimeString();
        $cashAccount = 'expense account';

        $sourceAccount = Account::whereName($bankName)->first(); // where user where active
        $endAccount = Account::whereName($cashAccount)->first();

        $date = Carbon::createFromFormat('Y-m-d H:i:s', $dateString);

        $transaction = $findTransaction->find($transactionDescription, $amount, $dateString);

        $transactionExists = $transaction != null;

        if ($transactionExists) {
            $this->info('Skipped...');
            return 0;
        }

        $user = User::whereEmail($email)->first();

        Auth::login($user, true);

        $params = [
            'transactions' => [
                [
                    "type" => 'withdrawal',
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
}
