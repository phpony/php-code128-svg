<?php

class code128 {

    /**
     * C128 barcodes.
     * Very capable code, excellent density, high reliability; in very wide use world-wide.
     *
     * @param string $code code to represent
     * @param string $type barcode type: A, B, C or empty for automatic switch (AUTO mode)
     *
     * @return array barcode representation
     */
    protected function barcode_c128($code, $type = '')
    {
        $chr = array(
            '212222', '222122', '222221', '121223', '121322', '131222', '122213', '122312', '132212', '221213', '221312', '231212', '112232', '122132', '122231', '113222', '123122', '123221', '223211', '221132', '221231', '213212', '223112', '312131', '311222', '321122', '321221', '312212', '322112', '322211', '212123', '212321', '232121', '111323', '131123', '131321', '112313', '132113', '132311', '211313', '231113', '231311', '112133', '112331', '132131', '113123', '113321', '133121', '313121', '211331', '231131', '213113', '213311', '213131', '311123', '311321', '331121', '312113', '312311', '332111', '314111', '221411', '431111', '111224', '111422', '121124', '121421', '141122', '141221', '112214', '112412', '122114', '122411', '142112', '142211', '241211', '221114', '413111', '241112', '134111', '111242', '121142', '121241', '114212', '124112', '124211', '411212', '421112', '421211', '212141', '214121', '412121', '111143', '111341', '131141', '114113', '114311', '411113', '411311', '113141', '114131', '311141', '411131', '211412', '211214', '211232', '233111', '200000'
        );
        $keys_a = ' !"#$%&\'()*+,-./0123456789:;<=>?@ABCDEFGHIJKLMNOPQRSTUVWXYZ[\\]^_';
        $keys_a .= chr(0).chr(1).chr(2).chr(3).chr(4).chr(5).chr(6).chr(7).chr(8).chr(9);
        $keys_a .= chr(10).chr(11).chr(12).chr(13).chr(14).chr(15).chr(16).chr(17).chr(18).chr(19);
        $keys_a .= chr(20).chr(21).chr(22).chr(23).chr(24).chr(25).chr(26).chr(27).chr(28).chr(29);
        $keys_a .= chr(30).chr(31);
        $keys_b = ' !"#$%&\'()*+,-./0123456789:;<=>?@ABCDEFGHIJKLMNOPQRSTUVWXYZ[\\]^_`abcdefghijklmnopqrstuvwxyz{|}~'.chr(127);
        $fnc_a = array(241 => 102, 242 => 97, 243 => 96, 244 => 101);
        $fnc_b = array(241 => 102, 242 => 97, 243 => 96, 244 => 100);
        $code_data = array();
        $len = strlen($code);
        switch (strtoupper($type)) {
            case 'A':
                $startid = 103;
                for ($i = 0; $i < $len; ++$i) {
                    $char = $code[$i];
                    $char_id = ord($char);
                    if (($char_id >= 241) and ($char_id <= 244)) {
                        $code_data[] = $fnc_a[$char_id];
                    } elseif (($char_id >= 0) and ($char_id <= 95)) {
                        $code_data[] = strpos($keys_a, $char);
                    } else {
                        throw new Exception('Char '.$char.' is unsupported');
                    }
                }
                break;
            case 'B':
                $startid = 104;
                for ($i = 0; $i < $len; ++$i) {
                    $char = $code[$i];
                    $char_id = ord($char);
                    if (($char_id >= 241) and ($char_id <= 244)) {
                        $code_data[] = $fnc_b[$char_id];
                    } elseif (($char_id >= 32) and ($char_id <= 127)) {
                        $code_data[] = strpos($keys_b, $char);
                    } else {
                        throw new Exception('Char '.$char.' is unsupported');
                    }
                }
                break;
            case 'C': 
                $startid = 105;
                if (241 == ord($code[0])) {
                    $code_data[] = 102;
                    $code = substr($code, 1);
                    --$len;
                }
                if (0 != ($len % 2)) {
                    throw new Exception('Length must be even');
                }
                for ($i = 0; $i < $len; $i += 2) {
                    $chrnum = $code[$i].$code[$i + 1];
                    if (preg_match('/([0-9]{2})/', $chrnum) > 0) {
                        $code_data[] = intval($chrnum);
                    } else {
                        throw new Exception();
                    }
                }
                break;
            default:
                $sequence = array();
                $numseq = array();
                preg_match_all('/([0-9]{4,})/', $code, $numseq, PREG_OFFSET_CAPTURE);
                if (isset($numseq[1]) and !empty($numseq[1])) {
                    $end_offset = 0;
                    foreach ($numseq[1] as $val) {
                        $offset = $val[1];
                        $slen = strlen($val[0]);
                        if (0 != ($slen % 2)) {
                            ++$offset;
                            $val[0] = substr($val[0], 1);
                        }
                        if ($offset > $end_offset) {
                            $sequence = array_merge($sequence, $this->get128ABsequence(substr($code, $end_offset, ($offset - $end_offset))));
                        }
                        $slen = strlen($val[0]);
                        if (0 != ($slen % 2)) {
                            --$slen;
                        }
                        $sequence[] = array('C', substr($code, $offset, $slen), $slen);
                        $end_offset = $offset + $slen;
                    }
                    if ($end_offset < $len) {
                        $sequence = array_merge($sequence, $this->get128ABsequence(substr($code, $end_offset)));
                    }
                } else {
                    $sequence = array_merge($sequence, $this->get128ABsequence($code));
                }
                foreach ($sequence as $key => $seq) {
                    switch ($seq[0]) {
                        case 'A':
                            if (0 == $key) {
                                $startid = 103;
                            } elseif ('A' != $sequence[($key - 1)][0]) {
                                if ((1 == $seq[2]) and ($key > 0) and ('B' == $sequence[($key - 1)][0]) and (!isset($sequence[($key - 1)][3]))) {
                                    $code_data[] = 98;
                                    $sequence[$key][3] = true;
                                } elseif (!isset($sequence[($key - 1)][3])) {
                                    $code_data[] = 101;
                                }
                            }
                            for ($i = 0; $i < $seq[2]; ++$i) {
                                $char = $seq[1][$i];
                                $char_id = ord($char);
                                if (($char_id >= 241) and ($char_id <= 244)) {
                                    $code_data[] = $fnc_a[$char_id];
                                } else {
                                    $code_data[] = strpos($keys_a, $char);
                                }
                            }
                            break;
                        case 'B':
                            if (0 == $key) {
                                $tmpchr = ord($seq[1][0]);
                                if ((1 == $seq[2]) and ($tmpchr >= 241) and ($tmpchr <= 244) and isset($sequence[($key + 1)]) and ('B' != $sequence[($key + 1)][0])) {
                                    switch ($sequence[($key + 1)][0]) {
                                        case 'A':
                                            $startid = 103;
                                            $sequence[$key][0] = 'A';
                                            $code_data[] = $fnc_a[$tmpchr];
                                            break;

                                        case 'C':
                                            $startid = 105;
                                            $sequence[$key][0] = 'C';
                                            $code_data[] = $fnc_a[$tmpchr];
                                            break;
                                    }
                                    break;
                                }
                                $startid = 104;
                            } elseif ('B' != $sequence[($key - 1)][0]) {
                                if ((1 == $seq[2]) and ($key > 0) and ('A' == $sequence[($key - 1)][0]) and (!isset($sequence[($key - 1)][3]))) {
                                    $code_data[] = 98;
                                    $sequence[$key][3] = true;
                                } elseif (!isset($sequence[($key - 1)][3])) {
                                    $code_data[] = 100;
                                }
                            }
                            for ($i = 0; $i < $seq[2]; ++$i) {
                                $char = $seq[1][$i];
                                $char_id = ord($char);
                                if (($char_id >= 241) and ($char_id <= 244)) {
                                    $code_data[] = $fnc_b[$char_id];
                                } else {
                                    $code_data[] = strpos($keys_b, $char);
                                }
                            }
                            break;
                        case 'C':
                            if (0 == $key) {
                                $startid = 105;
                            } elseif ('C' != $sequence[($key - 1)][0]) {
                                $code_data[] = 99;
                            }
                            for ($i = 0; $i < $seq[2]; $i += 2) {
                                $chrnum = $seq[1][$i].$seq[1][$i + 1];
                                $code_data[] = intval($chrnum);
                            }
                            break;
                    }
                }
        }
        $sum = $startid;
        foreach ($code_data as $key => $val) {
            $sum += ($val * ($key + 1));
        }
        $code_data[] = ($sum % 103);
        $code_data[] = 106;
        $code_data[] = 107;
        array_unshift($code_data, $startid);
        $bararray = array('code' => $code, 'maxw' => 0, 'maxh' => 1, 'bcode' => array());
        foreach ($code_data as $val) {
            $seq = $chr[$val];
            for ($j = 0; $j < 6; ++$j) {
                if (0 == ($j % 2)) {
                    $t = true; 
                } else {
                    $t = false; 
                }
                $w = $seq[$j];
                $bararray['bcode'][] = array('t' => $t, 'w' => $w, 'h' => 1, 'p' => 0);
                $bararray['maxw'] += $w;
            }
        }
        return $bararray;
    }

