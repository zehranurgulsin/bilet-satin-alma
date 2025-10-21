<?php
require __DIR__.'/bootstrap.php'; require_login();
$tid=(int)($_GET['id']??0);
$st=$pdo->prepare("SELECT tk.*, tr.origin,tr.destination,tr.departure_at, c.name AS company_name
                   FROM tickets tk JOIN trips tr ON tr.id=tk.trip_id
                   JOIN companies c ON c.id=tr.company_id
                   WHERE tk.id=? AND tk.user_id=?");
$st->execute([$tid, me()['id']]); $t=$st->fetch();
if(!$t) die("Bilet bulunamadı.");

$fpdfPath=__DIR__."/vendor/fpdf.php";
if(!file_exists($fpdfPath)){
  header('Content-Type:text/plain; charset=utf-8');
  echo "PDF kütüphanesi bulunamadı. Lütfen vendor/fpdf.php ekleyin (https://www.fpdf.org/).";
  exit;
}
require $fpdfPath;

$pdf=new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial','B',16);
$pdf->Cell(0,10,'Otobus Biletiniz',0,1,'C');

$pdf->SetFont('Arial','',12);
$pdf->Cell(0,8,'Firma: '.$t['company_name'],0,1);
$pdf->Cell(0,8,'Guzergah: '.$t['origin'].' -> '.$t['destination'],0,1);
$pdf->Cell(0,8,'Kalkis: '.date('d.m.Y H:i', strtotime($t['departure_at'])),0,1);
$pdf->Cell(0,8,'Koltuk: '.$t['seat_no'],0,1);
$pdf->Cell(0,8,'Odeme: '.number_format($t['price_paid']/100,2,',','.').' TL',0,1);
$pdf->Cell(0,8,'Durum: '.$t['status'],0,1);
$pdf->Ln(4);
$pdf->SetFont('Arial','I',10);
$pdf->Cell(0,8,'PNR: T'.$t['id'].'-U'.me()['id'],0,1);
$pdf->Output('D', 'bilet-'.$t['id'].'.pdf');
