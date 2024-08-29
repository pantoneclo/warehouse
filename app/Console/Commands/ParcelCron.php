<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Models\Shipping;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class ParcelCron extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'Parcel:cron';

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

        try {
            if (Shipping::where('parcel_company_id', 1)
            // hello
                ->where(function ($query) {
                    $query->where('parcel_status', '!=', 'delivered')
                    ->orWhereNull('parcel_status');
                })->exists()) {

                $sale = Shipping::where('parcel_company_id', 1)
                    ->where(function ($query) {
                        $query->where('parcel_status', '!=', 'delivered')
                            ->orWhereNull('parcel_status');
                    })->first();

                $credential = ['gls_username', 'gls_password'];
                $credentials = Setting::whereIn('key', $credential)->pluck('value', 'key')->toArray();
                $pwd = $credentials['gls_password'];
                $username = $credentials['gls_username'];
                $password_converted = "[" . implode(',', unpack('C*', hash('sha512', $pwd, true))) . "]";
                $password = json_decode($password_converted, true);
                $apiUrl = 'https://api.mygls.si/ParcelService.svc/json/GetParcelStatuses';
                $parcelNumber = $sale->parcel_number;

                $requestData = [
                    "Username" => $username,
                    "Password" => $password,
                    "ParcelNumber" => $parcelNumber,
                    "ReturnPOD" => false,
                    "LanguageIsoCode" => "SI",
                ];

                // Make the HTTP request to the target API
                $response = Http::post($apiUrl, $requestData, [
                    'headers' => [
                        'Content-Type' => 'application/json',
                    ],
                ]);

                if ($response->successful()) {
                    $responseData = $response->json();
                } else {
                    $responseData = $this->sendError('Failed to retrieve data from the API', $response->status());
                }
                $latestStatus = getLatestStatus($responseData['ParcelStatusList'] ?? []);

                $sale['latest_status'] = $latestStatus['StatusDescription'];

                \Log::info($sale['latest_status']);

            }

        } catch (\Exception $e) {
            \Log::error("Parcel cron job failed: " . $e->getMessage());
        }
    }
}
