<?php
require 'vendor/autoload.php';

set_time_limit(5000); //Reading excel can be long same for writing them, not too long but this can be removed

use PhpOffice\PhpSpreadsheet\Spreadsheet;

if (!file_exists('setting.json')) { //If setting file doesn't exist, will create it here with default value
	$setting["number_line"] = 5000;
	$setting["number_line_description"] = "By default number_line is at 5000, if you want a smaller amount of file simply put a higher number or the reverse for more file";
	$setting["number_padding"] = 4;
	$setting["number_padding_description"] = "By default number_padding is at 4, if you have more than 9999 files extracted you need to put a higher padding number";
	$setting["spread_sheet_header"] = true;
	$setting["spread_sheet_header_description"] = "By default spread_sheet_header is at true as you have one by default, if you have no heading (description like orginal text,initial, better translation... at the first line Set it to false";
	$setting["regex_activated"] = true;
	$setting["regex_activated_description"] = "By default regex_activated is at true as you have one by default, if you want no regex trying to change anything you can disable but it may give weird result or error when there is code, you can set it to false";
	$setting["all_regex"] = ["/(\\\\[a-zA-Z]\\[([a-zA-Z]|[0-9]){1,}\\])/"];
	$setting["all_regex_description"] = "I writen one for my sake, you can test your if you type regex online, important note that on php if your regex expression has \ , you must add another like this \\ or it will give an error, you must have a delimiter like / at the start and the end. to add it, put , after the \" and write between \" that you add";
	$setting["spreadsheet_column"] = "B";
	$setting["spreadsheet_column_description"] = "Where it should put the translated value, if it also check if there exist one already there";
	file_put_contents("setting.json", json_encode($setting, JSON_PRETTY_PRINT)); //make pretty so user can easily read it
	echo "Created setting file";
}
$setting = file_get_contents("setting.json");
$setting = json_decode($setting);

$PADDING_NUMBER = '%0' . $setting->number_padding . 'd';
$NUMBER_OF_LINE = $setting->number_line;
$SPREAD_SHEET_HEADER = $setting->spread_sheet_header;
$REGEX_ACTIVATED = $setting->regex_activated;
$ALL_REGEX = $setting->all_regex;
$SPREADSHEET_COLUMN = $setting->spreadsheet_column;

$list_of_excel_file = [];

$list_of_excel_file = dirToOptions("input", 0, $list_of_excel_file);

$data = [];

foreach ($list_of_excel_file as $file) {
	$spreadsheet = new Spreadsheet();
	$spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file);
	$workSheet = $spreadsheet->getActiveSheet();
	if ($SPREAD_SHEET_HEADER) {
		$cell_number = 2;
	} else {
		$cell_number = 1;
	}

	$highestRow = $spreadsheet->getActiveSheet()->getHighestRow();
	for ($i = $cell_number; $i <= $highestRow; $i++) {
		$cellB = $workSheet->getCell($SPREADSHEET_COLUMN . $i);
		if (empty((string) $cellB->getValue())) { //Check if Initial is empty otherwise a translation already exist
			$cellA = $workSheet->getCell('A' . $i);
			array_push($data, (string) $cellA->getValue());
		}
	}
	unset($spreadsheet); //Do not forget to clear the spreadsheet after reading it
}

$currentLine = 0;
$indexFile = 1;
if (!file_exists('extract')) {
	mkdir('extract', 0777, true);
}
if (!file_exists('input')) {
	mkdir('input', 0777, true);
}
if (!file_exists('output')) {
	mkdir('output', 0777, true);
}

