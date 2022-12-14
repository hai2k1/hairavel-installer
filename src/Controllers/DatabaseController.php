<?php

namespace haibase\HairavelInstaller\Controllers;

use Illuminate\Routing\Controller;
use haibase\HairavelInstaller\Events\InstallSeed;
use Exception;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Output\BufferedOutput;

class DatabaseController extends Controller
{

    public function database()
    {
        $outputLog = new BufferedOutput;
        $response = $this->migrate($outputLog);
        return redirect()->route('DuxravelInstaller::final')->with(['message' => $response]);

    }

    /**
     * Merge data table structure
     * @param BufferedOutput $outputLog
     * @return array
     */
    private function migrate(BufferedOutput $outputLog)
    {
        Artisan::call('migrate', ['--force' => true], $outputLog);
        try {
            Artisan::call('migrate', ['--force' => true], $outputLog);
        } catch (Exception $e) {
            return $this->response($e->getMessage(), 'error', $outputLog);
        }
        return $this->seed($outputLog);
    }

    /**
     * Merge installation data
     * @param BufferedOutput $outputLog
     * @return array
     */
    private function seed(BufferedOutput $outputLog)
    {
        try {
            $data = array_filter(event(new InstallSeed));
            foreach ($data as $vo) {
                Artisan::call('db:seed', [
                    '--force' => true,
                    '--class' => $vo,
                ]);
            }
            Artisan::call('db:seed', ['--force' => true], $outputLog);
        } catch (Exception $e) {
            return $this->response($e->getMessage(), 'error', $outputLog);
        }
        return $this->response('Installation data succeeded', 'success', $outputLog);
    }

    /**
     * @param $message
     * @param $status
     * @param BufferedOutput $outputLog
     * @return array
     */
    private function response($message, $status, BufferedOutput $outputLog)
    {
        return [
            'status' => $status,
            'message' => $message,
            'dbOutputLog' => $outputLog->fetch(),
        ];
    }
}
