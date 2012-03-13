<?
$secret = "dupa231";
?>
<link rel="stylesheet" type="text/css" media="screen,projection" href="tpl/stdstyle/css/logbook.css" /> 
</style>
<script type="text/javascript">
//<![CDATA[

var cururl;
var curpage = 1;
var numpages = 1;

function startCallback() {
	// make something useful before submit (onStart)
	return true;
}

function setPreview(page) {
	document.getElementById('previewimg').src = cururl + "-page" + page + ".jpg";
	document.getElementById('previewimg').style.display = 'block';
	document.getElementById('previewblock').getElementsByTagName('span')[curpage-1].className = '';
	document.getElementById('previewblock').getElementsByTagName('span')[page-1].className = 'selected';
	curpage = page;
}

function bindArgument(fn, arg)
{
	return function () { return fn(arg); };
}

function removeChildrenFromNode(node)
{
	var len = node.childNodes.length;
	while (node.hasChildNodes()) {
		node.removeChild(node.firstChild);
	}
}

function updatePreview(newurl) {
	cururl = newurl;
	pages = document.getElementById('previewpages');
	removeChildrenFromNode(pages);

	for(var i = 1; i <= numpages; i++) {
	    var s = document.createElement('span');
	    s.innerHTML = i;
	    s.onclick = bindArgument(setPreview, i);
	    pages.appendChild(s);
	}
	setPreview(1);
}

function completeCallback(response) {
	document.getElementById('previewpageselect').style.visibility = 'visible';	
	var splitted = response.split(",");
	if(splitted[2] == 'pdf') {
		window.location = "logbook/" + splitted[1] + ".pdf";
	}
	else {
		numpages = splitted[0];
		updatePreview("logbook/" + splitted[1]);
	}
}

function onSubmit(obj) {
	if(obj.d1.value && obj.d2.value && obj.m1.value && obj.m2.value)
		obj.coords.value = "N " +
			obj.d1.value +
			"° " +
			obj.m1.value +
			"' E " +
			obj.d2.value +
			"° " + obj.m2.value + "'";

	return AIM.submit(obj, {'onStart' : startCallback, 'onComplete' : completeCallback});
}

//]]>
	</script>
<div id="previewblock">
<div id="previewpageselect">
Podgląd: 
<div id="previewpages"></div>
</div>
<div id="preview<?echo (int)$_GET['logbook_type'];?>
">
	<img src="" id="previewimg" alt="Logbook preview" />
</div>
</div>
<div>

