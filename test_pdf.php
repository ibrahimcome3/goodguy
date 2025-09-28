<?php
// Include TCPDF (adjust path if needed)
require_once 'vendor/autoload.php'; // If using Composer
// OR require_once 'libraries/tcpdf/tcpdf.php'; // If manual installation

// Create new PDF document
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Set document information
$pdf->SetCreator('GoodGuy Shop');
$pdf->SetAuthor('GoodGuy Shop');
$pdf->SetTitle('Test PDF');

// Add a page
$pdf->AddPage();

// Set font
$pdf->SetFont('helvetica', 'B', 16);

// Add content
$pdf->Cell(0, 10, 'Hello, TCPDF!', 0, 1, 'C');

// Output the PDF
$pdf->Output('test.pdf', 'I'); // 'I' displays in browser, 'D' forces download