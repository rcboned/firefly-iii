<?php

namespace FireflyIII\Console\Commands\Transaction;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class ReadTransactionsFromFile extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'transaction:file:import';

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
    public function handle()
    {
        $handle = fopen("storage/upload/MovimientosCuenta.Q43", "r");
        if ($handle) {
            $lineNum = 0;
            $transaction = [];
            while (($line = fgets($handle)) !== false) {
                $lineNum++;
                if ($lineNum == 1) {
                    continue;
                }

                $isFinancial = ($lineNum % 2 == 0);

                if ($isFinancial) {
                    $isWithdrawal = (int) substr($line, 27, 1);
                    $unitAmount = (int) substr($line, 28, 12);
                    $decimalAmount = (int) substr($line, 40, 2);
                    $amount = (float) (string) $unitAmount . '.' . $decimalAmount;
                    $operationYear = 22 . (int) substr($line, 10, 2);
                    $operationMonth = substr($line, 12, 2);
                    $operationDay = substr($line, 14, 2);

                    $operationDate = Carbon::create($operationYear, $operationMonth, $operationDay);

                    if ($isWithdrawal) {

                        $transaction['amount'] = $amount;
                        $transaction['date'] = $operationDate->toDateTimeString();
                    }

                } else {
                    $description = trim(substr($line, 4, 100));

                    $transaction['description'] = $description;

                    if (
                        isset($transaction['description']) &&
                        isset($transaction['amount']) &&
                        isset($transaction['date'])
                    ) {
                        Artisan::call('transaction:create:withdrawal', [
                            'amount' => $transaction['amount'],
                            'description' => $transaction['description'],
                            '--date' => $transaction['date'],
                        ]);
                    }

                    $transaction = [];
                }
            }

            fclose($handle);
        };
    }
}
