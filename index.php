<?php

// Create connection

define('SERVERNAME', "localhost");
define('USERNAME', "budgetter");
define('PASSWORD', "budgetter");
define('DBNAME', "budgeteer");
define('DB_EXPENSES_TABLE', "expense");


$err = $dbg = [];

function log_err($error) {
	global $err;
	array_push($err, $error);
	return true;
}

function debug($info) {
	global $dbg;
	array_push($dbg, $info);
	return true;
}

$conn = mysqli_connect(SERVERNAME, USERNAME, PASSWORD, DBNAME);

$budget_id = 1; // eek

// ************* Selected date **************

// selected date is today by default. 

$curdate = mysqli_fetch_assoc(mysqli_query(
	$conn, 
	"SELECT CURDATE() as curdate"
))['curdate'];

$seldate = (  @$_GET['date'] ? $_GET['date'] : $curdate );

$cururl = $_SERVER['PHP_SELF']."?date=$seldate";

// ************* cruD **************


@$_GET['delete'] 
and ( 
	mysqli_query($conn, $q = sprintf(
		"DELETE FROM `%s` WHERE `id` = %d",
		 DB_EXPENSES_TABLE, $_GET['delete'] 
	)) 
	or log_err(mysqli_error($conn)) 
)
;


// ************* Register new expense *************

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  // collect value of input field
  if (empty($_POST['amt'])) {
    log_err([
    	'line' => __LINE__,
    	'message' => "Amount is missing"
    ]);
  } else {
		$sql = sprintf("INSERT INTO `%s` (amt, note, budget_id, `date`) VALUES (?,?,?,DATE(?))", DB_EXPENSES_TABLE);
		$stmt= $conn->prepare($sql);
		$stmt->bind_param("dsis", $_POST['amt'], $_POST['note'], $budget_id, $seldate);
		$stmt->execute() or log_err([
			'line' => __LINE__,
			'message' => $stmt->error
		]);
  }
}


// ********** Summary ***********



$q = sprintf("
 SELECT 
 	DATEDIFF(NOW(), budget.start_date) + 1 as days, 
	budget.daily as daily_budget,
	SUM(expense.amt) as expenses,
	(SELECT SUM(amt) as sum FROM %s WHERE (DATE(date) = $seldate)) as todays_expenses
from budget JOIN expense on expense.budget_id = budget.id 
WHERE budget.id = $budget_id;
", DB_EXPENSES_TABLE);

$q_daily = 	sprintf("SELECT amt, note  FROM %s WHERE DATE(`date`) = '$seldate' AND budget_id = $budget_id", DB_EXPENSES_TABLE);

debug($q_daily);

$row = mysqli_fetch_assoc(mysqli_query($conn, $q));



?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<title></title>
	<script type="text/javascript">
		console.dir(<?= json_encode($err) ?>);
		console.dir(<?= json_encode($dbg) ?>);
	</script>
	<style>
			table.daily td {
				padding: 5px;
				border: 1px solid rgba(0,0,0,.3);
			}
			table.daily td textarea,
			table.daily td input {
				box-sizing: 	border-box;
				border: 0;
				padding: 0;
				min-height: 	3em;
			}
	</style>
</head>
<body>

	<h2>Budget</h2>

	<p>Days passed: <?= $row['days'] ?></p>
	<p>Daily budget: <?= $row['daily_budget'] ?></p>
	<p>Cumulative expenses: <?= $row['expenses'] ?></p>
	<p>Balance: <?= ($row['days'] * $row['daily_budget']) - $row['expenses'] ?></p>

	<?php
	if ($seldate == $curdate) {
		echo "<h2>Today's expenses</h2>";
	} else {
		echo  "<a href=\"".$_SERVER['PHP_SELF']."?date=$curdate"."\">Goto today</a>";
		echo "<h2>Expenses on $seldate</h2>";
	}
	?>
	

	



	<form method="post" action="<?php echo $cururl ;?>">
		<table class="daily">
			<?php foreach (mysqli_query($conn, $q_daily)->fetch_all(MYSQLI_ASSOC) as $key => $expense): ?>
			<tr>
				<td>
					<?=	$expense['amt'] ?>
				</td>
				<td>
					<pre><?=	$expense['note'] ?></pre>
				</td>
				<td>
					<input type="button" value="Change">
				</td>				
			</tr>
			<?php endforeach ?>
			<tr>
				<td>
					<input type="number" step=.1 name="amt" placeholder="amount" >
				</td>
				<td>
					<textarea name="note" placeholder="note"></textarea>
				</td>
				<td>
					<input type="submit" value="Add">
				</td>
			</tr>
		</table>
	</form>

	<p>Total: <?= $row['todays_expenses'] ? $row['todays_expenses'] : 0  ?></p>


</body>
</html>