<?php

namespace Config;

use CodeIgniter\Database\Config;

/**
 * Database Configuration
 */
class Database extends Config
{
    /**
     * The directory that holds the Migrations and Seeds directories.
     */
    public string $filesPath = APPPATH . 'Database' . DIRECTORY_SEPARATOR;

    /**
     * Lets you choose which connection group to use if no other is specified.
     */
    public string $defaultGroup = 'default';

    /**
     * The default database connection.
     *
     * @var array<string, mixed>
     */
    public array $default = [
        'DSN'        => '',
        'hostname'   => 'localhost',
        'username'   => 'admin',
        'password'   => 'pointofsale',
        'database'   => 'ospos',
        'DBDriver'   => 'MySQLi',
        'DBPrefix'   => 'ospos_',
        'pConnect'   => false,
        'DBDebug'    => (ENVIRONMENT !== 'production'),
        'charset'    => 'utf8mb4',
        'DBCollat'   => 'utf8mb4_general_ci',
        'swapPre'    => '',
        'encrypt'    => false,     // will be replaced in constructor if SSL present
        'compress'   => false,
        'strictOn'   => false,
        'failover'   => [],
        'port'       => 3306,
        'dateFormat' => [
            'date'     => 'Y-m-d',
            'datetime' => 'Y-m-d H:i:s',
            'time'     => 'H:i:s',
        ],
    ];

    /**
     * This database connection is used when running PHPUnit database tests.
     *
     * @var array<string, mixed>
     */
    public array $tests = [
        'DSN'         => '',
        'hostname'    => 'localhost',
        'username'    => 'admin',
        'password'    => 'pointofsale',
        'database'    => 'ospos',
        'DBDriver'    => 'MySQLi',
        'DBPrefix'    => 'ospos_',
        'pConnect'    => false,
        'DBDebug'     => (ENVIRONMENT !== 'production'),
        'charset'     => 'utf8mb4',
        'DBCollat'    => 'utf8mb4_general_ci',
        'swapPre'     => '',
        'encrypt'     => false,
        'compress'    => false,
        'strictOn'    => false,
        'failover'    => [],
        'port'        => 3306,
        'foreignKeys' => true,
        'busyTimeout' => 1000,
        'dateFormat'  => [
            'date'     => 'Y-m-d',
            'datetime' => 'Y-m-d H:i:s',
            'time'     => 'H:i:s',
        ],
    ];

    /**
     * This database connection is used when developing against non-production data.
     *
     * @var array
     */
    public array $development = [
        'DSN'         => '',
        'hostname'    => 'localhost',
        'username'    => 'admin',
        'password'    => 'pointofsale',
        'database'    => 'ospos',
        'DBDriver'    => 'MySQLi',
        'DBPrefix'    => 'ospos_',
        'pConnect'    => false,
        'DBDebug'     => (ENVIRONMENT !== 'production'),
        'charset'     => 'utf8mb4',
        'DBCollat'    => 'utf8mb4_general_ci',
        'swapPre'     => '',
        'encrypt'     => false,
        'compress'    => false,
        'strictOn'    => false,
        'failover'    => [],
        'port'        => 3306,
        'foreignKeys' => true,
        'busyTimeout' => 1000,
        'dateFormat'   => [
            'date'     => 'Y-m-d',
            'datetime' => 'Y-m-d H:i:s',
            'time'     => 'H:i:s',
        ],
    ];

    public function __construct()
    {
        parent::__construct();

        // ensure correct default group based on ENVIRONMENT constant
        switch (ENVIRONMENT) {
            case 'testing':
                $this->defaultGroup = 'tests';
                break;
            case 'development';
                $this->defaultGroup = 'development';
                break;
        }

        // ----
        // Read environment variables used in your Render/Aiven setup.
        // We intentionally read the exact names you provided:
        // DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT, DB_SSL_CA
        // ----

        // hostname / user / pass / db / port
        $envHost  = getenv('DB_HOST');
        $envUser  = getenv('DB_USER');
        $envPass  = getenv('DB_PASS');
        $envName  = getenv('DB_NAME');
        $envPort  = getenv('DB_PORT');
        $envCA    = getenv('DB_SSL_CA'); // PEM (base64/multiline) as provided

        if ($envHost !== false && $envHost !== '') {
            $this->default['hostname'] = $envHost;
            $this->tests['hostname']   = $envHost;
            $this->development['hostname'] = $envHost;
        }

        if ($envUser !== false && $envUser !== '') {
            $this->default['username'] = $envUser;
            $this->tests['username']   = $envUser;
            $this->development['username'] = $envUser;
        }

        if ($envPass !== false && $envPass !== '') {
            $this->default['password'] = $envPass;
            $this->tests['password']   = $envPass;
            $this->development['password'] = $envPass;
        }

        if ($envName !== false && $envName !== '') {
            $this->default['database'] = $envName;
            $this->tests['database']   = $envName;
            $this->development['database'] = $envName;
        }

        // Port: use environment port if provided, otherwise prefer Aiven default 15049
        if ($envPort !== false && $envPort !== '') {
            $this->default['port'] = (int) $envPort;
            $this->tests['port']   = (int) $envPort;
            $this->development['port'] = (int) $envPort;
        } else {
            // Since your Aiven instance uses 15049, we use that as fallback (not 3306)
            $this->default['port'] = 15049;
            $this->tests['port']   = 15049;
            $this->development['port'] = 15049;
        }

        // SSL / CA handling:
        // If DB_SSL_CA is provided, write it to a temporary file and configure SSL.
        // We support either a PEM string directly or a base64-encoded PEM block.
        if ($envCA !== false && $envCA !== '') {
            // Normalize newlines and remove surrounding quotes if any
            $pem = $envCA;

            // If value seems quoted (e.g. starts and ends with "), strip quotes
            if (substr($pem, 0, 1) === '"' && substr($pem, -1) === '"') {
                $pem = substr($pem, 1, -1);
            }

            // If the content looks base64 (no -----BEGIN, etc.), try to base64-decode
            if (strpos($pem, '-----BEGIN') === false && base64_decode($pem, true) !== false) {
                $decoded = base64_decode($pem, true);
                if ($decoded !== false) {
                    $pem = $decoded;
                }
            }

            // Normalize line endings to LF and write to temp file
            $pem = str_replace("\r\n", "\n", $pem);
            $pemFile = '/tmp/ca-cert.pem';
            @file_put_contents($pemFile, $pem);

            // Use encrypt array to instruct MySQLi to use SSL CA
            $this->default['encrypt'] = [
                'ssl_ca'     => $pemFile,
                'ssl_verify' => true,
            ];
            $this->tests['encrypt'] = $this->default['encrypt'];
            $this->development['encrypt'] = $this->default['encrypt'];
        } else {
            // No CA provided: leave encrypt false (no SSL)
            $this->default['encrypt'] = false;
            $this->tests['encrypt'] = false;
            $this->development['encrypt'] = false;
        }

        // Ensure DBDebug reflects CI environment if not explicitly set
        $isProd = (getenv('CI_ENVIRONMENT') ?: (ENVIRONMENT === 'production' ? 'production' : 'development')) === 'production';
        $this->default['DBDebug'] = !$isProd;
        $this->tests['DBDebug']   = !$isProd;
        $this->development['DBDebug'] = !$isProd;
    }
}
