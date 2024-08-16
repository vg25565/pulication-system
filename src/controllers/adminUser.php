<?php
class AdminUser {
    private $conn;
    // TO-DO : set conn as the connection id for the database

    public function __construct($dbConnection) {
        $this->conn = $dbConnection;
    }

    public function login($username, $password) {
        $query = $this->conn->prepare("SELECT * FROM admin WHERE username = ?");
        $query->bind_param('s', $username);
        $query->execute();
        $result = $query->get_result();
        $admin = $result->fetch_assoc();

        if ($admin && password_verify($password, $admin['password'])) {
            return ['success' => true, 'admin_id' => $admin['id']];
        } else {
            return ['success' => false, 'message' => 'Invalid credentials'];
        }
    }

    public function sendAlert($email, $message) {
        $subject = "Publication Alert";
        $headers = "From: "; 
        //TO-DO : Add a dynamic email header that uses Admin's registered email
        return mail($email, $subject, $message, $headers);
    }
}
?>
