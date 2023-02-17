<?php

declare(strict_types=1);

namespace Driver\Engines;

use PDO;
use PDOException;
use PDOStatement;

// phpcs:disable Generic.Files.LineLength
/**
 * @method PDOStatement|false prepare($query, array $options = [])
 * @method bool beginTransaction()
 * @method bool commit()
 * @method bool rollBack()
 * @method bool inTransaction()
 * @method bool setAttribute(int $attribute, $value)
 * @method int|false exec(string $statement)
 * @method PDOStatement|false query(string $statement, int $mode = PDO::ATTR_DEFAULT_FETCH_MODE, $arg3 = null, array $ctorargs = [])
 * @method string|false lastInsertId(?string $name)
 * @method mixed errorCode()
 * @method array errorInfo()
 * @method mixed getAttribute(int $attribute)
 * @method string|false quote(string $string, int $type = PDO::PARAM_STR)
 * @method static array getAvailableDrivers()
 * @method bool sqliteCreateFunction($function_name, $callback, int $num_args = -1, int $flags = 0)
 * @method bool pgsqlCopyFromArray(string $tableName, array $rows, string $separator, string $nullAs, ?string $fields)
 * @method bool pgsqlCopyFromFile(string $tableName, string $filename, string $separator = "\t", string $nullAs = "\\\\N", ?string $fields = null)
 * @method array|false pgsqlCopyToArray(string $tableName, string $separator = "\t", string $nullAs = "\\\\N", ?string $fields = null)
 * @method bool pgsqlCopyToFile(string $tableName, string $filename, string $separator = "\t", string $nullAs = "\\\\N", ?string $fields = null)
 * @method string|false pgsqlLOBCreate()
 * @method resource|false pgsqlLOBOpen(string $oid, string $mode = "rb")
 * @method bool pgsqlLOBUnlink(string $oid)
 * @method array|false pgsqlGetNotify(int $fetchMode = 0, int $timeoutMilliseconds = 0)
 * @method int pgsqlGetPid()
 */
// phpcs:enable
class ReconnectingPDO
{
    private const MYSQL_GENERAL_ERROR_CODE = 'HY000';
    private const SERVER_HAS_GONE_AWAY_ERROR_CODE = 2006;

    private string $dsn;
    private ?string $username;
    private ?string $password;
    /** @var array<int, string>|null */
    private ?array $options;
    private PDO $pdo;

    /**
     * @throws PDOException
     */
    public function __construct(string $dsn, ?string $username = null, ?string $password = null, ?array $options = null)
    {
        $this->dsn = $dsn;
        $this->username = $username;
        $this->password = $password;
        $this->options = $options;
        $this->pdo = $this->createPDO();
    }

    /**
     * @throws PDOException
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification,SlevomatCodingStandard.TypeHints.ReturnTypeHint
    public function __call(string $name, array $arguments)
    {
        try {
            $this->pdo->query('SELECT 1')->fetchColumn();
        } catch (PDOException $e) {
            if (
                $e->errorInfo[0] !== self::MYSQL_GENERAL_ERROR_CODE
                || $e->errorInfo[1] !== self::SERVER_HAS_GONE_AWAY_ERROR_CODE
            ) {
                throw $e;
            }
            $this->pdo = $this->createPDO();
        }
        return $this->pdo->$name(...$arguments);
    }

    /**
     * @throws PDOException
     */
    private function createPDO(): PDO
    {
        $pdo = new PDO($this->dsn, $this->username, $this->password, $this->options);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    }
}
