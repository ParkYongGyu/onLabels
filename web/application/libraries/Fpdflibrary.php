<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once APPPATH.'third_party/fpdf/tfpdf.php';
// require_once APPPATH.'third_party/fpdf/fpdf.php';
// require_once('easyTable.php');
require_once('fpdi.php');


class Fpdflibrary extends tFPDF {

    // public function __construct() {
        
    //     //$pdf = new tFPDF();
    //     // $pdf->AddPage();
    //     // $CI =& get_instance();
    //     // $CI->fpdf = $pdf;
    // }

    function __construct($orientation='P', $unit='mm', $size='A4')
    {
        // parent::tFPDF();
        parent::__construct($orientation, $unit, $size);
    }

    function GetPageWidth()
    {
        // Get current page width
        return $this->w;
    }

    function pdf1x1fromto($pdf, $order, $options) {

        $ci=&get_instance();
        $ci->load->model('order_model','order');
        $ci->load->model('printlabels_model','printlabel');
        $label_info = $ci->printlabel->detail($ci->session->userdata('uid'), 1);
        if ($label_info==0) $label_info = $ci->printlabel->get(1);
        $options['print_box_height'] = $label_info['print_box_height'];
        $options['print_box_width'] = $label_info['print_box_width'];
        $options['paper_margin_top'] = $label_info['paper_margin_top'];
        $options['paper_margin_bottom'] = $label_info['paper_margin_bottom'];
        $options['paper_margin_left'] = $label_info['paper_margin_left'];
        $options['paper_margin_right'] = $label_info['paper_margin_right'];
        $pdffiles = array();


        $pdf = new fpdflibrary('P','mm', array(132,190));
        $pdf->AddPage();
        $pdf->SetAutoPageBreak(false);
        $pdf->AddFont('NanumBarunGothic','','NanumBarunGothic.ttf',true);
        $pdf->AddFont('NanumBarunGothicBold','','NanumBarunGothicBold.ttf',true);
        for ($i=0; $i<count($order); $i++) :
            $this->createAirMail1Form(0, $pdf, $ci->orders->get_specific_order($order[$i]), $options);
            if (($i+1)<count($order)) $pdf->AddPage();
        endfor;
        $fromtopdfname = 'fromto-1-'.$options['uid'].'-'.$options['dimension'].'-'.$options['order_ids'].'.pdf';
        $pdffiles[] = $fromtopdfname;
        $pdf->Output($_SERVER['DOCUMENT_ROOT'].'/onlabels/assets2/'.$fromtopdfname,'F');

        if ($options['cn22']>0) :
            $pdf = new fpdflibrary('P','mm', array(132,190));
            $pdf->AddPage();
            $pdf->SetAutoPageBreak(false);
            $pdf->AddFont('NanumBarunGothic','','NanumBarunGothic.ttf',true);
            $pdf->AddFont('NanumBarunGothicBold','','NanumBarunGothicBold.ttf',true);
            for ($i=0; $i<count($order); $i++) :
                $this->createCustomsDeclaration1Form(0, $pdf, $ci->orders->get_specific_order($order[$i]), $options);
                if (($i+1)<count($order)) $pdf->AddPage();
            endfor;
            $cn22pdfname = 'cn22-1-'.$options['uid'].'-'.$options['dimension'].'-'.$options['order_ids'].'.pdf';
            $pdffiles[] = $cn22pdfname;
            $pdf->Output($_SERVER['DOCUMENT_ROOT'].'/onlabels/assets2/'.$cn22pdfname,'F');
        endif;

        $paper_margin_left = $options['paper_margin_left'];
        $paper_margin_top = $options['paper_margin_top'];
        $print_box_width = $options['print_box_width'];
        $print_box_height = $options['print_box_height'];

        $pdf1 = new fpdi('L'); 
        $plus = 0;
        $pdf1->AddPage();

        $pagescount = array();

        $repeater=0;
        for ($i=0;$i<count($pdffiles);$i++) :

            $pdf1->setSourceFile($_SERVER['DOCUMENT_ROOT'].'/onlabels/assets2/'.$pdffiles[$i]);

            if ($repeater>0) break;
            
            for ($p=1;$p<=count($order);$p++) :

                $separationwidth = $paper_margin_left;
                
                if ($p % 2 == 0) : 
                    $separationwidth = (($paper_margin_left*2)+$print_box_width);
                endif;

                // all cn22, from and to
                if ($options['cn22']>0 && $options['from']>0 && $options['to']>0) :
                    if ($p % 1 == 0) :  $separationwidth = $paper_margin_left; endif; 
                endif;

                if ($options['from']>0 || $options['to']>0):
                    //fromto
                    $pdf1->setSourceFile($_SERVER['DOCUMENT_ROOT'].'/onlabels/assets2/'.$pdffiles[0]);
                    $templateId = $pdf1->importPage($p);
                    $pdf1->useTemplate($templateId, $separationwidth, $paper_margin_top, $print_box_width, $print_box_height);
                endif;

                if ($options['cn22']>0):
                    if ($options['from']>0 || $options['to']>0) :
                        $separationwidth = (($paper_margin_left*2)+$print_box_width);
                    endif; 
                    //cn22
                    $pdf1->setSourceFile($_SERVER['DOCUMENT_ROOT'].'/onlabels/assets2/'.$pdffiles[1]);
                    $templateId1 = $pdf1->importPage($p);
                    $pdf1->useTemplate($templateId1, $separationwidth, $paper_margin_top, $print_box_width, $print_box_height);
                endif;

                // echo 'p== '.$p.'<br>';

                // // just from and to
                // if ($options['cn22']==0) :
                //     if ($p % 2 == 0) : 
                //        if ($p<count($order)) $pdf1->AddPage();
                //     endif;
                // endif;

                // // just cn22
                // if ($options['cn22']>0 && $options['from']==0 && $options['to']==0) :
                //     if ($p % 2 == 0) :  $pdf1->AddPage(); endif;
                // endif;

                // all cn22, from and to
                if ($options['cn22']>0 && $options['from']>0 && $options['to']>0) :
                    if ($p % 1 == 0) :  if ($p<count($order)) $pdf1->AddPage(); endif;
                else :
                    if ($p % 2 == 0) : 
                       if ($p<count($order)) $pdf1->AddPage();
                    endif;
                endif;
            
            endfor;
            $repeater++;
        endfor;

        $final = 'final-'.$options['uid'].'-'.$options['dimension'].'-'.$options['order_ids'].'.pdf';
        $pdf1->Output($_SERVER['DOCUMENT_ROOT'].'/onlabels/assets2/'.$final,'I');

        for ($i=0;$i<count($pdffiles);$i++) :
            unlink($_SERVER['DOCUMENT_ROOT'].'/onlabels/assets2/'.$pdffiles[$i]);
        endfor;
    }

    function pdf1x2fromto($pdf, $order, $options) {

        $ci=&get_instance();
        $ci->load->model('order_model','order');
        $ci->load->model('printlabels_model','printlabel');
        $label_info = $ci->printlabel->detail($ci->session->userdata('uid'), 2);
        if ($label_info==0) $label_info = $ci->printlabel->get(2);
        $options['print_box_height'] = $label_info['print_box_height'];
        $options['print_box_width'] = $label_info['print_box_width'];
        $options['paper_margin_top'] = $label_info['paper_margin_top'];
        $options['paper_margin_bottom'] = $label_info['paper_margin_bottom'];
        $options['paper_margin_left'] = $label_info['paper_margin_left'];
        $options['paper_margin_right'] = $label_info['paper_margin_right'];
        $pdffiles = array();

        $pdf = new fpdflibrary('P','mm', array(95, 135));
        $pdf->AddPage();
        $pdf->SetAutoPageBreak(false);
        $pdf->AddFont('NanumBarunGothic','','NanumBarunGothic.ttf',true);
        $pdf->AddFont('NanumBarunGothicBold','','NanumBarunGothicBold.ttf',true);
        for ($i=0; $i<count($order); $i++) :
            $this->createAirMail2Form(0, 0, $pdf, $ci->orders->get_specific_order($order[$i]), $options);
            if (($i+1)<count($order)) $pdf->AddPage();
        endfor;
        $fromtopdfname = 'fromto-2-'.$options['uid'].'-'.$options['dimension'].'-'.$options['order_ids'].'.pdf';
        $pdffiles[] = $fromtopdfname;
        $pdf->Output($_SERVER['DOCUMENT_ROOT'].'/onlabels/assets2/'.$fromtopdfname,'F');
        
        if ($options['cn22']>0) :
            $pdf = new fpdflibrary('P','mm', array(95,135));
            $pdf->AddPage();
            $pdf->SetAutoPageBreak(false);
            $pdf->AddFont('NanumBarunGothic','','NanumBarunGothic.ttf',true);
            $pdf->AddFont('NanumBarunGothicBold','','NanumBarunGothicBold.ttf',true);
            for ($i=0; $i<count($order); $i++) :
                $this->createCustomsDeclaration2Form(0, 0, $pdf, $ci->orders->get_specific_order($order[$i]), $options);
                if (($i+1)<count($order)) $pdf->AddPage();
            endfor;
            $cn22pdfname = 'cn22-2-'.$options['uid'].'-'.$options['dimension'].'-'.$options['order_ids'].'.pdf';
            $pdffiles[] = $cn22pdfname;
            $pdf->Output($_SERVER['DOCUMENT_ROOT'].'/onlabels/assets2/'.$cn22pdfname,'F');
        endif;

        $paper_margin_left = $options['paper_margin_left'];
        $paper_margin_top = $options['paper_margin_top'];
        $print_box_width = $options['print_box_width'];
        $print_box_height = $options['print_box_height'];
        
        $pdf1 = new fpdi('P'); 
        $plus = 0;
        $pdf1->AddPage();

        $pagescount = array();

        $repeater=0;
        for ($i=0;$i<count($pdffiles);$i++) :

            $pdf1->setSourceFile($_SERVER['DOCUMENT_ROOT'].'/onlabels/assets2/'.$pdffiles[$i]);

            if ($repeater>0) break;

            $margintopcounter = 1;
            for ($p=1;$p<=count($order);$p++) :

                $separationwidth = $paper_margin_left;
                $separationtop = $paper_margin_top;
                
                if ($p % 2 == 0) : 
                    $separationwidth = (($paper_margin_left*2)+$print_box_width);
                endif;

                // all cn22, from and to
                if ($options['cn22']>0 && $options['from']>0 && $options['to']>0) :
                    if ($p % 1 == 0) :  $separationwidth = $paper_margin_left; endif; 
                    if ($margintopcounter % 2 == 0) : $separationtop = (($paper_margin_top*2)+$print_box_height); endif;
                else :
                    if ($margintopcounter % 3 == 0 || $margintopcounter % 4 == 0) : 
                        $separationtop = (($paper_margin_top*2)+$print_box_height);
                        if ($margintopcounter>3) $margintopcounter=0; 
                    endif;
                endif;
                $margintopcounter++;

                // echo 'separationwidth ' . $separationwidth .'<br>';
                // echo 'separationtop ' . $separationtop .'<br>';

                if ($options['from']>0 || $options['to']>0):
                    //fromto
                    $pdf1->setSourceFile($_SERVER['DOCUMENT_ROOT'].'/onlabels/assets2/'.$pdffiles[0]);
                    $templateId = $pdf1->importPage($p);
                    $pdf1->useTemplate($templateId, $separationwidth, $separationtop, $print_box_width, $print_box_height);
                endif;

                if ($options['cn22']>0):
                    if ($options['from']>0 || $options['to']>0) :
                        $separationwidth = (($paper_margin_left*2)+$print_box_width);
                    endif; 
                    //cn22
                    $pdf1->setSourceFile($_SERVER['DOCUMENT_ROOT'].'/onlabels/assets2/'.$pdffiles[1]);
                    $templateId1 = $pdf1->importPage($p);
                    $pdf1->useTemplate($templateId1, $separationwidth, $separationtop, $print_box_width, $print_box_height);
                endif;

                // // just from and to
                // if ($options['cn22']==0) :
                //     if ($p % 4 == 0) : 
                //         $pdf1->AddPage(); 
                //     endif;
                // endif;

                // // just cn22
                // if ($options['cn22']>0 && $options['from']==0 && $options['to']==0) :
                //     if ($p % 4 == 0) :  $pdf1->AddPage(); endif;
                // endif;

                // all cn22, from and to
                if ($options['cn22']>0 && $options['from']>0 && $options['to']>0) :
                    if ($p % 2 == 0) :  if ($p<count($order)) $pdf1->AddPage(); endif;
                else :
                    if ($p % 4 == 0) : 
                        $pdf1->AddPage(); 
                    endif;
                endif;
            
            endfor;
            $repeater++;
        endfor;

        $final = 'final-'.$options['uid'].'-'.$options['dimension'].'-'.$options['order_ids'].'.pdf';
        $pdf1->Output($_SERVER['DOCUMENT_ROOT'].'/onlabels/assets2/'.$final,'I');

        for ($i=0;$i<count($pdffiles);$i++) :
           unlink($_SERVER['DOCUMENT_ROOT'].'/onlabels/assets2/'.$pdffiles[$i]);
        endfor;
    }

