<?php

namespace Config;

use CodeIgniter\Database\Config;

/**
 * Database Configuration
 */
class Database extends Config
{
    public string $filesPath = APPPATH . 'Database' . DIRECTORY_SEPARATOR;
    public string $defaultGroup = 'default';

    public array $default = [
        'DSN'      => '',
        'hostname' => 'localhost',
        'username' => 'admin',
        'password' => 'pointofsale',
        'database' => 'ospos',
        'DBDriver' => 'MySQLi',
        'DBPrefix' => 'ospos_',
        'pConnect' => false,
        'DBDebug'  => (ENVIRONMENT !== 'production'),
        'charset'  => 'utf8mb4',
        'DBCollat' => 'utf8mb4_general_ci',
        'swapPre'  => '',
        'encrypt'  => true, // Aiven SSL perlu ini aktif
        'compress' => false,
        'strictOn' => false,
        'failover' => [],
        'port'     => 3306,
        'ssl_ca'   => '',
    ];

    public array $tests = [];
    public array $development = [];

    public function __construct()
    {
        parent::__construct();

        // Pilih environment group
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

        // Ambil konfigurasi dari environment Render (Aiven)
        $this->default['hostname'] = getenv('DB_HOST') ?: $this->default['hostname'];
        $this->default['username'] = getenv('DB_USER') ?: $this->default['username'];
        $this->default['password'] = getenv('DB_PASS') ?: $this->default['password'];
        $this->default['database'] = getenv('DB_NAME') ?: $this->default['database'];
        $this->default['port']     = getenv('DB_PORT') ?: 3306;

        // SSL support (Render/Aiven requires CA cert)
        $this->default['encrypt'] = [
            'ssl_ca' => getenv('DB_SSL_CA') ?: null,
            'ssl_verify' => true,
        ];

        // Timezone (optional)
        date_default_timezone_set(getenv('PHP_TIMEZONE') ?: 'Asia/Jakarta');
    }
}
