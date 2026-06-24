<?php

declare(strict_types=1);

namespace EmbegeQ\Nutrisi\Tests\Database;

use EmbegeQ\Nutrisi\Config\Repository;
use EmbegeQ\Nutrisi\Container\ApplicationContainer;
use EmbegeQ\Nutrisi\Database\Connection;
use EmbegeQ\Nutrisi\Database\DatabaseManager;
use EmbegeQ\Nutrisi\Database\DatabaseServiceProvider;
use EmbegeQ\Nutrisi\Database\Query\Builder;
use EmbegeQ\Nutrisi\Database\Query\Grammars\Grammar;
use EmbegeQ\Nutrisi\Database\Query\Grammars\MySqlGrammar;
use EmbegeQ\Nutrisi\Database\Query\Grammars\PostgresGrammar;
use EmbegeQ\Nutrisi\Database\Query\Grammars\SQLiteGrammar;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PDO;

/**
 * Integrated unit tests for the Database and Query Builder modules.
 */
class DatabaseTest extends TestCase
{
    protected ApplicationContainer $container;
    protected Repository $config;
    protected DatabaseManager $db;

    protected function setUp(): void
    {
        parent::setUp();

        $this->container = new ApplicationContainer();
        $this->config = new Repository([
            'database' => [
                'default' => 'sqlite_test',
                'connections' => [
                    'sqlite_test' => [
                        'driver' => 'sqlite',
                        'database' => ':memory:',
                        'prefix' => 'emb_',
                    ],
                    'mysql_test' => [
                        'driver' => 'mysql',
                        'host' => '127.0.0.1',
                        'port' => 3306,
                        'database' => 'test_db',
                        'username' => 'root',
                        'password' => '',
                        'prefix' => 'emb_',
                    ],
                    'postgres_test' => [
                        'driver' => 'pgsql',
                        'host' => '127.0.0.1',
                        'port' => 5432,
                        'database' => 'test_db',
                        'username' => 'postgres',
                        'password' => '',
                        'prefix' => 'emb_',
                    ],
                ],
            ],
        ]);

        $this->container->instance('config', $this->config);

        $provider = new DatabaseServiceProvider();
        $provider->register($this->container);

        $this->db = $this->container->get(DatabaseManager::class);
    }

    #[Test]
    public function it_can_resolve_database_manager_and_connections(): void
    {
        $this->assertInstanceOf(DatabaseManager::class, $this->db);
        $this->assertSame('sqlite_test', $this->db->getDefaultConnection());

        $connection = $this->db->connection();
        $this->assertInstanceOf(Connection::class, $connection);
        $this->assertInstanceOf(SQLiteGrammar::class, $connection->getQueryGrammar());
    }

    #[Test]
    public function it_can_execute_raw_queries_on_sqlite(): void
    {
        $connection = $this->db->connection('sqlite_test');

        $connection->unprepared('CREATE TABLE emb_users (id INTEGER PRIMARY KEY, name TEXT, email TEXT)');

        $insertResult = $connection->insert(
            'INSERT INTO emb_users (name, email) VALUES (?, ?)',
            ['Alice', 'alice@example.com']
        );
        $this->assertTrue($insertResult);

        $selectResult = $connection->select('SELECT * FROM emb_users');
        $this->assertCount(1, $selectResult);
        $this->assertSame('Alice', $selectResult[0]['name']);
        $this->assertSame('alice@example.com', $selectResult[0]['email']);

        $updateCount = $connection->update('UPDATE emb_users SET name = ? WHERE id = ?', ['Bob', 1]);
        $this->assertSame(1, $updateCount);

        $bob = $connection->selectOne('SELECT * FROM emb_users WHERE id = ?', [1]);
        $this->assertNotNull($bob);
        $this->assertSame('Bob', $bob['name']);

        $deleteCount = $connection->delete('DELETE FROM emb_users WHERE id = ?', [1]);
        $this->assertSame(1, $deleteCount);

        $empty = $connection->select('SELECT * FROM emb_users');
        $this->assertEmpty($empty);
    }

    #[Test]
    public function it_supports_transactions_and_nested_savepoints(): void
    {
        $connection = $this->db->connection('sqlite_test');
        $connection->unprepared('CREATE TABLE emb_users (id INTEGER PRIMARY KEY, name TEXT)');

        // Test normal commit
        $connection->beginTransaction();
        $connection->insert('INSERT INTO emb_users (name) VALUES (?)', ['Alice']);
        $connection->commit();

        $this->assertSame(1, $connection->table('users')->count());

        // Test normal rollback
        $connection->beginTransaction();
        $connection->insert('INSERT INTO emb_users (name) VALUES (?)', ['Bob']);
        $connection->rollBack();

        $this->assertSame(1, $connection->table('users')->count());

        // Test nested savepoints rollback
        $connection->beginTransaction(); // Level 1
        $connection->insert('INSERT INTO emb_users (name) VALUES (?)', ['Bob']);

        $connection->beginTransaction(); // Level 2 (Savepoint)
        $connection->insert('INSERT INTO emb_users (name) VALUES (?)', ['Charlie']);
        $connection->rollBack(); // Rollback Level 2

        $connection->commit(); // Commit Level 1

        $this->assertSame(2, $connection->table('users')->count());
        $this->assertSame(1, $connection->table('users')->where('name', 'Bob')->count());
        $this->assertSame(0, $connection->table('users')->where('name', 'Charlie')->count());

        // Test transaction callback helper
        $connection->transaction(function (Connection $conn) {
            $conn->insert('INSERT INTO emb_users (name) VALUES (?)', ['Dave']);
        });

        $this->assertSame(3, $connection->table('users')->count());
    }

