<?php
/*
*  Module written/ported by Xavier Noguer <xnoguer@rezebra.com>
*
*  The majority of this is _NOT_ my code.  I simply ported it from the
*  PERL Spreadsheet::WriteExcel module.
*
*  The author of the Spreadsheet::WriteExcel module is John McNamara 
*  <jmcnamara@cpan.org>
*
*  I _DO_ maintain this code, and John McNamara has nothing to do with the
*  porting of this code to PHP.  Any questions directly related to this
*  class library should be directed to me.
*
*  License Information:
*
*    Spreadsheet_Excel_Writer:  A library for generating Excel Spreadsheets
*    Copyright (C) 2002 Xavier Noguer xnoguer@rezebra.com
*
*    This library is free software; you can redistribute it and/or
*    modify it under the terms of the GNU Lesser General Public
*    License as published by the Free Software Foundation; either
*    version 2.1 of the License, or (at your option) any later version.
*
*    This library is distributed in the hope that it will be useful,
*    but WITHOUT ANY WARRANTY; without even the implied warranty of
*    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
*    Lesser General Public License for more details.
*
*    You should have received a copy of the GNU Lesser General Public
*    License along with this library; if not, write to the Free Software
*    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

/**
* Class for generating Excel XF records (formats)
*
* @author Xavier Noguer <xnoguer@rezebra.com>
* @package Spreadsheet_Excel_Writer
*/

class Format extends PEAR
{
    /**
    * The index given by the workbook when creating a new format.
    * @var integer
    */
    var $_xf_index;

    /**
    * Height of font (1/20 of a point)
    * @var integer
    */
    var $_size;

    /**
    * Bold style
    * @var integer
    */
    var $_bold;

    /**
    * Bit specifiying if the font is italic.
    * @var integer
    */
    var $_italic;

    /**
    * Index to the cell's color
    * @var 
    */
    var $_color;

    /**
    * The text underline property
    * @var integer
    */
    var $_underline;

    /**
    * Bit specifiying if the font has strikeout.
    * @var integer
    */
    var $_font_strikeout;

    /**
    * The two bits specifying the text rotation.
    * @var integer
    */
    var $_rotation;

    /**
    * Constructor
    *
    * @access public
    * @param integer $index the XF index for the format.
    * @param array   $properties array with properties to be set on initialization.
    */
    function Format($index = 0,$properties =  array())
    {
        $this->_xf_index       = $index;
                               
        $this->font_index      = 0;
        $this->font            = 'Arial';
        $this->_size           = 10;
        $this->_bold           = 0x0190;
        $this->_italic         = 0;
        $this->_color          = 0x7FFF;
        $this->_underline      = 0;
        $this->_font_strikeout = 0;
        $this->font_outline    = 0;
        $this->font_shadow     = 0;
        $this->font_script     = 0;
        $this->font_family     = 0;
        $this->font_charset    = 0;
                               
        $this->_num_format     = 0;
                               
        $this->_hidden         = 0;
        $this->_locked         = 1;
                               
        $this->_text_h_align   = 0;
        $this->_text_wrap      = 0;
        $this->_text_v_align   = 2;
        $this->_text_justlast  = 0;
        $this->_rotation       = 0;
                               
        $this->fg_color        = 0x40;
        $this->bg_color        = 0x41;
                               
        $this->pattern         = 0;
                               
        $this->bottom          = 0;
        $this->top             = 0;
        $this->left            = 0;
        $this->right           = 0;
                               
        $this->bottom_color    = 0x40;
        $this->top_color       = 0x40;
        $this->left_color      = 0x40;
        $this->right_color     = 0x40;
    
        // Set properties passed to Workbook::addFormat()
        foreach($properties as $property => $value)
        {
            if(method_exists($this,'set'.ucwords($property)))
            {
                $method_name = 'set'.ucwords($property);
                $this->$method_name($value);
            }
        }
    }