$file = fopen('extract/extracted' . sprintf($PADDING_NUMBER, $indexFile) . '.txt', 'w');
foreach ($data as $key => $value) {
	if ($currentLine >= $NUMBER_OF_LINE) {
		$currentLine = 0;
		fclose($file);
		//Remove white space sugpi translation give weird result when translating white space
		$corrected = file_get_contents('extract/extracted' . sprintf($PADDING_NUMBER, $indexFile) . '.txt');
		$corrected = rtrim($corrected);
		file_put_contents('extract/extracted' . sprintf($PADDING_NUMBER, $indexFile) . '.txt', $corrected);

		$indexFile++;
		$file = fopen('extract/extracted' . sprintf($PADDING_NUMBER, $indexFile) . '.txt', 'w');
	}
	$matches = "";
	$value = str_replace("　", "  ", $value);
	preg_match('/[\x{3000}-\x{303F}]|[\x{3040}-\x{309F}]|[\x{30A0}-\x{30FF}]|[\x{FF00}-\x{FFEF}]|[\x{4E00}-\x{9FAF}]|[\x{2605}-\x{2606}]|[\x{2190}-\x{2195}]|\x{203B}/u', $value, $matches, PREG_UNMATCHED_AS_NULL); //We only take japanese character if there none, no need to translate
	if (!empty($matches)) {
		$temp = trim(preg_replace('/\s\s+/', ' ', str_replace("\n", "||||", $value))); //Dialog box has multiple line but we want to seperate them in order to translate properly
		$list_regex = [];
		if (str_contains($temp, "||||")) {
			if ($REGEX_ACTIVATED) {
				foreach ($ALL_REGEX as $regex) {
					$temp = preg_replace($regex, "====", $temp);
				}
			}
			$temp = explode("||||", $temp);
			foreach ($temp as $line) {
				if (str_contains($line, "====")) {
					$line = explode("====", $line);
					foreach ($line as $temp_line) {
						$temp = trim(preg_replace('/\s\s+/', ' ', str_replace("\n", "", $temp_line)));
						if (!empty($temp)) {
							fwrite($file, $temp . PHP_EOL);
							$currentLine++;
						}
					}
				} else {
					$temp = trim(preg_replace('/\s\s+/', ' ', str_replace("\n", "", $line)));
					if (!empty($temp)) {
						fwrite($file, $temp . PHP_EOL);
						$currentLine++;
					}
				}
			}
		} else {
			if ($REGEX_ACTIVATED) {
				foreach ($ALL_REGEX as $regex) {
					$temp = preg_replace($regex, "====", $temp);
				}
			}
			if (str_contains($temp, "====")) {
				$line = explode("====", $temp);
				foreach ($line as $temp_line) {
					$temp = trim(preg_replace('/\s\s+/', ' ', str_replace("\n", "", $temp_line)));
					if (!empty($temp)) {
						fwrite($file, $temp . PHP_EOL);
						$currentLine++;
					}
				}
			} else {
				fwrite($file, $value . PHP_EOL);
				$currentLine++;
			}
		}
	}
}

fclose($file);

//Remove white space sugpi translation give weird result when translating white space
$corrected = file_get_contents('extract/extracted' . sprintf($PADDING_NUMBER, $indexFile) . '.txt');
$corrected = rtrim($corrected);
file_put_contents('extract/extracted' . sprintf($PADDING_NUMBER, $indexFile) . '.txt', $corrected);

function dirToOptions($path = __DIR__, $level = 0, $list_of_excel_file)
{ //Recursive to get all xlsx file
	$items = scandir($path);
	foreach ($items as $item) {
		// ignore items strating with a dot (= hidden or nav)
		if (strpos($item, '.') === 0) {
			continue;
		}
		$fullPath = $path . DIRECTORY_SEPARATOR . $item;
		// file
		if (is_file($fullPath)) {
			if (strlen($item) >= 5) {
				if (substr($item, -4) == "xlsx") { //Correct file type
					array_push($list_of_excel_file, $fullPath);
				}
			}
		}
		// dir
		else if (is_dir($fullPath)) {
			// recursive call to self to add the subitems
			$list_of_excel_file = dirToOptions($fullPath, $level + 1, $list_of_excel_file);
		}
	}
	return $list_of_excel_file;
}

?>
<html>

<body>
	<?php
	echo "<p>Current settings:</p>";
	echo "<p>Number of lines extracted: " . $NUMBER_OF_LINE . "</p>";
	echo "<p>Number of padding extracted: " . $setting->number_padding . "</p>";
	echo "<p>Extraction of process Done</p>";
	echo "<p>Resulted in a number of files: " . $indexFile . " extracted</p>";
	?>
</body>

</html>