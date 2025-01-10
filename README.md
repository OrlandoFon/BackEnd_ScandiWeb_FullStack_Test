# ScandiWeb Full Stack Test - Back End

## Overview

This project is a backend application developed as part of the ScandiWeb Junior Full Stack Dev test. It is designed to manage a product catalog, orders, and associated operations using PHP, GraphQL and Doctrine ORM for seting up and manage a MySQL database. The project includes basic CRUD functionality, database management, and testing capabilities.

## Features

- **Product Management**: Create, read, update, and delete products.
- **Order Management**: Create and manage orders for products.
- **Doctrine ORM**: Utilized for database interactions.
- **Testing**: Includes PHPUnit tests to validate functionality.
- **Logging**: Logs application events to the `/logs` directory.
- **PHP-CS-Fixer**: Ensures code follows PSR standards.

## Technologies Used

- **PHP**: Version 8.1.
- **Doctrine ORM**: For database interactions.
- **MySQL**: Used as the database.
- **Docker**: For containerization.
- **PHPUnit**: For testing.
- **PHP-CS-Fixer**: For coding standards compliance.

---

### How the Code Works

Using Doctrine ORM, the application establishes relationships between database classes and tables.

#### **Entities and Database Interactions**

##### **Abstract Classes**

- **`Order`**: Serves as the base class for orders. Defines a structure for storing products as JSON, managing totals, and handling timestamps for order creation.
- **`Product`**: A base class for products, encapsulating shared fields like name, price, attributes, and relationships with categories.

##### **Concrete Classes**

- **`StandardOrder`**: Implements `Order` and provides logic for calculating the total cost of ordered products.
- **`StandardProduct`**: Extends `Product` and introduces category validation and attribute management specific to each product type.
- **`Category`**: Represents product categories and defines their relationships with products.
- **`Price`**: Manages product pricing, including support for multiple currencies.
- **`Attribute`**: Defines attributes (e.g., size, color) and their possible values for products.
- **`Currency`**: An embeddable class within `Price` to handle currency information (e.g., label and symbol).

---

#### **Factories**

- **`OrderFactory`**: Responsible for creating and persisting orders. Validates product availability, handles attribute matching, and ensures database transactions are atomic.
- **`ProductFactory`**: Simplifies the creation of products by handling category registration and initializing attributes for different categories.

---

#### **Standard Implementation**

The **Standard** classes provide concrete functionality for managing products and orders:

- **`StandardOrder`**: Implements custom logic to calculate the total cost of an order, considering products and quantities.
- **`StandardProduct`**: Enhances `Product` by enforcing category-specific attribute validation and enabling flexible attribute management.

---

### Database Seeding

The database seeding process initializes the database with default data from `data.json`, including categories, products, and prices. This is managed by the `seed.php` script, which leverages the `Seeder` class to:

- Clean the database by removing outdated or existing data.
- Populate tables with predefined entries for products, categories, and pricing information.

The seeding process is automatically executed during the build process by a dedicated container, `db_seeder`. If needed, the seeding script can also be manually executed with the following command:

```bash
docker exec -it php_app php /var/www/html/config/seed.php
```

---

### GraphQL Queries and Mutations

The application exposes a GraphQL API with the following capabilities:

- **Queries**: Fetch data for categories, products, attributes, prices, and orders.
- **Mutations**: Create and update products or orders through structured inputs with strong type validation.

---

### Reverse Proxy with NGINX

NGINX acts as a reverse proxy, forwarding requests to the backend PHP application. It is configured to:

- Handle Cross-Origin Resource Sharing (CORS) for frontend communication.
- Route requests to the `/graphql` endpoint at `http://php_app:9000`.

---

### Logging

Application logs are stored in the `/logs` directory, including:

- General application logs.
- GraphQL-specific logs for debugging queries and mutations.

---

### Testing

The backend includes a comprehensive testing setup:

- **`DbTest`**: Ensures the database is correctly structured and seeded.
- **`GraphQLTest`**: Validates the functionality of queries and mutations.

Tests are executed in an isolated SQLite database using `TestSetup`, which provides a consistent environment for validation.

---

## Installation

### Prerequisites

Ensure the following tools are installed on your system:

- Docker
- Docker Compose
- Composer

### Steps

1. **Clone the Repository**  
   Start by cloning the repository to your local environment:

   ```bash
   git clone https://github.com/OrlandoFon/BackEnd_ScandiWeb_FullStack_Test.git
   cd BackEnd_ScandiWeb_FullStack_Test
   ```

2. **Configure Environment Variables**  
   The application requires specific environment variables to be set for database connectivity and testing.

   Create a `.env` file by copying the `.env.example` file:

   ```bash
   cp .env.example .env
   ```

   Update the following variables in the `.env` file:

   **Environment Variable Descriptions:**

   - **`DB_HOST`**: The hostname of the MySQL container.  
     Default: `mysql_db`

   - **`DB_NAME`**: The name of the database used for the application.  
     Default: `ecommercedata`

   - **`DB_USER`**: The username for connecting to the database.  
     Default: `scandiweb_user`

   - **`DB_PASSWORD`**: The password for the database user.  
     Default: `scandiweb_password`

   - **`DB_ROOT_PASSWORD`**: The root password for the MySQL database.  
     Default: `root`

   - **`TESTING`**: A flag to indicate if the application is running in testing mode.  
     Default: `0` (set to `1` for enabling testing configurations)

3. **Configure NGINX for Frontend Connectivity**

   Update the `nginx.conf` file to correctly handle CORS and allow connections from the frontend. Replace the placeholder `<frontend-localhost>` with the appropriate frontend URL (e.g., `http://localhost:5173` for Vite):

   ```
     add_header 'Access-Control-Allow-Origin' '<frontend-localhost>' always;
   ```

   Save the updated configuration.

