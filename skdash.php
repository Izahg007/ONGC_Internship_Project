<?php
session_start();
require_once "db_connection.php";

// Check if the user is not logged in
if (!isset($_SESSION["username"]) && !isset($_SESSION["phone_no"])) {
    header("Location: newlogin.php");
    exit();
}


// Logout handling
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["logout"])) {
    // Destroy the session and redirect to the login page
    session_destroy();
    header("Location: newlogin.php");
    exit();
}

$conn = $connection;
if (isset($_SESSION["cpf_no"])) {
    $cpf_no = $_SESSION["cpf_no"];
}
if (isset($_SESSION['fsuccess'])) {
    if ($_SESSION['fsuccess']) {
        echo "<script>alert('Order Submitted Successfully');</script>";
        $_SESSION['fsuccess'] = false;
    }
}
if (isset($_SESSION['esuccess'])) {
    if ($_SESSION['esuccess']) {
        echo "<script>alert('Order Edited Successfully');</script>";
        $_SESSION['esuccess'] = false;
    }
}
if (isset($_SESSION['cantedit'])) {
    if ($_SESSION['cantedit']) {
        echo "<script>alert('Order Can't be Edited');</script>";
        $_SESSION['cantedit'] = false;
    }
}
if (isset($_SESSION['asuccess'])) {
    if ($_SESSION['asuccess']) {
        echo "<script>alert('Order Approved Successfully');</script>";
        $_SESSION['asuccess'] = false;
    }
}
if (isset($_SESSION['rsuccess'])) {
    if ($_SESSION['rsuccess']) {
        echo "<script>alert('Order Reverted Successfully');</script>";
        $_SESSION['rsuccess'] = false;
    }
}
if (!isset($_SESSION['designation'])) {
    //get the designation of the user
    $query = "SELECT * FROM employee WHERE cpfno = '$cpf_no'";
    $result = mysqli_query($connection, $query);
    if (!$result || mysqli_num_rows($result) == 0) {
        header("Location: skdash.php");
        exit();
    }
    $user = mysqli_fetch_assoc($result);
    $designation = $user["designation"];
} else $designation = $_SESSION["designation"];


// Check if the user clicked on the collector link
if (($designation == "E") && isset($_GET['orderno'])) {
    $_SESSION['orderno'] = $_GET['orderno'];
    header("Location: collector-page.php");
    exit();
}

//Check if the user clicked on the security link
if (($designation == "S") && isset($_GET['orderno'])) {
    $_SESSION['orderno'] = $_GET['orderno'];
    header("Location: security-page.php");
    exit();
}

//Check if the user clicked on the guard link
if (($designation == "G") && isset($_GET['orderno'])) {
    $_SESSION['orderno'] = $_GET['orderno'];
    header("Location: guard-page.php");
    exit();
}

// Set the session variable 'isEditable' and redirect to form.php for "New Order" button
if ($designation == "E" && isset($_POST['new_order'])) {
    $_SESSION['isedit'] = 0;
    header("Location: form.php");
    exit();
}

// Set the session variable 'isEditable' and redirect to form.php for "Edit" button
if ($designation == "E" && isset($_POST['edit_order'])) {
    $orderno = $_POST['edit_order'];
    header("Location: tempform.php?orderno=$orderno");
    exit();
}

function getEmployeesByCpf($cpf)
{
    global $connection;
    $query = "SELECT empname FROM employee WHERE cpfno = '$cpf'";
    $result = mysqli_query($connection, $query);
    $employee = null;
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $employee = $row['empname'];
    }
    return $employee;
}
function getEmployeesvenue($cpf)
{
    global $connection;
    $query = "SELECT venue FROM employee WHERE cpfno = '$cpf'";
    $result = mysqli_query($connection, $query);
    $employee = null;
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $venue = $row['venue'];
    }
    return $venue;
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" crossorigin="anonymous">
</head>

