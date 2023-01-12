<?
$text = file_get_contents($_SERVER['DOCUMENT_ROOT'].'/local/roadmap.todo');

$re = '/^(.*✔)(.*)(@done.*)/m';
$str = $text;
$subst = '<div style="display: inline-flex;">
            <div style="color:green; display: inline-flex">$1$2</div>
            <div style="color:#3c3e3c; display: inline-flex">$3</div>
         </div>';
$text = preg_replace($re, $subst, $str);

$re = '/^(.*☐)(.*)`(.*)`(.*)/m';
$str = $text;
$subst = '<div style="display: inline-flex;">
            <div style="display: inline-flex">$1$2</div>
            <div style="color:#dd4d1b; display: inline-flex">$3</div>
            <div style="display: inline-flex">$4</div>
         </div>';
$text = preg_replace($re, $subst, $str);

$re = '/^(.*:)$/m';
$str = $text;
$subst = '<div style="color:#5682a3; margin-bottom: -20px; font-weight: 510; font-size: 16px;">$1</div>';
$text = preg_replace($re, $subst, $str);



echo '<pre style="font-weight: 490; font-size: 14px; width: 50%; margin-left: 25%;">';
print_r($text);
echo '</pre>';

?>