    function pdf2x2fromto($pdf, $order, $options) {

        $ci=&get_instance();
        $ci->load->model('order_model','order');
        $ci->load->model('printlabels_model','printlabel');
        $label_info = $ci->printlabel->detail($ci->session->userdata('uid'), 3);
        if ($label_info==0) $label_info = $ci->printlabel->get(3);
        $options['print_box_height'] = $label_info['print_box_height'];
        $options['print_box_width'] = $label_info['print_box_width'];
        $options['paper_margin_top'] = $label_info['paper_margin_top'];
        $options['paper_margin_bottom'] = $label_info['paper_margin_bottom'];
        $options['paper_margin_left'] = $label_info['paper_margin_left'];
        $options['paper_margin_right'] = $label_info['paper_margin_right'];
        $pdffiles = array();

        $pdf = new fpdflibrary('P','mm', array(66, 96));
        $pdf->AddPage();
        $pdf->SetAutoPageBreak(false);
        $pdf->AddFont('NanumBarunGothic','','NanumBarunGothic.ttf',true);
        $pdf->AddFont('NanumBarunGothicBold','','NanumBarunGothicBold.ttf',true);
        for ($i=0; $i<count($order); $i++) :
            $this->createAirMail3Form(0, 0, $pdf, $ci->orders->get_specific_order($order[$i]), $options);
            if (($i+1)<count($order)) $pdf->AddPage();
        endfor;
        $fromtopdfname = 'fromto-3-'.$options['uid'].'-'.$options['dimension'].'-'.$options['order_ids'].'.pdf';
        $pdffiles[] = $fromtopdfname;
        $pdf->Output($_SERVER['DOCUMENT_ROOT'].'/onlabels/assets2/'.$fromtopdfname,'F');
        
        if ($options['cn22']>0) :
            $pdf = new fpdflibrary('P','mm', array(66, 96));
            $pdf->AddPage();
            $pdf->SetAutoPageBreak(false);
            $pdf->AddFont('NanumBarunGothic','','NanumBarunGothic.ttf',true);
            $pdf->AddFont('NanumBarunGothicBold','','NanumBarunGothicBold.ttf',true);
            for ($i=0; $i<count($order); $i++) :
                $this->createCustomsDeclaration3Form(0, 0, $pdf, $ci->orders->get_specific_order($order[$i]), $options);
                if (($i+1)<count($order)) $pdf->AddPage();
            endfor;
            $cn22pdfname = 'cn22-3-'.$options['uid'].'-'.$options['dimension'].'-'.$options['order_ids'].'.pdf';
            $pdffiles[] = $cn22pdfname;
            $pdf->Output($_SERVER['DOCUMENT_ROOT'].'/onlabels/assets2/'.$cn22pdfname,'F');
        endif;

        $paper_margin_left = $options['paper_margin_left'];
        $paper_margin_top = $options['paper_margin_top'];
        $print_box_width = $options['print_box_width'];
        $print_box_height = $options['print_box_height'];
        
        $pdf1 = new fpdi('L'); 
        $plus = 0;
        $pdf1->AddPage();

        $pagescount = array();

        $repeater=0;
        for ($i=0;$i<count($pdffiles);$i++) :

            $pdf1->setSourceFile($_SERVER['DOCUMENT_ROOT'].'/onlabels/assets2/'.$pdffiles[$i]);

            if ($repeater>0) break;

            $margintopcounter = 1;
            $marginwidthcounter = 1;
            for ($p=1;$p<=count($order);$p++) :

                $separationwidth = $paper_margin_left;
                $separationtop = $paper_margin_top;
                
                // all cn22, from and to
                if ($options['cn22']>0 && $options['from']>0 && $options['to']>0) :
                    if ($margintopcounter % 2 == 0) : 
                        $separationtop = (($paper_margin_top*2)+$print_box_height);
                    endif;
                    if ($marginwidthcounter % 3 == 0 || $marginwidthcounter % 4 == 0) :
                        // $separationwidth = ((($paper_margin_left*2)+$print_box_width)*2);
                        $separationwidth = ($paper_margin_left*2)+($print_box_width*2)+$paper_margin_left;
                    endif;
                else :
                    if ($p % 2 == 0 ) : 
                        $separationwidth = (($paper_margin_left*2)+$print_box_width);
                    endif;

                    if ($margintopcounter % 3 == 0 || $margintopcounter % 4 == 0) : 
                        $separationtop = (($paper_margin_top*2)+$print_box_height);
                        if ($margintopcounter>3) $margintopcounter=0;
                    endif;

                    if ($marginwidthcounter % 5 == 0 || $marginwidthcounter % 6 == 0 || $marginwidthcounter % 7 == 0 || $marginwidthcounter % 8 == 0) : 
                        if ($marginwidthcounter % 5 == 0 || $marginwidthcounter % 7 == 0) :
                            // $separationwidth = ((($paper_margin_left*2)+$print_box_width)*2);
                            $separationwidth = ($paper_margin_left*2)+($print_box_width*2)+$paper_margin_left;
                        endif;
                        if ($marginwidthcounter % 6 == 0 || $marginwidthcounter % 8 == 0) :
                            $separationwidth = ((($paper_margin_left*2)+$print_box_width)*2)+$print_box_width;
                        endif;
                        if ($marginwidthcounter>7) : $marginwidthcounter=0; endif;
                    endif;
                endif;

                
                if ($options['from']>0 || $options['to']>0):
                    //fromto
                    $pdf1->setSourceFile($_SERVER['DOCUMENT_ROOT'].'/onlabels/assets2/'.$pdffiles[0]);
                    $templateId = $pdf1->importPage($p);
                    $pdf1->useTemplate($templateId, $separationwidth, $separationtop, $print_box_width, $print_box_height);
                endif;

                // echo '1-separationwidth ' . $separationwidth .'<br>';
                // echo '1-separationtop ' . $separationtop .'<br>';

                if ($options['cn22']>0):
                    if ($options['from']>0 || $options['to']>0) :
                        if ($marginwidthcounter % 1 == 0) : 
                            $separationwidth = (($paper_margin_left*2)+$print_box_width);
                        endif;

                        if ($marginwidthcounter % 3 == 0 || $marginwidthcounter % 4 == 0) :
                            $separationwidth = ((($paper_margin_left*2)+$print_box_width)*2)+$print_box_width;
                        endif;

                        if ($marginwidthcounter>4) : $marginwidthcounter=1; endif;
                    endif; 
                    
                    // if ($marginwidthcounter % 4 == 0) :
                    //     $separationwidth = ((($paper_margin_left*2)+$print_box_width)*2)+$print_box_width;
                    // endif;

                    //cn22
                    $pdf1->setSourceFile($_SERVER['DOCUMENT_ROOT'].'/onlabels/assets2/'.$pdffiles[1]);
                    $templateId1 = $pdf1->importPage($p);
                    $pdf1->useTemplate($templateId1, $separationwidth, $separationtop, $print_box_width, $print_box_height);
                endif;

                // echo '2-separationwidth ' . $separationwidth .'<br>';
                // echo '2-separationtop ' . $separationtop .'<br>';
                // echo 'margintopcounter ' . $margintopcounter . '<br>';

                $marginwidthcounter++;
                $margintopcounter++;
                
                // all cn22, from and to
                if ($options['cn22']>0 && $options['from']>0 && $options['to']>0) :
                    if ($p % 4 == 0) :  if ($p<count($order)) $pdf1->AddPage(); endif;
                else :
                    if ($p % 8 == 0) : 
                        $pdf1->AddPage(); 
                    endif;
                endif;
            
            endfor;
            $repeater++;
        endfor;

        $final = 'final-'.$options['uid'].'-'.$options['dimension'].'-'.$options['order_ids'].'.pdf';
        $pdf1->Output($_SERVER['DOCUMENT_ROOT'].'/onlabels/assets2/'.$final,'I');

        for ($i=0;$i<count($pdffiles);$i++) :
           unlink($_SERVER['DOCUMENT_ROOT'].'/onlabels/assets2/'.$pdffiles[$i]);
        endfor;
    }

    function pdf1x1($pdf, $order, $options)
    {
        // $pdf = new fpdflibrary('P','mm', 'A4');
        $pdf->AddPage();
        $pdf->SetAutoPageBreak(false);
        $pdf->AddFont('NanumBarunGothic','','NanumBarunGothic.ttf',true);
        $pdf->AddFont('NanumBarunGothicBold','','NanumBarunGothicBold.ttf',true);

        $ci=&get_instance();
        $ci->load->model('order_model','order');
        $ci->load->model('printlabels_model','printlabel');

        $y=0;
        for ($i=0; $i<count($order); $i++) :
            if ($i % 2 == 0) : 
                $x = 0;
                if ($i>1 && $options['cn22']==0) : $pdf->AddPage(); endif;

                // for cn22 only
                if ($options['from']==0 && $options['to']==0 && $options['cn22']>0) :
                    $x=-149;
                    // if ($i>1) : $pdf->AddPage(); endif;
                endif;
            else:
                $x = 149;
                if ($options['cn22']>0 && ($options['from']>0 || $options['to']>0)) $x=0;

                // for cn22 only
                if ($options['from']==0 && $options['to']==0 && $options['cn22']>0) :
                    $x=0;
                endif;
            endif;

            // echo 'x=='.$x .'<br>';

            if ($options['from']>0 || $options['to']>0) :
                $this->createAirMail1Form($x, $pdf, $ci->orders->get_specific_order($order[$i]), $options);

            endif;
            
            if ($options['cn22']>0) :
               // $this->createCustomsDeclaration1Form($x, $pdf, $ci->orders->get_specific_order($order[$i]), $options);

                if ($options['from']>0 || $options['to']>0) :

                    if ($i<(count($order)-1)) :
                        $pdf->AddPage();
                    endif;
                else:
                    if (($i+1)<count($order)) $pdf->AddPage();
                endif;
            endif;
        endfor;
    }

    function pdf1x2($pdf, $order, $options)
    {
        $pdf->AddPage();
        $pdf->SetAutoPageBreak(false);
        $pdf->AddFont('NanumBarunGothic','','NanumBarunGothic.ttf',true);
        $pdf->AddFont('NanumBarunGothicBold','','NanumBarunGothicBold.ttf',true);

        $ci=&get_instance();
        $ci->load->model('order_model','order');
        
        $merge_orders = array();
        for ($i=0; $i<count($order); $i++) :
            $merge_orders[] = $ci->orders->get_specific_order($order[$i]);
        endfor;

        $cnt_even=0;
        $x=0;
        if ($options['startpoint']>1):
            $empty_orders = array('id'=>0);
            array_unshift($merge_orders, $empty_orders);
        endif;

        for ($i=0; $i<count($merge_orders); $i++) :
            if ($i % 2 == 0) : 
                $y = 0;
                if ($cnt_even==1 && $options['cn22']==0) :
                    $x+=99;
                elseif ($cnt_even==1 && ($options['cn22']>0 && ($options['from']>0 || $options['to']>0))) :
                    $pdf->AddPage();
                endif;
                
                if ($cnt_even>0 && $cnt_even % 2 == 0):
                    $pdf->AddPage();
                    $x=0;
                endif; 
                $cnt_even++;

                // for cn22 only
                if ($options['from']==0 && $options['to']==0 && $options['cn22']>0) :
                    if ($i%4==0) : 
                        $x=-99;
                    else :
                        $x=0;
                    endif; 
                endif;
            else:
                $y+=146;
            endif;

            // echo 'x=='.$x .'y=='.$y .'<br>';

            if ($options['from']>0 || $options['to']>0) :
                $this->createAirMail2Form($x, $y, $pdf, $merge_orders[$i], $options);
            endif; 

            if ($options['cn22']>0) :
                $this->createCustomsDeclaration2Form($x, $y, $pdf, $merge_orders[$i]);
            endif;
        endfor;
    }

    function pdf2x2($pdf, $order, $options)
    {
        $pdf->AddPage();
        $pdf->SetAutoPageBreak(false);
        $pdf->AddFont('NanumBarunGothic','','NanumBarunGothic.ttf',true);
        $pdf->AddFont('NanumBarunGothicBold','','NanumBarunGothicBold.ttf',true);

        $ci=&get_instance();
        $ci->load->model('order_model','order');

        $merge_orders = array();
        for ($i=0; $i<count($order); $i++) :
            $merge_orders[] = $ci->orders->get_specific_order($order[$i]);
        endfor;
        
        $x = 8;
        $y = 4;
        
        $cnt_even=0;
        if ($options['startpoint']>1):
            switch ($options['startpoint']) {
                case 2: array_unshift($merge_orders, array('id' => 0)); break;
                case 3: 
                    if (($options['from']>0 || $options['to']>0) && $options['cn22']>0) :
                        array_unshift($merge_orders, array('id' => 0), array('id' => 0)); 
                    else :
                        array_unshift($merge_orders, array('id' => 0), array('id' => 0), array('id' => 0), array('id' => 0)); 
                    endif;
                break;
                case 4: 
                    if (($options['from']>0 || $options['to']>0) && $options['cn22']>0) :
                        array_unshift($merge_orders, array('id' => 0), array('id' => 0), array('id' => 0)); 
                    else :
                        array_unshift($merge_orders, array('id' => 0), array('id' => 0), array('id' => 0), array('id' => 0), array('id' => 0));
                    endif;
                break;
            }
        endif;

        for ($i=0; $i<count($merge_orders); $i++) :
            if ($i % 2 == 0) : 

                if ($i>0 && $options['cn22']==0) :
                    $x+=70;
                    $y=4;
                    if ($i%8==0) :
                        $pdf->AddPage();
                        $x=8;
                        $y=4;
                    endif;
                endif; 

                if ($i>0 && $options['cn22']>0 && ($options['from']>0 || $options['to']>0)) :
                    $x+=140;
                    $y=4;
                    if ($i%4==0) :
                        $pdf->AddPage();
                        $x=8;
                        $y=4;
                    endif;
                endif;

                // for cn22 only
                if ($options['from']==0 && $options['to']==0 && $options['cn22']>0) :
                    $x-=70;
                    if ($i>0 && $i%2==0) :
                        $x+=140;
                        $y=4;
                        if ($i%8==0) :
                            $pdf->AddPage();
                            $x=-62;
                            $y=4;
                        endif;
                    endif;
                endif;
            else:
                $y+=101;
            endif;

            // echo 'x=='.$x .'y=='.$y .'<br>';

            if ($options['from']>0 || $options['to']>0) :
                $this->createAirMail3Form($x, $y, $pdf, $merge_orders[$i], $options);
            endif; 
            
            if ($options['cn22']>0) :
                $this->createCustomsDeclaration3Form($x, $y, $pdf, $merge_orders[$i]);
            endif;
        endfor;
    }

    function pdf3xn($pdf, $orders, $options, $col = 7)
    {
        $pdf->AddPage();
        $pdf->SetAutoPageBreak(false);
        $pdf->AddFont('NanumBarunGothic','','NanumBarunGothic.ttf',true);
        $pdf->AddFont('NanumBarunGothicBold','','NanumBarunGothicBold.ttf',true);

        $ci=&get_instance();
        $ci->load->model('order_model','order');
        
        $array_order = array();
        $merge_orders = array();
        for ($i=0; $i<count($orders); $i++)
        {
            $array_order[$i] = $ci->orders->get_specific_order($orders[$i]);

            if ($options['from']>0) :
            $merge_orders[] = 
                    array ('FROM',
                           $array_order[$i]['seller_company_name'],
                           $array_order[$i]['seller_street1'].' '.$array_order[$i]['seller_street2'],
                           $array_order[$i]['seller_city'].' '.$array_order[$i]['seller_stateorprovice'],
                           $array_order[$i]['seller_country'],
                           $array_order[$i]['seller_phone_no']);
            endif; 

            if ($options['to']>0) :
            $merge_orders[] = 
                    array ('TO',
                           $array_order[$i]['name'],
                           $array_order[$i]['street1'].' '.$array_order[$i]['street2'],
                           $array_order[$i]['city_name'].' '.$array_order[$i]['stateorprovince'],
                           $array_order[$i]['country_name'],
                           $array_order[$i]['shipto_phone_no']);
            endif;
        }
        
        $empty_orders = array();
        for ($i=1;$i<$options['startpoint'];$i++) :
            $empty_orders[] = array('','','','','','');
        endfor;
        if (count($empty_orders)>0) $merge_orders = array_merge($empty_orders,$merge_orders);
        
        $x=3;
        $y=10;
        $height = 38.1;
        $count=0;
        if ($col==8) :
            $y=6;
            $height = 34;
        endif;

        for ($i=0; $i<count($merge_orders); $i++) :
            // echo $x.'<br>';

            if ($i % $col == 0) :
                if ($i>0) : 
                    if ($col==7) : 
                        $y=10;
                    elseif ($col==8) : 
                        $y=6;
                    endif;
                    $x+=67.3;
                endif;
                // if ($i>0) $x+=67.3;
                if (ctype_digit($i/($col*3).'')) $x=3;
            endif;
            $this->createBoxForm($x, $y, 66, $height, $pdf, $merge_orders[$i], $i, $options);

            // $y+=39.4;
            if ($col==7) : 
                $y+=39.4;
            elseif ($col==8) : 
                $y+=35.3;
            endif;
        endfor;
    }

    function heightpercentage($percent, $height)
    {
        return (($height/100)*$percent);
    }