<body>
    <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
        <button type="submit" id="lo" class="btn btn-outline-danger" name="logout">Logout</button>
    </form>
    <div class="container">
        <table>
            <tr>
                <td><img src="assets/images.png" class="logo"></td>
                <td>
                    <h1>Oil and Natural Gas Corporation</h1>
                    <h3>MUMBAI REGION- REGIONAL OFFICE- INFOCOM</h3>
                </td>
            </tr>
        </table>

    </div>
    <h3>Dashboard</h3>
    <?php if ($designation == "E") : ?>
        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <button type="submit" class="btn btn-primary" class="form-group" name="new_order">New Order</button>
        </form>
    <?php endif; ?>
    <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
        <?php

        // Retrieve data from the "order_no" table
        if ($designation == "E") {
            $query = "SELECT orderno, order_dest, issue_desc, placeoi, issueto, returnable, coll_approval, security_approval, comp_approval, guard_approval, forwarded_to, created_by FROM order_no WHERE coll_approval = 0 AND (forwarded_to = ? OR created_by = ?)";

            // Prepare the statement
            $stmt = $conn->prepare($query);

            // Bind the parameters
            $stmt->bind_param('ss', $cpf_no, $cpf_no);
        } else if ($designation == "S") {
            $ven = getEmployeesvenue($cpf_no);
            $query = "SELECT orderno, order_dest, issue_desc, placeoi, issueto, returnable, coll_approval, security_approval, comp_approval, guard_approval, forwarded_to, created_by FROM order_no WHERE placeoi = ? AND security_approval = 0 AND coll_approval = 1 AND guard_approval = 1";

            // Prepare the statement
            $stmt = $conn->prepare($query);

            // Bind the parameter
            $stmt->bind_param('s', $ven);
        } else {
            $query = "SELECT orderno, order_dest, issue_desc, placeoi, issueto, returnable, coll_approval, security_approval, comp_approval, guard_approval, forwarded_to, created_by FROM order_no WHERE placeoi = ? AND coll_approval = 1 AND guard_approval = 0";

            // Prepare the statement
            $stmt = $conn->prepare($query);

            // Bind the parameter
            $stmt->bind_param('s', $_SESSION["venue"]);
        }

        // Execute the statement
        $stmt->execute();

        // Get the result
        $result = $stmt->get_result();
        // Check if the query was successful
        if ($result && mysqli_num_rows($result) > 0) {
            // Display the data in a table
            echo "<table id='dynamic-table'>";
            echo "<tr><th>Order No</th><th>Created By</th><th>Order Destination</th><th>Issue Department</th><th>Place of Issue</th><th>Issue To</th><th>Returnable</th>";
            echo "<th>Action</th>";
            if ($designation == "E") echo "<th>Status</th></tr>";
            while ($row = mysqli_fetch_assoc($result)) {
                echo "<tr>";
                echo "<td>" . $row['orderno'] . "</td>";
                $creatorname = getEmployeesByCpf($row['created_by']);
                echo "<td>" . $creatorname . "</td>";
                echo "<td>" . $row['order_dest'] . "</td>";
                echo "<td>" . $row['issue_desc'] . "</td>";
        ?><?php
                $placeoi = $row['placeoi']; // Assuming $row['placeoi'] contains the value
                $displayText = '';

                if ($placeoi === 'N') {
                    $displayText = 'NBP Green Heights';
                } elseif ($placeoi === 'V') {
                    $displayText = 'Vasundhara Bhavan';
                } elseif ($placeoi === 'H') {
                    $displayText = '11 HIGH';
                }
                echo "<td>" . $displayText . "</td>";
                ?>
<?php
                echo "<td>" . $row['issueto'] . "</td>";
                $returnableValue = ($row['returnable'] ? 'Yes' : 'No');

                echo "<td>" . ($returnableValue) . "</td>";

                if ($designation == "E" && $row['forwarded_to'] == $cpf_no && $row['coll_approval'] == 0) {
                    echo "<td><a href='skdash.php?orderno=" . $row['orderno'] . "'>Collector Link</a></td>";
                } else if ($designation == "S")
                    echo "<td><a href='skdash.php?orderno=" . $row['orderno'] . "'>Security Link</a></td>";
                else if ($designation == "G")
                    echo "<td><a href='skdash.php?orderno=" . $row['orderno'] . "'>Guard Link</a></td>";
                else  if ($row['created_by'] == $cpf_no && $row['coll_approval'] == -1 || $row['security_approval'] == -1 || $row['guard_approval'] == -1) {
                    echo '<td>';
                    echo '<input type="hidden" name="Orderno" value="' . $row['orderno'] . '">';
                    echo '<button type="submit" name="edit_order" class="btn btn-outline-secondary" value="' . $row['orderno'] . '">Edit</button>';
                    echo '</td>';
                    if ($row['coll_approval'] == -1)

                        echo '<td>Order Reverted BY Collector </td>';

                    elseif ($row['security_approval'] == -1)

                        echo '<td>Order Reverted By Security </td>';

                    elseif ($row['guard_approval'] == -1)

                        echo '<td>Order Reverted By Guard </td>';
                } else  echo '<td>-</td>';
                // else if( $row['coll_approval'] != -1 )  {
                //         echo '<td>-</td>';
                // }
                // else if( $row['security_approval'] != -1 )  {
                //         echo '<td>-</td>';
                // }
                // else if( $row['guard_approval'] != -1 )  {
                //         echo '<td>-</td>';
                // }


                // $row['coll_approval'] != -1 || $row['security_approval'] != -1 ||
                if ($designation == "E" && $row['created_by'] == $cpf_no) {
                    if ($returnableValue == "Yes") {
                        // if ($row['coll_approval'] == -1 || $row['security_approval'] == -1 || $row['guard_approval'] == -1) {
                        //         echo '<td>';
                        //         echo '<input type="hidden" name="Orderno" value="' . $row['orderno'] . '">';
                        //         echo '<button type="submit" name="edit_order" class="btn btn-outline-secondary" value="' . $row['orderno'] . '">Edit</button>';
                        //         echo '</td>';
                        //         echo '<td>Order Reverted </td>';
                        // }
                        if ($row['coll_approval'] == 1 && $row['security_approval'] == 0)
                            echo '<td>Approved by Collector</td>';

                        else if ($row['security_approval'] == 1 && $row['guard_approval'] == 0)
                            echo '<td>Approved by Security</td>';

                        else if ($row['coll_approval'] == 1 && $row['security_approval'] == 1 && $row['guard_approval'] == 1)
                            echo '<td>Approved and Out</td>';

                        else if ($row['coll_approval'] == 0 && $row['security_approval'] == 0 && $row['guard_approval'] == 0 && $row['comp_approval'] == 0)
                            echo '<td>Collector Approval Pending</td>';

                        else if ($row['coll_approval'] == 1 && $row['security_approval'] == 1 && $row['guard_approval'] == 1 && $row['comp_approval'] == 1)
                            echo '<td>Order Completed</td>';
                    } elseif ($returnableValue == "No") {
                        // if ($row['coll_approval'] == -1 || $row['security_approval'] == -1 || $row['guard_approval'] == -1 )
                        //     {echo '<td><input type="hidden" name="orderno" value="' . $row['orderno'] . '">';
                        //         echo '<button type="submit" name="edit_order">Edit</button></td>';}

                        if ($row['coll_approval'] == 1 && $row['guard_approval'] == 0)
                            echo '<td>Approved by Collector</td>';

                        else if ($row['guard_approval'] == 1 && $row['security_approval'] == 0)
                            echo '<td>Approved by Guard</td>';

                        else if ($row['coll_approval'] == 0 && $row['guard_approval'] == 0 && $row['guard_approval'] == 0 && $row['comp_approval'] == 0)
                            echo '<td>Order Pending</td>';

                        // else if ($row['coll_approval'] == 1 && $row['security_approval'] == 1 && $row['guard_approval'] == 1)
                        //     echo '<td>Approved and Out</td>';    

                        else if ($row['coll_approval'] == 1 && $row['security_approval'] == 1 && $row['guard_approval'] == 1)
                            echo '<td>Order Completed</td>';
                    }
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
    </form>
</body>

</html>