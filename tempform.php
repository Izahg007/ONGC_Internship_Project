<?php
session_start();
require_once "db_connection.php";
require_once "enc_dec.php";
$decryptedClientIP = decrypt($_SESSION['encrypted_client_ip'], $secretKey);
$decryptedUserAgent = decrypt($_SESSION['encrypted_user_agent'], $secretKey);
if ($_SERVER['REMOTE_ADDR'] != $decryptedClientIP && $_SESSION['user_agent'] !== $decryptedUserAgent){
    session_unset();
    session_destroy();
}
if (!isset($_SESSION['regenerated']) || ($_SESSION['regenerated'] + 30 * 60) < time()) {
    session_regenerate_id(true);
    $_SESSION['regenerated'] = time();
}
// echo "hello".$_SESSION['orderno'];
// Check if the user is not logged in
if (!isset($_SESSION["username"])) {
    header("Location: newlogin.php");
    exit();
}

//check if right person(store keeper) is accessing the forms page   
if ($_SESSION["designation"] != "E") {
    header("Location: skdash.php");
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
$orderno = $_SESSION['orderno'];

$checkquery = "SELECT coll_approval,security_approval,guard_approval, sign_approval  FROM order_no WHERE orderno = '$orderno'";
$result = mysqli_query($conn, $checkquery);

if ($result) {
    $row = mysqli_fetch_assoc($result);
    $collApproval = $row['coll_approval'];
    $sApproval = $row['security_approval'];
    $sgApproval = $row['sign_approval'];
    $gApproval = $row['guard_approval'];
    // Check if the 'coll_approval' value is not equal to -1
    if ($collApproval != -1 && $sApproval != -1 && $gApproval != -1 && $sgApproval != -1) {
        $_SESSION['cantedit'] = true; // Using session variable

        // Redirect to the next page
        header("Location: skdash.php");
        exit();
    }
}


if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if (isset($_POST["submit"])) {
        // Escape user inputs to prevent SQL injection
        // Escape user inputs to prevent SQL injection
        $returnable = $_POST["return"] == "1" ? 1 : 0;
        $issueTo = mysqli_real_escape_string($conn, $_POST["issuet"]);
        $placeOfDestination = '';
        $returnDate = strtotime($_POST['returnDate']);
        $returnDate = date('Y-m-d', $returnDate);
        if ($_POST["pod"] == "other") {
            $placeOfDestination = mysqli_real_escape_string($conn, $_POST["otherOption"]);
        } else {
            $placeOfDestination = mysqli_real_escape_string($conn, $_POST["pod"]);
        }
        $forwardTo = mysqli_real_escape_string($conn, $_POST["fors"]);
        $signatory = mysqli_real_escape_string($conn, $_POST["signatory"]);
        $coll_approval = 0;
        $security_approval = 0;
        $guard_approval = 0;
        $sign_approval = 0;

        // Insert data into the 'order_no' table
        $UpdateOrderNoQuery = "UPDATE order_no SET order_dest = ?, issueto = ?, securityn = '', returnable = ?, returndate= ?,coll_approval = ?, sign_approval=?,security_approval = ?, guard_approval = ?, forwarded_to = ?, signatory= ? WHERE orderno = ?";

        // Prepare the statement
        $stmt = $conn->prepare($UpdateOrderNoQuery);

        // Bind the parameters
        $stmt->bind_param('ssisiiiiiii', $placeOfDestination, $issueTo, $returnable, $returnDate, $coll_approval, $sign_approval, $security_approval, $guard_approval, $forwardTo,  $signatory, $orderno);

        // Execute the statement
        if ($stmt->execute()) {

            //******/ Insert data into the 'orders' table *********
            // $orderno = $_POST['serial_number'];
            // Retrieve the form data
            $serialNumbers = $_POST['serial_number'];
            $description = $_POST['description'];
            $num = $_POST['num'];
            $dispatchnotes = $_POST['dispatchnotes'];
            $remarks = $_POST['remarks'];

            $delquery = "DELETE FROM orders WHERE orderno = '$orderno'";
            $conn->query($delquery);
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
                $stmt->bindParam(':orderno', $orderno);
                $stmt->execute();
            }

            /********** Done ************/

            $_SESSION['esuccess'] = true; // Using session variable

            // Redirect to the next page
            header("Location: skdash.php");
            exit();
        } else {
            // Handle the case where the insertion failed
            echo "Error: " . mysqli_error($conn);
        }

        // Close the database connection
        mysqli_close($conn);
        $conn2 = null;
    }
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
?>

<html>

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Edit Order</title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="css/styles.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" crossorigin="anonymous">
    <script type="text/javascript" src="form.js"></script>
</head>

