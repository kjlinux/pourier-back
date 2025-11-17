<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Withdrawal;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class WithdrawalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $photographers = User::whereHas('photographerProfile', function ($query) {
            $query->where('status', 'approved');
        })->get();

        if ($photographers->isEmpty()) {
            $this->command->warn('⚠️  No approved photographers found. Skipping WithdrawalSeeder.');
            return;
        }

        $admin = User::role('admin')->first();

        $mobileMoneyOperators = [
            'ORANGE_MONEY_BF' => 'Orange Money',
            'MOOV_MONEY_BF' => 'Moov Money',
            'MTN' => 'MTN Mobile Money',
            'WAVE' => 'Wave',
        ];

        $totalWithdrawals = 20;
        $completedCount = 0;
        $pendingCount = 0;
        $rejectedCount = 0;

        for ($i = 0; $i < $totalWithdrawals; $i++) {
            $photographer = $photographers->random();
            $requestDate = now()->subDays(rand(1, 90));

            // 60% completed, 25% pending, 15% rejected
            $statusRand = rand(1, 100);
            if ($statusRand <= 60) {
                $status = 'completed';
                $completedCount++;
            } elseif ($statusRand <= 85) {
                $status = 'pending';
                $pendingCount++;
            } else {
                $status = 'rejected';
                $rejectedCount++;
            }

            // Amount between 5,000 and 500,000 FCFA
            $amount = rand(1, 100) * 5000;

            // 90% mobile money, 10% bank transfer
            $paymentMethod = rand(1, 100) <= 90 ? 'mobile_money' : 'bank_transfer';

            if ($paymentMethod === 'mobile_money') {
                $operatorKey = array_rand($mobileMoneyOperators);
                $operatorName = $mobileMoneyOperators[$operatorKey];
                $phoneNumber = '+226 ' . rand(70, 79) . ' ' . rand(10, 99) . ' ' . rand(10, 99) . ' ' . rand(10, 99);

                $paymentDetails = [
                    'operator' => $operatorKey,
                    'operator_name' => $operatorName,
                    'phone' => $phoneNumber,
                    'account_name' => $photographer->first_name . ' ' . $photographer->last_name,
                ];
            } else {
                $paymentDetails = [
                    'bank_name' => ['Coris Bank', 'Bank of Africa', 'Ecobank', 'UBA'][rand(0, 3)],
                    'account_number' => 'BF' . rand(10000000, 99999999),
                    'account_name' => $photographer->first_name . ' ' . $photographer->last_name,
                    'swift_code' => 'CORIBFBF',
                ];
            }

            $rejectionReasons = [
                'Informations de paiement incorrectes. Veuillez vérifier votre numéro de téléphone.',
                'Solde disponible insuffisant. Vous devez attendre la fin de la période de sécurité de 30 jours.',
                'Document d\'identité requis pour ce montant. Veuillez soumettre une copie de votre CNI.',
                'Compte mobile money invalide. Veuillez mettre à jour vos informations de paiement.',
            ];

            Withdrawal::create([
                'id' => Str::uuid(),
                'photographer_id' => $photographer->id,
                'amount' => $amount,
                'status' => $status,
                'payment_method' => $paymentMethod,
                'payment_details' => json_encode($paymentDetails),
                'rejection_reason' => $status === 'rejected' ? $rejectionReasons[array_rand($rejectionReasons)] : null,
                'processed_by' => $status !== 'pending' ? $admin->id : null,
                'processed_at' => $status !== 'pending' ? $requestDate->addDays(rand(1, 7)) : null,
                'created_at' => $requestDate,
                'updated_at' => $status !== 'pending' ? $requestDate->addDays(rand(1, 7)) : $requestDate,
            ]);
        }

        $this->command->info("✅ Withdrawals seeded: {$totalWithdrawals} total ({$completedCount} completed, {$pendingCount} pending, {$rejectedCount} rejected)");
    }
}
