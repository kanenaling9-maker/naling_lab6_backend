<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

require_once APP_DIR . 'controllers/ApiController.php';

class Products extends ApiController
{
    public function index()
    {
        $this->require_scope('read');

        $statement = $this->db->raw(
            'SELECT id, product_name, description, price, quantity, created_at FROM products ORDER BY created_at DESC, id DESC'
        );
        $this->api->respond($statement->fetchAll(PDO::FETCH_ASSOC));
    }

    public function create()
    {
        $this->require_scope('write');
        $product = $this->product_input($this->json_body());

        $this->db->raw(
            'INSERT INTO products (product_name, description, price, quantity) VALUES (?, ?, ?, ?)',
            [
                $product['product_name'],
                $product['description'],
                $product['price'],
                $product['quantity'],
            ]
        );

        $this->api->respond(['message' => 'Product created successfully'], 201);
    }

    public function update($id)
    {
        $this->require_scope('write');
        $product_id = filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($product_id === false) {
            $this->api->respond_error('Invalid product id', 400);
        }

        $product = $this->product_input($this->json_body());
        $existing = $this->db->raw('SELECT id FROM products WHERE id = ? LIMIT 1', [$product_id]);
        if (!$existing->fetch(PDO::FETCH_ASSOC)) {
            $this->api->respond_error('Product not found', 404);
        }

        $this->db->raw(
            'UPDATE products SET product_name = ?, description = ?, price = ?, quantity = ? WHERE id = ?',
            [
                $product['product_name'],
                $product['description'],
                $product['price'],
                $product['quantity'],
                $product_id,
            ]
        );

        $this->api->respond(['message' => 'Product updated successfully']);
    }

    public function delete($id)
    {
        $this->require_scope('delete');
        $product_id = filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($product_id === false) {
            $this->api->respond_error('Invalid product id', 400);
        }

        $existing = $this->db->raw('SELECT id FROM products WHERE id = ? LIMIT 1', [$product_id]);
        if (!$existing->fetch(PDO::FETCH_ASSOC)) {
            $this->api->respond_error('Product not found', 404);
        }

        $this->db->raw('DELETE FROM products WHERE id = ?', [$product_id]);
        $this->api->respond(['message' => 'Product deleted successfully']);
    }
}
