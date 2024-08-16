<?php
class ReviewSystem {
    private $conn;

    public function __construct($dbConnection) {
        $this->conn = $dbConnection;
    }

    public function viewPublications($faculty_id) {
        $query = $this->conn->prepare("SELECT * FROM FacultyInformation WHERE id = ?");
        $query->bind_param('i', $faculty_id);
        $query->execute();
        $result = $query->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function downloadPublication($publication_id) {
        $query = $this->conn->prepare("SELECT file_path FROM FacultyInformation WHERE id = ?");
        $query->bind_param('i', $publication_id);
        $query->execute();
        $result = $query->get_result();
        $publication = $result->fetch_assoc();
        
        if ($publication) {
            $file_path = $publication['file_path'];
            header('Content-Description: File Transfer');
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="'.basename($file_path).'"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            readfile($file_path);
            exit;
        } else {
            return ['success' => false, 'message' => 'File not found'];
        }
    }

    public function approvePublication($publication_id) {
        $query = $this->conn->prepare("UPDATE publications SET status = 'approved' WHERE id = ?");
        $query->bind_param('i', $publication_id);
        if ($query->execute()) {
            return ['success' => true];
        } else {
            return ['success' => false, 'message' => 'Failed to approve publication'];
        }
    }

    public function rejectPublication($publication_id) {
        $query = $this->conn->prepare("UPDATE publications SET status = 'rejected' WHERE id = ?");
        $query->bind_param('i', $publication_id);
        if ($query->execute()) {
            return ['success' => true];
        } else {
            return ['success' => false, 'message' => 'Failed to reject publication'];
        }
    }

    public function checkFacultyPublications() {
        $last_month = date('d-m-Y', strtotime('-1 month'));
        $query = $this->conn->prepare("SELECT faculty.id, faculty.email FROM faculty LEFT JOIN publications ON faculty.id = publications.faculty_id AND publications.date >= ? WHERE publications.id IS NULL");
        $query->bind_param('s', $last_month);
        $query->execute();
        $result = $query->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getFacultyWithoutPublications() {
        $last_month = date('Y-m-d', strtotime('-1 month'));
        $query = $this->conn->prepare(
            "SELECT faculty.id, faculty.name, faculty.email
             FROM faculty
             LEFT JOIN publications ON faculty.id = publications.faculty_id AND publications.date >= ?
             WHERE publications.id IS NULL"
        );
        $query->bind_param('s', $last_month);
        $query->execute();
        $result = $query->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }
}
?>