    #[Test]
    public function it_compiles_sql_correctly_with_sqlite_grammar(): void
    {
        $connection = $this->db->connection('sqlite_test');
        $builder = $connection->table('users');

        $this->assertSame('select * from "emb_users"', $builder->toSql());

        $builder->where('id', 1)->where('status', 'active');
        $this->assertSame('select * from "emb_users" where "id" = ? and "status" = ?', $builder->toSql());
        $this->assertSame([1, 'active'], $builder->getBindings());
    }

    #[Test]
    public function it_compiles_sql_correctly_with_mysql_grammar(): void
    {
        $connection = new Connection(fn() => new PDO('sqlite::memory:'), 'test', 'emb_');
        $connection->setQueryGrammar(new MySqlGrammar($connection));

        $builder = $connection->table('users')
            ->select('id', 'name as user_name')
            ->where('id', '>', 5)
            ->orderBy('id', 'desc')
            ->limit(10)
            ->offset(20);

        $this->assertSame(
            'select `id`, `name` as `user_name` from `emb_users` where `id` > ? order by `id` desc limit 10 offset 20',
            $builder->toSql()
        );
    }

    #[Test]
    public function it_compiles_sql_correctly_with_postgres_grammar(): void
    {
        $connection = new Connection(fn() => new PDO('sqlite::memory:'), 'test', 'emb_');
        $connection->setQueryGrammar(new PostgresGrammar($connection));

        $builder = $connection->table('users')
            ->select('id', 'name')
            ->where('id', '>', 5);

        $this->assertSame(
            'select "id", "name" from "emb_users" where "id" > ?',
            $builder->toSql()
        );
    }

    #[Test]
    public function it_performs_query_builder_crud_operations(): void
    {
        $connection = $this->db->connection('sqlite_test');
        $connection->unprepared('CREATE TABLE emb_posts (id INTEGER PRIMARY KEY, title TEXT, status TEXT, views INTEGER)');

        // Insert
        $inserted = $connection->table('posts')->insert([
            'title' => 'Post 1',
            'status' => 'published',
            'views' => 100,
        ]);
        $this->assertTrue($inserted);

        // Batch Insert
        $connection->table('posts')->insert([
            ['title' => 'Post 2', 'status' => 'draft', 'views' => 10],
            ['title' => 'Post 3', 'status' => 'published', 'views' => 200],
        ]);

        // Count
        $this->assertSame(3, $connection->table('posts')->count());
        $this->assertSame(2, $connection->table('posts')->where('status', 'published')->count());

        // Get
        $posts = $connection->table('posts')->where('status', 'published')->orderBy('views', 'asc')->get();
        $this->assertCount(2, $posts);
        $this->assertSame('Post 1', $posts[0]['title']);
        $this->assertSame('Post 3', $posts[1]['title']);

        // First & Value
        $first = $connection->table('posts')->where('id', 2)->first();
        $this->assertNotNull($first);
        $this->assertSame('Post 2', $first['title']);

        $title = $connection->table('posts')->where('id', 2)->value('title');
        $this->assertSame('Post 2', $title);

        // Pluck
        $titles = $connection->table('posts')->orderBy('id', 'asc')->pluck('title');
        $this->assertSame(['Post 1', 'Post 2', 'Post 3'], $titles);

        $titledKeys = $connection->table('posts')->orderBy('id', 'asc')->pluck('title', 'id');
        $this->assertSame([1 => 'Post 1', 2 => 'Post 2', 3 => 'Post 3'], $titledKeys);

        // Exists
        $this->assertTrue($connection->table('posts')->where('id', 1)->exists());
        $this->assertFalse($connection->table('posts')->where('id', 99)->exists());

        // Update
        $affected = $connection->table('posts')->where('id', 2)->update(['status' => 'published', 'views' => 15]);
        $this->assertSame(1, $affected);
        $this->assertSame('published', $connection->table('posts')->where('id', 2)->value('status'));

        // Delete
        $deleted = $connection->table('posts')->where('id', 3)->delete();
        $this->assertSame(1, $deleted);
        $this->assertSame(2, $connection->table('posts')->count());
    }

    #[Test]
    public function it_supports_complex_wheres_and_joins(): void
    {
        $connection = $this->db->connection('sqlite_test');
        $connection->unprepared('CREATE TABLE emb_users (id INTEGER PRIMARY KEY, name TEXT)');
        $connection->unprepared('CREATE TABLE emb_profiles (id INTEGER PRIMARY KEY, user_id INTEGER, bio TEXT)');

        $connection->table('users')->insert(['id' => 1, 'name' => 'Alice']);
        $connection->table('profiles')->insert(['id' => 10, 'user_id' => 1, 'bio' => 'Developer']);

        // WhereIn, WhereNull, WhereBetween
        $builder = $connection->table('users')
            ->whereIn('id', [1, 2, 3])
            ->whereNull('name', 'or')
            ->whereBetween('id', [1, 10]);

        $this->assertSame(
            'select * from "emb_users" where "id" in (?, ?, ?) or "name" is null and "id" between ? and ?',
            $builder->toSql()
        );
        $this->assertSame([1, 2, 3, 1, 10], $builder->getBindings());

        // Join
        $joined = $connection->table('users')
            ->join('profiles', 'users.id', '=', 'profiles.user_id')
            ->select('users.name', 'profiles.bio')
            ->first();

        $this->assertNotNull($joined);
        $this->assertSame('Alice', $joined['name']);
        $this->assertSame('Developer', $joined['bio']);
    }
}
