<?php

require_once __DIR__ . '/vendor/autoload.php';

Class Roadmap {

    protected $p = null;
    protected $c = null; // config

    protected static $sizes = ['A0'=>'1189x841','A4'=>'297x210'];
    
    protected $pageSize = 'A4';
    protected $pageWidth = 1190;
    protected $pageHeight = 842;

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

    protected $tagColor = '#666666';
    protected $tagCol = 0;

    protected $lineColor = '#666666';

    protected $font = null;
    protected $buffer = null;

    public function __construct($config) {

        if(strpos($config,'{') === 0) {
            $this->c = json_decode($config, true);
        } else {
            $this->c = $this->parseConfig($config);
        }

        if ($this->c == null) {
            throw new Exception("Empty config or not parseable.");
        }

        $this->p = $p = new \Mpdf\Mpdf(['mode'=>'utf-8', 'dpi'=>'72']);

        $p->SetCreator("https://github.com/DerFichtl/roadmappinger");
        $p->SetAuthor("@derfichtl");
        $p->SetTitle($this->c['title']);

        if(isset($this->c['size']) && ! empty($this->c['size'])) {
            $this->pageSize = strtoupper($this->c['size']);
            list($this->pageWidth, $this->pageHeight) = explode('x', self::$sizes[$this->pageSize]);
        }

        if(isset($this->c['fontSize']) && ! empty($this->c['fontSize'])) {
            $this->fontSize = $this->c['fontSize'];
        }

        if(isset($this->c['barHeight']) && ! empty($this->c['barHeight'])) {
            $this->barHeight = $this->c['barHeight'];
        }

        if(isset($this->c['barGap']) && ! empty($this->c['barGap'])) {
            $this->barGap = $this->c['barGap'];
        }

        if(isset($this->c['pageMargin']) && ! empty($this->c['pageMargin'])) {
            $this->pageMargin = $this->c['pageMargin'];
        }

        if(isset($this->c['lineWidth']) && ! empty($this->c['lineWidth'])) {
            $this->lineWidth = $this->c['lineWidth'];
        }

        if(isset($this->c['tagColor']) && ! empty($this->c['tagColor'])) {
            $this->tagColor = $this->c['tagColor'];
        }

        $p->AddPageByArray(['orientation'=>'L', 'sheet-size'=>$this->pageSize]);


        $this->colCount = count($this->c['cols']);

        $this->mapTop = $this->pageMargin+$this->fontSize;
        $this->mapLeft = $this->pageMargin;
        $this->mapRight = $this->pageWidth-$this->pageMargin*2;

        if(isset($this->c['tagCol']) && is_numeric($this->c['tagCol'])) {
            $this->mapLeft += $this->c['tagCol'];
            $this->mapRight -= $this->c['tagCol'];
            $this->tagCol = $this->c['tagCol'];
        }

        $this->font = "Arial";
    }

    public function draw() {
        $this->grid();
        $this->title();

        $blockOffset = $this->mapTop + ($this->fontSize*2);
        $blockPadding = 4; // 20
        $barGap = $this->barGap;
        $barHeight = $this->barHeight;
        $blockTitleHeight = $this->barHeight;

        foreach($this->c['blocks'] as $block) {

            $blockHeight = $this->calcBlockHeight($block['bars'], $barHeight+$barGap)+$blockTitleHeight+$blockPadding;
            if(! $block['title']) {
                $blockHeight -= $blockTitleHeight;
            }

            if ($block['bars']) {
                $this->block($block['title'], $blockOffset, $blockHeight);

                $barOffset = $blockOffset+$this->fontSize;
                if(! $block['title']) {
                    $barOffset -= $blockTitleHeight;
                }

                foreach($block['bars'] as $bar) {
                    $this->bar($bar, $barOffset, $barHeight);

                    if (isset($bar['dates'])) {
                        foreach($bar['dates'] as $date) {

                            if(! isset($date['background'])) {
                                $date['background'] = '';
                            }

                            $this->date($date['title'], $barOffset+$this->barHeight/2, $date['date'], $date['background']);
                        }
                    }

                    $barOffset += $barHeight + $barGap;
                }

                $blockOffset += $blockHeight + $blockPadding;
            }
        }

        return $this->buffer = $this->p->Output('', 'S');
    }

    public function output() {
        $len = strlen($this->buffer);

        header("Content-type: application/pdf");
        header("Content-Length: $len");
        header("Content-Disposition: inline; filename=output.pdf");
        print $this->buffer;
    }

    public function write($filename) {
        return file_put_contents($filename, $this->buffer);
    }

    public function grid() {

        $p = $this->p;
        $this->colWidth = ($this->pageWidth-($this->pageMargin+$this->mapLeft))/$this->colCount;
        $this->colHeight = ($this->pageHeight-($this->pageMargin+$this->mapTop));

        foreach($this->c['cols'] as $i=>$col){

            if(isset($col['title'])) {
                $colTitle = $col['title'];
            } else {
                $colTitle = $col;
            }

            $left = $this->mapLeft+($i*$this->colWidth);
            $top = $this->mapTop+($this->fontSize/2);

            $p->SetTextColor(0, 0, 0);
            $p->SetFont($this->font, '', $this->fontSize);

            $p->SetXY($left, $top);
            $p->Cell($this->colWidth, $this->fontSize/2, $colTitle, 0, 0, 'C');
        }

        $x = 0;
        $y = ($this->fontSize);

        list($r, $g, $b) = $this->formatColor($this->lineColor);
        $p->SetDrawColor($r, $g, $b);
        $p->SetLineWidth($this->lineWidth);

        // top line
        $p->Line($this->mapLeft, $this->pageMargin+$y, $this->pageWidth-($this->pageMargin), $this->pageMargin+$y);

        // bottom line
        $p->Line($this->mapLeft, $this->pageHeight-$this->pageMargin, $this->pageWidth-$this->pageMargin, $this->pageHeight-$this->pageMargin);

        // $p->setdash(8, 8);

        for($i=1; $i<=$this->colCount+1; $i++) {
            $p->Line($x+$this->mapLeft, $this->pageMargin+$y, $x+$this->mapLeft, $this->pageHeight-$this->pageMargin);
            $x = $i*$this->colWidth;
        }
    }

    public function title() {
        $p = $this->p;

        $p->SetTextColor(0, 0, 0);
        $p->SetFont($this->font, '', $this->fontSize*2);

        $p->Text($this->mapLeft, $this->pageMargin, $this->c['title']);
    }

    public function block($title, $offset, $height) {
        $p = $this->p;

        $fontSize = $this->fontSize;
        $fontMargin = $this->fontSize/2.5;

        if($title) {
            $p->SetTextColor(0, 0, 0);
            $p->SetFont($this->font, '', $this->fontSize);

            $p->Text($this->mapLeft+$fontMargin, $offset+$fontSize/2, utf8_decode($title));
        }

        $p->SetAlpha(0.1);
        $p->SetFillColor(0, 0, 0);
        $p->Rect($this->mapLeft, $offset, $this->mapRight, $height, 'F');
        $p->SetAlpha(1);
    }


    public function calcBlockHeight($bars, $height) {

        $barHeight = 0;
        foreach($bars as $bar) {

            $title = '';
            if(isset($bar['parts'])) {
                $title = $bar['parts'][0]['title'];
            } elseif (isset($bar['title'])) {
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
        $fontMargin = $this->fontSize/2.5;

        if(! isset($bar['parts'])) {
            $bar['parts'][0] = $bar;
        }

        foreach($bar['parts'] as $part) {

            if(! isset($part['start']) || ! isset($part['end'])) {
                continue;
            }

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

            $p->SetAlpha(0.6);

            list($r,$g,$b) = $this->formatColor($part['background']);
            $p->SetFillColor($r, $g, $b);

            $boxMargin = 0;
            if (isset($part['margin'])) {
                $boxMargin = $part['margin'];
            }

            $p->Rect($left+$boxMargin, $offset, $width-$boxMargin*2, $height, "F");

            $p->SetAlpha(1);

            list($r,$g,$b) = $this->formatColor($part['color']);
            $p->SetDrawColor($r, $g, $b);

            $lineOffset = $offset;
            $p->SetFont($this->font, "", $fontSize);

            foreach($partTitle as $line) {
                $p->Text($left+$fontMargin+$boxMargin, $lineOffset+($height/2+$fontSize/8), utf8_decode($line));
                $lineOffset += $height;
            }

            /* if (isset($part['tags'])) {

                // $textWidth = $p->info_textline(utf8_decode($part['title']), "width", "");
                $textWidth = 0;
                $textOffset = $this->mapLeft+($part['start']*$this->colWidth)+$textWidth;

                foreach($part['tags'] as $tag) {
                    $tagWidth = $this->tag($tag, $offset+$this->fontSize, $textOffset, $this->tagColor);
                    $textOffset += $tagWidth;
                }
            } */
        }
    }

    public function date($title, $offset, $pos, $color = '') {
        $p = $this->p;

        $left = $this->mapLeft+($pos*$this->colWidth);
        $fontSize = $this->fontSize;
        $fontMargin = $this->fontSize/2;

        if($color) {
            list($r, $g, $b) = $this->formatColor($color);
            $p->SetFillColor($r, $g, $b);
        } else {
            $p->SetFillColor(1, 1, 0);
        }

        $p->Circle($left, $offset, $this->barHeight/2, "F");
        $p->SetFont($this->font, "", $fontSize);

        $p->Text($left+$fontMargin, $offset+($this->fontSize/8), utf8_decode($title));
    }

    /* public function tag($tag, $y, $x, $color) {
        $p = $this->p;

        if(! isset($this->c['tagCol']) || ! is_numeric($this->c['tagCol'])) {
            $color = '#666666';
        }

        list($r, $g, $b) = $this->formatColor($color);
        $p->SetDrawColor($r, $g, $b);

        if(isset($this->c['tagCol']) && is_numeric($this->c['tagCol'])) {
            // $p->Rect($this->mapLeft - $this->c['tagCol'], $y, $this->c['tagCol'], $this->fontSize, "D");
            // $p->Text($this->mapLeft - $this->c['tagCol'], $y, utf8_decode($tag));
        } else {
            $p->Rect($x, $y-($this->fontSize), 20, $this->fontSize, "D");
            $p->Text($x, $y-($this->fontSize), utf8_decode($tag));
        }

        $optlist = "matchbox={boxheight={ascender descender}
            borderwidth=".$this->lineWidth." strokecolor={rgb $r $g $b}
            offsetleft=-10 offsetright=10 offsettop=4 offsetbottom=0}";

        if(isset($this->c['tagCol']) && is_numeric($this->c['tagCol'])) {
            $p->fit_textline(utf8_decode($tag), $this->mapLeft - $this->c['tagCol'], $y + 4, $optlist);
        } else {
            $p->fit_textline(utf8_decode($tag), $x+$fontMargin, $y + 4, $optlist);
        }

        $info = $p->info_textline($tag, "width", "");
        return $info+$this->fontSize;
    } */

    public function formatColor($color) {
        if(strpos($color, "#") === 0) {
            $r = hexdec(substr($color,1,2));
            $g = hexdec(substr($color,3,2));
            $b = hexdec(substr($color,5,2));
            return array($r,$g,$b);
        }
        return array(0,0,0);
    }

    public function parseConfig($lines) {

        $config = array();
        $lines = preg_replace('/\/\/(.*)\n/', "\n", $lines);

        $lines = explode("\n", $lines);
        $blockIdx = -1;
        $barIdx = 0;

        foreach($lines as $lineIdx => $line) {
            if(! $line) {
                continue;
            }

            if($line[0] == '!') {

                list($key, $value) = explode(':', str_replace('!','',$line));
                $key = trim($key);

                if(! in_array($key, array('size','fontSize','pageMargin','barHeight','barGap',
                    'lineWidth','cols','colors','tagCol','tagColor'))) {

                    throw new Exception("Unknown config Key '$key' on line $lineIdx.");
                }

                if($key == 'cols') {

                    $config[$key] = explode(',', $value);

                } elseif ($key == 'colors') {

                    $colors = explode(',', $value);

                    foreach($colors as $color) {
                        list($code, $hex) = explode('=', $color);
                        $config[$key][trim($code)] = trim($hex);

                        if(! $this->isColor($hex)) {
                            throw new Exception("Unknown color Key '$hex' on line $lineIdx ... must be something like #ffffff.");
                        }
                    }

                } elseif ($key == 'size') {

                    $value = trim($value);
                    if(! $this->isSize($value)) {
                        throw new Exception("Must be a size (like 123x123) but it is '$value' on line $lineIdx.");
                    } 

                    $config[$key] = $value;

                } elseif ($key == 'tagColor') {

                    $value = trim($value);
                    if(! $this->isColor($value)) {
                        throw new Exception("Unknown color Key '$value' on line $lineIdx ... must be something like #ffffff.");
                    }
                    $config[$key] = $value;

                } else {

                    $value = trim($value);
                    if(! is_numeric($value)) {
                        throw new Exception("Must be numeric but it is '$value' on line $lineIdx.");
                    }

                    $config[$key] = $value;
                }

            } elseif(strpos($line, '==') === 0) {

                $blockIdx++;
                $title = trim(str_replace('=', '',  $line));
                $config['blocks'][$blockIdx] = array('title'=>$title, 'bars'=>array());

            } elseif(strpos($line, '=') === 0) {

                $config['title'] = trim(str_replace('=', '', $line));

            } elseif($line[0] == '*') {

                $parts = array();
                $dates = array();

                $configParts = explode(';', str_replace('*','',$line));
                $partIdx = 0;

                foreach($configParts as $part) {

                    $part = trim($part);
                    $start = false;
                    $end = false;
                    preg_match('/(\d+(\.\d)?(-\d+(\.\d)?)?)/', $part, $time);

                    if($time) {

                        if(strpos($time[0],'-') === false) {
                            $start = $time[0];
                        } else {
                            list($start, $end) = explode('-', $time[0]);
                        }

                        $title = trim(str_replace($time[0], '', $part));
                        $background = '';

                        $tags = array();
                        preg_match('/\[.*\]/', $part, $tags);
                        if($tags) {
                            $title = trim(str_replace($tags[0], '', $title));

                            foreach($tags as $i => $tag) {
                                $tags[$i] = str_replace(array('[',']'), array('',''), $tag);
                            }
                        }

                        if(isset($config['colors']) && ! empty($config['colors'])) {
                            foreach($config['colors'] as $code => $color) {
                                if(strpos($title, $code) !== false) {
                                    $title = trim(str_replace($code, '', $title));
                                    $background = $color;
                                }
                            }
                        }

                        if(is_numeric($start) && is_numeric($end)) {
                            $parts[$partIdx] = array('title'=>$title, 'start'=>$start, 'end'=>$end, 'tags'=>$tags);
                            if($background) {
                                $parts[$partIdx]['background'] = $background;
                            }
                        }

                        if($end == false) {
                            $dates[$partIdx] = array('title'=>$title, 'date'=>$start, 'background'=>$background);
                        }

                        $partIdx++;
                    }
                }

                if($parts) {
                    $config['blocks'][$blockIdx]['bars'][$barIdx]['parts'] = $parts;
                }

                if($dates) {
                    $config['blocks'][$blockIdx]['bars'][$barIdx]['dates'] = $dates;
                }

                if($dates || $parts) {
                    $barIdx++;
                }

            }
        }

        return $config;
    }


    public static function isColor($str) {
        return @preg_match("/^#[a-f0-9]{6}$/i", $str);
    }

    /* public static function isSize($str) {
        return @preg_match("/^[0-9]{2,5}x[0-9]{2,5}$/i", $str);
    } */

    public static function isSize($str) {
        return isset(self::$sizes[strtoupper($str)]);
    }   
    
    public static function toMm($px) {
        return ($px * 25.4) / 72;
    }
}

