<?php

/**
 * This class overcomes a few common annoyances with the DOMDocument class,
 * such as saving partial HTML without automatically adding extra tags
 * and properly recognizing various encodings, specifically UTF-8.
 *
 * @author Artem Russakovskii
 * @author Denis Chenu
 * @version 0.5.1
 * @link http://beerpla.net
 * @link http://www.php.net/manual/en/class.domdocument.php
 * @license MIT
 */

namespace toolsDomDocument;

class SmartDOMDocument extends \DOMDocument
{

    /**
     * @var boolean $debug : See warning when loading HTML
     */
    public $debug = false;

    /**
     * Adds an ability to use the SmartDOMDocument object as a string in a string context.
     * For example, echo "Here is the HTML: $dom";
     */
    public function __toString()
    {
        return $this->saveHTMLExact();
    }

    /**
     * Load HTML with a proper encoding fix/hack.
     * Borrowed from the link below.
     *
     * @link http://www.php.net/manual/en/domdocument.loadhtml.php
     *
     * @param string $html
     * @param string $encoding , default to UTF-8
     *
     * @return bool
     */
    public function loadHTML($html, $encoding = "UTF-8")
    {
        $html = mb_convert_encoding($html, 'HTML-ENTITIES', $encoding);
        if (!$this->debug)
            return @parent::loadHTML($html); // suppress warnings
        else
            return parent::loadHTML($html);
    }

    /**
     * Load partial HTML adding a doctype, and an empty head
     *
     * @param string $html
     * @param string $doctype , default to html (HTML5)
     * @param string $encoding , default to UTF-8
     *
     * @return bool
     * @see loadHTML
     *
     */
    public function loadPartialHTML($html, $doctype = 'html', $encoding = "UTF-8")
    {
        $html = '<!DOCTYPE ' . $doctype . '><html><head></head><body>' . $html . '</body></html>';
        return self::loadHTML($html);
    }

    /**
     * Return HTML while stripping the annoying auto-added <html>, <body>, and doctype.
     *
     * @link http://php.net/manual/en/migration52.methods.php
     *
     * @return string
     */
    public function saveHTMLExact()
    {
        $content = preg_replace(array("/^\<\!DOCTYPE.*?<html>.*?<body>/si",
            "!</body></html>$!si"),
            "",
            $this->saveHTML());
        return $content;
    }
}
