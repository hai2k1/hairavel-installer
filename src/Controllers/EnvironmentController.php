<?php

namespace duxphp\DuxravelInstaller\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Routing\Redirector;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Validator;
use function Couchbase\defaultDecoder;

class EnvironmentController extends Controller
{

    private $envPath;

    private $envExamplePath;

    private $fields = [];
    private $rules = [];

    public function __construct()
    {
        $this->envPath = base_path('.env');
        $this->envExamplePath = base_path('.env.example');

        $this->fields = [
            [
                'APP_NAME' => 'system name',
                'APP_URL' => 'system address',
            ],
            [
                'DB_HOST' => 'Database address',
                'DB_PORT' => 'Database port',
                'DB_DATABASE' => 'database name',
                'DB_USERNAME' => 'database account',
                'DB_PASSWORD' => 'database password',
                'DB_TABLE_PREFIX' => 'Data table prefix',
            ]
        ];
        $this->rules = [
            'APP_NAME' => 'required|string|max:50',
            'APP_URL' => 'required|url',
            'DB_HOST' => 'required|string|max:50',
            'DB_PORT' => 'required|numeric',
            'DB_DATABASE' => 'required|string|max:50',
            'DB_USERNAME' => 'required|string|max:50',
            'DB_PASSWORD' => 'nullable|string|max:50',
            'DB_TABLE_PREFIX' => 'nullable|string|max:50',
        ];
    }

    public function environment()
    {
        $envConfig = $this->getEnvContent();
        return view('vendor/haibase/hairavel-installer/src/Views/environment', [
            'env' => \Dotenv\Dotenv::parse($envConfig),
            'data' => $this->fields
        ]);
    }

    public function save(Request $request, Redirector $redirect)
    {
        $validator = Validator::make($request->all(), $this->rules);

        if ($validator->fails()) {
            return $redirect->route('DuxravelInstaller::environment')->withInput()->withErrors($validator->errors());
        }

        if (!$this->checkDatabaseConnection($request)) {
            return $redirect->route('DuxravelInstaller::environment')->withInput()->withErrors([
                'DB_HOST' => 'Database connection failed',
            ]);
        }

        $data = $request->input();
        $contentArray = collect(file($this->envPath, FILE_IGNORE_NEW_LINES));
        $contentArray->transform(function ($item) use ($data) {
            foreach ($data as $key => $vo) {
                if (str_contains($item, $key . '=')) {
                    return $key . '=' . $vo;
                }
            }
            return $item;
        });
        $content = implode("\n", $contentArray->toArray());

        $results = 'Configuration file saved successfully';
        try {
            file_put_contents($this->envPath, $content);
        } catch (Exception $e) {
            $results = 'Failed to save configuration file';
        }

        return $redirect->route('DuxravelInstaller::database')
            ->with(['results' => $results]);
    }

    private function getEnvContent()
    {
        if (!file_exists($this->envPath)) {
            if (file_exists($this->envExamplePath)) {
                copy($this->envExamplePath, $this->envPath);
            } else {
                touch($this->envPath);
            }
        }

        return file_get_contents($this->envPath);
    }

    private function checkDatabaseConnection(Request $request)
    {
        $connection = 'mysql';

        $settings = config("database.connections.$connection");

        config([
            'database' => [
                'default' => $connection,
                'connections' => [
                    $connection => array_merge($settings, [
                        'driver' => $connection,
                        'host' => $request->input('DB_HOST'),
                        'port' => $request->input('DB_PORT'),
                        'database' => $request->input('DB_DATABASE'),
                        'username' => $request->input('DB_USERNAME'),
                        'password' => $request->input('DB_PASSWORD'),
                    ]),
                ],
            ],
        ]);

        DB::purge();
        try {
            DB::connection()->getPdo();
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}
