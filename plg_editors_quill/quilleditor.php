<?php

/**
 * @copyright   Copyright (C) EddyChang
 * @license     GNU General Public License version 2 or later.
 */

defined('_JEXEC') or die('Restricted access');

class plgEditorquilleditor extends JPlugin
{

    public function onInit()
    {
        $path = JURI::root() . 'plugins/editors/quilleditor/quill/';

        $doc = JFactory::getDocument();

        $doc->addScript($path . 'quill.min.js');

        return '';
    }

    public function onSave($editor)
    {


        $js = "\t   var content = $.QuillNS.quillditor.root.innerHTML;\n

                      document.getElementById('{$editor}').value = content;\n";
        return $js;
    }

    public function onGetContent($id)
    {
        return "document.getElementById('$id').value;\n";
    }

    /**
     * Set the editor content.
     *
     * @param   string $id The id of the editor field.
     * @param   string $html The content to set.
     *
     * @return  string
     */
    public function onSetContent($id, $html)
    {
        return "document.getElementById('$id').value = $html;\n";
    }

    /**
     * Inserts html code into the editor
     *
     * @param   string $id The id of the editor field
     *
     * @return  boolean  returns true when complete
     */
    /**
     * @return bool
     */
    public function onGetInsertMethod()
    {
        static $done = false;

        // Do this only once.
        if (!$done) {
            $done = true;
            $doc = JFactory::getDocument();

            $path = JURI::root();

            //1. add path to image for preview.
            //2. use toMarkdown.js converter.
            //3. parse htmlentities (encode)
            $js = <<<JS
function jInsertEditorText(text, editor_id) {

  var obj = {
      mystring: text
  }


  var img_tag = jQuery(obj.mystring).filter('img')[0];
  var img_src = jQuery(img_tag).attr('src');
  var new_img_src = "{$path}" + img_src;

  // console.log(text);
  // console.log(new_img_src);

//  $.QuillNS.quillditor.insertEmbed(10, 'image', new_img_src);
var range = $.QuillNS.quillditor.getSelection();
if (range) {
  if (range.length == 0) {
    console.log('User cursor is at index', range.index);
  } else {
    var text = $.QuillNS.quillditor.getText(range.index, range.length);
    console.log('User has highlighted: ', text);
  }
} else {
  console.log('User cursor is not in editor');
}


  $.QuillNS.quillditor.insertEmbed(range.index, 'image', new_img_src );

  $.QuillNS.quillditor.insertText(range.index, text);
  $.QuillNS.quillditor.formatText(range.index, text.length, text, true);
}
JS;


            $doc->addScriptDeclaration($js);
        }

        return true;
    }


    /**
     * @param $name
     * @param $content
     * @param $width
     * @param $height
     * @param $col
     * @param $row
     * @param bool $buttons
     * @return string
     * @throws Exception
     */
    public function onDisplay(
        $name, $content, $width, $height, $col, $row, $buttons = true, $id = null, $asset = null, $author = null, $params = array())
    {
        $id = empty($id) ? $name : $id;

        // Only add "px" to width and height if they are not given as a percentage.
        $width .= is_numeric($width) ? 'px' : '';
        $height .= is_numeric($height) ? 'px' : '';

        $doc = JFactory::getDocument();

        $token = JSession::getFormToken();
        $admin = JFactory::getApplication()->isAdmin();


        $path = JURI::root() . 'plugins/editors/quilleditor/quill/';

        // if ($uri->isSSL()) {
        //     $path = str_replace('http://', 'https://', $path);
        // }
        $editor_theme = $this->params->get('editor_theme', 'snow');
        //$preview_theme = $this->params->get('preview_theme', 'preview-dark');
        //$auto_save = (int)$this->params->get('auto_save', 500);
        //


        $doc->addScript($path . 'quill.min.js');

        $doc->addStyleSheet($path .'quill.'.$editor_theme.'.css');

        //heredoc string for javascript
        //sync innerhtml from textarea to quill container
$optionJs = <<<JS
jQuery(document).ready(function (){

  jQuery('#quilleditor').html(jQuery('#{$id}').val());

  var toolbarOptions = [
  ['bold', 'italic', 'underline', 'strike'],        // toggled buttons
  ['blockquote', 'code-block'],

  [{ 'header': 1 }, { 'header': 2 }],               // custom button values
  [{ 'list': 'ordered'}, { 'list': 'bullet' }],
  [{ 'script': 'sub'}, { 'script': 'super' }],      // superscript/subscript
  [{ 'indent': '-1'}, { 'indent': '+1' }],          // outdent/indent
  [{ 'direction': 'rtl' }],                         // text direction

  [{ 'size': ['small', false, 'large', 'huge'] }],  // custom dropdown
  [{ 'header': [1, 2, 3, 4, 5, 6, false] }],

  [{ 'color': [] }, { 'background': [] }],          // dropdown with defaults from theme
  [{ 'font': [] }],
  [{ 'align': [] }],

  ['clean']                                         // remove formatting button
];


  $.QuillNS = {};
  $.QuillNS.quillditor = new Quill('#quilleditor', {
    modules: {
      toolbar: toolbarOptions
    },
    theme: '{$editor_theme}'
  });

  $.QuillNS.quillditor.on('text-change', function() {
    jQuery('#{$id}').val(jQuery('#quilleditor').html());
  });

});
JS;

        $doc->addScriptDeclaration($optionJs);

        // $doc->addScriptDeclaration($script);
        $buttons = $this->_displayButtons($id, $buttons, $asset, $author);

        $html = array();

        //$html[] = "<textarea name=\"$name\" id=\"$id\" cols=\"$col\" rows=\"$row\" >$content</textarea>";
         $html[] = '<div id="quilleditor"></div>';

        $html[] = '<textarea name="' . $name . '" id="' . $id . '" cols="' . $col . '" rows="' . $row . '" style="display:none">' . $content . '</textarea>';


        $html[] = $buttons;


        $output = "";
        $output .= implode("\n", $html);

        return $output;

    }

