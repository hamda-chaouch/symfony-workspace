<?php

namespace App\Doctrine;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsMiddleware;
use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\Driver\Middleware;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\ServerVersionProvider;

#[AsMiddleware(connections: ['trading_bot'])]
final class SqlitePragmaMiddleware implements Middleware
{
    public function wrap(Driver $driver): Driver
    {
        return new class($driver) implements Driver {

            private Driver $driver;

            public function __construct(Driver $driver)
            {
                $this->driver = $driver;
            }

            public function connect(array $params): Connection
            {
                $connection = $this->driver->connect($params);

                try {
                    if (method_exists($connection, 'getNativeConnection')) {
                        $native = $connection->getNativeConnection();
                        if ($native instanceof \PDO) {
                            $native->exec('PRAGMA journal_mode = WAL');
                            $native->exec('PRAGMA synchronous = NORMAL');
                            $native->exec('PRAGMA busy_timeout = 5000');
                            $native->exec('PRAGMA foreign_keys = ON');
                        }
                    }
                } catch (\Throwable $e) {
                    error_log('SQLite PRAGMA initialization failed: ' . $e->getMessage());
                }

                return $connection;
            }

            public function getDatabasePlatform(ServerVersionProvider $versionProvider): AbstractPlatform
            {
                return $this->driver->getDatabasePlatform($versionProvider);
            }

            public function getExceptionConverter(): \Doctrine\DBAL\Driver\API\ExceptionConverter
            {
                return $this->driver->getExceptionConverter();
            }
        };
    }
}
