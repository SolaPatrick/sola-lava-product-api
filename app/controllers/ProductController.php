<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

class ProductController extends Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->call->library('api');

        $this->api->require_jwt();
        $this->call->model('ProductModel');
    }

    private function validate($data)
    {
        $errors = [];

        $name = $data['product_name'] ?? '';
        if ($name === '' || strlen($name) > 100) {
            $errors['product_name'] = 'Product name is required (max 100 characters).';
        }
        if (!isset($data['price']) || !is_numeric($data['price']) || $data['price'] < 0) {
            $errors['price'] = 'Price must be a number, 0 or more.';
        }
        if (!isset($data['quantity']) || filter_var($data['quantity'], FILTER_VALIDATE_INT) === false || $data['quantity'] < 0) {
            $errors['quantity'] = 'Quantity must be a whole number, 0 or more.';
        }
        return $errors;
    }

    private function clean($data)
    {
        return [
            'product_name' => htmlspecialchars_decode($data['product_name'], ENT_QUOTES),
            'description'  => htmlspecialchars_decode($data['description'] ?? '', ENT_QUOTES),
            'price'        => $data['price'],
            'quantity'     => (int) $data['quantity'],
        ];
    }

    public function index()
    {
        $this->api->require_method('GET');
        $this->api->respond(['products' => $this->ProductModel->all()]);
    }

    public function show($id)
    {
        $product = $this->ProductModel->find($id);
        if (!$product) {
            $this->api->respond_error('Product not found.', 404);
        }
        $this->api->respond($product);
    }

    public function create()
    {
        $this->api->require_method('POST');
        $data = $this->api->body();

        $errors = $this->validate($data);
        if ($errors) {
            $this->api->respond(['error' => 'Validation failed', 'status' => 422, 'errors' => $errors], 422);
        }

        $id = $this->ProductModel->insert($this->clean($data));
        $this->api->respond($this->ProductModel->find($id), 201);
    }

    public function update($id)
    {
        if (!$this->ProductModel->find($id)) {
            $this->api->respond_error('Product not found.', 404);
        }

        $data = $this->api->body();
        $errors = $this->validate($data);
        if ($errors) {
            $this->api->respond(['error' => 'Validation failed', 'status' => 422, 'errors' => $errors], 422);
        }

        $this->ProductModel->update($id, $this->clean($data));
        $this->api->respond($this->ProductModel->find($id));
    }

    public function delete($id)
    {
        $this->api->require_method('DELETE');
        if (!$this->ProductModel->find($id)) {
            $this->api->respond_error('Product not found.', 404);
        }
        $this->ProductModel->delete($id);
        $this->api->respond(['message' => 'Product deleted.']);
    }
}