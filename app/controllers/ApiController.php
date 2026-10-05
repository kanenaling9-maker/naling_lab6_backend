<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

class ApiController extends Controller
{
    protected $api;
    protected $db;

    public function __construct()
    {
        parent::__construct();
        $this->db = $this->call->database();
        $this->api = $this->call->library('api');
        header('Content-Type: application/json; charset=utf-8');
    }

    protected function json_body()
    {
        if (stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') === false) {
            $this->api->respond_error('Content-Type must be application/json', 415);
        }

        $body = json_decode(file_get_contents('php://input'), true);
        if (!is_array($body) || json_last_error() !== JSON_ERROR_NONE) {
            $this->api->respond_error('Request body must be a valid JSON object', 400);
        }

        return $body;
    }

    protected function product_input(array $body)
    {
        $name = $body['product_name'] ?? null;
        $description = $body['description'] ?? '';
        $price = $body['price'] ?? null;
        $quantity = $body['quantity'] ?? null;

        if (!is_string($name) || trim($name) === '') {
            $this->api->respond_error('Product name is required', 422);
        }

        $name = trim($name);
        $name_length = preg_match_all('/./us', $name, $matches);
        if ($name_length === false || $name_length > 100) {
            $this->api->respond_error('Product name must be 100 characters or fewer', 422);
        }

        if (!is_string($description) || strlen($description) > 65535) {
            $this->api->respond_error('Description must be valid text no longer than 65535 bytes', 422);
        }

        if (
            (!is_string($price) && !is_int($price) && !is_float($price)) ||
            !is_numeric($price) ||
            !is_finite((float) $price) ||
            (float) $price < 0 ||
            (float) $price > 99999999.99 ||
            !preg_match('/^\d+(?:\.\d{1,2})?$/', (string) $price)
        ) {
            $this->api->respond_error('Price must be a non-negative amount with up to two decimal places', 422);
        }

        if (
            (!is_string($quantity) && !is_int($quantity)) ||
            !preg_match('/^(0|[1-9]\d*)$/', (string) $quantity) ||
            (float) $quantity > 2147483647
        ) {
            $this->api->respond_error('Quantity must be a non-negative whole number', 422);
        }

        return [
            'product_name' => $name,
            'description' => $description,
            'price' => (string) $price,
            'quantity' => (int) $quantity,
        ];
    }

    protected function require_user()
    {
        return $this->api->require_jwt();
    }

    protected function require_scope($scope)
    {
        $user = $this->require_user();
        if (!in_array($scope, $user['scopes'] ?? [], true)) {
            $this->api->respond_error('Forbidden: insufficient permissions', 403);
        }

        return $user;
    }

    protected function require_admin()
    {
        $user = $this->require_user();
        if (($user['role'] ?? '') !== 'admin') {
            $this->api->respond_error('Forbidden: admin access required', 403);
        }

        return $user;
    }
}
