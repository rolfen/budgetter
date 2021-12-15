<?php

// Create connection

$err = "";

$servername = "localhost";
$username = "budgetter";
$password = "budgetter";
$dbname = "budgeteer";
$conn = mysqli_connect($servername, $username, $password, $dbname);

$budget_id = 1;


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
	(SELECT SUM(amt) as sum FROM expense WHERE (DATE(date) = CURDATE())) as todays_expenses
from budget JOIN expense on expense.budget_id = budget.id 
WHERE budget.id = $budget_id;
";

$q_daily = 	"SELECT amt, note FROM expense WHERE DATE(expense.date) = CURDATE() AND budget_id = $budget_id";

$row = mysqli_fetch_assoc(mysqli_query($conn, $q));



?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<title></title>
</head>
<body>

	<h2>Budget</h2>

	<p>Days passed: <?= $row['days'] ?></p>
	<p>Daily budget: <?= $row['daily_budget'] ?></p>
	<p>Cumulative expenses: <?= $row['expenses'] ?></p>
	<p>Balance: <?= ($row['days'] * $row['daily_budget']) - $row['expenses'] ?></p>

	<h2>Today</h2>



	<form method="post" action="<?php echo $_SERVER['PHP_SELF'];?>">
		<table>
			<?php foreach (mysqli_query($conn, $q_daily)->fetch_all(MYSQLI_ASSOC) as $key => $expense): ?>
			<tr>
				<td>
					<?=	$expense['amt'] ?>
				</td>
				<td>
					<?=	$expense['note'] ?>
				</td>
				<td>
					<input type="button" value="Change">
				</td>				
			</tr>
			<?php endforeach ?>
			<tr>
				<td>
					<input type="number" name="amt" placeholder="amount" min="0">
				</td>
				<td>
					<input type="text" name="note" placeholder="note">
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