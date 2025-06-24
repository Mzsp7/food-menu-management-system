<?php
/**
 * Response Helper Class for API Endpoints
 */

class Response {
    
    /**
     * Send success response
     */
    public static function success($message = 'Success', $data = null, $code = 200) {
        http_response_code($code);
        
        $response = [
            'success' => true,
            'message' => $message
        ];
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        echo json_encode($response, JSON_PRETTY_PRINT);
        exit();
    }
    
    /**
     * Send error response
     */
    public static function error($message = 'Error', $code = 400, $errors = null) {
        http_response_code($code);
        
        $response = [
            'success' => false,
            'message' => $message
        ];
        
        if ($errors !== null) {
            $response['errors'] = $errors;
        }
        
        echo json_encode($response, JSON_PRETTY_PRINT);
        exit();
    }
    
    /**
     * Send validation error response
     */
    public static function validationError($errors, $message = 'Validation failed') {
        self::error($message, 422, $errors);
    }
    
    /**
     * Send not found response
     */
    public static function notFound($message = 'Resource not found') {
        self::error($message, 404);
    }
    
    /**
     * Send unauthorized response
     */
    public static function unauthorized($message = 'Unauthorized') {
        self::error($message, 401);
    }
    
    /**
     * Send forbidden response
     */
    public static function forbidden($message = 'Forbidden') {
        self::error($message, 403);
    }
    
    /**
     * Send method not allowed response
     */
    public static function methodNotAllowed($message = 'Method not allowed') {
        self::error($message, 405);
    }
    
    /**
     * Send internal server error response
     */
    public static function serverError($message = 'Internal server error') {
        self::error($message, 500);
    }
    
    /**
     * Send paginated response
     */
    public static function paginated($data, $total, $page, $perPage, $message = 'Success') {
        $totalPages = ceil($total / $perPage);
        
        $response = [
            'success' => true,
            'message' => $message,
            'data' => $data,
            'pagination' => [
                'total' => (int)$total,
                'per_page' => (int)$perPage,
                'current_page' => (int)$page,
                'total_pages' => (int)$totalPages,
                'has_next' => $page < $totalPages,
                'has_prev' => $page > 1
            ]
        ];
        
        echo json_encode($response, JSON_PRETTY_PRINT);
        exit();
    }
}

/**
 * Input Validation Helper Class
 */
class Validator {
    private $errors = [];
    private $data = [];
    
    public function __construct($data) {
        $this->data = $data;
    }
    
    /**
     * Validate required field
     */
    public function required($field, $message = null) {
        if (!isset($this->data[$field]) || empty(trim($this->data[$field]))) {
            $this->errors[$field] = $message ?? "The {$field} field is required";
        }
        return $this;
    }
    
    /**
     * Validate email format
     */
    public function email($field, $message = null) {
        if (isset($this->data[$field]) && !filter_var($this->data[$field], FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = $message ?? "The {$field} must be a valid email address";
        }
        return $this;
    }
    
    /**
     * Validate minimum length
     */
    public function minLength($field, $length, $message = null) {
        if (isset($this->data[$field]) && strlen($this->data[$field]) < $length) {
            $this->errors[$field] = $message ?? "The {$field} must be at least {$length} characters";
        }
        return $this;
    }
    
    /**
     * Validate maximum length
     */
    public function maxLength($field, $length, $message = null) {
        if (isset($this->data[$field]) && strlen($this->data[$field]) > $length) {
            $this->errors[$field] = $message ?? "The {$field} must not exceed {$length} characters";
        }
        return $this;
    }
    
    /**
     * Validate numeric value
     */
    public function numeric($field, $message = null) {
        if (isset($this->data[$field]) && !is_numeric($this->data[$field])) {
            $this->errors[$field] = $message ?? "The {$field} must be a number";
        }
        return $this;
    }
    
    /**
     * Validate positive number
     */
    public function positive($field, $message = null) {
        if (isset($this->data[$field]) && (float)$this->data[$field] <= 0) {
            $this->errors[$field] = $message ?? "The {$field} must be a positive number";
        }
        return $this;
    }
    
    /**
     * Validate URL format
     */
    public function url($field, $message = null) {
        if (isset($this->data[$field]) && !empty($this->data[$field]) && !filter_var($this->data[$field], FILTER_VALIDATE_URL)) {
            $this->errors[$field] = $message ?? "The {$field} must be a valid URL";
        }
        return $this;
    }
    
    /**
     * Validate field is in array of values
     */
    public function in($field, $values, $message = null) {
        if (isset($this->data[$field]) && !in_array($this->data[$field], $values)) {
            $this->errors[$field] = $message ?? "The {$field} must be one of: " . implode(', ', $values);
        }
        return $this;
    }
    
    /**
     * Check if validation passed
     */
    public function passes() {
        return empty($this->errors);
    }
    
    /**
     * Check if validation failed
     */
    public function fails() {
        return !$this->passes();
    }
    
    /**
     * Get validation errors
     */
    public function errors() {
        return $this->errors;
    }
    
    /**
     * Get first error message
     */
    public function firstError() {
        return !empty($this->errors) ? reset($this->errors) : null;
    }
    
    /**
     * Validate and return response if failed
     */
    public function validate() {
        if ($this->fails()) {
            Response::validationError($this->errors());
        }
    }
}

/**
 * Request Helper Class
 */
class Request {
    
    /**
     * Get all input data
     */
    public static function all() {
        $input = [];
        
        // Get POST data
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $input = array_merge($input, $_POST);
        }
        
        // Get GET data
        $input = array_merge($input, $_GET);
        
        // Get JSON data
        $json = json_decode(file_get_contents('php://input'), true);
        if ($json) {
            $input = array_merge($input, $json);
        }
        
        return $input;
    }
    
    /**
     * Get specific input value
     */
    public static function input($key, $default = null) {
        $all = self::all();
        return $all[$key] ?? $default;
    }
    
    /**
     * Check if input has key
     */
    public static function has($key) {
        $all = self::all();
        return isset($all[$key]);
    }
    
    /**
     * Get only specified keys from input
     */
    public static function only($keys) {
        $all = self::all();
        $result = [];
        
        foreach ($keys as $key) {
            if (isset($all[$key])) {
                $result[$key] = $all[$key];
            }
        }
        
        return $result;
    }
    
    /**
     * Get request method
     */
    public static function method() {
        return $_SERVER['REQUEST_METHOD'];
    }
    
    /**
     * Check if request is POST
     */
    public static function isPost() {
        return self::method() === 'POST';
    }
    
    /**
     * Check if request is GET
     */
    public static function isGet() {
        return self::method() === 'GET';
    }
    
    /**
     * Check if request is PUT
     */
    public static function isPut() {
        return self::method() === 'PUT';
    }
    
    /**
     * Check if request is DELETE
     */
    public static function isDelete() {
        return self::method() === 'DELETE';
    }
    
    /**
     * Get client IP address
     */
    public static function ip() {
        return $_SERVER['REMOTE_ADDR'] ?? '';
    }
    
    /**
     * Get user agent
     */
    public static function userAgent() {
        return $_SERVER['HTTP_USER_AGENT'] ?? '';
    }
}
?>