    /**
    * Generate an Excel BIFF XF record (style or cell).
    *
    * @param string $style The type of the XF record ('style' or 'cell').
    * @return string The XF record
    */
    function getXf($style)
    {
        // Set the type of the XF record and some of the attributes.
        if ($style == "style") {
            $style = 0xFFF5;
        }
        else {
            $style   = $this->_locked;
            $style  |= $this->_hidden << 1;
        }
    
        // Flags to indicate if attributes have been set.
        $atr_num     = ($this->_num_format != 0)?1:0;
        $atr_fnt     = ($this->font_index != 0)?1:0;
        $atr_alc     = ($this->_text_wrap)?1:0;
        $atr_bdr     = ($this->bottom   ||
                        $this->top      ||
                        $this->left     ||
                        $this->right)?1:0;
        $atr_pat     = (($this->fg_color != 0x40) ||
                        ($this->bg_color != 0x41) ||
                        $this->pattern)?1:0;
        $atr_prot    = 0;
    
        // Zero the default border colour if the border has not been set.
        if ($this->bottom == 0) {
            $this->bottom_color = 0;
            }
        if ($this->top  == 0) {
            $this->top_color = 0;
            }
        if ($this->right == 0) {
            $this->right_color = 0;
            }
        if ($this->left == 0) {
            $this->left_color = 0;
            }
    
        $record         = 0x00E0;              // Record identifier
        $length         = 0x0010;              // Number of bytes to follow
                                               
        $ifnt           = $this->font_index;   // Index to FONT record
        $ifmt           = $this->_num_format;  // Index to FORMAT record
    
        $align          = $this->_text_h_align;       // Alignment
        $align         |= $this->_text_wrap     << 3;
        $align         |= $this->_text_v_align  << 4;
        $align         |= $this->_text_justlast << 7;
        $align         |= $this->_rotation      << 8;
        $align         |= $atr_num                << 10;
        $align         |= $atr_fnt                << 11;
        $align         |= $atr_alc                << 12;
        $align         |= $atr_bdr                << 13;
        $align         |= $atr_pat                << 14;
        $align         |= $atr_prot               << 15;
    
        $icv            = $this->fg_color;           // fg and bg pattern colors
        $icv           |= $this->bg_color      << 7;
    
        $fill           = $this->pattern;            // Fill and border line style
        $fill          |= $this->bottom        << 6;
        $fill          |= $this->bottom_color  << 9;
    
        $border1        = $this->top;                // Border line style and color
        $border1       |= $this->left          << 3;
        $border1       |= $this->right         << 6;
        $border1       |= $this->top_color     << 9;
    
        $border2        = $this->left_color;         // Border color
        $border2       |= $this->right_color   << 7;
    
        $header      = pack("vv",       $record, $length);
        $data        = pack("vvvvvvvv", $ifnt, $ifmt, $style, $align,
                                        $icv, $fill,
                                        $border1, $border2);
        return($header.$data);
    }
    
    /**
    * Generate an Excel BIFF FONT record.
    *
    * @see Workbook::_storeAllFonts()
    * @return string The FONT record
    */
    function getFont()
    {
        $dyHeight   = $this->_size * 20;   // Height of font (1/20 of a point)
        $icv        = $this->_color;       // Index to color palette
        $bls        = $this->_bold;        // Bold style
        $sss        = $this->font_script;  // Superscript/subscript
        $uls        = $this->_underline;   // Underline
        $bFamily    = $this->font_family;  // Font family
        $bCharSet   = $this->font_charset; // Character set
        $rgch       = $this->font;         // Font name
    
        $cch        = strlen($rgch);       // Length of font name
        $record     = 0x31;                // Record identifier
        $length     = 0x0F + $cch;         // Record length
        $reserved   = 0x00;                // Reserved
        $grbit      = 0x00;                // Font attributes
        if ($this->_italic) {
            $grbit     |= 0x02;
        }
        if ($this->_font_strikeout) {
            $grbit     |= 0x08;
        }
        if ($this->font_outline) {
            $grbit     |= 0x10;
        }
        if ($this->font_shadow) {
            $grbit     |= 0x20;
        }
    
        $header  = pack("vv",         $record, $length);
        $data    = pack("vvvvvCCCCC", $dyHeight, $grbit, $icv, $bls,
                                      $sss, $uls, $bFamily,
                                      $bCharSet, $reserved, $cch);
        return($header . $data. $this->font);
    }
    
    /**
    * Returns a unique hash key for a font. Used by Workbook->_storeAllFonts()
    *
    * The elements that form the key are arranged to increase the probability of
    * generating a unique key. Elements that hold a large range of numbers
    * (eg. _color) are placed between two binary elements such as _italic
    *
    * @return string A key for this font
    */
    function getFontKey()
    {
        $key  = "$this->font$this->_size";
        $key .= "$this->font_script$this->_underline";
        $key .= "$this->_font_strikeout$this->_bold$this->font_outline";
        $key .= "$this->font_family$this->font_charset";
        $key .= "$this->font_shadow$this->_color$this->_italic";
        $key  = str_replace(" ","_",$key);
        return ($key);
    }
    
    /**
    * Returns the index used by Worksheet->_XF()
    *
    * @return integer The index for the XF record
    */
    function getXfIndex()
    {
        return($this->_xf_index);
    }
    
