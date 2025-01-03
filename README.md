# ScandiWeb Full Stack Test - Back End

## Overview

This project is a backend application developed as part of the ScandiWeb Full Stack Test. It is designed to manage a product catalog, orders, and associated operations using PHP and Doctrine ORM. The project includes basic CRUD functionality, database management, and testing capabilities.

## Features

- **Product Management**: Create, read, update, and delete products.
- **Category Management**: Handle product categories.
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

## Installation

### Prerequisites

Ensure the following tools are installed on your system:

- Docker
- Docker Compose
- Composer

### Steps

1. Clone the repository:

   ```bash
   git clone <repository-url>
   cd Back_End
   ```

2. Copy the `.env.example` file to `.env` and configure your environment variables:

   ```bash
   cp .env.example .env
   ```

3. Build and start the containers:

   ```bash
   docker-compose up --build -d
   ```

4. Install Composer dependencies:

   ```bash
   docker exec -it php_app composer install
   ```

5. Run the database migrations:

   ```bash
   docker exec -it php_app php bin/console doctrine:migrations:migrate
   ```

6. Seed the database:
   ```bash
   docker exec -it db_seeder php seed.php
   ```

## Usage

### Access the Application

- The backend application is available on `http://localhost:9000`.
- Logs are stored in the `/logs` directory.

### Run Tests

To execute the test suite:

```bash
docker exec -it php_app ./vendor/bin/phpunit
```

### Fix Code Standards

To fix code standards using PHP-CS-Fixer:

```bash
docker exec -it php_cs_fixer composer fix-cs
```

## Project Structure

- **App/**: Contains the application logic, including controllers, entities, and factories.
- **config/**: Configuration files for the database and application.
- **data/**: Contains the `data.json` file for database seeding.
- **logs/**: Stores log files.
- **public/**: The document root for the PHP server.
- **tests/**: Contains PHPUnit test cases.

## License

This project is licensed under the MIT License. See the `LICENSE` file for details.

## Contact

For any questions or issues, please contact:

- **Name**: Orlando Fonseca
- **Email**: [your-email@example.com]

---

Thank you for using this application!