<form method="get" action="logbook.php">
<label for="logbook_type">Rodzaj: </label>
<select name="logbook_type" id="logbook_type" onchange="parentNode.submit();">
<?
$_GET['logbook_type'] = (int)$_GET['logbook_type'];
if(!($_GET['logbook_type'] > 0 && (int)$_GET['logbook_type']<= 2)) print '<option value="0" selected="selected">Wybierz</option>';?>
<option value="1" <?if($_GET['logbook_type'] == 1) print "selected=\"selected\"";?>>Logbook A6 Dwustronny</option>
<option value="2" <?if($_GET['logbook_type'] == 2) print "selected=\"selected\"";?>>Logbook A7 Dwustronny</option>
</select>
</form>
<hr />
<?
if($_GET['logbook_type'] == 0) {
	print 'Proszę wybrać typ logbooku.';
}
else if($_GET['logbook_type'] == 1) {
print
	'<span class="note">Zostaw puste pola jeśli chcesz je wspisać później ręcznie lub też zostawić wartości standardowe.</span>
	<form id="logbookopts" action="logbook/remotelogbook.php" method="post" onsubmit="return onSubmit(this);" enctype="multipart/form-data">
	<input type="hidden" name="MAX_FILE_SIZE" value="1000000" />
	<input type="hidden" name="secret" value="' .encrypt($_GET['logbook_type'] . " This is a secret message", $secret) . '" />
		<p><label for="cache_name">Nazwa skrzynki:</label> <input type="text" maxlength="80" name="cache_name" id="cache_name" /></p>
		<p><label for="coords">Współrzędne:</label> <input type="hidden" maxlength="80" name="coords" id="coords" /> N <input type="text" name="d1" maxlength="3" class="degrees" /> ° <input class="minutes" maxlength="6" name="m1" type="text" /> \' E <input class="degrees" maxlength="3" name="d2" type="text" /> ° <input class="minutes" maxlength="6" name="m2" type="text" /> \'</p>
		<p><label for="image_file">Obrazek tytułowy:</label> <input type="file" name="image_file" id="image_file" class="file" /><br />
			<span class="posmod"> x <input type="text" name="x1" value="0.0" /> y <input type="text" name="y1" value="0.0" /> w <input type="text" name="w1" value="0.0"/> h <input type="text" name="h1" value="0.0"/></span>
		</p>
		<p><label for="image_file">Obrazek w tle:</label> <input type="file" name="bgimage_file" id="bgimage_file" class="file" /><br />

			<span class="posmod"> x <input type="text" name="x2" value="0.0" /> y <input type="text" name="y2" value="0.0" /> w <input type="text" name="w2" value="0.0"/> h <input type="text" name="h2" value="0.0"/> Widoczność <input type="text" name="opacity" value="20" />% </span>
<br />
<span class="note">Modyfikatory dla obrazka: np. -2 w polu <strong>x</strong> spowoduje przesunięcie o 2 piksele w lewo, a 4 w <strong>h</strong> zwiększy wysokośc o 4 piksele.</span>

</p>
		<p><label for="nick">Bez ozdób FTF:</label> <input type="checkbox" name="noftf" id="noftf" /></p>
<!--		<p><label for="nick">Bez ramek:</label> <input type="checkbox" name="noborders" id="noborders" />
			<span class="note">przydatny gdy jest trudnośc w równym wydrukowaniu obu stron, ucina jedynie na stronach nieparzystych</span></p>-->
		<p><label for="nick">Nick:</label> <input type="text" name="nick" id="nick" /></p>
		<p><label for="nick">E-Mail:</label> <input type="text" name="email" id="email" />
			<span class="note">wykorzystywany jest tylko do uzupełnienia pola w Log Booku</span></p>
		<p><input type="submit" onclick=\'document.getElementById("pdf").value = ""\'  name="gen_log" value="Podgląd" /><input type="submit" name="gen_log" onclick=\'document.getElementById("pdf").value = "pdf"\' value="Generuj PDF" /></p>
		<input type="hidden" name="pdf" id="pdf" />
	</form>
';
}
else if($_GET['logbook_type'] == 2) {
print
	'<span class="note">Zostaw puste pola jeśli chcesz je wspisać później ręcznie lub też zostawić wartości standardowe.</span>
	<form id="logbookopts" action="logbook/remotelogbook.php" method="post" onsubmit="return onSubmit(this);" enctype="multipart/form-data">
	<input type="hidden" name="MAX_FILE_SIZE" value="1000000" />
	<input type="hidden" name="secret" value="' .encrypt($_GET['logbook_type'] . " This is a secret message", $secret) . '" />
		<p><label for="cache_name">Nazwa skrzynki:</label> <input type="text" maxlength="80" name="cache_name" id="cache_name" /></p>
		<p><label for="coords">Współrzędne:</label> <input type="hidden" maxlength="80" name="coords" id="coords" /> N <input type="text" name="d1" maxlength="3" class="degrees" /> ° <input class="minutes" maxlength="6" name="m1" type="text" /> \' E <input class="degrees" maxlength="3" name="d2" type="text" /> ° <input class="minutes" maxlength="6" name="m2" type="text" /> \'</p>
		<p><label for="image_file">Obrazek tytułowy:</label> <input type="file" name="image_file" id="image_file" class="file" /><br />
			<span class="posmod"> x <input type="text" name="x1" value="0.0" /> y <input type="text" name="y1" value="0.0" /> w <input type="text" name="w1" value="0.0"/> h <input type="text" name="h1" value="0.0"/></span>
		</p>
		<p><label for="image_file">Obrazek w tle:</label> <input type="file" name="bgimage_file" id="bgimage_file" class="file" /><br />
<div>
			<span class="posmod"> x <input type="text" name="x2" value="0.0" /> y <input type="text" name="y2" value="0.0" /> w <input type="text" name="w2" value="0.0"/> h <input type="text" name="h2" value="0.0"/> Widoczność <input type="text" name="opacity" value="20" />% </span>
<br />
<span class="note">Modyfikatory dla obrazka: np. -2 w polu <strong>x</strong> spowoduje przesunięcie o 2 piksele w lewo, a 4 w <strong>h</strong> zwiększy wysokośc o 4 piksele.</span>
</div>
</p>
		<p><label for="nick">Bez ozdób FTF:</label> <input type="checkbox" name="noftf" id="noftf" /></p>
		<p><label for="nick">Bez ramek:</label> <input type="checkbox" name="noborders" id="noborders" />
			<span class="note">przydatny gdy jest trudnośc w równym wydrukowaniu obu stron, ucina jedynie na stronach nieparzystych</span></p>
		<p><label for="nick">Nick:</label> <input type="text" name="nick" id="nick" /></p>
		<p><label for="nick">E-Mail:</label> <input type="text" name="email" id="email" />
			<span class="note">wykorzystywany jest tylko do uzupełnienia pola w Log Booku</span></p>
		<p><input type="submit" onclick=\'document.getElementById("pdf").value = ""\'  name="gen_log" value="Podgląd" /><input type="submit" name="gen_log" onclick=\'document.getElementById("pdf").value = "pdf"\' value="Generuj PDF" /></p>
		<input type="hidden" name="pdf" id="pdf" />
	</form>
';
}
else {
	print 'Wrong logbook type!';
}
?>
</div>

