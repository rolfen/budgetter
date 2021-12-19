<?php

// Create connection

$err = "";

$servername = "localhost";
$username = "budgetter";
$password = "budgetter";
$dbname = "budgeteer";
$conn = mysqli_connect($servername, $username, $password, $dbname);

$budget_id = 1;


// ************* Selected date **************

$curdate = mysqli_fetch_assoc(mysqli_query(
	$conn, 
	"SELECT CURDATE() as curdate"
))['curdate'];

$seldate = (  @$_GET['date'] ? (int)$_GET['date'] : $curdate );

// ************* Register new expense *************

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  // collect value of input field
  if (empty($_POST['amt'])) {
    $err = "Oops: No amount";
  } else {
	$sql = "INSERT INTO expense (amt, note, budget_id) VALUES (?,?,?)";
	$stmt= $conn->prepare($sql);
	$stmt->bind_param("dsi", $_POST['amt'], $_POST['note'], $budget_id);
	$stmt->execute();
  }
}


// ********** Summary ***********



$q = "
 SELECT 
 	DATEDIFF(NOW(), budget.start_date) + 1 as days, 
	budget.daily as daily_budget,
	SUM(expense.amt) as expenses,
	(SELECT SUM(amt) as sum FROM expense WHERE (DATE(date) = $seldate)) as todays_expenses
from budget JOIN expense on expense.budget_id = budget.id 
WHERE budget.id = $budget_id;
";

$q_daily = 	"SELECT amt, note  FROM expense WHERE DATE(expense.date) = $seldate AND budget_id = $budget_id";

$row = mysqli_fetch_assoc(mysqli_query($conn, $q));



?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<title></title>
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
				min-width: 100%;
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

	<h2><?= ($seldate == $curdate) ? "Today's expenses" : "Expense on ".$seldate ?></h2>



	<form method="post" action="<?php echo $_SERVER['PHP_SELF'];?>">
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
					<input type="number" step=.1 name="amt" placeholder="amount" min="0">
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