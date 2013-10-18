<?php

/*
packs all of the content of the directory into a single php file
when the single file is executed it then unpacks the content into that folder
*/

$fileName = "phpPacker.php";
$outFileName = "installer.php";
$nextFile = "";
$rootDir = "";

function getDirectoryList ($dir,$root) {
	$dirList = array();
	if ($handle = opendir($dir)) {
    		while (false !== ($entry = readdir($handle))) {
        		if ($entry != "." && $entry != "..") {
            			if (is_dir($dir.$entry)) {
					$resDir = $root."\\".$entry;
					$dirList[] = $resDir;
					$dirList = array_merge($dirList,getDirectoryList($dir.$entry."\\",$resDir));
				}
        		}
    		}
	}
    	closedir($handle);
	return $dirList;
}

function getFileList ($dir) {
	$fileList = array();
	if ($handle = opendir($dir)) {
    		while (false !== ($entry = readdir($handle))) {
        		if ($entry != "." && $entry != "..") {
            			if (!is_dir($dir."\\".$entry)) {
					$fileList[] = $dir."\\".$entry;
				}
        		}
    		}
	}
    	closedir($handle);
	return $fileList;
}

function getAllFilesList ($dirs) {
	$files = getFileList(realpath("."));
	$path = realpath(".");
	$pathLen = strlen($path);
	foreach ($dirs as $dir) {
		$files = array_merge($files,getFileList($path.$dir));
	}
	foreach ($files as &$file) {
		$file = substr($file,$pathLen);
	}
	return $files;
}

function packDirectory ($dir) {
	$dirList = array();
	if ($handle = opendir($dir)) {
    		while (false !== ($entry = readdir($handle))) {
        		if ($entry != "." && $entry != "..") {
            			if (is_dir($dir.$entry)) {
					$dirList[$entry] = packDirectory($dir.$entry."\\");
				} else {
					$dirList[$entry] = file_get_contents($dir.$entry);
				}
        		}
    		}
	}
    	closedir($handle);
	return $dirList;
}

function getUnpackerCode () {

	global $nextFile;
	global $rootDir;
	
	$unPackCodeLen = 370;
	$redirect = "";
	
	if (strlen($nextFile) > 0) {
		$nextFile = realpath(realpath(".").$nextFile);
		$scriptPath = realpath(dirname($_SERVER['SCRIPT_FILENAME']));
		$url = substr($nextFile,strlen($scriptPath)+1);
		if ($rootDir !== "\\") {
			$url = substr($url,strlen($rootDir));
		}
		$url = str_replace("\\","/",$url);
		$redirect = 'header("Location: '.$url.'");';
		$unPackCodeLen = $unPackCodeLen + strlen($redirect);
	}
	
	return '<?php function deTree($path,$data){if(is_array($data)){if(realpath(dirname($_SERVER["SCRIPT_FILENAME"]))!=$path){mkdir($path);}foreach($data as $key=>$value){$name=$path."\\\\".$key;deTree($name,$value);}}else{file_put_contents($path,$data);}}$data=file_get_contents($_SERVER["SCRIPT_FILENAME"]);deTree(realpath("."),unserialize(gzuncompress(substr($data,'.$unPackCodeLen.'))));'.$redirect.'exit();?>';
}

function startPacking ($dir) {
	
	global $outFileName;
	
	$outFileContent = getUnpackerCode();
	$outFileContent .= gzcompress(serialize(packDirectory($dir)), 9);
	file_put_contents($outFileName,$outFileContent);
}

function printPack () {

	global $outFileName;
	global $nextFile;
	global $rootDir;	

	$outFileName = $_POST['outFileName'];
	$nextFile = $_POST['nextFile'];
	$rootDir = $_POST['rootDir'];

	$path = realpath(".").$rootDir.($rootDir != "\\" ? "\\" : "");
	startPacking($path);

	echo "Your files have been packed. To unpack your files simply run the installer file that has been created. Thank you for using phpPacker!";
}

function printOptions () {
	
	global $fileName;
	global $outFileName;
	
	$dirList = getDirectoryList(realpath(".")."\\","");
	$fileList = getAllFilesList($dirList);
	
	echo "Welcome to phpPacker select the root directory for witch you would like to have an installer, 
		the name of the installer file then and the file that should be redirected to after 
		the installer unpacks then click pack!";
	echo "<form method='post' action='$fileName?a=pack'>";
	echo "<table width='100%'><tr><td class='contract'>";
	echo "<label for='outFileName'>Output File Name: </label>";
	echo "</td><td class='expand'>";
	echo "<input name='outFileName' value='".$outFileName."' />";
	echo "</td></tr><tr><td class='contract'>";
	echo "<label for='rootDir'>Root Directory: </label>";
	echo "</td><td class='expand'>";
	echo "<select name='rootDir' id='rootDir' onchange='filterNexFile()'>";
	echo "<option value='\\'>\\</option>";
	foreach ($dirList as $dir) {
		echo "<option value='$dir'>$dir</option>";
	}
	echo "</select>";
	echo "</td></tr><tr><td class='contract'>";
	echo "<label for='nextFile'>Next Redirect: </label>";
	echo "</td><td class='expand'>";
	echo "<select name='nextFile' id='nextFile'>";
	echo "<option value=''>(none)</option>";
	foreach ($fileList as $file) {
		echo "<option value='$file'>$file</option>";
	}
	echo "</select>";
	echo "</td></tr><tr><td></td><td>";
	echo "<button type='submit'>Pack</button>";
	echo "</td></tr></table>";
	echo "</form>";
	echo "</div>";
	echo "<script>
	function getSelectedValue (id) {
		var select = document.getElementById(id);
		return select.options[select.selectedIndex].value
	}
	function filterNexFile () {
		var select = document.getElementById('nextFile');
		select.selectedIndex = 0;
		var path = getSelectedValue('rootDir');
		for (var i=1; i<select.options.length; i+=1) {
			if (select.options[i].value.indexOf(path) == 0) {
				select.options[i].style.display = 'block';
			} else {
				select.options[i].style.display = 'none';
			}
		}
	}
	</script>";
}

function printTemplate ($viewFunc) {
	echo "<html><head>";
	echo "<style>
	body	  { font-family: Arial, Helvetica, sans-serif; }
	form      { margin-top: 20px; }
	td 	  { text-align: right; }
	label     { white-space: nowrap; } 
	.contract { width: 1%; } 
	.expand, input, select   { width: 100%; }
	.frame	  { margin: 50px; background-color: rgb(240,240,240); border: 1px dashed silver; }
	.content  { margin: 20px 20px 20px 20px; }
	.logo { font-size:large; text-align:right; margin-bottom: 20px; }
	</style>";
	echo "</head><body>";
	echo "<div class='frame'><div class='content'><div class='logo'>phpPacker</div>";	
	$viewFunc();
	echo "</div></div>";
	echo "</body></html>";
}

switch (isset($_GET['a']) ? $_GET['a'] : null) {
	case "pack":
		printTemplate("printPack");
		break;
	default:
		printTemplate("printOptions");
		break;
}

?>