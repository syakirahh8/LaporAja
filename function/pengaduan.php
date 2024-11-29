<?php


ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


// insert data report
function buat_pengaduan($user_id, $message, $image, $conn)
{
    // Prepare SQL Injection => mencegah pihak luar memanipulasi data yg ada pada database
    $stmt = $conn->prepare("INSERT INTO reports (user_id, message, image, status, created_at) VALUES (?, ?, ?, ?, NOW())");


    // memberikan default vlue pada parameter status
    $status = 'proses';


    // menguhungkan argumen dengan query - bind_param
    $stmt->bind_param("ssss", $user_id, $message, $image, $status);
    // ssss itu parameter yg kita gunakan berjenis string


    // eksekusi, menyimpan dalam database
    return $stmt->execute();
}


//medapatkan data reposr sesuai data status


function get_pengaduan_by_status($username, $status, $conn)
{
    //mendapatkan id user
    $query_id = "SELECT id FROM users WHERE username = '$username'";
    $result_id = mysqli_query($conn, $query_id);
    $row = mysqli_fetch_assoc($result_id);
    $id = $row['id'];


    //mengambil data laporan sesuai status
    $query = "SELECT * FROM reports WHERE user_id = '$id'AND status = '$status' ";
    $result = mysqli_query($conn, $query);


    //menyimpan data yang sudah di dapatkan


    //[] agar menjelaskan kalo ini data array
    $pengaduan = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $pengaduan[] = $row;
    }


    return $pengaduan;
}


function get_all_pengaduan_by_status($status, $conn)
{
    // buat query/permintaan
    $query = "SELECT * FROM reports WHERE status = ?";


    // prepared statement
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $status);


    // ekseskusi
    $stmt->execute();


    // mengambil hasil result
    $result = $stmt->get_result();


    $pengaduan = [];
    while ($row = $result->fetch_assoc()) {
        $pengaduan[] = $row;
    }


    return $pengaduan;
}


//menambahkan feedback


//kalau terang berarti sudah dipakai
//menambahkan feedback
function addFeedback($report_id, $petugas_id, $feedback, $conn)
{
    // kita melakukan 2 operasi sekaligus
    // 1. menambahkan feedback
    // 2. mengubah status report yg tadinya proses ke selesai


    //begin_transaction = agar dua operasi berjalan secara bersamaan, satu gagal, semua gagal
    $conn->begin_transaction();


    //menyiapkan query untuk menambahkan feedback pada database
    try {
        $stmt = $conn->prepare("INSERT INTO feedbacks(report_id, feedback, petugas_id, created_at) VALUES (?,?,?, NOW())");
        $stmt->bind_param("iis", $report_id, $feedback, $petugas_id);


        //eksekusi query
        if (!$stmt->execute()) {
            throw new Exception("Gagal menyimpan feedback");
        }


        //mengubah status report
        $updateStmt = $conn->prepare("UPDATE reports SET status = 'selesai' WHERE id = ?");
        $updateStmt->bind_param("i", $report_id);


        if (!$updateStmt->execute()) {  // fixed lineee
            throw new Exception("Gagal update status");
        }


        //jika semua operasi berhasil
        $conn->commit(); //menyimpan perubahan di database
        return true; //operasi yg dilakukan berhasil


    } catch (\Throwable $error) {
        //ini kalau gagal
        $conn->rollback(); //untuk membatalkan semua perubahan yg telah dilakukan
        echo "Error: " . $error->getMessage();
        return false;
    }
}


// get reports with feedback by statu selesai
function get_reports_with_feedback_by_status($conn)
{
    //melakukan query
    //LEFT JOIN untuk mencocokan data dari data lain dan menggabungkannya
    $query = "SELECT
   reports.id,
   users.username AS pelapor,
   reports.message,
   reports.image,
   reports.created_at AS report_date,
   feedbacks.feedback,
   feedbacks.created_at AS feedback_date
FROM reports
LEFT JOIN feedbacks ON reports.id = feedbacks.report_id
LEFT JOIN users ON reports.user_id = users.id
WHERE reports.status = 'selesai'
ORDER BY reports.created_at DESC";




    $result = $conn->query($query);

    //menyimpan data dari hasil query
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }


    return $data;
}

//    get reports with feedback by user and by status

function get_reports_with_feedback_by_user($username, $conn)
{
    $user_id = $conn->query("SELECT id FROM users WHERE username = '$username'")->fetch_assoc()['id'];
    

    $query = "SELECT 
                reports.id,
                reports.message,
                reports.created_at AS report_date,
                feedbacks.feedback,
                feedbacks.created_at AS feedback_date
                FROM reports
                LEFT JOIN feedbacks ON reports.id = feedbacks.report_id
                WHERE reports.user_id = ? AND reports.status = 'selesai'
                ORDER BY reports.created_at DESC";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $data = [];
    while ($row = $result->fetch_assoc()) {
    $data[] = $row;
    }

    return $data;
}
?>
