<?php
session_start();
require_once "db_connection.php";

// Check if the user is not logged in
if (!isset($_SESSION["username"])) {
    header("Location: login.php");
    exit();
}

// Logout handling
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["logout"])) {
    // Destroy the session and redirect to the login page
    session_destroy();
    header("Location: login.php");
    exit();
}

$conn = $connection;
if (isset($_SESSION["cpf_no"])) {
    $cpf_no = $_SESSION["cpf_no"];
}

// Get the designation of the user
$query = "SELECT * FROM employee WHERE cpfno = '$cpf_no'";
$result = mysqli_query($connection, $query);
if (!$result || mysqli_num_rows($result) == 0) {
    header("Location: form.php");
    exit();
}
$user2 = mysqli_fetch_assoc($result);
$designation = $user2["designation"];

// Check if the user clicked on the collector link
if (($designation == "E") && isset($_GET['orderno'])) {
    $_SESSION['orderno'] = $_GET['orderno'];
    header("Location: collector-page.php");
    exit();
}

// Set the session variable 'isEditable' and redirect to form.php for "New Order" button
if ($designation == "store_keeper" && isset($_POST['new_order'])) {
    $_SESSION['isEditable'] = false;
    header("Location: form.php");
    exit();
}

// Set the session variable 'isEditable' and redirect to form.php for "Edit" button
if ($designation == "store_keeper" && isset($_POST['edit_order'])) {
    $_SESSION['isEditable'] = true;
    header("Location: form.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Dashboard</title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
        <button type="submit" name="logout">Logout</button>
    </form>
    <table>
        <tr>
            <td><img src="assets\images.png" class="logo"></td>
            <td>
                <h1>Oil and Natural Gas Corporation</h1>
                <h3>MUMBAI REGION- REGIONAL OFFICE- INFOCOM</h3>
            </td>
        </tr>
    </table>
    <h3>Dashboard</h3>
    <?php if ($designation == "store_keeper") : ?>
        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <button type="submit" name="new_order">New Order</button>
        </form>
    <?php endif; ?>

    <?php
    // Retrieve data from the "order_no" table
    if ($designation == "collector") {
        $query = "SELECT orderno, order_dest, issue_desc, placeoi, issueto, returnable, coll_approval, security_approval FROM order_no WHERE coll_approval = 0 AND security_approval != -1 AND forwarded_to = '{$cpf_no}'";
    } else if ($designation == "security") {
        $query = "SELECT orderno, order_dest, issue_desc, placeoi, issueto, returnable, coll_approval, security_approval FROM order_no WHERE security_approval = 0 AND coll_approval != -1";
    } else {
        $query = "SELECT orderno, order_dest, issue_desc, placeoi, issueto, returnable, coll_approval, security_approval FROM order_no";
    }
    $result = mysqli_query($connection, $query);

    // Check if the query was successful
    if ($result && mysqli_num_rows($result) > 0) {
        // Display the data in a table
        echo "<table>";
        echo "<tr><th>Order No</th><th>Order Destination</th><th>Issue Description</th><th>Place of Issue</th><th>Issue To</th><th>Returnable</th>";
        if ($designation == "collector" || $designation == "security")
            echo "<th>Action<th></tr>";
        else echo "<th>Status</th></tr>";
        while ($row = mysqli_fetch_assoc($result)) {
            echo "<tr>";
            echo "<td>" . $row['orderno'] . "</td>";
            echo "<td>" . $row['order_dest'] . "</td>";
            echo "<td>" . $row['issue_desc'] . "</td>";
            echo "<td>" . $row['placeoi'] . "</td>";
            echo "<td>" . $row['issueto'] . "</td>";
            echo "<td>" . ($row['returnable'] ? 'Yes' : 'No') . "</td>";
            if ($designation == "collector")
                echo "<td><a href='skdash.php?orderno=" . $row['orderno'] . "'>Collector Link</a></td>";
            if ($designation == "security")
                echo "<td><a href='skdash.php?orderno=" . $row['orderno'] . "'>Security Link</a></td>";
            if ($designation == "store_keeper") {
                if ($row['coll_approval'] == -1 || $row['security_approval'] == -1)
                    echo '<td><button type="submit" name="edit_order">Edit</button></td>';
                else if ($row['coll_approval'] == 1 && $row['security_approval'] == 1)
                    echo '<td>Approved</td>';
                else if ($row['coll_approval'] == 0 || $row['security_approval'] == 0)
                    echo '<td>Pending</td>';
            }
            echo '</tr>';
        }

        echo "</table>";
    } else {
        echo "No records found.";
    }
    // Close the database connection
    mysqli_close($connection);
    ?>
</body>
</html>