<body>
    <button class="btn btn-secondary" id="gb" onclick="window.location.href = 'skdash.php'">Go Back</button>

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
    <div class="tableclass">
    <h2 class="wlc">Welcome, <?php echo $_SESSION["username"]; ?>!</h2><br>
    <?php
    // $orderno = $_GET['orderno']; // Get the 'orderno' parameter from the URL
    echo "Order No:" . $orderno;

    $selectOrderNoQuery = "SELECT * FROM order_no WHERE orderno = '$orderno'";
    $result1 = mysqli_query($conn, $selectOrderNoQuery);
    $selectOrdersQuery = "SELECT * FROM orders WHERE orderno = '$orderno'";
    $result = mysqli_query($conn, $selectOrdersQuery);
    $orderItems = array();
    while ($row = mysqli_fetch_assoc($result)) {
        $orderItems[] = $row;
    }

    if ($result1 && mysqli_num_rows($result1) > 0) {
        $orderData = mysqli_fetch_assoc($result1);
        echo "<br><h5>Remarks:" . $orderData['new_remarks'] . "</h5>";
    ?>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
            <div class="pos">

                <label for="return">Returnable</label>
                <input type="radio" class="form-group" name="return" value="1" <?php echo $orderData['returnable'] == 1 ? 'checked' : ''; ?> onchange="toggleReturnDateForm(this.value)" required>

                <label for="nreturn">Non Returnable</label>
                <input type="radio" class="form-group" name="return" value="0" <?php echo $orderData['returnable'] == 0 ? 'checked' : ''; ?> onchange="toggleReturnDateForm(this.value)"><br>
                <table class="postt">
                    <tr>
                        <td><label for="issued">Issuing department/Office</label>
                            <input type="text" class="form-group" name="issued" value="<?php
                                                                                        if ($orderData['issue_dep'] === "I") {
                                                                                            $department = "Infocom";
                                                                                        } elseif ($orderData['issue_dep'] === "M") {
                                                                                            $department = "Management";
                                                                                        } elseif ($orderData['issue_dep'] === "P") {
                                                                                            $department = "Production";
                                                                                        }
                                                                                        echo $department; ?>" required readonly><br>
                        </td>
                        <td><label for="issuet">Issue To</label>
                            <input type="text" class="form-group" name="issuet" value="<?php echo $orderData['issueto']; ?>" required><br>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label for="placei">Place of Issue</label>
                            <input type="text" class="form-group" name="placei" value="<?php
                                                                                        if ($orderData['placeoi'] == "N") {
                                                                                            $venue = "NBP Green Heights";
                                                                                        } elseif ($orderData['placeoi'] == "V") {
                                                                                            $venue = "Vasundhara Bhavan";
                                                                                        } elseif ($orderData['placeoi'] == "H") {
                                                                                            $venue = "11 High";
                                                                                        }
                                                                                        echo $venue; ?>" readonly>
                        </td>
                        <td><label for="pod">Place of Destination</label>
                            <input type="text" class="form-group" name="pod" value="<?php echo $orderData['order_dest']; ?>" required>
                        </td>
                    </tr>
                </table>

                <h4></h4>
            </div>
            <table id="dynamic-table">
                <tr>
                    <th>Sr No</th>
                    <th>Brief description</th>
                    <th>No of Packages</th>
                    <th>Deliver Note Or Dispatch convey note no OR Indent no</th>
                    <th>Remarks</th>
                    <th>Action</th>
                </tr>
                <?php foreach ($orderItems as $index => $item) { ?>
                    <tr>
                        <td><input type='hidden' name='serial_number[]'> <?php echo $index + 1; ?></input></td>
                        <td><input type="text" name="description[]" value="<?php echo $item['descrip']; ?>" required></td>
                        <td><input type="text" name="num[]" value="<?php echo $item['nop']; ?>" required></td>
                        <td><input type="text" name="dispatchnotes[]" value="<?php echo $item['deliverynote']; ?>" required></td>
                        <td><input type="text" name="remarks[]" value="<?php echo $item['remark']; ?>" required></td>
                        <td> </td>
                    </tr>

                <?php }
                ?>
            </table>
            <br>
            <button type="button" onclick="addRow()">Add Row</button>
            <br><br>
            <div id="returnDateForm" style="display: <?php echo $orderData['returnable'] == 1 ? 'block' : 'none'; ?>">
                <label for="returnDate">Return Date:</label>
                <input type="date" name="returnDate" id="returnDate" min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" value="<?php 
                    if($orderData['returndate']){
                        echo $orderData['returndate'];
                    }
                    else{
                        echo date('Y-m-d',strtotime('+1 day'));
                    }
                     ?>">
            </div>
            

            <div class="sugges">
                <div class="result1">
                    <p>Signatory Officer:</p>
                </div>
                <input type="text" name="signatory" oninput="findso(this.value)" value="<?php echo $orderData['signatory']; ?>">
                <ul class="autocomplete-list1"></ul>
                <div class="clear1"></div>
            </div>


            <div class="sugg">
                <div class="result">
                    <p>Forwarded To:</p>
                </div>
                <input type="text" name="fors" oninput="findet(this.value)" onLoad="findet(this.value)" value="<?php echo $orderData['forwarded_to']; ?>">
                <ul class="autocomplete-list"></ul>
                <div class="clear"></div>
            </div>
            <br>
            <br>
            <input type="hidden" name="orderno" value="<?php echo $orderno; ?>">
            <input type="submit" name="submit" id="submitButton" value="Submit">
        </form>
        </div>
        </div>
        <?php
            } else {
                // Handle the case where no rows were found
                echo "No order data found.";
            }
                ?>
    <script type="text/javascript" src="form.js"></script>
</body>

</html>