4. **Run the Application**

   Build and start the Docker containers using Docker Compose:

   ```bash
   docker compose up --build -d
   ```

   After the containers are running, install the PHP dependencies using Composer:

   ```bash
   docker compose exec app composer install
   ```
   
5. **Verify the Setup**  
   Once the containers are running, you can access the backend:

   - Backend (via NGINX): `http://localhost:8080`
   - Direct PHP server: `http://localhost:9000`

---

## Usage

### Access the Application

The backend application can be accessed via a reverse proxy configured in NGINX at `http://localhost:8080`. Ensure the `nginx.conf` is correctly set up to accept connections from the front-end (e.g., Vite running on `http://localhost:5173`).

The primary endpoint for interacting with the backend is:

- **Endpoint**: `/graphql`  
  This endpoint allows GraphQL queries and mutations to interact with the database.

---

### Queries

You can send GraphQL queries to retrieve data from the following database tables:

- `attributes`
- `categories`
- `orders`
- `prices`
- `products`

#### Example Queries

1. **Fetch All Products**  
   This query retrieves all products along with their attributes, prices, and associated categories.

   ```graphql
   query {
     products {
       id
       name
       brand
       description
       inStock
       gallery
       price {
         amount
         currency {
           label
           symbol
         }
       }
       attributes {
         name
         items {
           value
           displayValue
         }
       }
       category {
         id
         name
       }
     }
   }
   ```

2. **Fetch All Categories**  
   This query retrieves all product categories.

   ```graphql
   query {
     categories {
       id
       name
     }
   }
   ```

3. **Fetch Product by ID**  
   This query retrieves a specific product by its ID, including details such as name, brand, and price.

   ```graphql
   query ($id: Int!) {
     product(id: $id) {
       id
       name
       brand
       description
       inStock
       gallery
       price {
         amount
         currency {
           label
           symbol
         }
       }
       attributes {
         name
         items {
           value
           displayValue
         }
       }
       category {
         id
         name
       }
     }
   }
   ```

   **Variables:**

   ```json
   {
     "id": 1
   }
   ```

4. **Fetch All Orders**  
   This query retrieves all orders, including the ordered products and their details.

   ```graphql
   query {
     orders {
       id
       orderedProducts {
         product {
           name
           brand
         }
         quantity
         unitPrice
         total
         selectedAttributes {
           name
           value
         }
       }
       total
       createdAt
     }
   }
   ```

5. **Fetch All Attributes**  
   This query retrieves all attributes and their possible values.

   ```graphql
   query {
     attributes {
       name
       items {
         value
         displayValue
       }
     }
   }
   ```

6. **Fetch All Prices**  
   This query retrieves all price records, including amounts and currency details.

   ```graphql
   query {
     prices {
       amount
       currency {
         label
         symbol
       }
     }
   }
   ```

---

### Mutations

The following GraphQL mutations are available for managing the backend data:

1. **Create Order**  
   Create a new order by specifying products and their attributes.

   **Mutation:**

   ```graphql
   mutation CreateOrder($products: [OrderProductInput!]!) {
     createOrder(products: $products) {
       id
       orderedProducts {
         product {
           name
         }
         quantity
         unitPrice
         total
         selectedAttributes {
           name
           value
         }
       }
       total
       createdAt
     }
   }
   ```

   **Variables:**

   ```json
   {
     "products": [
       {
         "productId": 1,
         "quantity": 2,
         "selectedAttributes": [
           { "name": "Size", "value": "Medium" },
           { "name": "Color", "value": "Blue" }
         ]
       }
     ]
   }
   ```

2. **Create Product**  
   Add a new product with attributes, price, and category.

   **Mutation:**

   ```graphql
   mutation {
     createProduct(
       name: "New Product"
       category: "tech"
       brand: "Brand Name"
       description: "Product Description"
       inStock: true
       gallery: ["url1", "url2"]
       attributes: [
         { name: "Size", items: [{ value: "L", displayValue: "Large" }] }
       ]
       price: { amount: 99.99, currency: { label: "USD", symbol: "$" } }
     ) {
       id
       name
       brand
       description
       inStock
     }
   }
   ```

3. **Update Product**  
   Modify an existing product.

   **Mutation:**

   ```graphql
   mutation {
     updateProduct(
       id: 1
       name: "Updated Product Name"
       description: "Updated Description"
     ) {
       id
       name
       description
     }
   }
   ```

4. **Delete Product**  
   Remove a product by its ID.

   **Mutation:**

   ```graphql
   mutation {
     deleteProduct(id: 1)
   }
   ```

---

### Run Tests

To execute the test suite, ensure that the environment variable `TESTING` is set to `1` or `true` in your `.env` file. This enables the test configuration for the application.

The project includes two test files:

- **`DbTest`**: Verifies that the database structure and seeding process are correctly implemented.
- **`GraphQLTest`**: Ensures that all GraphQL queries and mutations work as expected.

Both tests use a shared setup defined in the `TestSetup` class, which creates a file-based SQLite database (`test_db.sqlite`) to simulate the production database for testing. The database is seeded with test data using the `Seeder` class.

#### Execute the Tests

Execute the following command to run all tests:

```bash
docker exec -it php_app ./vendor/bin/phpunit /var/www/html/tests
```

---

## Project Structure

- **App/**: Contains the application logic, including controllers, entities, and factories.
- **config/**: Configuration files for the database and application.
- **data/**: Contains the `data.json` file for database seeding.
- **logs/**: Stores log files.
- **public/**: The document root for the PHP server.
- **tests/**: Contains PHPUnit test cases.

---

Thank you for using this application!
