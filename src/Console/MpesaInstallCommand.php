<?php

namespace NyanumbaCodes\Mpesa\Console;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class MpesaInstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mpesa:install {environment=sandbox : The environment to install the certificate (sandbox or production)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Download the M-PESA Sandbox Certificate (cert.cer)';

    /**
     * Execute the console command.
     */

    private function getCertificateUrl($environment = 'sandbox')
    {
        $urls = [
            'sandbox' => 'https://developer.safaricom.co.ke/api/v1/GenerateSecurityCredential/SandboxCertificate.cer',
            'production' => 'https://developer.safaricom.co.ke/api/v1/GenerateSecurityCredential/ProductionCertificate.cer',
        ];

        return $urls[$environment] ?? null;
    }

    private function downloadCertificate($url, $path)
    {
        if (!File::exists(dirname($path))) {
            File::makeDirectory(dirname($path), 0755, true);
        }

        $contents = file_get_contents($url);

        if ($contents === false) {
            throw new Exception("Unable to fetch the certificate from {$url}");
        }

        File::put($path, $contents);
    }

    public function handle()
    {
        $environment = $this->argument('environment');
        $url = $this->getCertificateUrl($environment);
        $destinationPath = public_path('mpesa/cert.cer');

        if (!$url) {
            $this->error("Invalid environment specified. Use 'sandbox' or 'prod'.");
            return Command::FAILURE;
        }

        try {
            $this->downloadCertificate($url, $destinationPath);
            $this->info("Certificate for {$environment} downloaded successfully to {$destinationPath}.");
            return Command::SUCCESS;
        } catch (Exception $e) {
            $this->error("Failed to download certificate: {$e->getMessage()}");
            return Command::FAILURE;
        }
    }
}
