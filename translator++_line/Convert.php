<?php
require 'vendor/autoload.php';

set_time_limit(5000); //Reading excel can be long same for writing them, not too long but this can be removed

use PhpOffice\PhpSpreadsheet\Spreadsheet;

$directory = scandir("extract");
$translation = [];
$original_extracted = [];
foreach ($directory as $file) {
	if (is_file("extract/" . $file)) {
		$handle = fopen("extract/" . $file, "r");
		if ($handle) {
			if (str_contains("extract/" . $file, "_output") && is_file("extract/" . $file)) {
				while (($line = fgets($handle)) !== false) {
					array_push($translation, $line);
				}
			}
			if (!str_contains("extract/" . $file, "_output") && is_file("extract/" . $file)) {
				while (($line = fgets($handle)) !== false) {
					array_push($original_extracted, $line);
				}
			}
			fclose($handle);
		}
	}
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

$current_line = 0;

if ($REGEX_ACTIVATED) {
	$regex_match_temp = [];
	$new_regex = "";
	$all_regex_index = 0;
	$first_regex = true;
	foreach ($ALL_REGEX as $regex) {
		if (!$first_regex) {
			$regex = substr($regex, 1);
		} else {
			$first_regex = false;
		}
		if (count($ALL_REGEX) - 1 != $all_regex_index) {
			$regex = substr($regex, 0, -1);
		}
		$new_regex .= $regex;

		if (count($ALL_REGEX) - 1 > $all_regex_index) {
			$new_regex .= "|";
		}
		$all_regex_index++;
	}
}

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
	for ($e = $cell_number; $e <= $highestRow; $e++) {
		$cellB = $workSheet->getCell($SPREADSHEET_COLUMN . $e);
		if (empty((string) $cellB->getValue())) { //Check if Initial is empty otherwise a translation already exist
			$cellA = $workSheet->getCell('A' . $e);
			$value = (string) $cellA->getValue();

			$matches = "";
			$value = str_replace("　", "  ", $value);
			preg_match('/[\x{3000}-\x{303F}]|[\x{3040}-\x{309F}]|[\x{30A0}-\x{30FF}]|[\x{FF00}-\x{FFEF}]|[\x{4E00}-\x{9FAF}]|[\x{2605}-\x{2606}]|[\x{2190}-\x{2195}]|\x{203B}/u', $value, $matches, PREG_UNMATCHED_AS_NULL); //We only take japanese character if there none, no need to translate
			if (!empty($matches)) {

				$newLine = "";
				$index_line = 0;
				$temp = preg_replace('/\s\s+/', ' ', str_replace("\n", "||||", $value)); //Dialog box has multiple line but we want to seperate them in order to translate properly
				if (!empty($matches)) {
					$temp = trim(preg_replace('/\s\s+/', ' ', str_replace("\n", "||||", $value))); //Dialog box has multiple line but we want to seperate them in order to translate properly
					if (str_contains($temp, "||||")) {
						$temp = explode("||||", $temp);
						foreach ($temp as $line) {
							if ($REGEX_ACTIVATED) {
								$regex_match_temp = [];
								$line_contain_regex = preg_match_all($new_regex, $line, $regex_match);
								foreach ($regex_match as $j) {
									foreach ($j as $p) {
										array_push($regex_match_temp, $p); //We wante to combine all the match together
									}
								}
							}
							$line_temp = $line;
							if ($REGEX_ACTIVATED) {
								$line_temp = preg_replace($new_regex, "====", $line_temp);
							}
							if (str_contains($line_temp, "====")) {
								$start_with_regex = false;
								$end_with_regex = false;
								if (substr(trim($line_temp), 0, 4) == "====") {
									$start_with_regex = true;
								}
								if (substr(trim($line_temp), -4) == "====") {
									$end_with_regex = true;
								}
								$line_temp = explode("====", $line_temp);
								$index_temp_line = 0;
								$temp_line_index = 0;
								if ($start_with_regex) {
									array_shift($line_temp);
								}
								if ($end_with_regex) {
									array_pop($line_temp);
								}
								foreach ($line_temp as $temp_line) {
									preg_match('/[\x{3000}-\x{303F}]|[\x{3040}-\x{309F}]|[\x{30A0}-\x{30FF}]|[\x{FF00}-\x{FFEF}]|[\x{4E00}-\x{9FAF}]|[\x{2605}-\x{2606}]|[\x{2190}-\x{2195}]|\x{203B}/u', $original_extracted[$current_line], $matches, PREG_UNMATCHED_AS_NULL);
									if (empty($matches)) {
										$temp = $original_extracted[$current_line];
									} else {
										$temp = $translation[$current_line];
									}
									$line_temp[$index_temp_line] = trim(preg_replace('/\s\s+/', ' ', str_replace("\n", "", $temp)));
									$current_line++;
									$index_temp_line++;
									$temp_line_index++;
								}
								if ($start_with_regex) {
									array_unshift($line_temp, "");
								}
								if ($end_with_regex) {
									array_push($line_temp, "");
								}
								$temp = implode("====", $line_temp);
								for ($index_for_match = 0; $index_for_match < count($regex_match_temp); $index_for_match++) {
									$temp = preg_replace('/' . preg_quote("====", '/') . '/', $regex_match_temp[$index_for_match], $temp, 1);
								}
								$newLine .= trim(preg_replace('/\s\s+/', ' ', str_replace("\n", "", $temp)));
							} else {
								$temp = trim(preg_replace('/\s\s+/', ' ', str_replace("\n", "", $line)));

								if (!empty($temp)) {
									preg_match('/[\x{3000}-\x{303F}]|[\x{3040}-\x{309F}]|[\x{30A0}-\x{30FF}]|[\x{FF00}-\x{FFEF}]|[\x{4E00}-\x{9FAF}]|[\x{2605}-\x{2606}]|[\x{2190}-\x{2195}]|\x{203B}/u', $original_extracted[$current_line], $matches, PREG_UNMATCHED_AS_NULL);
									if (empty($matches)) {
										$temp = $original_extracted[$current_line];
									} else {
										$temp = $translation[$current_line];
									}
									$newLine .= trim($temp);
									$current_line++;
								}
							}
							$newLine .= "\n";
						}
						$newLine = substr_replace($newLine, '', -1);
					} else {
						$line = $temp;
						$line_temp = $line;
						if ($REGEX_ACTIVATED) {
							$regex_match_temp = [];
							$line_contain_regex = preg_match_all($new_regex, $line, $regex_match);
							foreach ($regex_match as $j) {
								foreach ($j as $p) {
									array_push($regex_match_temp, $p); //We wante to combine all the match together
								}
							}
							$line_temp = preg_replace($new_regex, "====", $line_temp);
						}
						if (str_contains($line_temp, "====")) {
							$start_with_regex = false;
							$end_with_regex = false;
							if (substr(trim($line_temp), 0, 4) == "====") {
								$start_with_regex = true;
							}
							if (substr(trim($line_temp), -4) == "====") {
								$end_with_regex = true;
							}
							$line_temp = explode("====", $line_temp);
							$index_temp_line = 0;
							$temp_line_index = 0;
							if ($start_with_regex) {
								array_shift($line_temp);
							}
							if ($end_with_regex) {
								array_pop($line_temp);
							}
							foreach ($line_temp as $temp_line) {
								preg_match('/[\x{3000}-\x{303F}]|[\x{3040}-\x{309F}]|[\x{30A0}-\x{30FF}]|[\x{FF00}-\x{FFEF}]|[\x{4E00}-\x{9FAF}]|[\x{2605}-\x{2606}]|[\x{2190}-\x{2195}]|\x{203B}/u', $original_extracted[$current_line], $matches, PREG_UNMATCHED_AS_NULL);
								if (empty($matches)) {
									$temp = $original_extracted[$current_line];
								} else {
									$temp = $translation[$current_line];
								}
								$line_temp[$index_temp_line] = trim(preg_replace('/\s\s+/', ' ', str_replace("\n", "", $temp)));
								$current_line++;
								$index_temp_line++;
								$temp_line_index++;
							}
							if ($start_with_regex) {
								array_unshift($line_temp, "");
							}
							if ($end_with_regex) {
								array_push($line_temp, "");
							}
							$temp = implode("====", $line_temp);
							for ($index_for_match = 0; $index_for_match < count($regex_match_temp); $index_for_match++) {
								$temp = preg_replace('/' . preg_quote("====", '/') . '/', $regex_match_temp[$index_for_match], $temp, 1);
							}
							$newLine .= trim(preg_replace('/\s\s+/', ' ', str_replace("\n", "", $temp)));
						} else {
							$temp = trim(preg_replace('/\s\s+/', ' ', str_replace("\n", "", $line)));

							if (!empty($temp)) {
								preg_match('/[\x{3000}-\x{303F}]|[\x{3040}-\x{309F}]|[\x{30A0}-\x{30FF}]|[\x{FF00}-\x{FFEF}]|[\x{4E00}-\x{9FAF}]|[\x{2605}-\x{2606}]|[\x{2190}-\x{2195}]|\x{203B}/u', $original_extracted[$current_line], $matches, PREG_UNMATCHED_AS_NULL);
								if (empty($matches)) {
									$temp = $original_extracted[$current_line];
								} else {
									$temp = $translation[$current_line];
								}
								$newLine .= trim($temp);
								$current_line++;
							}
						}
					}
					$workSheet->setCellValue($SPREADSHEET_COLUMN . $e, $newLine);
				}
			} else {
				$workSheet->setCellValue($SPREADSHEET_COLUMN . $e, (string) $cellA->getValue());
			}
		}
	}

	$writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, "Xlsx");
	$temp_file_name = explode("\\", substr($file, 5));
	array_pop($temp_file_name);
	$temp_file_name = implode("\\", $temp_file_name);

	if (!file_exists("output" . $temp_file_name)) {
		mkdir("output" . $temp_file_name, 0777, true);
	}
	$writer->save("output" . substr($file, 5)); //We make a copy file but saving in output instead
	unset($spreadsheet); //Do not forget to clear the spreadsheet after saving it
}

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
	echo "<p>Convertion done:</p>";
	echo "<p>Xlsx in output folder has been created </p>";
	?>
</body>

</html>