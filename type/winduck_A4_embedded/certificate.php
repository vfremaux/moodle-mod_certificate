<?php

// This file is part of the Certificate module for Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * A4_embedded certificate type
 *
 * @package    mod
 * @subpackage certificate
 * @copyright  Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); // It must be included from view.php
}

$pdf = new PDF($certificate->orientation, 'mm', 'A4', true, 'UTF-8', false);

$pdf->SetTitle($certificate->name);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetAutoPageBreak(false, 0);
$pdf->AddPage();

// Define variables
// Landscape
if ($certificate->orientation == 'L') {
    $x = 10;
    $y = 40;
    $custx = 5;
    $custy = 140;
    $qrcx = 260;
    $qrcy = 120;
    $brdrx = 0;
    $brdry = 0;
    $brdrw = 297;
    $brdrh = 210;
    $codey = 155;
} else { // Portrait
    $x = 10;
    $y = 40;
    $custx = 30;
    $custy = 230;
    $brdrx = 0;
    $brdry = 0;
    $brdrw = 210;
    $brdrh = 297;
    $codey = 250;
}

// Add images and lines
certificate_print_image($pdf, $certificate, CERT_IMAGE_BORDER, $brdrx, $brdry, $brdrw, $brdrh);
// certificate_draw_frame($pdf, $certificate);
// Set alpha to semi-transparency
$pdf->SetAlpha(1);

// Add text
$pdf->SetTextColor(0, 0, 120);
$y = certificate_print_text($pdf, $x, $y, 'C', 'freesans', '', 28, format_text($certificate->caption));
$pdf->SetTextColor(0, 0, 0);
certificate_print_text($pdf, $x, $y + 70, 'C', 'freesans', '', 26, fullname($USER));
certificate_print_text($pdf, $x, $y + 84, 'C', 'freesans', '', 16, certificate_get_string('statement', 'winduck_A4_embedded'));
certificate_print_text($pdf, $x, $y + 94, 'C', 'freesans', '', 18, $course->summary);
certificate_print_text($pdf, $x, $y + 126, 'C', 'freesans', '', 13,  certificate_get_date($certificate, $certrecord, $course));
if ($certificate->printhours) {
    certificate_print_text($pdf, $x, $y + 132, 'C', 'freesans', '', 13, get_string('credithours', 'certificate') . ': ' . $certificate->printhours);
}


certificate_print_text($pdf, $custx, $custy, 'L', 'freesans', '', 13, $certificate->customtext);
$code = certificate_get_code($certificate, $certrecord);
certificate_print_qrcode($pdf, $code, $qrcx, $qrcy);

$pdf->SetAlpha(0.5);
certificate_print_text($pdf, $x, $codey, 'C', 'freemono', '', 40, $code);
