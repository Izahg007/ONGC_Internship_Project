<?php
session_start();
require_once "db_connection.php";

// Check if the user is not logged in
if (!isset($_SESSION["username"])) {
    header("Location: login.php");
    exit();
}

//check if right person(store keeper) is accessing the forms page
if ($_SESSION["designation"] != "store_keeper") {
    header("Location: skdash.php");
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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Escape user inputs to prevent SQL injection
    $returnable = $_POST["return"] == "1" ? 1 : 0;
    $issueDesc = mysqli_real_escape_string($conn, $_POST["issued"]);
    $placeOfIssue = mysqli_real_escape_string($conn, $_POST["placei"]);
    $issueTo = mysqli_real_escape_string($conn, $_POST["issuet"]);
    $placeOfDestination = mysqli_real_escape_string($conn, $_POST["pod"]);
    $forwardTo = mysqli_real_escape_string($conn, $_POST["fors"]);

    // Insert data into the 'order_no' table
    $insertOrderNoQuery = "INSERT INTO order_no (order_dest, issue_desc, placeoi, issueto, securityn, collectorid, returnable, forwarded_to) 
                           VALUES ('$placeOfDestination', '$issueDesc', '$placeOfIssue', '$issueTo', '', '', $returnable, '$forwardTo')";

    if (mysqli_query($conn, $insertOrderNoQuery)) {
        $orderNo = mysqli_insert_id($conn); // Get the auto-generated order ID

        //*****************/ Insert data into the 'orders' table ************************

        // Retrieve the form data
        $serialNumbers = $_POST['serial_number'];
        $description = $_POST['description'];
        $num = $_POST['num'];
        $dispatchnotes = $_POST['dispatchnotes'];
        $remarks = $_POST['remarks'];


        // Create a PDO connection to the database
        $conn2 = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn2->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Prepare the SQL statement for insertion
        $stmt = $conn2->prepare("INSERT INTO orders (descrip, nop, deliverynote, remark, orderno) VALUES (:descrip, :nop, :deliverynote, :remark, :orderno)");

        // Iterate over the rows and insert them into the database
        for ($i = 0; $i < count($serialNumbers); $i++) {
            $stmt->bindParam(':descrip', $description[$i]);
            $stmt->bindParam(':nop', $num[$i]);
            $stmt->bindParam(':deliverynote', $dispatchnotes[$i]);
            $stmt->bindParam(':remark', $remarks[$i]);
            $stmt->bindParam(':orderno', $orderNo);

            $stmt->execute();
        }

        /****************************** Done **********************************/

        // Redirect to a success page or display a success message
        header("Location: form.php");
        exit();
    } else {
        // Handle the case where the insertion failed
        echo "Error: " . mysqli_error($conn);
    }

    // Close the database connection
    mysqli_close($conn);
    $conn2 = null;
}

// Function to get employee names and CPF numbers based on designation
function getEmployeesByDesignation($designation)
{
    global $connection;
    $query = "SELECT empname, cpfno FROM employee WHERE designation = '$designation'";
    $result = mysqli_query($connection, $query);
    $employees = array();
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $employee = array(
                'empname' => $row['empname'],
                'cpfno' => $row['cpfno']
            );
            $employees[] = $employee;
        }
    }
    return $employees;
}
?>

<html>

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>New Order</title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="css/styles.css">
</head>

<body>
    <a href="skdash.php">Go Back</a>

    <div class="container">
        <h2>Welcome, <?php echo $_SESSION["username"]; ?>!</h2>
        <p>Fill the form below.</p>
        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <button type="submit" name="logout">Logout</button>
        </form>
    </div>
    <table>
        <tr>
            <td><img src="assets/images.png" class="logo"></td>
            <td>
                <h1>Oil and Natural Gas Corporation</h1>
                <h3>MUMBAI REGION- REGIONAL OFFICE- INFOCOM</h3>
            </td>
        </tr>
    </table>
    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
        <label for="return">Returnable</label>
        <input type="radio" name="return" value="1">
        <label for="nreturn">Non Returnable</label>
        <input type="radio" name="return" value="0"><br>
        <label for="issued">Issue description</label>
        <input type="text" name="issued"><br>
        <label for="placei">Place of Issue</label>
        <input type="text" name="placei"><br>
        <label for="issuet">Issue To</label>
        <input type="text" name="issuet"><br>
        <label for="pod">Place of Destination</label>
        <input type="text" name="pod">
        <h4></h4>
        <table id="dynamic-table">
            <tr>
                <th>Sr No</th>
                <th>Brief description</th>
                <th>No of Packages</th>
                <th>Deliver Note Or Dispatch convey note no OR Indent no</th>
                <th>Remarks</th>
                <th>Action</th>
            </tr>
            <tr>
                <td><input type="hidden" name="serial_number[]">1. </td>
                <td><input type="text" name="description[]"></td>
                <td><input type="text" name="num[]"></td>
                <td><input type="text" name="dispatchnotes[]"></td>
                <td><input type="text" name="remarks[]"></td>
                <td> </td>
            </tr>
        </table>
        <br>
        <button type="button" onclick="addRow()">Add Row</button>
        <br><br>
        <div class="result">
            <p>Forwarded To:</p>
        </div>
        <input type="text" name="fors" onkeyup="findet(this.value)">
        
        <br>
        <br>
        <input type="submit" name="submit" value="Submit">
    </form>
    <script type="text/javascript" src="form.js"></script>
</body>

</html>