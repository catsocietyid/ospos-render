<?php

namespace Config;

use CodeIgniter\Database\Config;
use CodeIgniter\Database\Exceptions\DatabaseException;

/**
 * Database Configuration (Final Aiven-compatible)
 */
class Database extends Config
{
    public string $filesPath = APPPATH . 'Database' . DIRECTORY_SEPARATOR;
    public string $defaultGroup = 'default';

    public array $default = [
        'DSN'          => '',
        'hostname'     => 'localhost',
        'username'     => 'admin',
        'password'     => 'pointofsale',
        'database'     => 'ospos',
        'DBDriver'     => 'MySQLi',
        'DBPrefix'     => 'ospos_',
        'pConnect'     => false,
        'DBDebug'      => (ENVIRONMENT !== 'production'),
        'charset'      => 'utf8mb4',
        'DBCollat'     => 'utf8mb4_general_ci',
        'swapPre'      => '',
        'encrypt'      => false,
        'compress'     => false,
        'strictOn'     => false,
        'failover'     => [],
        'port'         => 3306,
        'dateFormat'   => [
            'date'     => 'Y-m-d',
            'datetime' => 'Y-m-d H:i:s',
            'time'     => 'H:i:s',
        ],
    ];

    public array $tests = [];
    public array $development = [];

    public function __construct()
    {
        parent::__construct();

        // Baca environment Aiven (Render)
        $envHost = getenv('DB_HOST') ?: 'localhost';
        $envUser = getenv('DB_USER') ?: 'admin';
        $envPass = getenv('DB_PASS') ?: 'pointofsale';
        $envName = getenv('DB_NAME') ?: 'ospos';
        $envPort = getenv('DB_PORT') ?: 3306;
        $envCA   = getenv('DB_SSL_CA') ?: '';

        // Terapkan ke semua koneksi
        foreach ([&$this->default, &$this->tests, &$this->development] as &$config) {
            $config['hostname'] = $envHost;
            $config['username'] = $envUser;
            $config['password'] = $envPass;
            $config['database'] = $envName;
            $config['port']     = (int)$envPort;
        }

        // === SSL Handling (Aiven-compatible) ===
        $pemFile = '/tmp/aiven-ca.pem';
        if ($envCA !== '') {
            try {
                // Simpan sertifikat SSL ke file
                file_put_contents($pemFile, $envCA);

                // Jika file valid, gunakan mode SSL terverifikasi
                if (is_file($pemFile) && filesize($pemFile) > 100) {
                    $sslConfig = [
                        'ssl_ca'       => $pemFile,
                        'ssl_verify'   => false, // disable strict verification, Aiven compatible
                        'ssl_key'      => null,
                        'ssl_cert'     => null,
                        'ssl_capath'   => null,
                        'ssl_cipher'   => null,
                        'ssl_disable'  => false,
                    ];
                    $this->default['encrypt'] = $sslConfig;
                    $this->development['encrypt'] = $sslConfig;
                    $this->tests['encrypt'] = $sslConfig;
                } else {
                    // Jika file gagal dibuat → gunakan SSL tanpa CA
                    $this->default['encrypt'] = true;
                    $this->development['encrypt'] = true;
                    $this->tests['encrypt'] = true;
                }
            } catch (\Throwable $e) {
                // Fallback total: koneksi SSL tanpa CA
                $this->default['encrypt'] = true;
                $this->development['encrypt'] = true;
                $this->tests['encrypt'] = true;
            }
        } else {
            // Jika tidak ada DB_SSL_CA sama sekali
            $this->default['encrypt'] = true;
            $this->development['encrypt'] = true;
            $this->tests['encrypt'] = true;
        }

        // === Set default group berdasarkan environment ===
        switch (ENVIRONMENT) {
            case 'testing':
                $this->defaultGroup = 'tests';
                break;
            case 'development':
                $this->defaultGroup = 'development';
                break;
            default:
                $this->defaultGroup = 'default';
                break;
        }
    }
}