    function createAirMail1Form($ValueToAdd = 0, $pdf, $order, $options)
    {   
        $options['paper_margin_left'] = 0;
        $options['paper_margin_top'] = 0;   
        $options['print_box_width'] = 132;
        $options['print_box_height'] = 190;
        $pdf->SetLineWidth(0.2);
        $pdf->SetFillColor(255);
        $pdf->RoundedRect($options['paper_margin_left']+$ValueToAdd,
                          $options['paper_margin_top'], 
                          $options['print_box_width'], 
                          $options['print_box_height'], 0, 'DF'); // 
        // lines
        $pdf->SetLineWidth(0.7);
        $pdf->Line($options['paper_margin_left']+5+$ValueToAdd,
                   $options['paper_margin_top']+5.5,
                   $options['paper_margin_left']+5+$ValueToAdd,
                   $options['paper_margin_top']+11.5); // header line

        $pdf->SetDrawColor(169,169,169);
        $pdf->SetLineWidth(0.4);
    
        $fromNtoY = 0;
        for ($i=0;$i<2;$i++) :
            if ($i>0) $fromNtoY+=69;

            $pdf->Line($options['paper_margin_left']+5+$ValueToAdd,
                       $options['paper_margin_top']+19+$fromNtoY,
                       $options['paper_margin_left']+127+$ValueToAdd,
                       $options['paper_margin_top']+19+$fromNtoY); // top
            $pdf->Line($options['paper_margin_left']+5+$ValueToAdd,
                       $options['paper_margin_top']+19+$fromNtoY,
                       $options['paper_margin_left']+5+$ValueToAdd,
                       $options['paper_margin_top']+88+$fromNtoY); // left
            $pdf->Line($options['paper_margin_left']+127+$ValueToAdd,
                       $options['paper_margin_top']+19+$fromNtoY,
                       $options['paper_margin_left']+127+$ValueToAdd,
                       $options['paper_margin_top']+88+$fromNtoY); // right
            $pdf->Line($options['paper_margin_left']+5+$ValueToAdd,
                       $options['paper_margin_top']+88+$fromNtoY,
                       $options['paper_margin_left']+127+$ValueToAdd,
                       $options['paper_margin_top']+88+$fromNtoY); // bottom
            $pdf->Line($options['paper_margin_left']+30+$ValueToAdd,
                       $options['paper_margin_top']+19+$fromNtoY,
                       $options['paper_margin_left']+30+$ValueToAdd,
                       $options['paper_margin_top']+34+$fromNtoY); // first row
            $pdf->Line($options['paper_margin_left']+104+$ValueToAdd,
                       $options['paper_margin_top']+19+$fromNtoY,
                       $options['paper_margin_left']+104+$ValueToAdd,
                       $options['paper_margin_top']+34+$fromNtoY); 
            $pdf->Line($options['paper_margin_left']+5+$ValueToAdd,
                       $options['paper_margin_top']+34+$fromNtoY,
                       $options['paper_margin_left']+127+$ValueToAdd,
                       $options['paper_margin_top']+34+$fromNtoY); 
            $pdf->Line($options['paper_margin_left']+5+$ValueToAdd,
                       $options['paper_margin_top']+64+$fromNtoY,
                       $options['paper_margin_left']+127+$ValueToAdd,
                       $options['paper_margin_top']+64+$fromNtoY); // second row
            $pdf->Line($options['paper_margin_left']+5+$ValueToAdd,
                       $options['paper_margin_top']+76+$fromNtoY,
                       $options['paper_margin_left']+127+$ValueToAdd,
                       $options['paper_margin_top']+76+$fromNtoY); // third row
            $pdf->Line($options['paper_margin_left']+30+$ValueToAdd,
                       $options['paper_margin_top']+64+$fromNtoY,
                       $options['paper_margin_left']+30+$ValueToAdd,
                       $options['paper_margin_top']+76+$fromNtoY);
            $pdf->Line($options['paper_margin_left']+62+$ValueToAdd,
                       $options['paper_margin_top']+64+$fromNtoY,
                       $options['paper_margin_left']+62+$ValueToAdd,
                       $options['paper_margin_top']+76+$fromNtoY);
            $pdf->Line($options['paper_margin_left']+87+$ValueToAdd,
                       $options['paper_margin_top']+64+$fromNtoY,
                       $options['paper_margin_left']+87+$ValueToAdd,
                       $options['paper_margin_top']+76+$fromNtoY);
            $pdf->Line($options['paper_margin_left']+30+$ValueToAdd,
                       $options['paper_margin_top']+76+$fromNtoY,
                       $options['paper_margin_left']+30+$ValueToAdd,
                       $options['paper_margin_top']+88+$fromNtoY);

            if ($fromNtoY>68) :
                $fromNtoY=96;
                $pdf->Line($options['paper_margin_left']+5+$ValueToAdd,
                           $options['paper_margin_top']+19+$fromNtoY,
                           $options['paper_margin_left']+5+$ValueToAdd,
                           $options['paper_margin_top']+88+$fromNtoY); // left
                $pdf->Line($options['paper_margin_left']+127+$ValueToAdd,
                           $options['paper_margin_top']+19+$fromNtoY,
                           $options['paper_margin_left']+127+$ValueToAdd,
                           $options['paper_margin_top']+88+$fromNtoY); // right
                $pdf->Line($options['paper_margin_left']+5+$ValueToAdd,
                           $options['paper_margin_top']+88+$fromNtoY,
                           $options['paper_margin_left']+127+$ValueToAdd,
                           $options['paper_margin_top']+88+$fromNtoY); // bottom
            endif;
        endfor;

        $pdf->SetDrawColor(0);
        $pdf->SetLineWidth(0.2);
        $pdf->SetXY($options['paper_margin_left']+5+$ValueToAdd,$options['paper_margin_top']+6);
        $pdf->SetFont('Arial','B',20);
        
        $pdf->CellFitScale(79,6,' AIR MAIL Small Packet',0,0,'L');

        $pdf->SetXY($options['paper_margin_left']+5+$ValueToAdd,$options['paper_margin_top']+24);
        $pdf->SetFont('Arial','B',18);
        $pdf->Cell(25,5,'FROM',0,1,'C');

        $pdf->SetXY($options['paper_margin_left']+5+$ValueToAdd,$options['paper_margin_top']+93);
        $pdf->SetFont('Arial','B',18);
        $pdf->Cell(25,5,'TO',0,1,'C');

        // custom text
        $nameYvalue = 2;
        if (strlen($order['seller_company_name']) > 25) $nameYvalue = 10;
        $pdf->SetXY($options['paper_margin_left']+31+$ValueToAdd,$options['paper_margin_top']+22+$nameYvalue);
        $pdf->SetFont('Arial','B',16);
        $pdf->MultiCell(72,5,$order['seller_company_name'],0,'C');

        $pdf->SetXY($options['paper_margin_left']+104+$ValueToAdd,$options['paper_margin_top']+24);
        $pdf->SetFont('Arial','B',16);

        $seller_country_code = 'KR';
        if (strtolower($order['seller_country']) !== 'south korea')
            $seller_country_code = $order['seller_country'];
        $pdf->Cell(23,5,$seller_country_code,0,1,'C');

        $pdf->SetXY($options['paper_margin_left']+8+$ValueToAdd,$options['paper_margin_top']+37);
        $pdf->SetFont('Arial','',16);

if (!empty($order['seller_street2'])) :
        $pdf->MultiCell(116,8,$order['seller_street1'].'
'.$order['seller_street2'].'
'.$order['seller_city'].' '.$order['seller_stateorprovice'],0,'L');
else :
        $pdf->MultiCell(116,8,$order['seller_street1'].'
'.$order['seller_city'].' '.$order['seller_stateorprovice'],0,'L');
endif;
        //$pdf->Cell(118,5,'Maplestore',1,1,'L');

        $pdf->SetXY($options['paper_margin_left']+5+$ValueToAdd,$options['paper_margin_top']+68);
        $pdf->SetFont('Arial','',14);
        $pdf->Cell(25,4,'Post Code',0,1,'C');

        $pdf->SetXY($options['paper_margin_left']+30+$ValueToAdd,$options['paper_margin_top']+68);
        $pdf->SetFont('Arial','B',16);
        $pdf->Cell(32,5,$order['seller_postal_code'],0,1,'C');

        $pdf->SetXY($options['paper_margin_left']+62+$ValueToAdd,$options['paper_margin_top']+68);
        $pdf->SetFont('Arial','',14);
        $pdf->Cell(25,4,'Country',0,1,'C');

        $pdf->SetXY($options['paper_margin_left']+87+$ValueToAdd,$options['paper_margin_top']+68);
        $pdf->SetFont('Arial','B',16);
        $pdf->Cell(40,5,$order['seller_country'],0,1,'C');

        $pdf->SetXY($options['paper_margin_left']+5+$ValueToAdd,$options['paper_margin_top']+80);
        $pdf->SetFont('Arial','',14);
        $pdf->Cell(25,4,'TEL',0,1,'C');

        $pdf->SetXY($options['paper_margin_left']+33+$ValueToAdd,$options['paper_margin_top']+80);
        $pdf->Cell(91,4,$order['seller_phone_no'],0,1,'L');

        // second
        $nameYvalue = 2;
        if (strlen($order['name']) > 25) $nameYvalue = 0;
        $pdf->SetXY($options['paper_margin_left']+31+$ValueToAdd,$options['paper_margin_top']+91+$nameYvalue);
        $pdf->SetFont('Arial','B',16);
        //$pdf->Cell(57,5,$order['name'],0,1,'C');
        $pdf->MultiCell(72,5,$order['name'],0,'C');

        $pdf->SetXY($options['paper_margin_left']+104+$ValueToAdd,$options['paper_margin_top']+93);
        $pdf->SetFont('Arial','B',16);
        $pdf->Cell(23,5,$order['country_code'],0,1,'C');


        $pdf->SetXY($options['paper_margin_left']+8+$ValueToAdd,$options['paper_margin_top']+106);
        $pdf->SetFont('Arial','',16);

if (!empty($order['street2'])) :
        $pdf->MultiCell(116,8,$order['street1'].'
'.$order['street2'].'
'.$order['city_name'].' '.$order['stateorprovince'],0,'L');
else :
            $pdf->MultiCell(116,8,$order['street1'].'
'.$order['city_name'].' '.$order['stateorprovince'],0,'L');
endif;
        $pdf->SetXY($options['paper_margin_left']+5+$ValueToAdd,$options['paper_margin_top']+137);
        $pdf->SetFont('Arial','',14);
        $pdf->Cell(25,4,'Post Code',0,1,'C');

        $pdf->SetXY($options['paper_margin_left']+30+$ValueToAdd,$options['paper_margin_top']+137);
        $pdf->SetFont('Arial','B',16);
        $pdf->Cell(32,5,$order['postal_code'],0,1,'C');

        $pdf->SetXY($options['paper_margin_left']+62+$ValueToAdd,$options['paper_margin_top']+137);
        $pdf->SetFont('Arial','',14);
        $pdf->Cell(25,4,'Country',0,1,'C');

        $pdf->SetXY($options['paper_margin_left']+87+$ValueToAdd,$options['paper_margin_top']+137);
        $pdf->SetFont('Arial','B',16);
        $pdf->Cell(40,5,$order['country_name'],0,1,'C');

        $pdf->SetXY($options['paper_margin_left']+5+$ValueToAdd,$options['paper_margin_top']+149);
        $pdf->SetFont('Arial','',14);
        $pdf->Cell(25,4,'TEL',0,1,'C');

        $pdf->SetXY($options['paper_margin_left']+33+$ValueToAdd,$options['paper_margin_top']+149);
        $pdf->Cell(91,4,$order['shipto_phone_no'],0,1,'L');

        $pdf->SetFont('NanumBarunGothicBold','',12);
        $pdf->SetXY($options['paper_margin_left']+8.5+$ValueToAdd,$options['paper_margin_top']+162);
        $pdf->MultiCell(115,5,$order['seller_msg'],0,'C');

        // $pdf->Output($_SERVER['DOCUMENT_ROOT'].'/onlabels/assets2/onlables'.$order['id'].'.pdf','F');

        //generate
        // $pdfname = 'fromto-'.$options['uid'].'-'.$options['dimension'].'-'.$options['order_ids'].'.pdf';
        // $pdf->Output($_SERVER['DOCUMENT_ROOT'].'/onlabels/assets2/'.$pdfname,'F');

        //formulate
        // $pdf1=new fpdi('L'); // so we initiate exFPDF instead of FPDI
        // $pdf1->AddPage();
        // $pdf1->setSourceFile($_SERVER['DOCUMENT_ROOT'].'/onlabels/assets2/'.$pdfname);
        // $tplIdx = $pdf1->importPage(1);
        // $pdf1->useTemplate($tplIdx, 0, 0, 264, 380);
        // $pdf1->Output();
        // unlink($_SERVER['DOCUMENT_ROOT'].'/onlabels/assets2/'.$pdfname);
    }

    function createAirMail2Form($xToAdd = 0, $yToAdd = 0, $pdf, $order, $options)
    {
        if ($order['id']==0) return;

        $xToAdd=-8;
        $yToAdd=-8;


        $pdf->SetDrawColor(0);
        $pdf->SetLineWidth(0.2);
        $pdf->SetFillColor(255);
        $pdf->RoundedRect(8+$xToAdd, 8+$yToAdd, 95, 135, 0, 'DF'); // 
        // lines
        $pdf->SetLineWidth(0.5);
        $pdf->Line(12+$xToAdd,11+$yToAdd,12+$xToAdd,15+$yToAdd); // header line
        $pdf->SetDrawColor(169,169,169);
        $pdf->SetLineWidth(0.4);
        
        $fromNtoY = 0;
        for ($i=0;$i<2;$i++) :
            if ($i>0) $fromNtoY+=51;

            $pdf->Line(12+$xToAdd,20+$yToAdd+$fromNtoY,99+$xToAdd,20+$yToAdd+$fromNtoY); // top
            $pdf->Line(12+$xToAdd,20+$yToAdd+$fromNtoY,12+$xToAdd,71+$yToAdd+$fromNtoY); // left
            $pdf->Line(99+$xToAdd,20+$yToAdd+$fromNtoY,99+$xToAdd,71+$yToAdd+$fromNtoY); // right
            $pdf->Line(12+$xToAdd,71+$yToAdd+$fromNtoY,99+$xToAdd,71+$yToAdd+$fromNtoY); // bottom

            $pdf->Line(12+$xToAdd,31+$yToAdd+$fromNtoY,99+$xToAdd,31+$yToAdd+$fromNtoY); // first row
            $pdf->Line(30+$xToAdd,20+$yToAdd+$fromNtoY,30+$xToAdd,31+$yToAdd+$fromNtoY); 
            $pdf->Line(85+$xToAdd,20+$yToAdd+$fromNtoY,85+$xToAdd,31+$yToAdd+$fromNtoY); 
            $pdf->Line(12+$xToAdd,53+$yToAdd+$fromNtoY,99+$xToAdd,53+$yToAdd+$fromNtoY); // second row
            $pdf->Line(12+$xToAdd,62+$yToAdd+$fromNtoY,99+$xToAdd,62+$yToAdd+$fromNtoY); // third row
            $pdf->Line(30+$xToAdd,53+$yToAdd+$fromNtoY,30+$xToAdd,62+$yToAdd+$fromNtoY);
            $pdf->Line(53+$xToAdd,53+$yToAdd+$fromNtoY,53+$xToAdd,62+$yToAdd+$fromNtoY);
            $pdf->Line(70+$xToAdd,53+$yToAdd+$fromNtoY,70+$xToAdd,62+$yToAdd+$fromNtoY); 
            $pdf->Line(30+$xToAdd,62+$yToAdd+$fromNtoY,30+$xToAdd,71+$yToAdd+$fromNtoY);

            if ($fromNtoY>50) :
                $fromNtoY=102;
                $pdf->Line(12+$xToAdd,20+$yToAdd+$fromNtoY,12+$xToAdd,39+$yToAdd+$fromNtoY); // left
                $pdf->Line(99+$xToAdd,20+$yToAdd+$fromNtoY,99+$xToAdd,39+$yToAdd+$fromNtoY); // right
                $pdf->Line(12+$xToAdd,39+$yToAdd+$fromNtoY,99+$xToAdd,39+$yToAdd+$fromNtoY); // bottom
            endif;
        endfor;

        $pdf->SetDrawColor(0);
        $pdf->SetLineWidth(0.2);
        $pdf->SetXY(13+$xToAdd,11.5+$yToAdd);
        $pdf->SetFont('Arial','B',15);
        $pdf->Cell(60,4,'AIR MAIL Small Packet',0,0,'L');
        $pdf->SetXY(12+$xToAdd,23.5+$yToAdd);
        $pdf->SetFont('Arial','B',13);
        $pdf->Cell(18,4,'FROM',0,1,'C');
        $pdf->SetXY(12+$xToAdd,74.5+$yToAdd);
        $pdf->SetFont('Arial','B',13);
        $pdf->Cell(18,4,'TO',0,1,'C');
        // custom text

        $nameYvalue = 0;
        if (strlen($order['seller_company_name']) > 25) $nameYvalue = -2;
        $pdf->SetXY(30+$xToAdd,23.5+$yToAdd+$nameYvalue);
        $pdf->SetFont('Arial','B',12);
        $pdf->MultiCell(55,4,$order['seller_company_name'],0,'C');
        
        $pdf->SetXY(85+$xToAdd,23.5+$yToAdd);
        $pdf->SetFont('Arial','B',12);
        $seller_country_code = 'KR';
        if (strtolower($order['seller_country']) !== 'south korea')
            $seller_country_code = $order['seller_country'];
        $pdf->Cell(14,4,$seller_country_code,0,1,'C');
        $pdf->SetXY(14+$xToAdd,33+$yToAdd);
        $pdf->SetFont('Arial','',11);

        if (!empty($order['seller_street2'])) :
        $pdf->MultiCell(83,5,$order['seller_street1'].'
'.$order['seller_street2'].'
'.$order['seller_city'].' '.$order['seller_stateorprovice'],0,'L');
        else :
        $pdf->MultiCell(83,5,$order['seller_street1'].'
'.$order['seller_city'].' '.$order['seller_stateorprovice'],0,'L');
        endif;

        $pdf->SetXY(12+$xToAdd,56+$yToAdd);
        $pdf->SetFont('Arial','',10);
        $pdf->Cell(18,3,'Post Code',0,1,'C');
        $pdf->SetXY(30+$xToAdd,56+$yToAdd);
        $pdf->SetFont('Arial','B',12);
        $pdf->Cell(23,3,$order['seller_postal_code'],0,1,'C');
        $pdf->SetXY(53+$xToAdd,56+$yToAdd);
        $pdf->SetFont('Arial','',10);
        $pdf->Cell(17,3,'Country',0,1,'C');
        $pdf->SetXY(70+$xToAdd,56+$yToAdd);
        $pdf->SetFont('Arial','B',12);
        $pdf->Cell(29,3,$order['seller_country'],0,1,'C');
        $pdf->SetXY(12+$xToAdd,65+$yToAdd);
        $pdf->SetFont('Arial','',10);
        $pdf->Cell(18,3,'TEL',0,1,'C');
        $pdf->SetXY(32+$xToAdd,65+$yToAdd);
        $pdf->Cell(65,3,$order['seller_phone_no'],0,1,'L');
        // second

        $nameYvalue = 0;
        if (strlen($order['name']) > 25) $nameYvalue = -2;
        $pdf->SetXY(30+$xToAdd,74.5+$yToAdd+$nameYvalue);
        $pdf->SetFont('Arial','B',12);
        $pdf->MultiCell(55,4,$order['name'],0,'C');

        $pdf->SetXY(85+$xToAdd,74.5+$yToAdd);
        $pdf->SetFont('Arial','B',12);
        $pdf->Cell(14,4,$order['country_code'],0,1,'C');
        $pdf->SetXY(14+$xToAdd,84+$yToAdd);
        $pdf->SetFont('Arial','',11);

        if (!empty($order['street2'])) :
        $pdf->MultiCell(83,5,$order['street1'].'
'.$order['street2'].'
'.$order['city_name'].' '.$order['stateorprovince'],0,'L');
        else :
        $pdf->MultiCell(83,5,$order['street1'].'
'.$order['city_name'].' '.$order['stateorprovince'],0,'L');
        endif;
        $pdf->SetXY(12+$xToAdd,107+$yToAdd);
        $pdf->SetFont('Arial','',10);
        $pdf->Cell(18,3,'Post Code',0,1,'C');
        $pdf->SetXY(30+$xToAdd,107+$yToAdd);
        $pdf->SetFont('Arial','B',12);
        $pdf->Cell(23,3,$order['postal_code'],0,1,'C');
        $pdf->SetXY(53+$xToAdd,107+$yToAdd);
        $pdf->SetFont('Arial','',10);
        $pdf->Cell(17,3,'Country',0,1,'C');
        $pdf->SetXY(70+$xToAdd,107+$yToAdd);
        $pdf->SetFont('Arial','B',12);
        $pdf->Cell(29,3,$order['country_name'],0,1,'C');
        $pdf->SetXY(12+$xToAdd,116+$yToAdd);
        $pdf->SetFont('Arial','',11);
        $pdf->Cell(18,3,'TEL',0,1,'C');
        $pdf->SetXY(32+$xToAdd,116+$yToAdd);
        $pdf->Cell(65,3,$order['shipto_phone_no'],0,1,'L');

        $pdf->SetFont('NanumBarunGothicBold','',9);
        $pdf->SetXY(14+$xToAdd,126+$yToAdd);
        $pdf->MultiCell(83,4,$order['seller_msg'],0,'C');

        // if ($options['from']<1)
        // {
        //     $pdf->SetFillColor(255);
        //     $pdf->Rect(11, 20+$yToAdd, 89, 50.8, 'F');
        // }
        // if ($options['to']<1)
        // {
        //     $pdf->SetFillColor(255);
        //     $pdf->Rect(11, 71.3+$yToAdd, 89, 70, 'F');
        // }
        // if ($options['from']<1 && $options['to']<1)
        // {
        //     $pdf->SetFillColor(255);
        //     $pdf->Rect(7, 7+$yToAdd, 97, 137, 'F');
        // }
    }

    function createAirMail3Form($xToAdd = 0, $yToAdd = 0, $pdf, $order, $options)
    {
        // print_r($order);
        if ($order['id']==0) return;

        $xToAdd=-2;
        $yToAdd=-2;
           
        $pdf->SetDrawColor(0);
        $pdf->SetLineWidth(0.1);
        $pdf->SetFillColor(255);
        $pdf->RoundedRect(2+$xToAdd, 2+$yToAdd, 66, 96, 0, 'DF'); // 
        // lines
        $pdf->SetLineWidth(0.2);
        $pdf->Line(4+$xToAdd,4.4+$yToAdd,4+$xToAdd,6.9+$yToAdd); // header line
        $pdf->SetDrawColor(169,169,169);
        $pdf->Line(4+$xToAdd,10+$yToAdd,66+$xToAdd,10+$yToAdd); // top
        $pdf->Line(4+$xToAdd,10+$yToAdd,4+$xToAdd,96+$yToAdd); // left
        $pdf->Line(66+$xToAdd,10+$yToAdd,66+$xToAdd,96+$yToAdd); // right
        $pdf->Line(4+$xToAdd,96+$yToAdd,66+$xToAdd,96+$yToAdd); // bottom

        $pdf->Line(4+$xToAdd,18+$yToAdd,66+$xToAdd,18+$yToAdd); // first row
        $pdf->Line(16+$xToAdd,10+$yToAdd,16+$xToAdd,18+$yToAdd);
        $pdf->Line(56+$xToAdd,10+$yToAdd,56+$xToAdd,18+$yToAdd);
        $pdf->Line(4+$xToAdd,34+$yToAdd,66+$xToAdd,34+$yToAdd); // second row
        $pdf->Line(4+$xToAdd,40+$yToAdd,66+$xToAdd,40+$yToAdd); // third row
        $pdf->Line(16+$xToAdd,34+$yToAdd,16+$xToAdd,40+$yToAdd);
        $pdf->Line(32+$xToAdd,34+$yToAdd,32+$xToAdd,40+$yToAdd);
        $pdf->Line(46+$xToAdd,34+$yToAdd,46+$xToAdd,40+$yToAdd); 
        $pdf->Line(4+$xToAdd,46+$yToAdd,66+$xToAdd,46+$yToAdd); // fourth row
        $pdf->Line(16+$xToAdd,40+$yToAdd,16+$xToAdd,46+$yToAdd);
        $pdf->Line(4+$xToAdd,54+$yToAdd,66+$xToAdd,54+$yToAdd); // repeat first row
        $pdf->Line(16+$xToAdd,46+$yToAdd,16+$xToAdd,54+$yToAdd); 
        $pdf->Line(56+$xToAdd,46+$yToAdd,56+$xToAdd,54+$yToAdd);
        $pdf->Line(4+$xToAdd,70+$yToAdd,66+$xToAdd,70+$yToAdd); // second row
        $pdf->Line(4+$xToAdd,76+$yToAdd,66+$xToAdd,76+$yToAdd); // third row
        $pdf->Line(16+$xToAdd,70+$yToAdd,16+$xToAdd,76+$yToAdd);
        $pdf->Line(32+$xToAdd,70+$yToAdd,32+$xToAdd,76+$yToAdd);
        $pdf->Line(46+$xToAdd,70+$yToAdd,46+$xToAdd,76+$yToAdd); 
        $pdf->Line(4+$xToAdd,82+$yToAdd,66+$xToAdd,82+$yToAdd); // fourth row
        $pdf->Line(16+$xToAdd,76+$yToAdd,16+$xToAdd,82+$yToAdd);

        $pdf->SetDrawColor(0);
        $pdf->SetLineWidth(0.2);
        $pdf->SetXY(4+$xToAdd,4.8+$yToAdd);
        $pdf->SetFont('Arial','B',10);
        $pdf->Cell(37,2,'AIR MAIL Small Packet',0,0,'L');
        $pdf->SetXY(4+$xToAdd,12.8+$yToAdd);
        $pdf->SetFont('Arial','B',9);
        $pdf->Cell(12,2.5,'FROM',0,1,'C');
        $pdf->SetXY(4+$xToAdd,48.5+$yToAdd);
        $pdf->SetFont('Arial','B',9);
        $pdf->Cell(12,2.5,'TO',0,1,'C');
        // custom text

        $nameYvalue = 0;
        if (strlen($order['seller_company_name']) > 25) $nameYvalue = -1;
        $pdf->SetXY(16+$xToAdd,12.8+$yToAdd+$nameYvalue);
        $pdf->SetFont('Arial','B',8);
        $pdf->MultiCell(40,2.7,$order['seller_company_name'],0,'C');

        $pdf->SetXY(56+$xToAdd,12.8+$yToAdd);
        $pdf->SetFont('Arial','B',8);
        $seller_country_code = 'KR';
        if (strtolower($order['seller_country']) !== 'south korea')
            $seller_country_code = $order['seller_country'];
        $pdf->Cell(10,2.7,$seller_country_code,0,1,'C');
        $pdf->SetXY(5+$xToAdd,20+$yToAdd);
        $pdf->SetFont('Arial','',8);
        if (!empty($order['seller_street2'])) :
        $pdf->MultiCell(60,4,$order['seller_street1'].'
'.$order['seller_street2'].'
'.$order['seller_city'].' '.$order['seller_stateorprovice'],0,'L');
        else :
        $pdf->MultiCell(60,4,$order['seller_street1'].'
'.$order['seller_city'].' '.$order['seller_stateorprovice'],0,'L');
        endif;
        $pdf->SetXY(4+$xToAdd,35.8+$yToAdd);
        $pdf->SetFont('Arial','',7);
        $pdf->Cell(12,2.5,'Post Code',0,1,'C');
        $pdf->SetXY(16+$xToAdd,35.8+$yToAdd);
        $pdf->SetFont('Arial','B',8);
        $pdf->Cell(16,2.7,$order['seller_postal_code'],0,1,'C');
        $pdf->SetXY(32+$xToAdd,35.8+$yToAdd);
        $pdf->SetFont('Arial','',7);
        $pdf->Cell(14,2.7,'Country',0,1,'C');
        $pdf->SetXY(46+$xToAdd,35.8+$yToAdd);
        $pdf->SetFont('Arial','B',8);
        $pdf->Cell(20,2.7,$order['seller_country'],0,1,'C');
        $pdf->SetXY(4+$xToAdd,41.7+$yToAdd);
        $pdf->SetFont('Arial','',7);
        $pdf->Cell(12,2.5,'TEL',0,1,'C');
        $pdf->SetXY(18+$xToAdd,41.8+$yToAdd);
        $pdf->Cell(46,2,$order['seller_phone_no'],0,1,'L');
        // // second

        $nameYvalue = 0;
        if (strlen($order['name']) > 25) $nameYvalue = -1;
        $pdf->SetXY(16+$xToAdd,48.5+$yToAdd+$nameYvalue);
        $pdf->SetFont('Arial','B',8);
        $pdf->MultiCell(40,2.7,$order['name'],0,'C');
        $pdf->SetXY(56+$xToAdd,48.5+$yToAdd);
        $pdf->Cell(10,2.7,$order['country_code'],0,1,'C');
        $pdf->SetXY(5+$xToAdd,56+$yToAdd);
        $pdf->SetFont('Arial','',8);

        if (!empty($order['street2'])) :
        $pdf->MultiCell(60,4,$order['street1'].'
'.$order['street2'].'
'.$order['city_name'].' '.$order['stateorprovince'],0,'L');
        else :
        $pdf->MultiCell(60,4,$order['street1'].'
'.$order['city_name'].' '.$order['stateorprovince'],0,'L');
        endif;
        $pdf->SetXY(4+$xToAdd,71.7+$yToAdd);
        $pdf->SetFont('Arial','',7);
        $pdf->Cell(12,2.5,'Post Code',0,1,'C');
        $pdf->SetXY(16+$xToAdd,71.7+$yToAdd);
        $pdf->SetFont('Arial','B',8);
        $pdf->Cell(16,2.7,$order['postal_code'],0,1,'C');
        $pdf->SetXY(32+$xToAdd,71.7+$yToAdd);
        $pdf->SetFont('Arial','',7);
        $pdf->Cell(14,2.7,'Country',0,1,'C');
        $pdf->SetXY(46+$xToAdd,71.7+$yToAdd);
        $pdf->SetFont('Arial','B',8);
        $pdf->Cell(20,2.7,$order['country_name'],0,1,'C');
        $pdf->SetXY(4+$xToAdd,77.7+$yToAdd);
        $pdf->SetFont('Arial','',7);
        $pdf->Cell(12,2.5,'TEL',0,1,'C');
        $pdf->SetXY(18+$xToAdd,77.7+$yToAdd);
        $pdf->Cell(46,2,$order['shipto_phone_no'],0,1,'L');

        $pdf->SetFont('NanumBarunGothicBold','',7);
        $pdf->SetXY(6+$xToAdd,85+$yToAdd);
        $pdf->MultiCell(58,3,$order['seller_msg'],0,'C');

        // if ($options['from']<1)
        // {
        //     $pdf->SetFillColor(255);
        //     $pdf->Rect(3+$xToAdd, 10+$yToAdd, 64, 35.8, 'F');
        // }
        // if ($options['to']<1)
        // {
        //     $pdf->SetFillColor(255);
        //     $pdf->Rect(3+$xToAdd, 46.2+$yToAdd, 64, 51, 'F');
        // }
        // if ($options['from']<1 && $options['to']<1)
        // {
        //     $pdf->SetFillColor(255);
        //     $pdf->Rect(1+$xToAdd, 1+$yToAdd, 68, 98, 'F');
        // }
    }

    function createCustomsDeclaration1Form($ValueToAdd = 0, $pdf, $order, $options)
    {
        $ValueToAdd = -157; 
        $yValueToADd = 0;

        $pdf->SetLineWidth(0.2);
        $pdf->SetFillColor(255);
        $pdf->RoundedRect(157+$ValueToAdd, $yValueToADd, 132, 190, 0, 'DF');

        // vertical line
        $pdf->SetLineWidth(0.7);
        $pdf->Line(162+$ValueToAdd,$yValueToADd+5.5,162+$ValueToAdd,24.5);

        $pdf->SetXY(162+$ValueToAdd,$yValueToADd+5);
        $pdf->SetFont('Arial','B',20);
        $pdf->Cell(37,6,' CUSTOMS',0,0,'L');
        $pdf->SetXY(162+$ValueToAdd,$yValueToADd+11.5);
        $pdf->Cell(53,6,' DECLARATION',0,0,'L');

        $pdf->SetXY(162+$ValueToAdd,$yValueToADd+18);
        $pdf->SetFont('NanumBarunGothic','',16);
        $pdf->Cell(27,6,' 세관신고서',0,0,'L');

        $pdf->SetXY(192+$ValueToAdd,$yValueToADd+5);
        $pdf->SetFont('Arial','B',35);
        $pdf->Cell(96,12,'CN22 ',0,1,'R');

        // vertical line
        $pdf->Line(162+$ValueToAdd,$yValueToADd+30,162+$ValueToAdd,$yValueToADd+32.5);

        $pdf->SetXY(162+$ValueToAdd,$yValueToADd+29.5);
        $pdf->SetFont('NanumBarunGothic','',10);
        $pdf->Cell(39,4,' May be opened officialy',0,1,'L');
        $pdf->SetXY(162+$ValueToAdd,$yValueToADd+34);
        $pdf->Cell(38,4,' 공개적으로 개봉될 수 있음',0,0,'L');

        // table
        $pdf->SetDrawColor(169,169,169);
        $pdf->SetLineWidth(0.4);
        $pdf->Line(162+$ValueToAdd,$yValueToADd+41,284+$ValueToAdd,$yValueToADd+41); // top
        $pdf->Line(162+$ValueToAdd,$yValueToADd+41,162+$ValueToAdd,$yValueToADd+185); // left
        $pdf->Line(162+$ValueToAdd,$yValueToADd+185,284+$ValueToAdd,$yValueToADd+185); // bottom
        $pdf->Line(284+$ValueToAdd,$yValueToADd+41,284+$ValueToAdd,$yValueToADd+185); // right

        $pdf->Line(162+$ValueToAdd,$yValueToADd+56,284+$ValueToAdd,$yValueToADd+56); // 1st row
        $pdf->Line(236+$ValueToAdd,$yValueToADd+41,236+$ValueToAdd,$yValueToADd+48); // 1st row vertical line
        $pdf->Line(162+$ValueToAdd,$yValueToADd+70,284+$ValueToAdd,$yValueToADd+70); // 2nd row 
        $pdf->Line(162+$ValueToAdd,$yValueToADd+85,284+$ValueToAdd,$yValueToADd+85); // 3rd row

        $pdf->Line(236+$ValueToAdd,$yValueToADd+70,236+$ValueToAdd,$yValueToADd+140); // 3rd row vertical line A
        $pdf->Line(260+$ValueToAdd,$yValueToADd+70,260+$ValueToAdd,$yValueToADd+140); // 3rd row vertical line B

        $pdf->Line(162+$ValueToAdd,$yValueToADd+100,284+$ValueToAdd,$yValueToADd+100); // 4th row
        $pdf->Line(162+$ValueToAdd,$yValueToADd+123,284+$ValueToAdd,$yValueToADd+123); // 5th row
        $pdf->Line(162+$ValueToAdd,$yValueToADd+140,284+$ValueToAdd,$yValueToADd+140); // 6th row
        //$pdf->Line(161,183,283,183); // 7th row

        $pdf->SetDrawColor(0);
        $pdf->SetLineWidth(0.2);

        $pdf->SetXY(163+$ValueToAdd,$yValueToADd+41);
        $pdf->SetFont('Arial','B',12);
        $pdf->Cell(45,7,'Designated operator',0,0,'L');
        $pdf->SetXY(237+$ValueToAdd,$yValueToADd+42.5);
        $pdf->SetFont('Arial','B',10);
        $pdf->MultiCell(27,4,'Important! See instructions on the back',0,'L');

        // table 2nd line
        $pdf->SetDrawColor(169,169,169);
        $pdf->SetTextColor(68,68,68);
        $pdf->SetXY(169+$ValueToAdd,$yValueToADd+58);
        $pdf->SetFont('NanumBarunGothicBold','',10);
        $pdf->Cell(17,5,'Gift(선물)',0,0,'L');
        // checkbox
        $pdf->Line(164+$ValueToAdd,$yValueToADd+57.5,169+$ValueToAdd,$yValueToADd+57.5); // top

        $pdf->SetDrawColor(0);
        $pdf->SetLineWidth(0.8);
        $pdf->Line(164+$ValueToAdd,$yValueToADd+57.5,169+$ValueToAdd,$yValueToADd+62.5); // cross
        $pdf->Line(164+$ValueToAdd,$yValueToADd+62.5,169+$ValueToAdd,$yValueToADd+57.5); // cross
        $pdf->SetLineWidth(0.2);

        $pdf->Line(164+$ValueToAdd,$yValueToADd+62.5,169+$ValueToAdd,$yValueToADd+62.5); // bottom
        $pdf->Line(164+$ValueToAdd,$yValueToADd+57.5,164+$ValueToAdd,$yValueToADd+62.5); // left
        $pdf->Line(169+$ValueToAdd,$yValueToADd+57.5,169+$ValueToAdd,$yValueToADd+62.5); // right

        $pdf->SetXY(169+$ValueToAdd,$yValueToADd+64);
        $pdf->Cell(29,5,'Documents(서류)',0,0,'L');
        // checkbox
        $pdf->Line(164+$ValueToAdd,$yValueToADd+63.5,169+$ValueToAdd,$yValueToADd+63.5); // top
        $pdf->Line(164+$ValueToAdd,$yValueToADd+68.5,169+$ValueToAdd,$yValueToADd+68.5); // bottom
        $pdf->Line(164+$ValueToAdd,$yValueToADd+63.5,164+$ValueToAdd,$yValueToADd+68.5); // left
        $pdf->Line(169+$ValueToAdd,$yValueToADd+63.5,169+$ValueToAdd,$yValueToADd+68.5); // right

        $pdf->SetXY(226+$ValueToAdd,$yValueToADd+58);
        $pdf->Cell(49,5,'Commercial Sample(상업샘플)',0,0,'L');
        // checkbox
        $pdf->Line(221+$ValueToAdd,$yValueToADd+57.5,226+$ValueToAdd,$yValueToADd+57.5); // top
        $pdf->Line(221+$ValueToAdd,$yValueToADd+62.5,226+$ValueToAdd,$yValueToADd+62.5); // bottom
        $pdf->Line(221+$ValueToAdd,$yValueToADd+57.5,221+$ValueToAdd,$yValueToADd+62.5); // left
        $pdf->Line(226+$ValueToAdd,$yValueToADd+57.5,226+$ValueToAdd,$yValueToADd+62.5); // right

        $pdf->SetXY(226+$ValueToAdd,$yValueToADd+64);
        $pdf->Cell(20,5,'Other(기타)',0,0,'L');
        // checkbox
        $pdf->Line(221+$ValueToAdd,$yValueToADd+63.5,226+$ValueToAdd,$yValueToADd+63.5); // top
        $pdf->Line(221+$ValueToAdd,$yValueToADd+68.5,226+$ValueToAdd,$yValueToADd+68.5); // bottom
        $pdf->Line(221+$ValueToAdd,$yValueToADd+63.5,221+$ValueToAdd,$yValueToADd+68.5); // left
        $pdf->Line(226+$ValueToAdd,$yValueToADd+63.5,226+$ValueToAdd,$yValueToADd+68.5); // right

        // table 3rd line
        $pdf->SetXY(163+$ValueToAdd,$yValueToADd+71);
        $pdf->SetFont('NanumBarunGothic','',10);
        $pdf->MultiCell(72,4,'Quantity and detailed description of contents(1)',0,'L');
        $pdf->SetXY(163+$ValueToAdd,$yValueToADd+79);
        $pdf->SetFont('NanumBarunGothic','',9);
        $pdf->Cell(72,4.5,'내용증명, 수량 등 자세한 설명',0,0,'L');

        // text for quantiy
        $pdf->SetFont('NanumBarunGothicBold','',9);
        $pdf->SetXY(164+$ValueToAdd,$yValueToADd+87);
        $pdf->MultiCell(70,11,$order['cnt'].'x '.$order['order_title'],0,'L');

        $pdf->SetXY(238+$ValueToAdd,$yValueToADd+71);
        $pdf->SetFont('NanumBarunGothic','',10);
        $pdf->MultiCell(20,4,'Weight    (in kg)(2)',0,'C');
        $pdf->SetXY(238+$ValueToAdd,$yValueToADd+79.5);
        $pdf->SetFont('NanumBarunGothic','',9);
        $pdf->Cell(20,3,'무게',0,0,'C');

        // text for weight
        $pdf->SetFont('NanumBarunGothicBold','',9);
        $pdf->SetXY(238+$ValueToAdd,$yValueToADd+87);
        $pdf->MultiCell(20,11,$order['item_weight'].' '.$order['item_weight_unit'],0,'C');

        $pdf->SetXY(262+$ValueToAdd,$yValueToADd+71);
        $pdf->SetFont('NanumBarunGothic','',10);
        $pdf->MultiCell(20,4,'Value (3)',0,'C');
        $pdf->SetXY(262+$ValueToAdd,$yValueToADd+75.5);
        $pdf->SetFont('NanumBarunGothic','',9);
        $pdf->Cell(20,3,'가격',0,0,'C');
        // table 4th line 
        
        // text for price
        $pdf->SetFont('NanumBarunGothicBold','',9);
        $pdf->SetXY(262+$ValueToAdd,$yValueToADd+87);
        $pdf->MultiCell(20,11,$order['item_price'].' '.$order['item_price_currency'],0,'C');

        // table 5th line
        $pdf->SetXY(163+$ValueToAdd,$yValueToADd+101);
        $pdf->SetFont('NanumBarunGothic','',10);
        $pdf->MultiCell(72,4,'For commercial items only if know, HS tarif number(4) and country of origin of goods(5)',0,'L');
        $pdf->SetXY(163+$ValueToAdd,$yValueToADd+113);
        $pdf->SetFont('NanumBarunGothic','',9);
        $pdf->MultiCell(72,4,'상업물품인 경우 원산지 및 HS코드(상품분류번호) 기입 ',0,'L');

        $pdf->SetXY(238+$ValueToAdd,$yValueToADd+101);
        $pdf->SetFont('NanumBarunGothic','',10);
        $pdf->MultiCell(20,4,'Total Weight (in kg)(6)',0,'C');
        $pdf->SetXY(238+$ValueToAdd,$yValueToADd+113.5);
        $pdf->SetFont('NanumBarunGothic','',9);
        $pdf->Cell(20,3,'총무게',0,0,'C');

        // text for total weight
        $pdf->SetFont('NanumBarunGothicBold','',9);
        $pdf->SetXY(238+$ValueToAdd,$yValueToADd+125);
        $pdf->MultiCell(20,13,number_format(($order['cnt'] * $order['item_weight']),2).' '.$order['item_weight_unit'],0,'C');

        $pdf->SetXY(262+$ValueToAdd,$yValueToADd+101);
        $pdf->SetFont('NanumBarunGothic','',10);
        $pdf->MultiCell(20,4,'Total Value (7)',0,'C');
        $pdf->SetXY(262+$ValueToAdd,$yValueToADd+109.5);
        $pdf->SetFont('NanumBarunGothic','',9);
        $pdf->Cell(20,3,'총가격',0,0,'C');

        // text for total price
        $pdf->SetFont('NanumBarunGothicBold','',9);
        $pdf->SetXY(262+$ValueToAdd,$yValueToADd+125);
        $pdf->MultiCell(20,13,number_format(($order['cnt'] * $order['item_price']),2).' '.$order['item_price_currency'],0,'C');

        // table 6th line
        $pdf->SetXY(163+$ValueToAdd,$yValueToADd+141);
        $pdf->SetFont('NanumBarunGothic','',11);
        $pdf->MultiCell(120,4.2,'I. the undersinged, whose name and address are given on the item, ceritify that the particulars given in this declaration are correct and that this item dose not contain any dangerous article or articles prohibited by legislation or by postal or customs regulations',0,'L');
        $pdf->SetXY(163+$ValueToAdd,$yValueToADd+164);
        $pdf->MultiCell(120,4.5,'신고 서에 시고한 물품이 정확하며, 법류, 유편 및 관세법에 규정된 금지물품이나 위험물품을 포함하지 않음을 증명합니다',0,'L');
        $pdf->SetXY(163+$ValueToAdd,$yValueToADd+176.5);
        $pdf->SetTextColor(0);
        $pdf->SetFont('Arial','B',17);
        $pdf->MultiCell(120,5,'Date and senders signature(8)',0,'L');

        //generate
        // $pdfname = 'cn22-'.$options['uid'].'-'.$options['dimension'].'-'.$options['order_ids'].'.pdf';
        // $pdf->Output($_SERVER['DOCUMENT_ROOT'].'/onlabels/assets2/'.$pdfname,'F');
    }

    function createCustomsDeclaration2Form($xToAdd = 0, $yToAdd = 0, $pdf, $order)
    {
        if ($order['id']==0) return;

        $xToAdd=-107;
        $yToAdd=-8;

        $pdf->SetDrawColor(0);
        $pdf->SetLineWidth(0.2);
        $pdf->SetFillColor(255);
        $pdf->RoundedRect(107+$xToAdd, 8+$yToAdd, 95, 135, 0, 'DF');
        // vertical line
        $pdf->SetLineWidth(0.5);
        $pdf->Line(111+$xToAdd,12+$yToAdd,111+$xToAdd,20+$yToAdd);
        $pdf->Line(111+$xToAdd,29+$yToAdd,111+$xToAdd,31+$yToAdd);
        $pdf->SetLineWidth(0.2);
        $pdf->SetXY(111.5+$xToAdd,12+$yToAdd);
        $pdf->SetFont('Arial','B',14);
        $pdf->Cell(26,4,'CUSTOMS',0,0,'L');
        $pdf->SetXY(111.5+$xToAdd,16.5+$yToAdd);
        $pdf->Cell(37,4,'DECLARATION',0,0,'L');
        $pdf->SetXY(111+$xToAdd,21+$yToAdd);
        $pdf->SetFont('NanumBarunGothic','',11);
        $pdf->Cell(20,4,'세관신고서',0,0,'L');
        $pdf->SetXY(111+$xToAdd,12+$yToAdd);
        $pdf->SetFont('Arial','B',25);
        $pdf->Cell(88,7,'CN22',0,1,'R');
        $pdf->SetXY(111+$xToAdd,28.5+$yToAdd);
        $pdf->SetFont('NanumBarunGothic','',8);
        $pdf->Cell(32,3,'May be opened officialy',0,1,'L');
        $pdf->SetXY(111+$xToAdd,32+$yToAdd);
        $pdf->Cell(31,3,'공개적으로 개봉될 수 있음',0,0,'L');
        // table
        $pdf->SetDrawColor(169,169,169);
        $pdf->SetLineWidth(0.4);
        $pdf->Line(111+$xToAdd,37+$yToAdd,198+$xToAdd,37+$yToAdd); // top
        $pdf->Line(111+$xToAdd,37+$yToAdd,111+$xToAdd,140+$yToAdd); // left
        $pdf->Line(111+$xToAdd,140+$yToAdd,198+$xToAdd,140+$yToAdd); // bottom
        $pdf->Line(198+$xToAdd,37+$yToAdd,198+$xToAdd,140+$yToAdd); // right
        $pdf->Line(111+$xToAdd,49+$yToAdd,198+$xToAdd,49+$yToAdd); // 1st row
        $pdf->Line(164+$xToAdd,37+$yToAdd,164+$xToAdd,43+$yToAdd); // 1st row vertical line
        $pdf->Line(111+$xToAdd,59+$yToAdd,198+$xToAdd,59+$yToAdd); // 2nd row 
        $pdf->Line(111+$xToAdd,70+$yToAdd,198+$xToAdd,70+$yToAdd); // 3rd row
        $pdf->Line(164+$xToAdd,59+$yToAdd,164+$xToAdd,114+$yToAdd); // 3rd row vertical line A
        $pdf->Line(181+$xToAdd,59+$yToAdd,181+$xToAdd,114+$yToAdd); // 3rd row vertical line B
        $pdf->Line(111+$xToAdd,85+$yToAdd,198+$xToAdd,85+$yToAdd); // 4th row
        $pdf->Line(111+$xToAdd,102+$yToAdd,198+$xToAdd,102+$yToAdd); // 5th row
        $pdf->Line(111+$xToAdd,114+$yToAdd,198+$xToAdd,114+$yToAdd); // 6th row
        $pdf->SetDrawColor(0);
        $pdf->SetLineWidth(0.2);
        $pdf->SetXY(111+$xToAdd,38+$yToAdd);
        $pdf->SetFont('Arial','B',9);
        $pdf->Cell(33,3,'Designated operator',0,0,'L');
        $pdf->SetXY(164.5+$xToAdd,38+$yToAdd);
        $pdf->SetFont('Arial','B',8);
        $pdf->MultiCell(27,3,'Important! See instructions on the back',0,'L');
        // table 2nd line
        $pdf->SetDrawColor(169,169,169);
        $pdf->SetTextColor(68,68,68);
        $pdf->SetXY(116+$xToAdd,50.5+$yToAdd);
        $pdf->SetFont('NanumBarunGothicBold','',8);
        $pdf->Cell(14,3,'Gift(선물)',0,0,'L');
        // // checkbox
        $pdf->Line(113+$xToAdd,50.5+$yToAdd,116+$xToAdd,50.5+$yToAdd); // top

        $pdf->SetDrawColor(0);
        $pdf->SetLineWidth(0.6);
        $pdf->Line(113+$xToAdd,50.5+$yToAdd,116+$xToAdd,53.5+$yToAdd); // cross
        $pdf->Line(113+$xToAdd,53.5+$yToAdd,116+$xToAdd,50.5+$yToAdd);; // cross
        $pdf->SetLineWidth(0.2);

        $pdf->Line(113+$xToAdd,53.5+$yToAdd,116+$xToAdd,53.5+$yToAdd); // bottom
        $pdf->Line(113+$xToAdd,50.5+$yToAdd,113+$xToAdd,53.5+$yToAdd); // left
        $pdf->Line(116+$xToAdd,50.5+$yToAdd,116+$xToAdd,53.5+$yToAdd); // right
        $pdf->SetXY(116+$xToAdd,54.5+$yToAdd);
        $pdf->Cell(24,3,'Documents(서류)',0,0,'L');
        // // checkbox
        $pdf->Line(113+$xToAdd,54.5+$yToAdd,116+$xToAdd,54.5+$yToAdd); // top
        $pdf->Line(113+$xToAdd,57.5+$yToAdd,116+$xToAdd,57.5+$yToAdd); // bottom
        $pdf->Line(113+$xToAdd,54.5+$yToAdd,113+$xToAdd,57.5+$yToAdd); // left
        $pdf->Line(116+$xToAdd,54.5+$yToAdd,116+$xToAdd,57.5+$yToAdd); // right
        $pdf->SetXY(152+$xToAdd,50.5+$yToAdd);
        $pdf->Cell(40,3,'Commercial Sample(상업샘플)',0,0,'L');
        // // checkbox
        $pdf->Line(149+$xToAdd,50.5+$yToAdd,152+$xToAdd,50.5+$yToAdd); // top
        $pdf->Line(149+$xToAdd,53.5+$yToAdd,152+$xToAdd,53.5+$yToAdd); // bottom
        $pdf->Line(149+$xToAdd,50.5+$yToAdd,149+$xToAdd,53.5+$yToAdd); // left
        $pdf->Line(152+$xToAdd,50.5+$yToAdd,152+$xToAdd,53.5+$yToAdd); // right
        $pdf->SetXY(152+$xToAdd,54.5+$yToAdd);
        $pdf->Cell(17,3,'Other(기타)',0,0,'L');
        // // checkbox
        $pdf->Line(149+$xToAdd,54.5+$yToAdd,152+$xToAdd,54.5+$yToAdd); // top
        $pdf->Line(149+$xToAdd,57.5+$yToAdd,152+$xToAdd,57.5+$yToAdd); // bottom
        $pdf->Line(149+$xToAdd,54.5+$yToAdd,149+$xToAdd,57.5+$yToAdd); // left
        $pdf->Line(152+$xToAdd,54.5+$yToAdd,152+$xToAdd,57.5+$yToAdd); // right
        // table 3rd line
        $pdf->SetXY(111.5+$xToAdd,60+$yToAdd);
        $pdf->SetFont('NanumBarunGothic','',8);
        $pdf->MultiCell(53,3,'Quantity and detailed description
    of contents(1)',0,'L');

        $pdf->SetXY(113+$xToAdd,74+$yToAdd);
        $pdf->SetFont('NanumBarunGothicBold','',7);
        $pdf->MultiCell(49,4,$order['cnt'].'x '.$order['order_title'],0,'L');

        $pdf->SetXY(111.5+$xToAdd,66.3+$yToAdd);
        $pdf->SetFont('NanumBarunGothic','',7);
        $pdf->Cell(53,3,'내용증명, 수량 등 자세한 설명',0,0,'L');
        $pdf->SetXY(164+$xToAdd,60+$yToAdd);
        $pdf->SetFont('NanumBarunGothic','',8);
        $pdf->MultiCell(17,3,'Weight    (in kg)(2)',0,'C');

        $pdf->SetXY(165+$xToAdd,74+$yToAdd);
        $pdf->SetFont('NanumBarunGothicBold','',7);
        $pdf->MultiCell(15,3,$order['item_weight'].' '.$order['item_weight_unit'],0,'C');

        $pdf->SetXY(164+$xToAdd,66.3+$yToAdd);
        $pdf->SetFont('NanumBarunGothic','',7);
        $pdf->Cell(17,3,'무게',0,0,'C');
        $pdf->SetXY(181+$xToAdd,60+$yToAdd);
        $pdf->SetFont('NanumBarunGothic','',8);
        $pdf->MultiCell(17,3,'Value (3)',0,'C');
        $pdf->SetXY(181+$xToAdd,63.3+$yToAdd);
        $pdf->SetFont('NanumBarunGothic','',7);
        $pdf->Cell(17,3,'가격',0,0,'C');

        $pdf->SetXY(182+$xToAdd,74+$yToAdd);
        $pdf->SetFont('NanumBarunGothicBold','',7);
        $pdf->MultiCell(15,3,$order['item_price'].' '.$order['item_price_currency'],0,'C');

        // table 5th line
        $pdf->SetXY(111.5+$xToAdd,86+$yToAdd);
        $pdf->SetFont('NanumBarunGothic','',8);
        $pdf->MultiCell(53,3,'For commercial items only if know,
    HS tarif number(4)
    and country of origin of goods(5)',0,'L');
        $pdf->SetXY(111.5+$xToAdd,95.3+$yToAdd);
        $pdf->SetFont('NanumBarunGothic','',7);
        $pdf->MultiCell(53,2.8,'상업물품인 경우 원산지 및 
    HS코드(상품분류번호) 기입 ',0,'L');
        $pdf->SetXY(164+$xToAdd,86+$yToAdd);
        $pdf->SetFont('NanumBarunGothic','',8);
        $pdf->MultiCell(17,3,'Total Weight (in kg)(6)',0,'C');
        $pdf->SetXY(164+$xToAdd,95.3+$yToAdd);
        $pdf->SetFont('NanumBarunGothic','',7);
        $pdf->Cell(17,3,'총무게',0,0,'C');

        $pdf->SetXY(165+$xToAdd,105.5+$yToAdd);
        $pdf->SetFont('NanumBarunGothicBold','',7);
        $pdf->MultiCell(15,3,number_format(($order['cnt'] * $order['item_weight']),2).' '.$order['item_weight_unit'],0,'C');

        $pdf->SetXY(181+$xToAdd,86+$yToAdd);
        $pdf->SetFont('NanumBarunGothic','',8);
        $pdf->MultiCell(17,3,'Total Value (7)',0,'C');
        $pdf->SetXY(181+$xToAdd,92.3+$yToAdd);
        $pdf->SetFont('NanumBarunGothic','',7);
        $pdf->Cell(17,3,'총가격',0,0,'C');

        $pdf->SetXY(182+$xToAdd,105.5+$yToAdd);
        $pdf->SetFont('NanumBarunGothicBold','',7);
        $pdf->MultiCell(15,3,number_format(($order['cnt'] * $order['item_price']),2).' '.$order['item_price_currency'],0,'C');

        // table 6th line
        $pdf->SetXY(111.5+$xToAdd,115+$yToAdd);
        $pdf->SetFont('NanumBarunGothic','',7);
        $pdf->MultiCell(87,3,'I. the undersinged, whose name and address are given on the item, ceritify that the particulars given in this declaration are correct and that this item dose not contain any dangerous article or articles prohibited by legislation or by postal or customs regulations',0,'L');
        $pdf->SetXY(111.5+$xToAdd,127.5+$yToAdd);
        $pdf->MultiCell(86,3,'신고 서에 시고한 물품이 정확하며, 법류, 유편 및 관세법에 규정된 금지물품이나위험물품을 포함하지 않음을 증명합니다',0,'L');
        $pdf->SetXY(111.5+$xToAdd,134+$yToAdd);
        $pdf->SetTextColor(0);
        $pdf->SetFont('Arial','B',9);
        $pdf->MultiCell(87,5,'Date and senders signature(8)',0,'L');
    }

    function createCustomsDeclaration3Form($xToAdd = 0, $yToAdd = 0, $pdf, $order)
    {
        if ($order['id']==0) return;

        $xToAdd=-72;
        $yToAdd=-2;

        $pdf->SetDrawColor(0);
        $pdf->SetLineWidth(0.1);
        $pdf->SetFillColor(255);
        $pdf->RoundedRect(72+$xToAdd, 2+$yToAdd, 66, 96, 0, 'DF');
        // vertical line
        $pdf->SetLineWidth(0.2);
        $pdf->Line(74+$xToAdd,5+$yToAdd,74+$xToAdd,10.5+$yToAdd);
        $pdf->Line(74+$xToAdd,16+$yToAdd,74+$xToAdd,17.5+$yToAdd);
        $pdf->SetLineWidth(0.2);
        $pdf->SetXY(74+$xToAdd,5+$yToAdd);
        $pdf->SetFont('Arial','B',10);
        $pdf->Cell(20,2.5,'CUSTOMS',0,0,'L');
        $pdf->SetXY(74+$xToAdd,8+$yToAdd);
        $pdf->Cell(28,2.5,'DECLARATION',0,0,'L');
        $pdf->SetXY(74+$xToAdd,11+$yToAdd);
        $pdf->SetFont('NanumBarunGothic','',8);
        $pdf->Cell(20,3,'세관신고서',0,0,'L');
        $pdf->SetXY(74+$xToAdd,5+$yToAdd);
        $pdf->SetFont('Arial','B',18);
        $pdf->Cell(62,6,'CN22',0,1,'R');
        $pdf->SetXY(74+$xToAdd,15.5+$yToAdd);
        $pdf->SetFont('NanumBarunGothic','',6);
        $pdf->Cell(25,2.5,'May be opened officialy',0,1,'L');
        $pdf->SetXY(74+$xToAdd,18.1+$yToAdd);
        $pdf->Cell(24,2.5,'공개적으로 개봉될 수 있음',0,0,'L');
        // table
        $pdf->SetDrawColor(169,169,169);
        $pdf->SetLineWidth(0.2);
        $pdf->Line(74+$xToAdd,22+$yToAdd,136+$xToAdd,22+$yToAdd); // top
        $pdf->Line(74+$xToAdd,22+$yToAdd,74+$xToAdd,95+$yToAdd); // left
        $pdf->Line(74+$xToAdd,95+$yToAdd,136+$xToAdd,95+$yToAdd); // bottom
        $pdf->Line(136+$xToAdd,22+$yToAdd,136+$xToAdd,95+$yToAdd); // right

        $pdf->Line(74+$xToAdd,30+$yToAdd,136+$xToAdd,30+$yToAdd); // 1st row
        $pdf->Line(112+$xToAdd,22+$yToAdd,112+$xToAdd,25+$yToAdd); // 1st row vertical line
        $pdf->Line(74+$xToAdd,37+$yToAdd,136+$xToAdd,37+$yToAdd); // 2nd row 
        $pdf->Line(74+$xToAdd,45+$yToAdd,136+$xToAdd,45+$yToAdd); // 3rd row
        $pdf->Line(112+$xToAdd,37+$yToAdd,112+$xToAdd,77+$yToAdd); // 3rd row vertical line A
        $pdf->Line(124+$xToAdd,37+$yToAdd,124+$xToAdd,77+$yToAdd); // 3rd row vertical line B
        $pdf->Line(74+$xToAdd,56+$yToAdd,136+$xToAdd,56+$yToAdd); // 4th row
        $pdf->Line(74+$xToAdd,68+$yToAdd,136+$xToAdd,68+$yToAdd); // 5th row
        $pdf->Line(74+$xToAdd,77+$yToAdd,136+$xToAdd,77+$yToAdd); // 6th row
        $pdf->SetDrawColor(0);
        $pdf->SetLineWidth(0.2);
        $pdf->SetXY(74+$xToAdd,23+$yToAdd);
        $pdf->SetFont('Arial','B',7);
        $pdf->Cell(26,2.5,'Designated operator',0,0,'L');
        $pdf->SetXY(112+$xToAdd,23+$yToAdd);
        $pdf->SetFont('Arial','B',6);
        $pdf->MultiCell(22,2,'Important! See instructions on the back',0,'L');
    //  // table 2nd line
        $pdf->SetDrawColor(169,169,169);
        $pdf->SetTextColor(68,68,68);
        $pdf->SetXY(77+$xToAdd,31+$yToAdd);
        $pdf->SetFont('NanumBarunGothicBold','',6);
        $pdf->Cell(11,2,'Gift(선물)',0,0,'L');
    //  // // checkbox
        $pdf->Line(75+$xToAdd,31+$yToAdd,77+$xToAdd,31+$yToAdd); // top

        $pdf->SetDrawColor(0);
        $pdf->SetLineWidth(0.4);
        $pdf->Line(75+$xToAdd,31+$yToAdd,77+$xToAdd,33+$yToAdd); // cross
        $pdf->Line(75+$xToAdd,33+$yToAdd,77+$xToAdd,31+$yToAdd); // cross
        $pdf->SetLineWidth(0.2);

        $pdf->Line(75+$xToAdd,33+$yToAdd,77+$xToAdd,33+$yToAdd); // bottom
        $pdf->Line(75+$xToAdd,31+$yToAdd,75+$xToAdd,33+$yToAdd); // left
        $pdf->Line(77+$xToAdd,31+$yToAdd,77+$xToAdd,33+$yToAdd); // right
        $pdf->SetXY(77+$xToAdd,34+$yToAdd);
        $pdf->Cell(19,2,'Documents(서류)',0,0,'L');
    //  // // checkbox
        $pdf->Line(75+$xToAdd,34+$yToAdd,77+$xToAdd,34+$yToAdd); // top
        $pdf->Line(75+$xToAdd,36+$yToAdd,77+$xToAdd,36+$yToAdd); // bottom
        $pdf->Line(75+$xToAdd,34+$yToAdd,75+$xToAdd,36+$yToAdd); // left
        $pdf->Line(77+$xToAdd,34+$yToAdd,77+$xToAdd,36+$yToAdd); // right
        $pdf->SetXY(104+$xToAdd,31+$yToAdd);
        $pdf->Cell(31,2,'Commercial Sample(상업샘플)',0,0,'L');
    //  // // checkbox
        $pdf->Line(102+$xToAdd,31+$yToAdd,104+$xToAdd,31+$yToAdd); // top
        $pdf->Line(102+$xToAdd,33+$yToAdd,104+$xToAdd,33+$yToAdd); // bottom
        $pdf->Line(102+$xToAdd,31+$yToAdd,102+$xToAdd,33+$yToAdd); // left
        $pdf->Line(104+$xToAdd,31+$yToAdd,104+$xToAdd,33+$yToAdd); // right
        $pdf->SetXY(104+$xToAdd,34+$yToAdd);
        $pdf->Cell(13,2,'Other(기타)',0,0,'L');
        // // checkbox
        $pdf->Line(102+$xToAdd,34+$yToAdd,104+$xToAdd,34+$yToAdd); // top
        $pdf->Line(102+$xToAdd,36+$yToAdd,104+$xToAdd,36+$yToAdd); // bottom
        $pdf->Line(102+$xToAdd,34+$yToAdd,102+$xToAdd,36+$yToAdd); // left
        $pdf->Line(104+$xToAdd,34+$yToAdd,104+$xToAdd,36+$yToAdd); // right
    //  // table 3rd line
        $pdf->SetXY(74+$xToAdd,38+$yToAdd);
        $pdf->SetFont('NanumBarunGothic','',6);
        $pdf->MultiCell(38,2.1,'Quantity and detailed description
    of contents(1)',0,'L');
        $pdf->SetXY(74+$xToAdd,42.5+$yToAdd);
        $pdf->SetFont('NanumBarunGothic','',5);
        $pdf->Cell(38,2,'내용증명, 수량 등 자세한 설명',0,0,'L');

        $pdf->SetXY(75+$xToAdd,48+$yToAdd);
        $pdf->SetFont('NanumBarunGothicBold','',5);
        $pdf->MultiCell(36,2.1,$order['cnt'].'x '.$order['order_title'],0,'L');

        $pdf->SetXY(112+$xToAdd,38+$yToAdd);
        $pdf->SetFont('NanumBarunGothic','',6);
        $pdf->MultiCell(12,2.1,'Weight    (in kg)(2)',0,'C');
        $pdf->SetXY(112+$xToAdd,42.5+$yToAdd);
        $pdf->SetFont('NanumBarunGothic','',5);
        $pdf->Cell(12,2.1,'무게',0,0,'C');

        $pdf->SetXY(113+$xToAdd,48+$yToAdd);
        $pdf->SetFont('NanumBarunGothicBold','',5);
        $pdf->MultiCell(10,2.1,$order['item_weight'].' '.$order['item_weight_unit'],0,'C');

        $pdf->SetXY(124+$xToAdd,38+$yToAdd);
        $pdf->SetFont('NanumBarunGothic','',6);
        $pdf->MultiCell(12,2.1,'Value (3)',0,'C');
        $pdf->SetXY(124+$xToAdd,40.4+$yToAdd);
        $pdf->SetFont('NanumBarunGothic','',5);
        $pdf->Cell(12,2.1,'가격',0,0,'C');

        $pdf->SetXY(125+$xToAdd,48+$yToAdd);
        $pdf->SetFont('NanumBarunGothicBold','',5);
        $pdf->MultiCell(10,2.1,$order['item_price'].' '.$order['item_price_currency'],0,'C');

    //  // table 5th line
        $pdf->SetXY(74+$xToAdd,57+$yToAdd);
        $pdf->SetFont('NanumBarunGothic','',6);
        $pdf->MultiCell(38,2.1,'For commercial items only if know,
    HS tarif number(4)
    and country of origin of goods(5)',0,'L');
        $pdf->SetXY(74+$xToAdd,63.6+$yToAdd);
        $pdf->SetFont('NanumBarunGothic','',5);
        $pdf->MultiCell(38,2.1,'상업물품인 경우 원산지 및 
    HS코드(상품분류번호) 기입 ',0,'L');
        $pdf->SetXY(112+$xToAdd,57+$yToAdd);
        $pdf->SetFont('NanumBarunGothic','',6);
        $pdf->MultiCell(12,2.1,'Total Weight (in kg)(6)',0,'C');

        $pdf->SetXY(113+$xToAdd,70.5+$yToAdd);
        $pdf->SetFont('NanumBarunGothicBold','',5);
        $pdf->MultiCell(10,2.1,number_format(($order['cnt'] * $order['item_weight']),2).' '.$order['item_weight_unit'],0,'C');

        $pdf->SetXY(112+$xToAdd,63.6+$yToAdd);
        $pdf->SetFont('NanumBarunGothic','',5);
        $pdf->Cell(12,2.1,'총무게',0,0,'C');
        $pdf->SetXY(124+$xToAdd,57+$yToAdd);
        $pdf->SetFont('NanumBarunGothic','',6);
        $pdf->MultiCell(12,2.1,'Total Value (7)',0,'C');
        $pdf->SetXY(124+$xToAdd,61.5+$yToAdd);
        $pdf->SetFont('NanumBarunGothic','',5);
        $pdf->Cell(12,2.1,'총가격',0,0,'C');

        $pdf->SetXY(125+$xToAdd,70.5+$yToAdd);
        $pdf->SetFont('NanumBarunGothicBold','',5);
        $pdf->MultiCell(10,2.1,number_format(($order['cnt'] * $order['item_price']),2).' '.$order['item_price_currency'],0,'C');

    //  // table 6th line
        $pdf->SetXY(74+$xToAdd,77.5+$yToAdd);
        $pdf->SetFont('NanumBarunGothic','',5.4);
        $pdf->MultiCell(61,2,'I. the undersinged, whose name and address are given on the item, ceritify that the particulars given in this declaration are correct and that this item dose not contain any dangerous article or articles prohibited by legislation or by postal or customs regulations',0,'L');
        $pdf->SetXY(74+$xToAdd,86.2+$yToAdd);
        $pdf->SetFont('NanumBarunGothic','',4.8);
        $pdf->MultiCell(61,2,'신고 서에 시고한 물품이 정확하며, 법류, 유편 및 관세법에 규정된 금지물품이나위험물품을 포함하지 않음을 증명합니다',0,'L');
        $pdf->SetXY(74+$xToAdd,91.5+$yToAdd);
        $pdf->SetTextColor(0);
        $pdf->SetFont('Arial','B',7);
        $pdf->MultiCell(61,2,'Date and senders signature(8)',0,'L');
    }

    function createBoxForm($xToAdd = 0, $yToAdd = 0, $sizex, $sizey, $pdf, $order, $count=0, $options=array())
    {   

        // print_r($order);
        if (empty($order[0][0])) return;

        // echo $count . '<br>';
        $yleft = 6; $yright = 1; $yline = 5.4;
        if ($sizey == 34) { $yleft = 5; $yright = 0.1; $yline = 4; }

        for ($i=1;$i<count($order);$i++) :

            //echo $yToAdd.'<br>';

            //if (isset($merge_orders[$i]['id'])) 
            // echo $count. '<br>';

            if ($yToAdd == 10 && $count>0) :
                if ($count%21==0) :
                    $pdf->AddPage();
                endif;
            elseif ($yToAdd == 6 && $count>0) :
                if ($count%24==0) :
                    $pdf->AddPage();
                endif;
            endif;

            if ($i==1) :
                if ($options['from']<1 && $order[0] == 'FROM' ||
                    $options['to']<1 && $order[0] == 'TO') :

                else :
                    $pdf->SetDrawColor(0);
                    $pdf->SetLineWidth(0.1);
                    $pdf->SetFillColor(255);
                    $pdf->RoundedRect(2+$xToAdd, 2+$yToAdd, $sizex, $sizey, 1, 'F');

                    $pdf->SetXY(3+$xToAdd,$yleft+$yToAdd);
                    $pdf->SetFont('NanumBarunGothicBold','',12);
                    $pdf->Cell(18,5,$order[0],0,1,'L');
                endif;
            endif;

            $yplus = 6;
            $sizey = 3;
            $fontsize = 8.5;
            if ($i==2)
            {
                $cnt_char = strlen($order[$i]);
                if ($cnt_char>25)
                {
                    $sizey = 2.2;
                    $yplus = 4.9;
                    $fontsize = 6.8;
                }
            }

            if ($options['from']<1 && $order[0] == 'FROM' || 
                $options['to']<1 && $order[0] == 'TO') :

            else:
                $pdf->SetXY(21+$xToAdd,$yright+$yToAdd+=$yplus);
                $pdf->SetFont('NanumBarunGothic','',$fontsize);
                $pdf->MultiCell(46,$sizey,$order[$i],0,'L');
                //$pdf->SetDash(1.8,2.5);
                //$pdf->Line(4+$xToAdd, $yline+$yToAdd, 66+$xToAdd, $yline+$yToAdd);
                //$pdf->SetDash();
            endif;
        endfor;
    }

    function RoundedRect($x, $y, $w, $h, $r, $style = '')
    {
        $k = $this->k;
        $hp = $this->h;
        if($style=='F')
            $op='f';
        elseif($style=='FD' || $style=='DF')
            $op='B';
        else
            $op='S';
        $MyArc = 4/3 * (sqrt(2) - 1);
        $this->_out(sprintf('%.2F %.2F m',($x+$r)*$k,($hp-$y)*$k ));
        $xc = $x+$w-$r ;
        $yc = $y+$r;
        $this->_out(sprintf('%.2F %.2F l', $xc*$k,($hp-$y)*$k ));

        $this->_Arc($xc + $r*$MyArc, $yc - $r, $xc + $r, $yc - $r*$MyArc, $xc + $r, $yc);
        $xc = $x+$w-$r ;
        $yc = $y+$h-$r;
        $this->_out(sprintf('%.2F %.2F l',($x+$w)*$k,($hp-$yc)*$k));
        $this->_Arc($xc + $r, $yc + $r*$MyArc, $xc + $r*$MyArc, $yc + $r, $xc, $yc + $r);
        $xc = $x+$r ;
        $yc = $y+$h-$r;
        $this->_out(sprintf('%.2F %.2F l',$xc*$k,($hp-($y+$h))*$k));
        $this->_Arc($xc - $r*$MyArc, $yc + $r, $xc - $r, $yc + $r*$MyArc, $xc - $r, $yc);
        $xc = $x+$r ;
        $yc = $y+$r;
        $this->_out(sprintf('%.2F %.2F l',($x)*$k,($hp-$yc)*$k ));
        $this->_Arc($xc - $r, $yc - $r*$MyArc, $xc - $r*$MyArc, $yc - $r, $xc, $yc - $r);
        $this->_out($op);
    }

    function _Arc($x1, $y1, $x2, $y2, $x3, $y3)
    {
        $h = $this->h;
        $this->_out(sprintf('%.2F %.2F %.2F %.2F %.2F %.2F c ', $x1*$this->k, ($h-$y1)*$this->k,
            $x2*$this->k, ($h-$y2)*$this->k, $x3*$this->k, ($h-$y3)*$this->k));
    }

    function SetDash($black=null, $white=null)
    {
        if($black!==null)
            $s=sprintf('[%.3F %.3F] 0 d',$black*$this->k,$white*$this->k);
        else
            $s='[] 0 d';
        $this->_out($s);
    }

    var $wLine; // Maximum width of the line
    var $hLine; // Height of the line
    var $Text; // Text to display
    var $border;
    var $align; // Justification of the text
    var $fill;
    var $Padding;
    var $lPadding;
    var $tPadding;
    var $bPadding;
    var $rPadding;
    var $TagStyle; // Style for each tag
    var $Indent;
    var $Space; // Minimum space between words
    var $PileStyle; 
    var $Line2Print; // Line to display
    var $NextLineBegin; // Buffer between lines 
    var $TagName;
    var $Delta; // Maximum width minus width
    var $StringLength; 
    var $LineLength;
    var $wTextLine; // Width minus paddings
    var $nbSpace; // Number of spaces in the line
    var $Xini; // Initial position
    var $href; // Current URL
    var $TagHref; // URL for a cell

    // Public Functions

    function WriteTag($w, $h, $txt, $border=0, $align="J", $fill=false, $padding=0)
    {
        $this->wLine=$w;
        $this->hLine=$h;
        $this->Text=trim($txt);
        $this->Text=preg_replace("/\n|\r|\t/","",$this->Text);
        $this->border=$border;
        $this->align=$align;
        $this->fill=$fill;
        $this->Padding=$padding;

        $this->Xini=$this->GetX();
        $this->href="";
        $this->PileStyle=array();        
        $this->TagHref=array();
        $this->LastLine=false;

        $this->SetSpace();
        $this->Padding();
        $this->LineLength();
        $this->BorderTop();

        while($this->Text!="")
        {
            $this->MakeLine();
            $this->PrintLine();
        }

        $this->BorderBottom();
    }


    function SetStyle($tag, $family, $style, $size, $color, $indent=-1)
    {
         $tag=trim($tag);
         $this->TagStyle[$tag]['family']=trim($family);
         $this->TagStyle[$tag]['style']=trim($style);
         $this->TagStyle[$tag]['size']=trim($size);
         $this->TagStyle[$tag]['color']=trim($color);
         $this->TagStyle[$tag]['indent']=$indent;
    }


    // Private Functions

    function SetSpace() // Minimal space between words
    {
        $tag=$this->Parser($this->Text);
        $this->FindStyle($tag[2],0);
        $this->DoStyle(0);
        $this->Space=$this->GetStringWidth(" ");
    }


    function Padding()
    {
        if(preg_match("/^.+,/",$this->Padding)) {
            $tab=explode(",",$this->Padding);
            $this->lPadding=$tab[0];
            $this->tPadding=$tab[1];
            if(isset($tab[2]))
                $this->bPadding=$tab[2];
            else
                $this->bPadding=$this->tPadding;
            if(isset($tab[3]))
                $this->rPadding=$tab[3];
            else
                $this->rPadding=$this->lPadding;
        }
        else
        {
            $this->lPadding=$this->Padding;
            $this->tPadding=$this->Padding;
            $this->bPadding=$this->Padding;
            $this->rPadding=$this->Padding;
        }
        if($this->tPadding<$this->LineWidth)
            $this->tPadding=$this->LineWidth;
    }


    function LineLength()
    {
        if($this->wLine==0)
            $this->wLine=$this->w - $this->Xini - $this->rMargin;

        $this->wTextLine = $this->wLine - $this->lPadding - $this->rPadding;
    }


    function BorderTop()
    {
        $border=0;
        if($this->border==1)
            $border="TLR";
        $this->Cell($this->wLine,$this->tPadding,"",$border,0,'C',$this->fill);
        $y=$this->GetY()+$this->tPadding;
        $this->SetXY($this->Xini,$y);
    }


    function BorderBottom()
    {
        $border=0;
        if($this->border==1)
            $border="BLR";
        $this->Cell($this->wLine,$this->bPadding,"",$border,0,'C',$this->fill);
    }


    function DoStyle($tag) // Applies a style
    {
        $tag=trim($tag);
        $this->SetFont($this->TagStyle[$tag]['family'],
            $this->TagStyle[$tag]['style'],
            $this->TagStyle[$tag]['size']);

        $tab=explode(",",$this->TagStyle[$tag]['color']);
        if(count($tab)==1)
            $this->SetTextColor($tab[0]);
        else
            $this->SetTextColor($tab[0],$tab[1],$tab[2]);
    }


    function FindStyle($tag, $ind) // Inheritance from parent elements
    {
        $tag=trim($tag);

        // Family
        if($this->TagStyle[$tag]['family']!="")
            $family=$this->TagStyle[$tag]['family'];
        else
        {
            reset($this->PileStyle);
            while(list($k,$val)=each($this->PileStyle))
            {
                $val=trim($val);
                if($this->TagStyle[$val]['family']!="") {
                    $family=$this->TagStyle[$val]['family'];
                    break;
                }
            }
        }

        // Style
        $style="";
        $style1=strtoupper($this->TagStyle[$tag]['style']);
        if($style1!="N")
        {
            $bold=false;
            $italic=false;
            $underline=false;
            reset($this->PileStyle);
            while(list($k,$val)=each($this->PileStyle))
            {
                $val=trim($val);
                $style1=strtoupper($this->TagStyle[$val]['style']);
                if($style1=="N")
                    break;
                else
                {
                    if(strpos($style1,"B")!==false)
                        $bold=true;
                    if(strpos($style1,"I")!==false)
                        $italic=true;
                    if(strpos($style1,"U")!==false)
                        $underline=true;
                } 
            }
            if($bold)
                $style.="B";
            if($italic)
                $style.="I";
            if($underline)
                $style.="U";
        }

        // Size
        if($this->TagStyle[$tag]['size']!=0)
            $size=$this->TagStyle[$tag]['size'];
        else
        {
            reset($this->PileStyle);
            while(list($k,$val)=each($this->PileStyle))
            {
                $val=trim($val);
                if($this->TagStyle[$val]['size']!=0) {
                    $size=$this->TagStyle[$val]['size'];
                    break;
                }
            }
        }

        // Color
        if($this->TagStyle[$tag]['color']!="")
            $color=$this->TagStyle[$tag]['color'];
        else
        {
            reset($this->PileStyle);
            while(list($k,$val)=each($this->PileStyle))
            {
                $val=trim($val);
                if($this->TagStyle[$val]['color']!="") {
                    $color=$this->TagStyle[$val]['color'];
                    break;
                }
            }
        }
         
        // Result
        $this->TagStyle[$ind]['family']=$family;
        $this->TagStyle[$ind]['style']=$style;
        $this->TagStyle[$ind]['size']=$size;
        $this->TagStyle[$ind]['color']=$color;
        $this->TagStyle[$ind]['indent']=$this->TagStyle[$tag]['indent'];
    }


    function Parser($text)
    {
        $tab=array();
        // Closing tag
        if(preg_match("|^(</([^>]+)>)|",$text,$regs)) {
            $tab[1]="c";
            $tab[2]=trim($regs[2]);
        }
        // Opening tag
        else if(preg_match("|^(<([^>]+)>)|",$text,$regs)) {
            $regs[2]=preg_replace("/^a/","a ",$regs[2]);
            $tab[1]="o";
            $tab[2]=trim($regs[2]);

            // Presence of attributes
            if(preg_match("/(.+) (.+)='(.+)'/",$regs[2])) {
                $tab1=preg_split("/ +/",$regs[2]);
                $tab[2]=trim($tab1[0]);
                while(list($i,$couple)=each($tab1))
                {
                    if($i>0) {
                        $tab2=explode("=",$couple);
                        $tab2[0]=trim($tab2[0]);
                        $tab2[1]=trim($tab2[1]);
                        $end=strlen($tab2[1])-2;
                        $tab[$tab2[0]]=substr($tab2[1],1,$end);
                    }
                }
            }
        }
         // Space
         else if(preg_match("/^( )/",$text,$regs)) {
            $tab[1]="s";
            $tab[2]=' ';
        }
        // Text
        else if(preg_match("/^([^< ]+)/",$text,$regs)) {
            $tab[1]="t";
            $tab[2]=trim($regs[1]);
        }

        $begin=strlen($regs[1]);
         $end=strlen($text);
         $text=substr($text, $begin, $end);
        $tab[0]=$text;

        return $tab;
    }


    function MakeLine()
    {
        $this->Text.=" ";
        $this->LineLength=array();
        $this->TagHref=array();
        $Length=0;
        $this->nbSpace=0;

        $i=$this->BeginLine();
        $this->TagName=array();

        if($i==0) {
            $Length=$this->StringLength[0];
            $this->TagName[0]=1;
            $this->TagHref[0]=$this->href;
        }

        while($Length<$this->wTextLine)
        {
            $tab=$this->Parser($this->Text);
            $this->Text=$tab[0];
            if($this->Text=="") {
                $this->LastLine=true;
                break;
            }

            if($tab[1]=="o") {
                array_unshift($this->PileStyle,$tab[2]);
                $this->FindStyle($this->PileStyle[0],$i+1);

                $this->DoStyle($i+1);
                $this->TagName[$i+1]=1;
                if($this->TagStyle[$tab[2]]['indent']!=-1) {
                    $Length+=$this->TagStyle[$tab[2]]['indent'];
                    $this->Indent=$this->TagStyle[$tab[2]]['indent'];
                }
                if($tab[2]=="a")
                    $this->href=$tab['href'];
            }

            if($tab[1]=="c") {
                array_shift($this->PileStyle);
                if(isset($this->PileStyle[0]))
                {
                    $this->FindStyle($this->PileStyle[0],$i+1);
                    $this->DoStyle($i+1);
                }
                $this->TagName[$i+1]=1;
                if($this->TagStyle[$tab[2]]['indent']!=-1) {
                    $this->LastLine=true;
                    $this->Text=trim($this->Text);
                    break;
                }
                if($tab[2]=="a")
                    $this->href="";
            }

            if($tab[1]=="s") {
                $i++;
                $Length+=$this->Space;
                $this->Line2Print[$i]="";
                if($this->href!="")
                    $this->TagHref[$i]=$this->href;
            }

            if($tab[1]=="t") {
                $i++;
                $this->StringLength[$i]=$this->GetStringWidth($tab[2]);
                $Length+=$this->StringLength[$i];
                $this->LineLength[$i]=$Length;
                $this->Line2Print[$i]=$tab[2];
                if($this->href!="")
                    $this->TagHref[$i]=$this->href;
             }

        }

        trim($this->Text);
        if($Length>$this->wTextLine || $this->LastLine==true)
            $this->EndLine();
    }


    function BeginLine()
    {
        $this->Line2Print=array();
        $this->StringLength=array();

        if(isset($this->PileStyle[0]))
        {
            $this->FindStyle($this->PileStyle[0],0);
            $this->DoStyle(0);
        }

        if(count($this->NextLineBegin)>0) {
            $this->Line2Print[0]=$this->NextLineBegin['text'];
            $this->StringLength[0]=$this->NextLineBegin['length'];
            $this->NextLineBegin=array();
            $i=0;
        }
        else {
            preg_match("/^(( *(<([^>]+)>)* *)*)(.*)/",$this->Text,$regs);
            $regs[1]=str_replace(" ", "", $regs[1]);
            $this->Text=$regs[1].$regs[5];
            $i=-1;
        }

        return $i;
    }


    function EndLine()
    {
        if(end($this->Line2Print)!="" && $this->LastLine==false) {
            $this->NextLineBegin['text']=array_pop($this->Line2Print);
            $this->NextLineBegin['length']=end($this->StringLength);
            array_pop($this->LineLength);
        }

        while(end($this->Line2Print)==="")
            array_pop($this->Line2Print);

        $this->Delta=$this->wTextLine-end($this->LineLength);

        $this->nbSpace=0;
        for($i=0; $i<count($this->Line2Print); $i++) {
            if($this->Line2Print[$i]=="")
                $this->nbSpace++;
        }
    }


    function PrintLine()
    {
        $border=0;
        if($this->border==1)
            $border="LR";
        $this->Cell($this->wLine,$this->hLine,"",$border,0,'C',$this->fill);
        $y=$this->GetY();
        $this->SetXY($this->Xini+$this->lPadding,$y);

        if($this->Indent!=-1) {
            if($this->Indent!=0)
                $this->Cell($this->Indent,$this->hLine);
            $this->Indent=-1;
        }

        $space=$this->LineAlign();
        $this->DoStyle(0);
        for($i=0; $i<count($this->Line2Print); $i++)
        {
            if(isset($this->TagName[$i]))
                $this->DoStyle($i);
            if(isset($this->TagHref[$i]))
                $href=$this->TagHref[$i];
            else
                $href='';
            if($this->Line2Print[$i]=="")
                $this->Cell($space,$this->hLine,"         ",0,0,'C',false,$href);
            else
                $this->Cell($this->StringLength[$i],$this->hLine,$this->Line2Print[$i],0,0,'C',false,$href);
        }

        $this->LineBreak();
        if($this->LastLine && $this->Text!="")
            $this->EndParagraph();
        $this->LastLine=false;
    }


    function LineAlign()
    {
        $space=$this->Space;
        if($this->align=="J") {
            if($this->nbSpace!=0)
                $space=$this->Space + ($this->Delta/$this->nbSpace);
            if($this->LastLine)
                $space=$this->Space;
        }

        if($this->align=="R")
            $this->Cell($this->Delta,$this->hLine);

        if($this->align=="C")
            $this->Cell($this->Delta/2,$this->hLine);

        return $space;
    }


    function LineBreak()
    {
        $x=$this->Xini;
        $y=$this->GetY()+$this->hLine;
        $this->SetXY($x,$y);
    }


    function EndParagraph()
    {
        $border=0;
        if($this->border==1)
            $border="LR";
        $this->Cell($this->wLine,$this->hLine/2,"",$border,0,'C',$this->fill);
        $x=$this->Xini;
        $y=$this->GetY()+$this->hLine/2;
        $this->SetXY($x,$y);
    }

    //Cell with horizontal scaling if text is too wide
    function CellFit($w, $h=0, $txt='', $border=0, $ln=0, $align='', $fill=false, $link='', $scale=false, $force=true)
    {
        //Get string width
        $str_width=$this->GetStringWidth($txt);

        //Calculate ratio to fit cell
        if($w==0)
            $w = $this->w-$this->rMargin-$this->x;
        $ratio = ($w-$this->cMargin*2)/$str_width;

        $fit = ($ratio < 1 || ($ratio > 1 && $force));
        if ($fit)
        {
            if ($scale)
            {
                //Calculate horizontal scaling
                $horiz_scale=$ratio*100.0;
                //Set horizontal scaling
                $this->_out(sprintf('BT %.2F Tz ET', $horiz_scale));
            }
            else
            {
                //Calculate character spacing in points
                $char_space=($w-$this->cMargin*2-$str_width)/max($this->MBGetStringLength($txt)-1, 1)*$this->k;
                //Set character spacing
                $this->_out(sprintf('BT %.2F Tc ET', $char_space));
            }
            //Override user alignment (since text will fill up cell)
            $align='';
        }

        //Pass on to Cell method
        $this->Cell($w, $h, $txt, $border, $ln, $align, $fill, $link);

        //Reset character spacing/horizontal scaling
        if ($fit)
            $this->_out('BT '.($scale ? '100 Tz' : '0 Tc').' ET');
    }

    //Cell with horizontal scaling only if necessary
    function CellFitScale($w, $h=0, $txt='', $border=0, $ln=0, $align='', $fill=false, $link='')
    {
        $this->CellFit($w, $h, $txt, $border, $ln, $align, $fill, $link, true, false);
    }

    //Cell with horizontal scaling always
    function CellFitScaleForce($w, $h=0, $txt='', $border=0, $ln=0, $align='', $fill=false, $link='')
    {
        $this->CellFit($w, $h, $txt, $border, $ln, $align, $fill, $link, true, true);
    }

    //Cell with character spacing only if necessary
    function CellFitSpace($w, $h=0, $txt='', $border=0, $ln=0, $align='', $fill=false, $link='')
    {
        $this->CellFit($w, $h, $txt, $border, $ln, $align, $fill, $link, false, false);
    }

    //Cell with character spacing always
    function CellFitSpaceForce($w, $h=0, $txt='', $border=0, $ln=0, $align='', $fill=false, $link='')
    {
        //Same as calling CellFit directly
        $this->CellFit($w, $h, $txt, $border, $ln, $align, $fill, $link, false, true);
    }

    //Patch to also work with CJK double-byte text
    function MBGetStringLength($s)
    {
        if($this->CurrentFont['type']=='Type0')
        {
            $len = 0;
            $nbbytes = strlen($s);
            for ($i = 0; $i < $nbbytes; $i++)
            {
                if (ord($s[$i])<128)
                    $len++;
                else
                {
                    $len++;
                    $i++;
                }
            }
            return $len;
        }
        else
            return strlen($s);
    }



    ///////
   
    
}