<?php
/**
 * Xss过滤类
 * @author tabalt
 */
class Xss {

    /**
     * 白名单标签列表
     */
    protected static $whiteTagList = array(
        'div', 
        'a', 
        'img', 
        'b', 
        'i', 
        'span', 
        'br', 
        'p', 
        'strong'
    );

    /**
     * 白名单属性列表
     * @author tabalt
     */
    protected static $whiteAttrList = array(
        'align', 
        'id', 
        'class', 
        'width', 
        'height'
    );

    /**
     * 需过滤的属性列表
     * @author tabalt
     */
    protected static $filterAttrList = array(
    	/*
        'style' => array(
            '/[^a-zA-Z0-9.:; ()\/]/' => '',  //非有用字符直接替换为空
            '/[a-zA-Z]{2,}:expression[a-zA-Z0-9.: ()\/]*;?/' => '' //关键字替换为空 IE可执行代码
        ), */
        'href' => array(
            //'/^?!((http|https):\/\/)([a-z0-9-_]+\.)+[a-z]+/i',
            '/javascript:.*/i' => '#', 
            '/vbscript:.*/i' => '#', 
            '/data:.*/i' => '#'
        ), 
        'src' => array(
            //'/^?!((http|https):\/\/)([a-z0-9-_]+\.)+[a-z]+/i',
            '/javascript:.*/i' => '',  //IE6可执行js代码,
            '/vbscript:.*/i' => '#', 
            '/data:.*/i' => ''
        )
    );

    /**
     * UBB标签列表
     * @author tabalt
     */
    protected static $ubbTagList = array(
        '/\[IMG\]((http|https):\/\/([a-z\d_-]+\.)+[a-z]{2,}(:[\d]{2,})?.*)\[\/IMG\]/i' => '<img src="\1" />', 
        '/\[B\](.*)\[\/B\]/i' => '<strong>\1</strong>'
    );

    /**
     * HTML标签转换为UBB标签列表
     * @author pengming
     */
    protected static $htmlToUbbTagList = array(
        '/<img src="((http|https):\/\/([a-z\d_-]+\.)+[a-z]{2,}(:[\d]{2,})?.*)".*(\/)?>/iU' => '[IMG]\1[/IMG]',
        '/<strong>(.*)<\/strong>/i'=>'[B]\1[/B]',
        '/<p>(.*)<\/p>/isU' => "\\1\n",
    );

    /**
     * 安全过滤
     * @author tabalt
     * @param DOMElement $element
     * @param array $ruleList
     */
    protected static function filterElement($element) {
        //需要删除的节点列表
        $needRemoveDomList = array();
        foreach ($element->childNodes as $item) {
            if ($item->nodeType == XML_ELEMENT_NODE) {
                //删除不在配置中的标签
                if (!in_array($item->tagName, self::$whiteTagList)) {
                    $needRemoveDomList[] = $item;
                    continue;
                }
                //标签属性过滤
                //需要删除的属性列表
                $needRemoveAttrList = array();
                foreach ($item->attributes as $attrName => $attrNode) {
                    
                    $attrName = strtolower($attrName);
                    if (in_array($attrName, self::$whiteAttrList)) {
                        //白名单属性 直接放过
                        continue;
                    } else if (array_key_exists($attrName, self::$filterAttrList)) {
                        //过滤灰名单属性
                        $filterList = self::$filterAttrList[$attrName];
                        foreach ($filterList as $pattern => $replacement) {
                            $item->setAttribute($attrName, preg_replace($pattern, $replacement, $item->getAttribute($attrName)));
                        }
                    } else {
                        //删除不在白名单 且不在灰名单 的属性
                        $needRemoveAttrList[] = $attrName;
                    }
                }
                //删除属性
                foreach ($needRemoveAttrList as $attrName) {
                    $item->removeAttribute($attrName);
                }
                //递归子标签
                self::filterElement($item);
            }
        }
        //删除节点
        foreach ($needRemoveDomList as $item) {
            $item->parentNode->removeChild($item);
        }
    }

    /**
     * 移除XSS代码
     * @author tabalt
     * @param string $input
     * @return $output
     */
    public static function remove($input) {
        $output = '';
        if (!empty($input)) {
            //随机生成ID 解决提交固定ID绕过的问题
            $blockId = 'block_' . md5(mt_rand() . time() . mt_rand());
            $input = '<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/><div id="' . $blockId . '">' . $input . '</div>';
            $doc = new DOMDocument();
            @$doc->loadHTML($input);
            //解决 很多版本 DOMDocument::getElementById获取不到的问题
            $block = $doc->getElementById($blockId);
            if (empty($block)) {
                foreach ($doc->getElementsByTagName('*') as $element) {
                    if ($element->hasAttribute('id')) {
                        $element->setIdAttribute('id', true);
                    }
                }
                $block = $doc->getElementById($blockId);
            }
            if ($block) {
                self::filterElement($block);
                $pattern = '/<div id="' . $blockId . '">(.*)<\/div><\/body>/s';
                preg_match($pattern, $doc->saveHTML(), $matches);
                $output = isset($matches[1]) ? $matches[1] : '';
            }
        }
        return $output;
    }

    /**
     * 将UBB代码转成html代码
     * @author tabalt
     * @param string $input
     * @return $output
     */
    public static function ubbToHtml($input) {
        $input = str_replace("　", '', trim($input));
        if ($input == '') {
            return '';
        }
        $output = htmlspecialchars(trim($input));
        foreach (self::$ubbTagList as $pattern => $replacement) {
            //preg_match($pattern, $output, $matches);
            //print_r($matches);
            $output = preg_replace($pattern, $replacement, $output);
        }
        $output = "<p>\n" . implode("\n</p>\n<p>\n", explode("\n", $output)) . "\n</p>";
        return $output;
    }

    /**
     * HTML代码转换为UBB代码
     * @author pengming
     * @param $input
     * @return mixed|string
     */
    public static function htmlToUbb($input) {
        $search = array(
            "\n",
            "\r"
        );
        $input = str_replace($search, '', trim($input));
        if ($input == '') {
            return '';
        }
        $output = trim($input);
        foreach (self::$htmlToUbbTagList as $pattern => $replacement) {
            $output = preg_replace($pattern, $replacement, $output);
        }
        $output = str_replace('&amp;', '&', $output);
        return $output;
    }
}