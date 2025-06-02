<?php
ob_start();
?><!DOCTYPE html>
<?php
		file_put_contents("./log/".uniqid().".json", JSON_ENCODE($_GET,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));


// server should keep session data for AT LEAST 1 hour
ini_set('session.gc_maxlifetime', 2000000);

// each client should remember their session id for EXACTLY 1 hour
session_set_cookie_params(2000000);
session_start();

$link = mysqli_connect("mysql01.nhl-data.dk", "scandinavianscou", "DHhoddg04!","scandinavianscouting_dk_www2") or die ("cannot connect to mysql\n");

if (!isset($_SESSION['login'])) {
	if (isset($_POST['username']) && isset($_POST['password']) && isset($_POST['organization'])) {
		// Check credentials against database
		$username = mysqli_real_escape_string($link, $_POST['username']);
		$password = mysqli_real_escape_string($link, $_POST['password']);
		$organization = mysqli_real_escape_string($link, $_POST['organization']);
		
		$query = "SELECT * FROM users WHERE username='$username' AND password='$password' AND organization='$organization' LIMIT 1";
		$result = mysqli_query($link, $query);
		
		if ($result && mysqli_num_rows($result) > 0) {
			$user = mysqli_fetch_assoc($result);
			$_SESSION['login'] = $user['username'];
			$_SESSION['organization'] = $user['organization'];
			header("Location: index.php");
			die();
		} else {
			$login_error = "Forkert brugernavn / password / organisation";
		}
	}
}
?>


<head>
	  <script src="https://cdn.tiny.cloud/1/ouol0ye5m8ykepfo20seshwer5npng06hjzxt9by41oyimyl/tinymce/5/tinymce.min.js" referrerpolicy="origin"></script>
	  <script type="text/javascript">
    tinyMCE.init({
      mode : "textareas",height : "700",plugins: "autoresize",branding: false,menubar:false
    });
    </script>
	<link rel="stylesheet" href="Trumbowyg-master/dist/ui/trumbowyg.min.css">

<!-- Import table plugin specific stylesheet -->
<link rel="stylesheet" href="Trumbowyg-master/dist/plugins/table/ui/trumbowyg.table.min.css">
  <title>SCANDINAVIANSCOUTING</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.0/css/bootstrap.min.css">
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.0/js/bootstrap.min.js"></script>
	  <link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">

  <script src="https://code.jquery.com/jquery-1.12.4.js"></script>
  <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
  <script>
		
  $( function() {
    $( "#datepicker" ).datepicker({ dateFormat: 'dd/mm/yy' });
		$( "#datepicker1" ).datepicker({ dateFormat: 'dd/mm/yy' });
  } );
  </script>
	<style>
		body
{
  margin: 0mm 0mm 0mm 2mm;
}
	</style>

</head>
<body>
<?php

if (!isset($_SESSION['login'])) {
	?>
	<style>
		.login-container {
			min-height: 100vh;
			display: flex;
			align-items: center;
			justify-content: center;
		}
		.login-card {
			width: 100%;
			max-width: 400px;
		}
	</style>
	<div class="login-container">
		<div class="container-fluid">
			<div class="row">
				<div class="col-12">
					<div class="card login-card mx-auto">
						<div class="card-header">
							<h3 class="text-center mb-0">SCANDINAVIANSCOUTING</h3>
							<p class="text-center text-muted mb-0">Login</p>
						</div>
						<div class="card-body">
							<?php if (isset($login_error)) { ?>
								<div class="alert alert-danger" role="alert">
									<?php echo $login_error; ?>
								</div>
							<?php } ?>
							<form action="index.php" method="post">
								<div class="form-group">
									<label for="organization">Organisation:</label>
									<input type="text" class="form-control" id="organization" name="organization" required>
								</div>
								<div class="form-group">
									<label for="username">Brugernavn:</label>
									<input type="text" class="form-control" id="username" name="username" required>
								</div>
								<div class="form-group">
									<label for="password">Password:</label>
									<input type="password" class="form-control" id="password" name="password" required>
								</div>
								<div class="form-group text-center">
									<button type="submit" class="btn btn-primary btn-block">Log ind</button>
								</div>
							</form>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	</body>
	</html>
	<?php
	die();
}

if (!isset($_GET['p'])) 
	menu(false);
else if ($_GET['p'] == "New player report" && !isset($_GET['id'])) // create brand new player
	spillerrapport(null);
