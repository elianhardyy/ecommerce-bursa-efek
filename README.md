# Laravel E-Commerce API

A comprehensive RESTful API for an e-commerce platform built with Laravel.

## Features

-   User authentication with JWT
-   Role-based authorization (Admin, Merchant, Customer)
-   Product management
-   Category management
-   Shopping cart functionality
-   Order processing
-   Payment handling
-   Transaction history
-   User reward points
-   API response caching

## Requirements

-   PHP >= 8.1
-   Composer
-   MySQL >= 5.7
-   Laravel 9.x

## Installation

1. Clone the repository:

```bash
git clone https://github.com/yourusername/laravel-ecommerce-api.git
cd laravel-ecommerce-api
```

2. Install dependencies:

```bash
composer install
```

3. Copy the environment file:

```bash
cp .env.example .env
```

4. Generate application key:

```bash
php artisan key:generate
```

5. Generate JWT secret:

```bash
php artisan jwt:secret
```

6. Configure your database in `.env` file:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ecommerce
DB_USERNAME=root
DB_PASSWORD=
```

7. Run migrations and seeders:

```bash
php artisan migrate --seed
```

8. Create symbolic link for storage:

```bash
php artisan storage:link
```

9. Start the development server:

```bash
php artisan serve
```

## API Endpoints

### Authentication

-   `POST /api/auth/register` - Register a new user
-   `POST /api/auth/login` - Login and get token
-   `POST /api/auth/logout` - Logout and invalidate token
-   `POST /api/auth/refresh` - Refresh token
-   `GET /api/auth/me` - Get authenticated user

### Categories

-   `GET /api/category-products` - Get all categories
-   `GET /api/category-products/{id}` - Get a specific category
-   `POST /api/category-products` - Create a new category
-   `PUT /api/category-products/{id}` - Update a category
-   `DELETE /api/category-products/{id}` - Delete a category

### Products

-   `GET /api/products` - Get all products
-   `GET /api/products/{id}` - Get a specific product
-   `POST /api/products` - Create a new product
-   `PUT /api/products/{id}` - Update a product
-   `DELETE /api/products/{id}` - Delete a product
-   `POST /api/products/{id}/rating` - Rate a product

### Cart

-   `GET /api/cart` - Get user's cart
-   `POST /api/cart` - Add item to cart
-   `PUT /api/cart/{id}` - Update cart item
-   `DELETE /api/cart/{id}` - Remove item from cart

### Orders

-   `GET /api/orders/list` - Get all orders (admin/merchant)
-   `GET /api/orders/my-orders` - Get user's orders
-   `GET /api/orders/{id}` - Get a specific order
-   `POST /api/orders` - Create a new order
-   `PUT /api/orders/{id}/status` - Update order status
-   `POST /api/orders/{id}/pay` - Process payment for an order

### Transactions

-   `GET /api/transactions` - Get user transactions
-   `GET /api/transactions/{id}` - Get a specific transaction
-   `GET /api/transactions/{id}/points` - Get points earned from a transaction
-   `GET /api/points` - Get user points
-   `POST /api/transactions/refund` - Process refund
-   `GET /api/transactions/report` - Generate transaction report

### Users

-   `GET /api/users` - Get all users (admin only)
-   `GET /api/users/{id}` - Get a specific user

## Default Users

The system is seeded with the following default users:

1. Admin User:

    - Email: admin@example.com
    - Password: password

2. Merchant User:

    - Email: merchant@example.com
    - Password: password

3. Customer User:
    - Email: customer@example.com
    - Password: password

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