    /**
     * @param $name
     * @param $buttons
     * @param $asset
     * @param $author
     *
     * @return string
     */
    protected function _displayButtons($name, $buttons, $asset, $author)
    {
        // Load modal popup behavior
        JHtml::_('behavior.modal', 'a.modal-button');

        $args['name'] = $name;
        $args['event'] = 'onGetInsertMethod';

        $return = '';
        $results[] = $this->update($args);
        foreach ($results as $result) {
            if (is_string($result) && trim($result)) {
                $return .= $result;
            }
        }

        if (is_array($buttons) || (is_bool($buttons) && $buttons)) {
            $results = $this->_subject->getButtons($name, $buttons, $asset, $author);

            $version = new JVersion();
            if (version_compare($version->getShortVersion(), '3.0', '>=')) {

                /*
                 * This will allow plugins to attach buttons or change the behavior on the fly using AJAX
                 */
                $return .= "\n<div id=\"editor-xtd-buttons\" class=\"btn-toolbar pull-left\">\n";
                $return .= "\n<div class=\"btn-toolbar\">\n";

                foreach ($results as $button) {
                    /*
                     * Results should be an object
                     */
                    if ($button->get('name')) {
                        $modal = ($button->get('modal')) ? ' class="modal-button btn"' : null;
                        $href = ($button->get('link')) ? ' class="btn" href="' . JURI::base() . $button->get('link') . '"' : null;
                        $onclick = ($button->get('onclick')) ? ' onclick="' . $button->get('onclick') . '"' : '';
                        $title = ($button->get('title')) ? $button->get('title') : $button->get('text');
                        $return .= '<a' . $modal . ' title="' . $title . '"' . $href . $onclick . ' rel="' . $button->get('options')
                            . '"><i class="icon-' . $button->get('name') . '"></i> ' . $button->get('text') . "</a>\n";
                    }
                }

                $return .= "</div>\n";
                $return .= "</div>\n";

            } else {

                // This will allow plugins to attach buttons or change the behavior on the fly using AJAX
                $return .= "\n" . '<div id="editor-xtd-buttons"><div class="btn-toolbar pull-left rokpad-clearfix">' . "\n";

                foreach ($results as $button) {
                    // Results should be an object
                    if ($button->get('name')) {
                        $modal = ($button->get('modal')) ? 'class="modal-button"' : null;
                        $href = ($button->get('link')) ? 'href="' . JURI::base() . $button->get('link') . '"' : null;
                        $onclick = ($button->get('onclick')) ? 'onclick="' . $button->get('onclick') . '"' : null;
                        $title = ($button->get('title')) ? $button->get('title') : $button->get('text');
                        $return .= "\n" . '<div class="button2-left"><div class="' . $button->get('name') . '">';
                        $return .= '<a ' . $modal . ' title="' . $title . '" ' . $href . ' ' . $onclick . ' rel="' . $button->get('options') . '">';
                        $return .= $button->get('text') . '</a>' . "\n" . '</div>' . "\n" . '</div>' . "\n";
                    }
                }

                $return .= '</div></div>' . "\n";
            }
        }

        return $return;
    }

  }