    /**
    * Used in conjunction with the set_xxx_color methods to convert a color
    * string into a number. Color range is 0..63 but we will restrict it
    * to 8..63 to comply with Gnumeric. Colors 0..7 are repeated in 8..15.
    *
    * @param string $name_color name of the color (i.e.: 'blue', 'red', etc..). Optional.
    * @return integer The color index
    */
    function _getColor($name_color = '')
    {
        $colors = array(
                        'aqua'    => 0x0F,
                        'cyan'    => 0x0F,
                        'black'   => 0x08,
                        'blue'    => 0x0C,
                        'brown'   => 0x10,
                        'magenta' => 0x0E,
                        'fuchsia' => 0x0E,
                        'gray'    => 0x17,
                        'grey'    => 0x17,
                        'green'   => 0x11,
                        'lime'    => 0x0B,
                        'navy'    => 0x12,
                        'orange'  => 0x35,
                        'purple'  => 0x14,
                        'red'     => 0x0A,
                        'silver'  => 0x16,
                        'white'   => 0x09,
                        'yellow'  => 0x0D
                       );
    
        // Return the default color, 0x7FFF, if undef,
        if($name_color == '') {
            return(0x7FFF);
        }
    
        // or the color string converted to an integer,
        if(isset($colors[$name_color])) {
            return($colors[$name_color]);
        }
    
        // or the default color if string is unrecognised,
        if(preg_match("/\D/",$name_color)) {
            return(0x7FFF);
        }
    
        // or an index < 8 mapped into the correct range,
        if($name_color < 8) {
            return($name_color + 8);
        }
    
        // or the default color if arg is outside range,
        if($name_color > 63) {
            return(0x7FFF);
        }
    
        // or an integer in the valid range
        return($name_color);
    }
    
    /**
    * Set cell alignment.
    *
    * @access public
    * @param string $location alignment for the cell ('left', 'right', etc...).
    */
    function setAlign($location)
    {
        if (preg_match("/\d/",$location)) {
            return;                      // Ignore numbers
        }
    
        $location = strtolower($location);
    
        if ($location == 'left')
            $this->_text_h_align = 1; 
        if ($location == 'centre')
            $this->_text_h_align = 2; 
        if ($location == 'center')
            $this->_text_h_align = 2; 
        if ($location == 'right')
            $this->_text_h_align = 3; 
        if ($location == 'fill')
            $this->_text_h_align = 4; 
        if ($location == 'justify')
            $this->_text_h_align = 5;
        if ($location == 'merge')
            $this->_text_h_align = 6;
        if ($location == 'equal_space') // For T.K.
            $this->_text_h_align = 7; 
        if ($location == 'top')
            $this->_text_v_align = 0; 
        if ($location == 'vcentre')
            $this->_text_v_align = 1; 
        if ($location == 'vcenter')
            $this->_text_v_align = 1; 
        if ($location == 'bottom')
            $this->_text_v_align = 2; 
        if ($location == 'vjustify')
            $this->_text_v_align = 3; 
        if ($location == 'vequal_space') // For T.K.
            $this->_text_v_align = 4; 
    }
    
    /**
    * This is an alias for the unintuitive setAlign('merge')
    *
    * @access public
    */
    function setMerge()
    {
        $this->setAlign('merge');
    }
    
    /**
    * Bold has a range 0x64..0x3E8.
    * 0x190 is normal. 0x2BC is bold.
    *
    * @access public
    * @param integer $weight Weight for the text, 0 maps to 0x190, 1 maps to 0x2BC. 
                             It's Optional, default is 1 (bold).
    */
    function setBold($weight = 1)
    {
        if($weight == 1) {
            $weight = 0x2BC;  // Bold text
        }
        if($weight == 0) {
            $weight = 0x190;  // Normal text
        }
        if($weight <  0x064) {
            $weight = 0x190;  // Lower bound
        }
        if($weight >  0x3E8) {
            $weight = 0x190;  // Upper bound
        }
        $this->_bold = $weight;
    }
    
    
    /************************************
    * FUNCTIONS FOR SETTING CELLS BORDERS
    */
    
    /**
    * Sets the bottom border of the cell
    *
    * @access public
    * @param integer $style style of the cell border. 1 => thin, 2 => thick.
    */
    function setBottom($style)
    {
        $this->bottom = $style;
    }
    
    /**
    * Sets the top border of the cell
    *
    * @access public
    * @param integer $style style of the cell top border. 1 => thin, 2 => thick.
    */
    function setTop($style)
    {
        $this->top = $style;
    }
    
    /**
    * Sets the left border of the cell
    *
    * @access public
    * @param integer $style style of the cell left border. 1 => thin, 2 => thick.
    */
    function setLeft($style)
    {
        $this->left = $style;
    }
    
    /**
    * Sets the right border of the cell
    *
    * @access public
    * @param integer $style style of the cell right border. 1 => thin, 2 => thick.
    */
    function setRight($style)
    {
        $this->right = $style;
    }
    
    
    /**
    * Set cells borders to the same style
    *
    * @access public
    * @param integer $style style to apply for all cell borders. 1 => thin, 2 => thick.
    */
    function setBorder($style)
    {
        $this->setBottom($style);
        $this->setTop($style);
        $this->setLeft($style);
        $this->setRight($style);
    }
    
    
    /*******************************************
    * FUNCTIONS FOR SETTING CELLS BORDERS COLORS
    */
    
