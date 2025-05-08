<?php
require_once '../config/config.php';
require_once '../vendor/autoload.php'; // For TCPDF

// Check if user is logged in and is a student
if (!is_logged_in() || get_user_role() !== 'student') {
    redirect('/login.php');
}

// Get exam result ID from URL
$exam_id = isset($_GET['exam']) ? (int)$_GET['exam'] : 0;
if (!$exam_id) {
    redirect('dashboard.php');
}

// Get exam result details
$stmt = $pdo->prepare("
    SELECT er.*, u.name as student_name, u.nis
    FROM exam_results er
    JOIN users u ON er.user_id = u.id
    WHERE er.id = ? AND er.user_id = ? AND er.score >= 70
");
$stmt->execute([$exam_id, $_SESSION['user_id']]);
$exam_result = $stmt->fetch();

if (!$exam_result) {
    redirect('dashboard.php');
}

// Check if certificate already exists
$stmt = $pdo->prepare("
    SELECT certificate_path 
    FROM certificates 
    WHERE exam_result_id = ? AND user_id = ?
");
$stmt->execute([$exam_id, $_SESSION['user_id']]);
$existing_certificate = $stmt->fetch();

if ($existing_certificate) {
    // Redirect to existing certificate
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="certificate.pdf"');
    readfile('../' . $existing_certificate['certificate_path']);
    exit;
}

// Create new PDF document
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Set document information
$pdf->SetCreator('DesignHIve');
$pdf->SetAuthor('SMK Negeri 3 Bantul');
$pdf->SetTitle('Certificate of Completion');

// Remove default header/footer
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// Set margins
$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);

// Set auto page breaks
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

// Set image scale factor
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

// Set font
$pdf->SetFont('helvetica', '', 12);

// Add a page
$pdf->AddPage('L', 'A4');

// Get certificate background image
$background_image = '../assets/images/certificate-background.jpg';

// Add background image
$pdf->Image($background_image, 0, 0, 297, 210, '', '', '', false, 300, '', false, false, 0);

// Certificate content
$html = <<<EOD
<style>
    h1 {
        color: #1E90FF;
        font-size: 36px;
        text-align: center;
        font-weight: bold;
        margin-bottom: 20px;
    }
    .certificate-title {
        color: #333;
        font-size: 24px;
        text-align: center;
        margin-bottom: 30px;
    }
    .student-name {
        color: #1E90FF;
        font-size: 28px;
        text-align: center;
        font-weight: bold;
        margin: 30px 0;
    }
    .description {
        color: #666;
        font-size: 14px;
        text-align: center;
        margin-bottom: 40px;
    }
    .date {
        color: #666;
        font-size: 14px;
        text-align: center;
        margin-top: 40px;
    }
    .signature {
        text-align: center;
        margin-top: 60px;
    }
</style>

<h1>CERTIFICATE OF COMPLETION</h1>
<div class="certificate-title">DesignHIve - Platform Pembelajaran Desain Grafis</div>

<div class="description">This is to certify that</div>
<div class="student-name">{$exam_result['student_name']}</div>
<div class="description">NIS: {$exam_result['nis']}</div>

<div class="description">
    has successfully completed the Graphic Design Course<br>
    with a final score of <strong>{$exam_result['score']}%</strong>
</div>

<div class="date">Awarded on: {$exam_result['completed_at']}</div>

<div class="signature">
    <img src="../assets/images/signature.png" width="150" height="75"><br>
    Principal<br>
    SMK Negeri 3 Bantul
</div>
EOD;

// Print certificate content
$pdf->writeHTML($html, true, false, true, false, '');

// Generate unique filename
$certificate_filename = 'certificate_' . uniqid() . '.pdf';
$certificate_path = 'uploads/certificates/' . $certificate_filename;

// Create certificates directory if it doesn't exist
if (!file_exists('../uploads/certificates')) {
    mkdir('../uploads/certificates', 0777, true);
}

// Save certificate
$pdf->Output('../' . $certificate_path, 'F');

// Save certificate record in database
$stmt = $pdo->prepare("
    INSERT INTO certificates (user_id, exam_result_id, certificate_path)
    VALUES (?, ?, ?)
");
$stmt->execute([$_SESSION['user_id'], $exam_id, $certificate_path]);

// Add achievement badge
$stmt = $pdo->prepare("
    UPDATE gamification 
    SET badges = JSON_ARRAY_APPEND(
        CASE 
            WHEN badges IS NULL OR badges = '' THEN '[]'
            ELSE badges 
        END, 
        '$', 
        'Course Completed'
    )
    WHERE user_id = ?
");
$stmt->execute([$_SESSION['user_id']]);

// Output PDF
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="certificate.pdf"');
readfile('../' . $certificate_path);
exit;
?>
