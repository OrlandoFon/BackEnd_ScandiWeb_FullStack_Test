<?php

namespace Tests\UnitaryTesting;

use PHPUnit\Framework\TestCase;
use PDO;
use Doctrine\ORM\EntityManager;
use Tests\TestSetup;

/**
 * Database Test Suite
 *
 * This class contains tests to verify the existence, structure, and population of the SQLite database.
 */
class DbTest extends TestCase
{
    /**
     * @var PDO The PDO connection to the SQLite database.
     */
    private \PDO $pdo;

    /**
     * @var EntityManager The Doctrine EntityManager instance.
     */
    protected static $entityManager;

    /**
     * @var array The data loaded from the JSON seed file.
     */
    private array $jsonData = [];

    /**
     * Initializes the EntityManager and populates the database before all tests.
     *
     * This method is executed once before any tests are run. It ensures that the database is
     * initialized and populated with the necessary test data.
     */
    public static function setUpBeforeClass(): void
    {
        // Initialize the EntityManager
        self::$entityManager = TestSetup::initializeEntityManager();

        // Populate the database, ensuring it is clean before populating
        TestSetup::populateDatabase(self::$entityManager);

        // Optional: Verify that the data has been correctly persisted
        $categories = self::$entityManager->getRepository(\App\Entities\Category::class)->findAll();
        $products = self::$entityManager->getRepository(\App\Entities\Product::class)->findAll();

        error_log(sprintf(
            '[DbTest] Database initialized: %d categories, %d products',
            count($categories),
            count($products)
        ));
    }

    /**
     * Sets up the test environment before each test.
     *
     * This method is executed before each test method. It establishes a PDO connection
     * and loads the test data from the JSON seed file.
     */
    protected function setUp(): void
    {
        // Obtain the PDO connection from the EntityManager
        $this->pdo = self::$entityManager->getConnection()->getNativeConnection();

        // Load test data from the JSON file
        $dataFile = __DIR__ . '/../../data/data.json';
        if (!file_exists($dataFile)) {
            throw new \RuntimeException("Data file not found: $dataFile");
        }

        $decodedData = json_decode(file_get_contents($dataFile), true);
        if (!isset($decodedData['data'])) {
            throw new \RuntimeException("Invalid data.json structure. The 'data' key is required.");
        }

        $this->jsonData = $decodedData['data'];
    }

    /**
     * Tests whether the database exists by checking for the presence of tables.
     *
     * @return void
     */
    public function testDatabaseExists(): void
    {
        $stmt = $this->pdo->query("SELECT name FROM sqlite_master WHERE type='table'");
        $tables = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->assertNotEmpty($tables, 'No tables found in the database.');
    }

    /**
     * Tests whether the expected tables exist in the database.
     *
     * @return void
     */
    public function testTablesExist(): void
    {
        $expectedTables = ['categories', 'products', 'attributes', 'prices'];
        $stmt = $this->pdo->query("SELECT name FROM sqlite_master WHERE type='table'");
        $tables = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'name');

        foreach ($expectedTables as $table) {
            $this->assertContains($table, $tables, "Table '$table' not found.");
        }
    }

    /**
     * Tests the structure of the database tables.
     *
     * @return void
     */
    public function testTableStructures(): void
    {
        $expectedStructures = [
            'categories' => ['id', 'name'],
            'products' => ['id', 'category_id', 'name', 'inStock', 'gallery', 'description', 'brand'],
            'attributes' => ['id', 'product_id', 'name', 'items'],
            'prices' => ['id', 'product_id', 'amount', 'currency_label', 'currency_symbol'],
        ];

        foreach ($expectedStructures as $table => $expectedColumns) {
            $stmt = $this->pdo->query("PRAGMA table_info($table)");
            $actualColumns = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'name');

            foreach ($expectedColumns as $column) {
                $this->assertContains(
                    $column,
                    $actualColumns,
                    "Column '$column' not found in '$table'."
                );
            }
        }
    }

    /**
     * Tests whether the database is correctly populated with data from data.json.
     *
     * @return void
     */
    public function testDatabasePopulated(): void
    {
        // Validate the count of categories
        $stmt = $this->pdo->query('SELECT COUNT(*) as cnt FROM categories');
        $count = (int)$stmt->fetch(PDO::FETCH_ASSOC)['cnt'];
        $this->assertEquals(
            count($this->jsonData['categories']),
            $count,
            'Mismatch in categories count.'
        );

        // Validate the count of products
        $stmt = $this->pdo->query('SELECT COUNT(*) as cnt FROM products');
        $count = (int)$stmt->fetch(PDO::FETCH_ASSOC)['cnt'];

        // Filter valid products from JSON
        $validProducts = array_filter(
            $this->jsonData['products'] ?? [],
            fn ($p) => isset($p['name']) && isset($p['category'])
        );
        $this->assertEquals(
            count($validProducts),
            $count,
            'Mismatch in products count.'
        );
    }
}