else if ($_GET['p'] == "New player report" && isset($_GET['id'])) // update existing if id is specified
	spillerrapport($_GET['id']);
else if ($_GET['p'] == "New Match report" && !isset($_GET['id']))
	spillerrapport(null,"matchreport");
else if ($_GET['p'] == "New Match report" && isset($_GET['id']))
	spillerrapport($_GET['id'],"matchreport");

else if ($_GET['p'] == "Search playerreports")
	searchreport();
else if ($_GET['p'] == "Search Match reports")
	searchreport("matchreport");
	else if ($_GET['p'] == "SaveSpiller") {
		savereport($_GET,"spillerrapport");
	}
else if ($_GET['p'] == "SaveMatch") {
	savereport($_GET,"matchreport");
}
else if ($_GET['p'] == "signout") {
	session_destroy();
	header("Location: index.php");
	die();
}
else die("unhandled page '$_GET[p]'");

function savereport($data,$table) {
	$data['CreatedBy'] = $_SESSION['login'];
	$data['organization'] = $_SESSION['organization'];
	global $link;
	$p = $data['p'];
	unset ($data['p']);
	$id = $data['id'];
	unset ($data['id']);
	$colnames = "";
	$values = "";
	$i = 0;
	foreach ($data as $col => $val) {
		$val = mysqli_real_escape_string($link,$val);
		if ($i != 0) {
			$colnames .= ",";
			$values .= ",";
		}
		$colnames .= "$col";
		$values .= "'$val'";
		$i++;
	}
	if (intval($id) < 1) { // insert new
		$sql = "insert into $table ($colnames) values ($values)";
		
		mysqli_query($link,$sql) or die(mysqli_error($link));
		echo "<font color=green><h1>New report saved</h1></font>";?>
		 <input onclick="window.location.href='index.php'" type="button" class="btn btn-info" value="Tilbage til hovedmenu"><br><br>
		
		<?php ;die();
	}
	else { // update 
				$sql = "insert into $table ($colnames) values ($values)";
		mysqli_query($link,$sql) or die(mysqli_error($link));
		mysqli_query($link,"update $table set Deleted=1 where id=$id limit 1") or die(mysqli_error($link));
		if ($table == "spillerrapport") $returnpage = "Search playerreports"; else if ($table == "matchreport") $returnpage = "Search Match reports";
		header("location: index.php?p=$returnpage&filter_Deleted=0");
	}
}
function getval($array,$key) {
	if (isset($array[$key]))
		return $array[$key];
	else
		return "";
}
function spillerrapport($id,$table = "spillerrapport") {
	
	global $link;
	if (intval($id) > 0) {
		$organization = mysqli_real_escape_string($link, $_SESSION['organization']);
		$data = mysqli_fetch_assoc(mysqli_query($link,"select * from $table where id=$id and organization='$organization' limit 1")) or die(mysqli_error($link));
	}
	else {
		$data = array();
	}
	echo "<center>Tabel: $table</center>";
	if ($table == "spillerrapport")
		$cols = "Name,Lastname,Club,Birthdate,Gametype,Date,Team,Team2,Year,Result,TV / Live,Weather,Height,Leg,Position,Position2,Rating,Note,Gamereport";
	else if ($table == "matchreport")
		$cols= "Date,Matchtype,TeamA,TeamB,TV / Live,Årgang / Level,Resultat,Player noted,Player noted2,Player noted 3, Player noted 4, Player noted 5,Line up Team A,Line up Team B,Match report Team A,Match report Team B";
	$cols = explode(",",$cols);
	$i = 0;
	$y = 0;
	$zz = 0;
	if ($table == "spillerrapport") $returnpage = "SaveSpiller"; else if ($table == "matchreport") $returnpage = "SaveMatch";
echo "<table class='table table-sm'><tr>	<form action=index.php method=GET><input type=hidden name=p value=$returnpage><input type=hidden name=id value=$id>";
	foreach ($cols as $curcol) {
		
		if ($i == 4) {
			echo "</tr><tr>";
			$i = 0;
		}
/*		if ($y == 4) {
			echo "</tr></table><br><table class='table table-sm'><br>";
		}*/
		$sanecol = preg_replace('/[^A-Za-z0-9]/', '', $curcol);

?>
<td>
		<div>
<?php if ($curcol == "Dato") {?>
			  <label for="date-picker-example">Select game date</label>
			  <input value="<?php echo getval($data,$sanecol);?>" readonly name="<?php echo $sanecol;?>" placeholder="Kampdato" type="text" id='datepicker1' class="form-control datepicker">


		<?php
		}
		
		else if ($curcol == "TV / Live") {?>
				      <label for="sanecol"><?php echo $curcol;?></label>
								<select value="<?php echo getval($data,$sanecol);?>" class="form-control" name="<?php echo $sanecol;?>">
																		<option <?php if (getval($data,$sanecol) == "Not specified") echo " selected ";?>value="Not specified">Not specified</option>

        <option <?php if (getval($data,$sanecol) == "TV") echo " selected ";?>value="TV">TV</option>
        <option <?php if (getval($data,$sanecol) == "Live") echo " selected ";?>value="Live">Live</option>
      </select>
			<?php }
				 else if ($curcol == "Rating") {?>
				      <label for="sanecol"><?php echo $curcol;?></label>
								<select value="<?php echo getval($data,$sanecol);?>" class="form-control" name="<?php echo $sanecol;?>">
																		<option <?php if (getval($data,$sanecol) == "Not specified") echo " selected ";?> value="Not specified">Not specified</option>

        <option <?php if (getval($data,$sanecol) == "A") echo " selected ";?> value="A">A</option>
        <option <?php if (getval($data,$sanecol) == "B") echo " selected ";?> value="B">B</option>
					<option <?php if (getval($data,$sanecol) == "C") echo " selected ";?> value="C">C</option>				
      </select>
			<?php }
						 else if ($curcol == "Leg") {?>
				      <label for="sanecol"><?php echo $curcol;?></label>
								<select value="<?php echo getval($data,$sanecol);?>" class="form-control" name="<?php echo $sanecol;?>">
									<option <?php if (getval($data,$sanecol) == "Not specified") echo " selected ";?> value="Not specified">Not specified</option>
        <option <?php if (getval($data,$sanecol) == "Left") echo " selected ";?> value="Left">Left</option>
        <option <?php if (getval($data,$sanecol) == "Right") echo " selected ";?> value="Right">Right</option>
					<option <?php if (getval($data,$sanecol) == "Both legs") echo " selected ";?> value="Both legs">Both legs</option>				
      </select>
			<?php }
			else if ($curcol != "Match report Team A" && $curcol != "Match report Team B" && $curcol != "Note" && $curcol != "Gamereport" && $curcol != "Line up Team A" && $curcol != "Line up Team B") {?>
			<label for="sanecol"><?php echo $curcol;?></label><input type="text" value="<?php echo getval($data,$sanecol);?>" class="form-control" name="<?php echo $sanecol;?>" aria-describedby="<?php echo $curcol;?>" placeholder="Enter <?php echo $curcol;?>">
			<?php } 
			else {?></tr></table>
					<?php if ($table == "spillerrapport" && $zz == 1) {?><P style="page-break-before: always"><?php $zz++;}?>
			<table class="table table-sm" 						 >
				<textarea height=250 						 <?php if ($sanecol == "MatchreportTeamA" || $sanecol == "MatchreportTeamB") echo " style='display:none'";?>
 class=mceNoEaditor id="<?php echo $sanecol;?>" name="<?php echo $sanecol;?>"><?php 
						if (strlen(getval($data,$sanecol)) > 0)
							echo getval($data,$sanecol);
					else {
						
						if ($sanecol == "LineupTeamA"||$sanecol=="LineupTeamB") {
							echo "<table width=100%>";
						for ($i = 0;$i<15;$i++)
							echo "<tr><td width=75>&nbsp</td><td width=800>&nbsp</td><td width=75>&nbsp;</td></tr>";
						echo "</table>";
						}
					}
					?></textarea>

			<?php }?>
		</div>
</td>
	<?php
		$i++;
		$y++;
	}
	echo "</table>		<button id=gembutton type=\"submit\" class=\"btn btn-primary\">Gem</button>";
	echo "&nbsp;<button id=cancelbutton type=\"button\" class=\"btn btn-secondary\" onclick=\"cancelReport()\">Annuller</button></form>";
			menu(false);

	print_report();

}