    /**
     * Split text code in A/B sequence for 128 code.
     *
     * @param $code (string) code to split
     *
     * @return array sequence
     * @protected
     */
    protected function get128ABsequence($code)
    {
        $len = strlen($code);
        $sequence = array();
        $numseq = array();
        preg_match_all('/([\x00-\x1f])/', $code, $numseq, PREG_OFFSET_CAPTURE);
        if (isset($numseq[1]) and !empty($numseq[1])) {
            $end_offset = 0;
            foreach ($numseq[1] as $val) {
                $offset = $val[1];
                if ($offset > $end_offset) {
                    $sequence[] = array(
                        'B',
                        substr($code, $end_offset, ($offset - $end_offset)),
                        ($offset - $end_offset),
                    );
                }
                $slen = strlen($val[0]);
                $sequence[] = array('A', substr($code, $offset, $slen), $slen);
                $end_offset = $offset + $slen;
            }
            if ($end_offset < $len) {
                $sequence[] = array('B', substr($code, $end_offset), ($len - $end_offset));
            }
        } else {
            $sequence[] = array('B', $code, $len);
        }
        return $sequence;
    }

    /**
     * Converts the Barcode array to a new style.
     *
     * @param array $oldBarcodeArray
     *
     * @return array
     */
    protected function convertBarcodeArrayToNewStyle($oldBarcodeArray)
    {
        if (!isset($oldBarcodeArray['maxWidth'])) {
            $newBarcodeArray = [];
            $newBarcodeArray['code'] = $oldBarcodeArray['code'];
            $newBarcodeArray['maxWidth'] = $oldBarcodeArray['maxw'];
            $newBarcodeArray['maxHeight'] = $oldBarcodeArray['maxh'];
            $newBarcodeArray['bars'] = [];
            foreach ($oldBarcodeArray['bcode'] as $oldbar) {
                $newBar = [];
                $newBar['width'] = $oldbar['w'];
                $newBar['height'] = $oldbar['h'];
                $newBar['positionVertical'] = $oldbar['p'];
                $newBar['drawBar'] = $oldbar['t'];
                $newBar['drawSpacing'] = !$oldbar['t'];
                $newBarcodeArray['bars'][] = $newBar;
            }
            return $newBarcodeArray;
        }
        return $oldBarcodeArray;
    }

