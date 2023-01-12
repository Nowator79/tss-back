<?
use \Godra\Api\Admin\Settings;

require $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin.php";?>

<?
# Класс для работы с настройками
$settings = new Settings($bEdit);
?>

<form enctype="multipart/form-data" method="POST" action="<? echo($APPLICATION->GetCurPage()); ?>?mid=main&lang=<? echo(LANG); ?>" name="settings">
    <?
    $tabControl = new CAdminTabControl("tabControl", $settings->tabs);
    $tabControl->Begin();
    $tabControl->BeginNextTab();

    /** Тут основной код настроек */
    foreach ($settings->data as $key => $inputs)
        $settings->hr($key)."<tr>".$settings->inputs($inputs)."</tr>";
    /*************************** */

    $tabControl->BeginNextTab();
    $tabControl->EndTab();
    $tabControl->Buttons([
        "disabled" => $only_read,
        "back_url" => (strlen($back_url)>0 && strpos($back_url, "/bitrix/admin/fileman_file_edit.php")!==0) ? htmlspecialcharsbx($back_url) : "/bitrix/admin/fileman_admin.php?".$addUrl."&site=".Urlencode($site)."&path=".UrlEncode($arParsedPath["PREV"])
    ]);
    $tabControl->End();
    echo bitrix_sessid_post();
    ?>
</form>
<style>
    option[selected] {
        color: green;
        border: solid green 1px;
    }

    option:active {
        color: green;
    }
</style>
<?require $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php";?>