function print_report() {
	?><script>
		
		$("textarea").height( $("textarea")[0].scrollHeight );
	function printpage() {

    //Get the print button and put it into a variable
    var printButton = document.getElementById("printpagebutton");
    var postButton = document.getElementById("menu");
		var gemButton = document.getElementById("gembutton");
		var cancelButton = document.getElementById("cancelbutton");



    //Set the button visibility to 'hidden' 


    printButton.style.visibility = 'hidden';
    postButton.style.visibility = 'hidden';
		gemButton.style.visibility = 'hidden';
		if (cancelButton) cancelButton.style.visibility = 'hidden';

	
    //Print the page content
    window.print()

    //Restore button visibility
    printButton.style.visibility = 'visible';
    postButton.style.visibility = 'visible';
		gemButton.style.visibility = 'visible';
		if (cancelButton) cancelButton.style.visibility = 'visible';



	}

	function cancelReport() {
		if (confirm("Er du sikker på at du vil annullere? Alle ugemte ændringer vil gå tabt.")) {
			window.location.href = "index.php";
		}
	}</script>

<input id="printpagebutton" class="btn btn-info" type="button" value="Print" onclick="printpage()"/>
<?php }
function searchreport($table = "spillerrapport") {

	if (!isset($_GET['filter_Deleted']))
			$_GET['filter_Deleted'] = 0;
	menu(false);
	global $link;
	$organization = mysqli_real_escape_string($link, $_SESSION['organization']);
	$q = "select * from $table where organization='$organization' order by id desc";
	$res = mysqli_query($link,$q) or die(mysqli_error($link));
	$i = 0;
	echo "<table class=\"table table-hover\"><form action=index.php><input type=hidden name=p value='$_GET[p]'>";
	while ($data = mysqli_fetch_assoc($res)) {
		echo "<tr>";
		if ($i == 0) {
			foreach (array_keys($data) as $key) { 
				if ($key != "id" && $key != "organization") echo "<td>$key</td>";
			}
			echo "</tr><tr>";
						foreach (array_keys($data) as $key) {
										if (isset($_GET["filter_" . $key]))
											$fk = $_GET["filter_" . $key];
										else
											$fk = "";
										
										if ($key != "id" && $key != "organization") echo "<td><input class=\"form-control\" type=text name=filter_$key value=\"$fk\"></td>\n";
						}
		}
		if (true) {
			echo "<tr>";
			$o = "";
			$show = true;
			foreach ($data as $key=>$val) {
				if (isset($_GET['filter_' . $key])) {
					$filter = $_GET['filter_' . $key];
					if (strlen($filter) > 0) {
						if (stristr($val,$filter) || $val == $filter)
							$show = true;
						else {
							$show = false;
							break;
						}
					}
				}
				$vs = substr(strip_tags($val),0,25);
				if ($table == "spillerrapport") $editlink = "New player report"; else if ($table == "matchreport") $editlink = "New Match report";
				if ($key != "id" && $key != "organization") $o .= "<td><a href=\"index.php?p=$editlink&id=$data[id]\">". ($vs) . "</a></td>\n";

			}
			if ($show)
				echo $o;
		}
		echo "</tr>";

		$i++;
	}
			echo "</table><input type=submit value=Søg></form>";
}
function menu($vertical = true) {
	echo "<div id=menu><center>";
	echo "<h3>Menu</h3>";
	echo "<p>Logged in as: " . $_SESSION['login'] . " (" . $_SESSION['organization'] . ")</p>";
	$buttons = array("New Match report","New player report","Search playerreports","Search Match reports");
	foreach ($buttons as $button) {
	?>
  <input onclick="window.location.href='index.php?p=<?php echo $button;?>'" type="button" class="btn btn-info" value="<?php echo $button;?>">



<?php if ($vertical) echo "<br><br>"; else echo "&nbsp;";
	}
	?>
  <input onclick="window.location.href='index.php?p=signout'" type="button" class="btn btn-warning" value="Log ud">
<?php
	echo "<br></center></div>";
}
?>
<!-- Import jQuery -->
<script src="//ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
<script>window.jQuery || document.write('<script src="js/vendor/jquery-3.3.1.min.js"><\/script>')</script>
<?php
//ob_end_flush();die();
$replace = array("Player noted2"=>"Weather","Player noted 3"=>"Note 1","Player noted 4"=>"Note 2","Player noted 5"=>"Note 3","Player noted"=>"Pitch");
$ob = ob_get_contents();
foreach ($replace as $rep => $with) {
	$ob = str_replace($rep,$with,$ob);
}
ob_end_clean();
echo $ob;?>