    /**
     * Return a SVG string representation of barcode.
     *
     * @param string  $code         barcode data
     * @param string  $codeType     barcode type: A, B, C or empty for automatic switch (AUTO mode)
     * @param int     $widthFactor  minimum width of a single bar in user units
     * @param int     $totalHeight  height of barcode in user units
     * @param string  $color        hexidecimal foreground color (in SVG format) for bar elements (background is transparent)
     *
     * @return string SVG code
     */
    public function generate($code, $codeType = '', $widthFactor = 2, $totalHeight = 30, $color = '#000000')
    {
        $barcodeData = $this->convertBarcodeArrayToNewStyle($this->barcode_c128($code, $codeType));
        $repstr = array("\0" => '', '&' => '&amp;', '<' => '&lt;', '>' => '&gt;');
        $width = round(($barcodeData['maxWidth'] * $widthFactor), 3);
        $svg = '<?xml version="1.0" standalone="no" ?>'."\n";
        $svg .= '<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">'."\n";
        $svg .= '<svg width="'.$width.'" height="'.$totalHeight.'" viewBox="0 0 '.$width.' '.$totalHeight.'" version="1.1" xmlns="http://www.w3.org/2000/svg">'."\n";
        $svg .= "\t".'<desc>'.strtr($barcodeData['code'], $repstr).'</desc>'."\n";
        $svg .= "\t".'<g id="bars" fill="'.$color.'" stroke="none">'."\n";
        $positionHorizontal = 0;
        foreach ($barcodeData['bars'] as $bar) {
            $barWidth = round(($bar['width'] * $widthFactor), 3);
            $barHeight = round(($bar['height'] * $totalHeight / $barcodeData['maxHeight']), 3);
            if ($bar['drawBar']) {
                $positionVertical = round(($bar['positionVertical'] * $totalHeight / $barcodeData['maxHeight']), 3);
                $svg .= "\t\t".'<rect x="'.$positionHorizontal.'" y="'.$positionVertical.'" width="'.$barWidth.'" height="'.$barHeight.'" />'."\n";
            }
            $positionHorizontal += $barWidth;
        }
        $svg .= "\t".'</g>'."\n";
        $svg .= '</svg>'."\n";
        return $svg;
    }
}

