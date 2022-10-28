<?php

namespace haibase\HairavelInstaller\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Output\BufferedOutput;

class FinalController extends Controller
{

    public function finish()
    {
        if (session('message') && session('message')['status'] == 'error') {
            return view('vendor/haibase/hairavel-installer/src/Views/finished');
        }
        $outputLog = new BufferedOutput;
        $this->generateKey($outputLog);
        $finalMessages = $outputLog->fetch();
        $finalStatusMessage = $this->generateLock();

        return view('vendor/haibase/hairavel-installer/src/Views/finished', compact('finalMessages', 'finalStatusMessage'));
    }

    /**
     * Generate security code
     *
     * @param BufferedOutput $outputLog
     *
     * @return array|BufferedOutput
     */
    private function generateKey(BufferedOutput $outputLog)
    {
        try {
            Artisan::call('key:generate', ['--force' => true], $outputLog);
        } catch (Exception $e) {
            return $this->response($e->getMessage(), $outputLog);
        }

        return $outputLog;
    }

    /**
     * Generate installation lock
     * @return string
     */
    public function generateLock()
    {
        $installedLogFile = storage_path('installed');

        $dateStamp = date('Y/m/d h:i:sa');
        if (!file_exists($installedLogFile)) {
            $message = 'installed ' . $dateStamp . "\n";
            file_put_contents($installedLogFile, $message);
        } else {
            $message = 'updated' . $dateStamp;
            file_put_contents($installedLogFile, $message . PHP_EOL, FILE_APPEND | LOCK_EX);
        }
        return $message;
    }

    /**
     * @param $message
     * @param BufferedOutput $outputLog
     *
     * @return array
     */
    private function response($message, BufferedOutput $outputLog)
    {
        return [
            'status' => 'error',
            'message' => $message,
            'dbOutputLog' => $outputLog->fetch(),
        ];
    }
}
