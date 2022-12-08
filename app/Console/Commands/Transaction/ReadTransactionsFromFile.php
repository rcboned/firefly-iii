<?php

namespace FireflyIII\Console\Commands\Transaction;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class ReadTransactionsFromFile extends Command
{
    private CONST CURRENT_CENTURY = 20;

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
        $fileName = env('FILE_NAME');

        $handle = fopen("storage/upload/" . $fileName, "r");
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
                    $operationYear = sprintf("%d%d", self::CURRENT_CENTURY, (int)substr($line, 10, 2));
                    $operationMonth = substr($line, 12, 2);
                    $operationDay = substr($line, 14, 2);

                    $operationDate = Carbon::create($operationYear, $operationMonth, $operationDay);

                    if ($isWithdrawal === 1) {
                        $transaction['amount'] = $amount;
                        $transaction['date'] = $operationDate->toDateTimeString();
                        $transaction['type'] = 'withdrawal';
                    } elseif ($isWithdrawal === 2){
                        $transaction['amount'] = $amount;
                        $transaction['date'] = $operationDate->toDateTimeString();
                        $transaction['type'] = 'deposit';
                    }

                } else {
                    $line = mb_convert_encoding($line, "UTF-8");

                    $description = trim(substr($line, 4, 100));

                    $transaction['description'] = $description;

                    if (
                        isset($transaction['description']) &&
                        isset($transaction['amount']) &&
                        isset($transaction['date'])
                    ) {
                        Artisan::call('transaction:create', [
                            'amount' => $transaction['amount'],
                            'description' => $transaction['description'],
                            '--date' => $transaction['date'],
                            '--type' => $transaction['type'],
                        ]);

                        $this->info(
                            $transaction['type'] . ' ' .
                            $transaction['date'] .
                            $transaction['amount'] . ' '.
                            $transaction['description'] . ' ' .
                            ' sending...'
                        );
                    }

                    $transaction = [];
                }
            }

            fclose($handle);
        }

        return 0;
    }
}
