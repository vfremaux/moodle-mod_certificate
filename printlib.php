<?php

/**
 * Sends text to output given the following params.
 *
 * @param stdClass $pdf
 * @param int $x horizontal position
 * @param int $y vertical position
 * @param char $align L=left, C=center, R=right
 * @param string $font any available font in font directory
 * @param char $style ''=normal, B=bold, I=italic, U=underline
 * @param int $size font size in points
 * @param string $text the text to print
 */
function certificate_print_text($pdf, $x, $y, $align, $font='freeserif', $style, $size=10, $text) {
    $pdf->setFont($font, $style, $size);
    $pdf->SetXY($x, $y);
    $pdf->writeHTMLCell(0, 0, '', '', $text, 0, 0, 0, true, $align);
}

/**
 * Creates rectangles for line border for A4 size paper.
 *
 * @param stdClass $pdf
 * @param stdClass $certificate
 */
function certificate_draw_frame($pdf, $certificate) {

    $printconfig = unserialize(@$certificate->printconfig);

    if (@$printconfig->bordercolor > 0) {
        if ($printconfig->bordercolor == 1) {
            $color = array(0, 0, 0); // black
        }
        if ($printconfig->bordercolor == 2) {
            $color = array(153, 102, 51); // brown
        }
        if ($printconfig->bordercolor == 3) {
            $color = array(0, 51, 204); // blue
        }
        if ($printconfig->bordercolor == 4) {
            $color = array(0, 180, 0); // green
        }
        switch ($certificate->orientation) {
            case 'L':
                // create outer line border in selected color
                $pdf->SetLineStyle(array('width' => 1.5, 'color' => $color));
                $pdf->Rect(10, 10, 277, 190);
                // create middle line border in selected color
                $pdf->SetLineStyle(array('width' => 0.2, 'color' => $color));
                $pdf->Rect(13, 13, 271, 184);
                // create inner line border in selected color
                $pdf->SetLineStyle(array('width' => 1.0, 'color' => $color));
                $pdf->Rect(16, 16, 265, 178);
            break;
            case 'P':
                // create outer line border in selected color
                $pdf->SetLineStyle(array('width' => 1.5, 'color' => $color));
                $pdf->Rect(10, 10, 190, 277);
                // create middle line border in selected color
                $pdf->SetLineStyle(array('width' => 0.2, 'color' => $color));
                $pdf->Rect(13, 13, 184, 271);
                // create inner line border in selected color
                $pdf->SetLineStyle(array('width' => 1.0, 'color' => $color));
                $pdf->Rect(16, 16, 178, 265);
            break;
        }
    }
}

/**
 * Creates rectangles for line border for letter size paper.
 *
 * @param stdClass $pdf
 * @param stdClass $certificate
 */
function certificate_draw_frame_letter($pdf, $certificate) {
    
    $printconfig = unserialize($certificate->printconfig);
    
    if (@$printconfig->bordercolor > 0) {
        if ($printconfig->bordercolor == 1) {
            $color = array(0, 0, 0); //black
        }
        if ($printconfig->bordercolor == 2) {
            $color = array(153, 102, 51); //brown
        }
        if ($printconfig->bordercolor == 3) {
            $color = array(0, 51, 204); //blue
        }
        if ($printconfig->bordercolor == 4) {
            $color = array(0, 180, 0); //green
        }
        switch ($certificate->orientation) {
            case 'L':
                // create outer line border in selected color
                $pdf->SetLineStyle(array('width' => 4.25, 'color' => $color));
                $pdf->Rect(28, 28, 736, 556);
                // create middle line border in selected color
                $pdf->SetLineStyle(array('width' => 0.2, 'color' => $color));
                $pdf->Rect(37, 37, 718, 538);
                // create inner line border in selected color
                $pdf->SetLineStyle(array('width' => 2.8, 'color' => $color));
                $pdf->Rect(46, 46, 700, 520);
                break;
            case 'P':
                // create outer line border in selected color
                $pdf->SetLineStyle(array('width' => 1.5, 'color' => $color));
                $pdf->Rect(25, 20, 561, 751);
                // create middle line border in selected color
                $pdf->SetLineStyle(array('width' => 0.2, 'color' => $color));
                $pdf->Rect(40, 35, 531, 721);
                // create inner line border in selected color
                $pdf->SetLineStyle(array('width' => 1.0, 'color' => $color));
                $pdf->Rect(51, 46, 509, 699);
            break;
        }
    }
}

/**
 * Prints border images from the borders folder in PNG or JPG formats.
 *
 * @param stdClass $pdf;
 * @param stdClass $certificate
 * @param int $x x position
 * @param int $y y position
 * @param int $w the width
 * @param int $h the height
 */
function certificate_print_image($pdf, $certificate, $type, $x, $y, $w, $h) {
    global $CFG;

    $fs = get_file_storage();
    $cm = get_coursemodule_from_instance('certificate', $certificate->id);
    $context = context_module::instance($cm->id);

    switch($type) {
        case CERT_IMAGE_BORDER :
            $attr = 'borderstyle';
            $defaultpath = "$CFG->dirroot/mod/certificate/pix/$type/defaultborder.jpg";

            $files = $fs->get_area_files($context->id, 'mod_certificate', 'printborder', 0, 'itemid, filepath, filename', false);
            $f = array_pop($files);
            if ($f) {
                $filepathname = $f->get_contenthash();
            } else {
                return;
            }

            break;
        case CERT_IMAGE_SEAL :
            $attr = 'printseal';

            $files = $fs->get_area_files($context->id, 'mod_certificate', 'printseal', 0, 'itemid, filepath, filename', false);
            $f = array_pop($files);
            if ($f) {
                $filepathname = $f->get_contenthash();
            } else {
                return;
            }

            break;
        case CERT_IMAGE_SIGNATURE :
            $attr = 'printsignature';

            $files = $fs->get_area_files($context->id, 'mod_certificate', 'printsignature', 0, 'itemid, filepath, filename', false);
            $f = array_pop($files);
            if ($f) {
                $filepathname = $f->get_contenthash();
            } else {
                return;
            }

            break;
        case CERT_IMAGE_WATERMARK :
            $attr = 'printwmark';

            $files = $fs->get_area_files($context->id, 'mod_certificate', 'printwmark', 0, 'itemid, filepath, filename', false);
            $f = array_pop($files);
            if ($f) {
                $filepathname = $f->get_contenthash();
            } else {
                return;
            }

            break;
    }

    $uploadpath = $CFG->dataroot.'/filedir/'.certificate_path_from_hash($filepathname).'/'.$filepathname;

    // Uploaded path will superseed.
    if (file_exists($uploadpath)) {
        $pdf->Image($uploadpath, $x, $y, $w, $h);
    } elseif (file_exists($defaultpath)) {
        $pdf->Image($path, $x, $y, $w, $h);
    }
}

function certificate_print_qrcode($pdf, $code, $x, $y) {
    global $CFG;

    $style = array(
            'border' => 2,
            'vpadding' => 'auto',
            'hpadding' => 'auto',
            'fgcolor' => array(0, 0, 0),
            'bgcolor' => array(255,255,255), //false
            'module_width' => 1, // width of a single module in points
            'module_height' => 1 // height of a single module in points
    );

    $codeurl = new moodle_url('/mod/certificate/verify.php', array('code' => $code));
    $pdf->write2DBarcode(''.$codeurl, 'QRCODE,H', $x, $y, 50, 50, $style, 'N');    
}
