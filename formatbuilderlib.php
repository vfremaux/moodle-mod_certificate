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

function certificate_get_item_pos_style($item, $certificate, $defaults) {

    $layout = json_decode(@$certificate->layout);

    if (empty($layout)) $layout = array();

    if (array_key_exists($item, $layout)) {
        $itempos = $layout[$item];
        return 'position:absolute;left:'.$itempos->x.';top:'.$itempos->y.';width:'.$itempos->w.';height:'.$itempos->h.';font-size:'.$itempos->fs.';color:'.@$itempos->c;
    }

    return 'position:absolute';
}

function certificate_builder_text($certificate, $name, $text, $defaults) {
    global $OUTPUT;

    $itemposstyle = certificate_get_item_pos_style($name, $certificate, $defaults);

    $str = '';

    $str .= '<div id="certificate-'.$name.'" class="builder-object" style="'.$itemposstyle.'">';
    $str .= '<div class="builder-controls">';
    $str .= '<div id="certificate-'.$name.'-fontsize-plus" z-order="0"><img src="'.$OUTPUT->pix_url('t/switch_plus').'" onclick="fontsizeplus(\'certificate-'.$name.'\')"></div>';
    $str .= ' <div id="certificate-'.$name.'-fontsize-minus" z-order="0"><img src="'.$OUTPUT->pix_url('t/switch_minus').'" onclick="fontsizeminus(\'certificate-'.$name.'\')"></div>';
    $str .= ' <div id="certificate-'.$name.'-move" z-order="0"><img src="'.$OUTPUT->pix_url('t/move').'" onclick="capture(event, this)"></div>';
    $str .= '</div>';
    $str .= '<div  id="certificate-'.$name.'-text" z-order="0" class="textbox">'.$text.'</div>';
    $str .= '</div>';

    return $str;
}

function certificate_builder_image($certificate, $cm, $name, $fs, $defaults, $attrs) {
    global $OUTPUT;

    $str = '';
    $url = null;

    $context = context_module::instance($cm->id);

    if ($fs) {
        $files = $fs->get_area_files($context->id, 'mod_certificate', $name, 0, 'itemid, filepath, filename', false);
        $f = array_pop($files);
        if ($f) {
            $url = moodle_url::make_pluginfile_url($f->get_contextid(), $f->get_component(), $f->get_filearea(), $f->get_itemid(), $f->get_filepath(), $f->get_filename());
        }
    } else {
        list($type, $name) = explode('/', $name);
        if ($type == 'pix') {
            $url = $OUTPUT->pix_url($name, 'certificate');
        }
    }

    if ($url) {
        $itemposstyle = certificate_get_item_pos_style($name, $certificate, $defaults);
        $str .= '<div id="certificate-'.$name.'" class="builder-object" style="'.$itemposstyle.'">';
        $str .= '<div class="builder-controls">';
        $str .= ' <div id="certificate-'.$name.'-move" z-order="0"><img src="'.$OUTPUT->pix_url('t/move').'" onclick="capture(event, this)"></div>';
        $str .= '</div>';
        $str .= '<div  id="certificate-'.$name.'-text" z-order="0" class="textbox">';
        $str .= '<img id="certificate-'.$name.'-image" src="'.$url.'" z-order="'.$attrs['z-order'].'" src="'.$url.'" style="'.$itemposstyle.'">';
        $str .= '</div>';
        $str .= '</div>';

        return $str;
    }
}

function certificate_print_defaults($certificate) {

    $layout = json_decode($certificate->layout);

    if (empty($layout)) {

        // Initial defaults (stands for A4_P).
        $printborders = new StdClass();
        $printborders->x = '0';
        $printborders->y = '0';
        $printborders->w = '600px';
        $printborders->h = '800px';
    
        $printwatermark = new StdClass();
        $printwatermark->x = '150px';
        $printwatermark->y = '200px';
        $printwatermark->w = '300px';
        $printwatermark->h = '400px';
    
        $printseal = new StdClass();
        $printseal->x = '30px';
        $printseal->y = '30px';
        $printseal->w = 'initial';
        $printseal->h = 'initial';
    
        $printsignature = new StdClass();
        $printsignature->x = '400px';
        $printsignature->y = '600px';
        $printsignature->w = 'initial';
        $printsignature->h = 'initial';
    
        $title = new StdClass();
        $title->x = '60px';
        $title->y = '150px';
        $title->w = 'initial';
        $title->h = 'initial';
        $title->fs = '15px';
        $title->ta = 'center';
        $title->c = '#000060';
    
        $user = new StdClass();
        $user->x = '60px';
        $user->y = '150px';
        $user->w = 'initial';
        $user->h = 'initial';
        $user->fs = '13px';
        $user->ta = 'left';
        $user->c = '#000000';
    
        $grade = new StdClass();
        $grade->x = '60px';
        $grade->y = '150px';
        $grade->w = 'initial';
        $grade->h = 'initial';
        $grade->fs = '13px';
        $grade->ta = 'left';
        $grade->c = '#000000';
    
        $date = new StdClass();
        $date->x = '60px';
        $date->y = '150px';
        $date->w = 'initial';
        $date->h = 'initial';
        $date->fs = '13px';
        $date->ta = 'left';
        $date->c = '#000000';
    
        $code = new StdClass();
        $code->x = '60px';
        $code->y = '450px';
        $code->w = 'initial';
        $code->h = 'initial';
        $code->fs = '13px';
        $code->ta = 'left';
        $code->c = '#000000';
    
        $qrcode = new StdClass();
        $qrcode->x = '60px';
        $qrcode->y = '450px';
        $qrcode->w = 'initial';
        $qrcode->h = 'initial';
    
        $statement = new StdClass();
        $statement->x = '60px';
        $statement->y = '450px';
        $statement->w = 'initial';
        $statement->h = 'initial';
        $statement->fs = '13px';
        $statement->ta = 'left';
        $statement->c = '#000000';
    
        $teachers = new StdClass();
        $teachers->x = '60px';
        $teachers->y = '280px';
        $teachers->w = 'initial';
        $teachers->h = 'initial';
        $teachers->fs = '13px';
        $teachers->ta = 'left';
        $teachers->c = '#000000';
    
        $defaults = array(
            'printborders' => $printborders,
            'printwatermark' => $printwatermark,
            'printseal' => $printseal,
            'printsignature' => $printsignature,
            'title' => $title,
            'user' => $user,
            'grade' => $grade,
            'date' => $date,
            'code' => $code,
            'qrcode' => $qrcode,
            'statement' => $statement,
            'teachers' => $teachers,
        );
    
        if ($certificate->orientation == 'P') {
            if (preg_match('/^letter_', $certificate->certificatetype)) {
                $defaults['printborders']->w = '570px';
                $defaults['printborders']->h = '800px';

                $default['printsignature']->y = '420px';
            }
        } else {
            // landscape formats
            if (preg_match('/^A4/', $certificate->certificatetype)) {
                $defaults['printborders'] = new StdClass;
                $defaults['printborders']->w = '800px';
                $defaults['printborders']->h = '570px';

                $default['printsignature'] = new StdClass;
                $default['printsignature']->x = '450px';
                $default['printsignature']->x = '650px';
            } else {
                // letter formats
                $defaults['printborders'] = new StdClass;
                $defaults['printborders']->w = '570px';
                $defaults['printborders']->h = '800px';
    
                $default['printsignature'] = new StdClass;
                $default['printsignature']->x = '650px';
                $default['printsignature']->y = '450px';
            }
        }
        return $defaults;
    } else {
        return $layout;
    }
}