<?
IncludeModuleLangFile(__FILE__);

if (!class_exists("TOP10_USERTYPE_USER")) {
	class TOP10_USERTYPE_USER {
		function GetUserTypeDescription() {
			return array(
				"USER_TYPE_ID"	=> "top10usertypeuser",
				"CLASS_NAME"	=> "TOP10_USERTYPE_USER",
				"DESCRIPTION"	=> GetMessage("TOP10_PROP_NAME"),
				"BASE_TYPE"		=> "int",
			);
		}

		function GetDBColumnType($arUserField) {
			global $DB;

			switch(strtolower($DB->type)) {
				case "mysql":	return "int(1)";
				case "oracle":	return "number(1)";
				case "mssql":	return "int";
			}
		}

		function PrepareSettings($arUserField) {
			return array();
		}

		function GetSettingsHTML($arUserField = false, $arHtmlControl, $bVarsFromForm) {
			return "";
		}

		function GetEditFormHTML($arUserField, $arHtmlControl) {
			$sField = FindUserID(
				$arUserField["FIELD_NAME"],		// ��� ���� ��� ����� ID ������������
				$arUserField["VALUE"],			// �������� ���� ��� ����� ID ������������
				"",								// ID, �����, ��� � ������� ������������, ��������� ����� � ����� ��� ����� ID ������������, ����� �� ����� �������� ��������
				"hlrow_edit_".$_REQUEST["ENTITY_ID"]."_form",//"user_edit_form",				// ��� �����, � ������� ��������� ���� ��� ����� ID ������������
				"5",							// ������ ���� ��� ����� ID ������������
				"",								// ������������ ���������� �������� � ���� ��� ����� ID ������������
				" ... ",						// ������� �� ������ ������� �� �������� ������ ������������
				"",								// CSS ����� ��� ���� ����� ID ������������
				""								// CSS ����� ��� ������ ������� �� �������� ������ ������������
			);

			return $sField;
		}

		function GetFilterHTML($arUserField, $arHtmlControl) {
			return '';
		}

		function GetAdminListViewHTML($arUserField, $arHtmlControl) {
			preg_match("/FIELDS\[([0-9]+)\]/", $arHtmlControl["NAME"], $a);

			if ($a[1] > 0) {
				require_once $_SERVER["DOCUMENT_ROOT"] . '/bitrix/modules/main/tools/prop_userid.php';

				return CIBlockPropertyUserID::GetAdminListViewHTML(Array(), $arHtmlControl, "");
			}

			return "&nbsp;";
		}

		function GetAdminListEditHTML($arUserField, $arHtmlControl) {
			$sField = FindUserID(
				$arHtmlControl["NAME"],			// ��� ���� ��� ����� ID ������������
				$arHtmlControl["VALUE"],		// �������� ���� ��� ����� ID ������������
				"",								// ID, �����, ��� � ������� ������������, ��������� ����� � ����� ��� ����� ID ������������, ����� �� ����� �������� ��������
				"form_tbl_user",				// ��� �����, � ������� ��������� ���� ��� ����� ID ������������
				"5",							// ������ ���� ��� ����� ID ������������
				"",								// ������������ ���������� �������� � ���� ��� ����� ID ������������
				" ... ",						// ������� �� ������ ������� �� �������� ������ ������������
				"",								// CSS ����� ��� ���� ����� ID ������������
				""								// CSS ����� ��� ������ ������� �� �������� ������ ������������
			);

			return $sField;
		}

		function CheckFields($arUserField, $value) {
			$aMsg = array();

			return $aMsg;
		}

		function OnSearchIndex($arUserField) {
			return "";
		}
	}
}