<?php

use PHPUnit\Framework\TestCase;
use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;
use App\Entities\Category;
use App\Entities\Product;
use App\Entities\Price;
use App\Entities\Attribute;
use App\Entities\Order;
use App\Factories\ProductFactory;

/**
 * Test class for database interactions and schema setup.
 */
class DbTest extends TestCase
{
    private ?PDO $pdo = null;
    private ?EntityManager $entityManager = null;
    private array $jsonData = [];

    /**
     * Sets up the database, schema, and seed data for tests.
     */
    protected function setUp(): void
    {
        // Setup Doctrine for SQLite
        $config = Setup::createAttributeMetadataConfiguration(
            [__DIR__ . '/../App/Entities'], // Path to entity classes
            true // Enable development mode
        );

        // SQLite in-memory connection
        $connectionParams = [
            'driver' => 'pdo_sqlite',
            'memory' => true
        ];

        // Create EntityManager for SQLite
        $this->entityManager = EntityManager::create($connectionParams, $config);

        // Get PDO connection
        $this->pdo = $this->entityManager->getConnection()->getNativeConnection();

        // Register product types and categories
        ProductFactory::registerProductType('tech', \App\Entities\Tech::class);
        ProductFactory::registerProductType('clothes', \App\Entities\Clothes::class);
        ProductFactory::registerCategory('tech', ['tech']);
        ProductFactory::registerCategory('clothes', ['clothes']);
        ProductFactory::registerCategory('all', ['tech', 'clothes']);

        // Create schema
        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->entityManager);
        $metadata = [
            $this->entityManager->getClassMetadata(Category::class),
            $this->entityManager->getClassMetadata(Product::class),
            $this->entityManager->getClassMetadata(Price::class),
            $this->entityManager->getClassMetadata(Attribute::class),
            $this->entityManager->getClassMetadata(Order::class),
        ];

        // Drop and recreate schema
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);

        // Load and parse JSON data for seeding
        $this->jsonData = json_decode(
            file_get_contents(__DIR__ . '/../data/data.json'),
            true
        )['data'];

        // Seed database with categories
        $categories = [];
        foreach ($this->jsonData['categories'] as $categoryData) {
            $category = new Category();
            $category->setName($categoryData['name']);
            $this->entityManager->persist($category);
            $categories[$categoryData['name']] = $category;
        }

        $this->entityManager->flush();

        // Seed database with products
        foreach ($this->jsonData['products'] as $productData) {
            $categoryName = $productData['category'];
            if (!isset($categories[$categoryName])) {
                continue;
            }

            $product = ProductFactory::create($categoryName, $categories[$categoryName]);
            $product->setName($productData['name'])
                ->setInStock($productData['inStock'])
                ->setGallery($productData['gallery'])
                ->setDescription($productData['description'])
                ->setBrand($productData['brand']);

            foreach ($productData['attributes'] as $attrData) {
                $product->addAttribute($attrData['name'], $attrData['items']);
            }

            if (!empty($productData['prices'])) {
                $priceData = $productData['prices'][0];
                $product->setPrice(
                    $priceData['amount'],
                    $priceData['currency']['label'],
                    $priceData['currency']['symbol']
                );
            }

            $categories[$categoryName]->addProduct($product);
            $this->entityManager->persist($product);
        }

        $this->entityManager->flush();
    }

    /**
     * Tests if the database exists.
     */
    public function testDatabaseExists(): void
    {
        $stmt = $this->pdo->query("SELECT name FROM sqlite_master WHERE type='table'");
        $this->assertNotEmpty($stmt->fetchAll(), 'No tables found in database.');
    }

    /**
     * Tests if the expected tables exist in the database.
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
     * Tests the structure of database tables.
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
     * Tests if the database is correctly populated with data from data.json.
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
            fn($p) => isset($p['name']) && isset($p['category'])
        );
        $this->assertEquals(
            count($validProducts),
            $count,
            'Mismatch in products count.'
        );
    }
}
