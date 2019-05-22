Description of dompdf 0.6.1 library import into Moodle.
If this file doesn't contain all of the changes, the original version is found ong github at:
https://github.com/dompdf/dompdf/tree/v0.6.1

2013/08/22
REMOVED:
- www/ folder (configuration utility)
- php-font-lib/www/ folder
- dompdf.php
- index.php

ADDED:
+ lib.php Moodle wrapper for dompdf
+ moodle_config.php moodle specific configuration for dompdf

index 6cd0efa..7d7e560 100644
diff --git a/lib/dompdf/dompdf_config.inc.php b/lib/dompdf/dompdf_config.inc.php
index 9970b61..51fbcb5 100644
--- a/lib/dompdf/dompdf_config.inc.php
+++ b/lib/dompdf/dompdf_config.inc.php
@@ -329,7 +329,7 @@ require_once(DOMPDF_LIB_DIR . "/html5lib/Parser.php");
  */
 if (DOMPDF_ENABLE_AUTOLOAD) {
   require_once(DOMPDF_INC_DIR . "/autoload.inc.php");
-  require_once(DOMPDF_LIB_DIR . "/php-font-lib/classes/Font.php");
+  require_once(DOMPDF_LIB_DIR . "/php-font-lib/classes/font.cls.php");
 }
 
 /**
diff --git a/lib/dompdf/include/cpdf_adapter.cls.php b/lib/dompdf/include/cpdf_adapter.cls.php
index da9a3c3..06947b5 100644
--- a/lib/dompdf/include/cpdf_adapter.cls.php
+++ b/lib/dompdf/include/cpdf_adapter.cls.php
@@ -636,7 +636,7 @@ class CPDF_Adapter implements Canvas {
   function text($x, $y, $text, $font, $size, $color = array(0,0,0), $word_space = 0.0, $char_space = 0.0, $angle = 0.0) {
     $pdf = $this->_pdf;
     
-    $pdf->setColor($color);
+    $pdf->setColor($color, true);
     
     $font .= ".afm";
     $pdf->selectFont($font);

--- a/lib/dompdf/include/image_cache.cls.php
+++ b/lib/dompdf/include/image_cache.cls.php
@@ -84,7 +84,7 @@ class Image_Cache {
           }
           else {
             set_error_handler("record_warnings");
+            $image = shezar_dompdf::file_get_contents($full_url);
-            $image = file_get_contents($full_url);
             restore_error_handler();
           }
   
diff --git a/lib/dompdf/include/image_frame_decorator.cls.php b/lib/dompdf/include/image_frame_decorator.cls.php
index e9d2497..b5a7983 100644
--- a/lib/dompdf/include/image_frame_decorator.cls.php
+++ b/lib/dompdf/include/image_frame_decorator.cls.php
@@ -53,7 +53,6 @@ class Image_Frame_Decorator extends Frame_Decorator {
 
     if ( Image_Cache::is_broken($this->_image_url) &&
          $alt = $frame->get_node()->getAttribute("alt") ) {
+      $this->_image_msg = '';
       $style = $frame->get_style();
       $style->width  = (4/3)*Font_Metrics::get_text_width($alt, $style->font_family, $style->font_size, $style->word_spacing);
       $style->height = Font_Metrics::get_font_height($style->font_family, $style->font_size);
diff --git a/lib/dompdf/include/image_renderer.cls.php b/lib/dompdf/include/image_renderer.cls.php
index bba9d07..561b701 100644
--- a/lib/dompdf/include/image_renderer.cls.php
+++ b/lib/dompdf/include/image_renderer.cls.php
@@ -18,10 +18,6 @@ class Image_Renderer extends Block_Renderer {
   function render(Frame $frame) {
     // Render background & borders
     $style = $frame->get_style();
+    if (Image_Cache::is_broken($frame->get_image_url())) {
+        $style->width = 32;
+        $style->height = 32;
+    }
     $cb = $frame->get_containing_block();
     list($x, $y, $w, $h) = $frame->get_border_box();

diff --git a/lib/dompdf/lib/res/html.css b/lib/dompdf/lib/res/html.css
index 2105f86..ca0179a 100644
--- a/lib/dompdf/lib/res/html.css
+++ b/lib/dompdf/lib/res/html.css
@@ -450,27 +450,6 @@ select option {
 select option[selected] {
   display: inline;
 }
-
-fieldset {
-  display: block;
-  margin: 0.6em 2px 2px;
-  padding: 0.75em;
-  border: 1pt groove #666;
-  position: relative;
-}
-
-fieldset > legend {
-  position: absolute;
-  top: -0.6em;
-  left: 0.75em;
-  padding: 0 0.3em;
-  background: white;
-}
-
-legend {
-  display: inline-block;
-}
-
 /* leafs */

 hr {
@@ -494,6 +473,8 @@ br {

 img, img_generated {
   display: -dompdf-image;
+  max-width: 100%;
+  height: auto;
 }

 dompdf_generated {
