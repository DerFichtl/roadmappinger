<?php

ini_set('display_errors', true);
error_reporting(E_ALL);

Class Roadmap {

    protected $p = null;
    protected $c = null;

    // A1 = 2380 x 1684
    // A3 = 842 x 1190
    protected $pageWidth = 2380;
    protected $pageHeight = 1684;

    protected $pageMargin = 100;
    protected $fontSize = 22;
    protected $lineWidth = 1;

    protected $mapTop = 0;
    protected $mapLeft = 0;
    protected $mapBottom = 0;
    protected $mapRight = 0;

    protected $barHeight = 30;
    protected $barOffset = 30;
    protected $barGap = 10;

    protected $colCount = 0;
    protected $colWidth = 0;

    protected $font = null;

    public function __construct($config) {

        $this->c = json_decode($config, true);
        if ($this->c == null) {
            die("JSON Error");
        }

        $this->p = $p = new PDFlib();
        if ($p->begin_document("", "") == 0) {
            die("Error: " . $p->get_errmsg());
        }

        $p->set_parameter("topdown", "true");
        $p->set_parameter("usercoordinates", "true");
        // p.scale(28.3465, 28.3465);

        $p->set_info("Creator", "https://github.com/DerFichtl/roadmappinger");
        $p->set_info("Author", "@derfichtl");
        $p->set_info("Title", $this->c['title']);

        if(isset($this->c['size']) && ! empty($this->c['size'])) {
            list($this->pageWidth, $this->pageHeight) = explode('x', $this->c['size']);
        }

        if(isset($this->c['fontSize']) && ! empty($this->c['fontSize'])) {
            $this->fontSize = $this->c['fontSize'];
        }

        if(isset($this->c['barHeight']) && ! empty($this->c['barHeight'])) {
            $this->barHeight = $this->c['barHeight'];
        }

        if(isset($this->c['pageMargin']) && ! empty($this->c['pageMargin'])) {
            $this->pageMargin = $this->c['pageMargin'];
        }

        if(isset($this->c['lineWidth']) && ! empty($this->c['lineWidth'])) {
            $this->lineWidth = $this->c['lineWidth'];
        }

        $p->begin_page_ext($this->pageWidth, $this->pageHeight, "");

        $this->colCount = count($this->c['cols']);

        $this->mapTop = $this->pageMargin+$this->fontSize;
        $this->mapLeft = $this->pageMargin;
        $this->mapRight = $this->pageWidth-$this->pageMargin*2;


        if(isset($this->c['tagCol']) && is_numeric($this->c['tagCol'])) {
            $this->mapLeft += $this->c['tagCol'];
            $this->mapRight -= $this->c['tagCol'];
        }

        $this->font = $p->load_font("Helvetica", "iso8859-1", "");

        if ($this->font == -1) {
            throw new Exception("Error: " + $p->get_errmsg());
        }
    }

    public function draw() {
        $this->grid();
        $this->title();

        $blockOffset = $this->mapTop+($this->fontSize*2.8);
        $blockPadding = 20;
        $barGap = 10;
        $barHeight = $this->barHeight;
        $blockTitleHeight = $this->barHeight;

        foreach($this->c['blocks'] as $block) {

            // $blockHeight = count($block['bars'])*($barHeight+$barGap)+$blockTitleHeight+$blockPadding;
            $blockHeight = $this->calcBlockHeight($block['bars'], $barHeight+$barGap)+$blockTitleHeight+$blockPadding;
            if(! $block['title']) {
                $blockHeight -= $blockTitleHeight;
            }

            if ($block['bars']) {
                $this->block($block['title'], $blockOffset, $blockHeight);

                $barOffset = $blockOffset+($this->fontSize*2);
                if(! $block['title']) {
                    $barOffset -= $blockTitleHeight;
                }

                foreach($block['bars'] as $bar) {
                    $this->bar($bar, $barOffset, $barHeight);

                    if (isset($bar['dates'])) {
                        foreach($bar['dates'] as $date) {
                            $this->date($date['title'], $barOffset+$this->barHeight/2, $date['date']);
                        }
                    }

                    $barOffset += $barHeight+$barGap;
                }

                $blockOffset += $blockHeight+$blockPadding;
            }
        }

        $this->output();
    }

    public function output() {
        $p = $this->p;

        $p->end_page_ext("");
        $p->end_document("");

        $buf = $p->get_buffer();
        $len = strlen($buf);

        header("Content-type: application/pdf");
        header("Content-Length: $len");
        header("Content-Disposition: inline; filename=output.pdf");
        print $buf;
    }

    public function grid() {
        $p = $this->p;
        $this->colWidth = ($this->pageWidth-($this->pageMargin+$this->mapLeft))/$this->colCount;

        foreach($this->c['cols'] as $i=>$col){

            $left = $this->mapLeft+($i*$this->colWidth);
            $top = $this->mapTop+($this->fontSize*2);

            $p->setcolor("both", "rgb", 0, 0, 0, 0);
            $p->setfont($this->font, $this->fontSize);
            $p->fit_textline($col['title'], $left, $top, "boxsize {".$this->colWidth." ".($this->fontSize/2)."} position {50 50}");
        }

        $x = 0;
        $y = ($this->fontSize*2);

        $p->setcolor("stroke", "rgb", 0.6, 0.6, 0.6, 0.0);
        $p->setlinewidth($this->lineWidth);

        // top line
        $p->moveto($this->mapLeft, $this->pageMargin+$y);
        $p->lineto($this->pageWidth-$this->pageMargin, $this->pageMargin+$y);
        $p->stroke();

        // bottom line
        $p->moveto($this->mapLeft, $this->pageHeight-$this->pageMargin);
        $p->lineto($this->pageWidth-$this->pageMargin, $this->pageHeight-$this->pageMargin);
        $p->stroke();

        $p->setdash(8, 8);

        for($i=1; $i<=$this->colCount+1; $i++) {
            $p->moveto($x+$this->mapLeft, $this->pageMargin+$y);
            $p->lineto($x+$this->mapLeft, $this->pageHeight-$this->pageMargin);
            $p->stroke();
            $x = $i*$this->colWidth;
        }
    }

    public function title() {
        $p = $this->p;

        $p->setcolor("both", "rgb", 0, 0, 0, 0);
        $p->setfont($this->font, $this->fontSize*2);
        $p->fit_textline($this->c['title'], $this->mapLeft, $this->pageMargin, "");
    }

    public function block($title, $offset, $height) {
        $p = $this->p;

        $fontSize = $this->fontSize;
        $fontMargin = $this->fontSize/3;

        if($title) {
            $p->setcolor("both", "rgb", 0, 0, 0, 0);
            $p->setfont($this->font, $fontSize);

            $p->fit_textline($title, $this->mapLeft+$fontMargin*2, $offset+$fontSize+$fontMargin, "");
        }

        $gstate = $p->create_gstate("opacityfill=.4");
        $p->set_gstate($gstate);

        $p->setcolor("both", "rgb", 0.9, 0.9, 0.9, 0.0);
        $p->rect($this->mapLeft, $offset+$height, $this->mapRight, $height);
        $p->fill();

        $gstate = $p->create_gstate("opacityfill=1");
        $p->set_gstate($gstate);
    }


    public function calcBlockHeight($bars, $height) {

        // return count($bars)*$height;

        $barHeight = 0;
        foreach($bars as $bar) {
            if(isset($bar['parts'])) {
                $title = $bar['parts'][0]['title'];
            } else {
                $title = $bar['title'];
            }

            $partTitle = $title;
            if(is_string($partTitle)) {
                $partTitle = array($partTitle);
            }

            $barHeight += count($partTitle)*$height;
        }
        return $barHeight;
    }

    public function bar($bar, $offset, $height) {
        $p = $this->p;

        $fontSize = $this->fontSize;
        $fontMargin = $this->fontSize/3;

        if(! isset($bar['parts'])) {
            $bar['parts'][0] = $bar;
        }

        foreach($bar['parts'] as $part) {

            if(! isset($part['background'])) {
                $part['background'] = "#88bf00";
            }

            if(! isset($part['color'])) {
                $part['color'] = "#333333";
            }

            $left = $this->mapLeft+($part['start']*$this->colWidth);
            $width = ($part['end']-$part['start']) * $this->colWidth;

            $partTitle = $part['title'];
            if(is_string($partTitle)) {
                $partTitle = array($partTitle);
            }

            $gstate = $p->create_gstate("opacityfill=.6");
            $p->set_gstate($gstate);

            list($r,$g,$b) = $this->formatColor($part['background']);
            $p->setcolor("both", "rgb", $r, $g, $b, 0);


            $boxMargin = 0;
            if (isset($part['margin'])) {
                $boxMargin = $part['margin'];
            }

            $p->rect($left+$boxMargin, $offset+$height*count($partTitle)+$boxMargin, $width-$boxMargin*2, $height*count($partTitle)+($boxMargin*2));
            $p->fill();

            $gstate = $p->create_gstate("opacityfill=1");
            $p->set_gstate($gstate);

            list($r,$g,$b) = $this->formatColor($part['color']);
            $p->setcolor("both", "rgb", $r, $g, $b, 0);

            $lineOffset = $offset;
            $p->setfont($this->font, $fontSize);
            foreach($partTitle as $line) {
                $p->fit_textline($line, $left+($fontMargin*2)+$boxMargin, $lineOffset+$fontSize, "");
                $lineOffset += $height;
            }

            if (isset($part['tags'])) {

                $textWidth = $p->info_textline($part['title'], "width", "");
                $textOffset = $this->mapLeft+($part['start']*$this->colWidth)+$textWidth;

                foreach($part['tags'] as $tag) {
                    $tagWidth = $this->tag($tag, $offset+$this->fontSize, $textOffset, $part['background']);
                    $textOffset += $tagWidth;
                }
            }
        }
    }

    public function date($title, $offset, $pos) {
        $p = $this->p;

        $left = $this->mapLeft+($pos*$this->colWidth);
        $fontSize = $this->fontSize;
        // $fontMargin = $this->fontSize;
        $fontMargin = $this->fontSize/3;

        $p->setcolor("both", "rgb", 1, 1, 0, 0.0);
        $p->circle($left, $offset, $this->barHeight/2);
        $p->fill();

        $p->setcolor("both", "rgb", 0.3, 0.3, 0.3, 0.0);
        $p->setfont($this->font, $fontSize);
        $p->fit_textline($title, $left+$fontMargin*3, $offset+$fontMargin, "");
    }

    public function tag($tag, $y, $x, $color) {
        $p = $this->p;

        $fontMargin = $this->fontSize*2;

        if(! isset($this->c['tagCol']) || ! is_numeric($this->c['tagCol'])) {
            $color = '#666666';
        }

        list($r,$g,$b) = $this->formatColor($color);
        $p->setcolor("both", "rgb", $r, $g, $b, 0);
        $optlist = "matchbox={boxheight={ascender descender}
            borderwidth=".$this->lineWidth." strokecolor={rgb $r $g $b}
            offsetleft=-10 offsetright=10 offsettop=4 offsetbottom=0}";

        if(isset($this->c['tagCol']) && is_numeric($this->c['tagCol'])) {
            $p->fit_textline($tag, $this->mapLeft - $this->c['tagCol'], $y + 4, $optlist);
        } else {
            $p->fit_textline($tag, $x+$fontMargin, $y + 4, $optlist);
        }

        $info = $p->info_textline($tag, "width", "");
        return $info+$this->fontSize;
    }

    public function formatColor($color) {
        if(strpos($color, "#") === 0) {
            $r = hexdec(substr($color,1,2))/255;
            $g = hexdec(substr($color,3,2))/255;
            $b = hexdec(substr($color,5,2))/255;
            return array($r,$g,$b);
        }
        return array(0,0,0);
    }

}

if(isset($_GET['file'])) {
    $config = file_get_contents($_GET['file']);
    $roadmap = new Roadmap($config);
    $roadmap->draw();
}
