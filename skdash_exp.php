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

if(!isset($_SESSION['designation'])){
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

$query = "SELECT created_at, orderno, order_dest, issue_desc, placeoi, issueto, securityn, guard_name, collector_name, returnable, forwarded_to, moc, vehno, created_by FROM order_no";
$result = mysqli_query($connection, $query);

if (!$result) {
    die('Query failed: ' . mysqli_error($connection));
}

$data = array();

while ($row = mysqli_fetch_assoc($result)) {
    $data[] = $row;
}

$jsonData = array(
    'data' => $data
);

$columns = array(
    'created_at',
    'orderno',
    'order_dest',
    'issue_desc',
    'placeoi',
    'issueto',
    'securityn',
    'guard_name',
    'collector_name',
    'returnable',
    'forwarded_to',
    'moc',
    'vehno',
    'created_by'
);

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
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.3/css/jquery.dataTables.min.css">
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
            <button type="submit" class="btn btn-primary" name="new_order">New Order</button>
        </form>
    <?php endif; ?>
        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>"> 
    <?php
    
    // Retrieve data from the "order_no" table
    if ($designation == "E") {
        $query = "SELECT orderno, order_dest, issue_desc, placeoi, issueto, returnable, coll_approval, security_approval,comp_approval,guard_approval,forwarded_to,created_by FROM order_no WHERE coll_approval = 0 AND forwarded_to = {$cpf_no} OR created_by = {$cpf_no}";
    } else if ($designation == "S") {
        $query = "SELECT orderno, order_dest, issue_desc, placeoi, issueto, returnable, coll_approval, security_approval,comp_approval,guard_approval,forwarded_to,created_by FROM order_no WHERE security_approval = 0 AND coll_approval = 1 AND guard_approval = 1";
    } else {
        $query = "SELECT orderno, order_dest, issue_desc, placeoi, issueto, returnable, coll_approval, security_approval,comp_approval,guard_approval,forwarded_to,created_by FROM order_no WHERE placeoi = '{$_SESSION["venue"]}' AND coll_approval = 1 AND guard_approval = 0";
    }
    $result = mysqli_query($connection, $query);
    // Check if the query was successful
    if ($result && mysqli_num_rows($result) > 0) {
        // Display the data in a table
        echo "<table id='dynamic-table'>";
        echo "<tr><th>Order No</th><th>Created By</th><th>Order Destination</th><th>Issue Description</th><th>Place of Issue</th><th>Issue To</th><th>Returnable</th>";
        echo "<th>Action</th>";
        if($designation == "E") echo "<th>Status</th></tr>";
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
            
            if ($designation == "E" && $row['forwarded_to'] == $cpf_no && $row['coll_approval'] == 0){
                echo "<td><a href='skdash.php?orderno=" . $row['orderno'] . "'>Collector Link</a></td>";
            }
            else if ($designation == "S")
                echo "<td><a href='skdash.php?orderno=" . $row['orderno'] . "'>Security Link</a></td>";
            else if ($designation == "G")
                echo "<td><a href='skdash.php?orderno=" . $row['orderno'] . "'>Guard Link</a></td>";
            else  if ($row['created_by'] == $cpf_no && $row['coll_approval'] == -1 || $row['security_approval'] == -1 || $row['guard_approval'] == -1) {
                echo '<td>';
                echo '<input type="hidden" name="Orderno" value="' . $row['orderno'] . '">';
                echo '<button type="submit" name="edit_order" class="btn btn-outline-secondary" value="' . $row['orderno'] . '">Edit</button>';
                echo '</td>';
                if ($row['coll_approval']==-1)

                    echo '<td>Order Reverted BY Collector </td>';
                
                elseif ($row['security_approval']==-1)

                echo '<td>Order Reverted By Security </td>';

                elseif($row['guard_approval']==-1)

                    echo '<td>Order Reverted By Guard </td>';
            }
            else  echo '<td>-</td>';
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
                if($returnableValue =="Yes"){
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
                }
                elseif($returnableValue=="No"){
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


    <div>
        <label for="columnSelect">Column:</label>
        <select id="columnSelect">
            <?php foreach ($columns as $column) { ?>
                <option value="<?php echo $column; ?>"><?php echo $column; ?></option>
            <?php } ?>
        </select>
        <label for="filterInput">Filter:</label>
        <input type="text" id="filterInput" placeholder="Enter filter value">
        <button id="applyFilterBtn">Apply</button>
        <button id="removeFilterBtn">Remove Filter</button>
    </div>

    <table id="orderTable">
        <thead>
            <tr>
                <th>created_at</th>
                <th>orderno</th>
                <th>order_dest</th>
                <th>issue_desc</th>
                <th>placeoi</th>
                <th>issueto</th>
                <th>securityn</th>
                <th>guard_name</th>
                <th>collector_name</th>
                <th>returnable</th>
                <th>forwarded_to</th>
                <th>moc</th>
                <th>vehno</th>
                <th>created_by</th>
            </tr>
        </thead>
    </table>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.3/js/jquery.dataTables.min.js"></script>
    <script>
        $(document).ready(function() {
            var table = $('#orderTable').DataTable({
                "data": <?php echo json_encode($jsonData['data']); ?>,
                "columns": [
                    { "data": "created_at" },
                    { "data": "orderno" },
                    { "data": "order_dest" },
                    { "data": "issue_desc" },
                    { "data": "placeoi" },
                    { "data": "issueto" },
                    { "data": "securityn" },
                    { "data": "guard_name" },
                    { "data": "collector_name" },
                    { "data": "returnable" },
                    { "data": "forwarded_to" },
                    { "data": "moc" },
                    { "data": "vehno" },
                    { "data": "created_by" }
                ]
            });

            $('#applyFilterBtn').click(function() {
                var columnValue = $('#columnSelect').prop('selectedIndex');
                var filterValue = $('#filterInput').val().trim();
                var table = $('#orderTable').DataTable();
                table.column(columnValue).search(filterValue).draw();
            });

            $('#removeFilterBtn').click(function() {
                table.search('').columns().search('').draw();
                $('#columnSelect').prop('selectedIndex', 0);
                $('#filterInput').val('');
            });
        });
    </script>
</body>

</html>