    /**
    * Sets all the cell's borders to the same color
    *
    * @access public
    * @param mixed $color The color we are setting. Either a string (like 'blue'), 
    *                     or an integer (like 0x41).
    */
    function setBorderColor($color)
    {
        $this->setBottomColor($color);
        $this->setTopColor($color);
        $this->setLeftColor($color);
        $this->setRightColor($color);
    }
    
    /**
    * Sets the cell's bottom border color
    *
    * @access public
    * @param mixed $color either a string (like 'blue'), or an integer (range is [8...63]).
    */
    function setBottomColor($color)
    {
        $value = $this->_getColor($color);
        $this->bottom_color = $value;
    }
    
    /**
    * Sets the cell's top border color
    *
    * @access public
    * @param mixed $color either a string (like 'blue'), or an integer (range is [8...63]).
    */
    function setTopColor($color)
    {
        $value = $this->_getColor($color);
        $this->top_color = $value;
    }
    
    /**
    * Sets the cell's left border color
    *
    * @access public
    * @param mixed $color either a string (like 'blue'), or an integer (like 0x41).
    */
    function setLeftColor($color)
    {
        $value = $this->_getColor($color);
        $this->left_color = $value;
    }
    
    /**
    * Sets the cell's right border color
    *
    * @access public
    * @param mixed $color either a string (like 'blue'), or an integer (like 0x41).
    */
    function setRightColor($color)
    {
        $value = $this->_getColor($color);
        $this->right_color = $value;
    }
    
    
    /**
    * Sets the cell's foreground color
    *
    * @access public
    * @param mixed $color either a string (like 'blue'), or an integer (like 0x41).
    */
    function setFgColor($color)
    {
        $value = $this->_getColor($color);
        $this->fg_color = $value;
    }
      
    /**
    * Sets the cell's background color
    *
    * @access public
    * @param mixed $color either a string (like 'blue'), or an integer (like 0x41).
    */
    function setBgColor($color)
    {
        $value = $this->_getColor($color);
        $this->bg_color = $value;
    }
    
    /**
    * Sets the cell's color
    *
    * @access public
    * @param mixed $color either a string (like 'blue'), or an integer (like 0x41).
    */
    function setColor($color)
    {
        $value = $this->_getColor($color);
        $this->_color = $value;
    }
    
    /**
    * Sets the pattern attribute of a cell
    *
    * @access public
    * @param integer $arg Optional. Defaults to 1.
    */
    function setPattern($arg = 1)
    {
        $this->pattern = $arg;
    }
    
    /**
    * Sets the underline of the text
    *
    * @access public
    * @param integer $underline The value for underline. Possible values are:
    *                          1 => underline, 2 => double underline.
    */
    function setUnderline($underline)
    {
        $this->_underline = $underline;
    }
 
    /**
    * Sets the font style as italic
    *
    * @access public
    */
    function setItalic()
    {
        $this->_italic = 1;
    }

    /**
    * Sets the font size 
    *
    * @access public
    * @param integer $size The font size (in pixels I think).
    */
    function setSize($size)
    {
        $this->_size = $size;
    }
    
    /**
    * Sets the num format
    *
    * @access public
    * @param integer $num_format The num format.
    */
    function setNumFormat($num_format)
    {
        $this->_num_format = $num_format;
    }
    
    /**
    * Sets text wrapping
    *
    * @access public
    * @param integer $text_wrap Optional. 0 => no text wrapping, 1 => text wrapping. 
    *                           Defaults to 1.
    */
    function setTextWrap($text_wrap = 1)
    {
        $this->_text_wrap = $text_wrap;
    }

    /**
    * Sets the orientation of the text
    *
    * @access public
    * @param integer $angle The rotation angle for the text (clockwise). Possible
                            values are: 0, 90, 270 and -1 for stacking top-to-bottom.
    */
    function setTextRotation($angle)
    {
        switch ($angle)
        {
            case 0:
                $this->_rotation = 0;
                break;
            case 90:
                $this->_rotation = 3;
                break;
            case 270:
                $this->_rotation = 2;
                break;
            case -1:
                $this->_rotation = 1;
                break;
            default :
                $this->raiseError("Invalid value for angle.".
                                  " Possible values are: 0, 90, 270 and -1 ".
                                  "for stacking top-to-bottom.");
                $this->_rotation = 0;
                break;
        }
    }

    /**
    * Sets Font Strikeout.
    *
    * @access public
    * @param integer $strikeout Optional. 0 => no font strikeout, 1 => font strikeout. 
    *                           Defaults to 1.
    */
    function setStrikeOut($strikeout = 1)
    {
        $this->_font_strikeout = $strikeout;
    }
}
?>