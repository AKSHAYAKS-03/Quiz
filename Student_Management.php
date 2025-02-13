<?php
include_once 'core_db.php';
session_start();

if (!isset($_SESSION['logged']) || $_SESSION['logged'] === '') {
    header('Location: index.php');
    exit;
}

// $department = isset($_POST['department']) ? $_POST['department'] : 'all';
// $section = isset($_POST['section']) ? $_POST['section'] : 'all';
// $year = isset($_POST['year']) ? $_POST['year'] : 'all';

$query = "SELECT * FROM users WHERE 1";

// if ($department !== 'all') {
//     $query .= " AND Department = '$department'";
// }
// if ($section !== 'all') {
//     $query .= " AND Section = '$section'";
// }
// if ($year !== 'all') {
//     $query .= " AND Year = '$year'";
// }

// $query .= " ORDER BY Department, Section, Year";

$result = mysqli_query($conn, $query);

// $users = [];
// while ($row = mysqli_fetch_assoc($result)) {
//     $users[] = $row;
// }

// echo json_encode($users);
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <title>Student Management</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        .sidebar {
            height: 100vh;
            width: 250px;
            background-color: #13274F;
            color: white;
            padding: 20px;
            position: fixed;
        }
        .sidebar a {
            display: block;
            color: white;
            padding: 10px;
            text-decoration: none;
            margin: 10px 0;
            background: #1E3A8A;
            text-align: center;
            border-radius: 5px;
        }
        .sidebar a:hover {
            background: #415A77;
        }
        button{
            display: block;
            color: white;
            padding: 10px;
            text-decoration: none;
            margin: 10px 0;
            background: #1E3A8A;
            text-align: center;
            border-radius: 5px;
            border: none;

        }
        select{
            display: block;
            color: white;
            padding: 10px;
            text-decoration: none;
            margin: 10px 0;
            background: #1E3A8A;
            text-align: center;
            border-radius: 5px;
            border: none;
        }
        .content {
            margin-left: 270px;
            padding: 20px;
        }
        .header-right {
            position: absolute;
            top: 10px;
            right: 20px;
            padding: 8px;
        }
        .header-right a {
            text-decoration: none;
            padding: 2px;
            display: inline-block;
        }
        .header-right a img {
            cursor: pointer;
            width: 26px;  
            height: 32px; 
        }
        .hidden {
            display: none;
        }
      

    </style>
</head>
<body>

    <!-- Sidebar -->
    <div class="header-right">
        <a href="about.html" title="About">
            <img src="icons/about.svg" alt="about">
        </a>
        <a href="logout.php" id="logout" title="Log Out">
            <img src="icons/exit.svg" alt="exit">
        </a>
    </div>

    <div class="sidebar">
        <h3>Student Actions</h3>
        <a href="add_student.php">➕ Add Student</a>

        <div class="filter">
            <h2>Filters</h2>
            <div class="category-label">Department</div>
            <select id="department">
                <option value="all" selected>All Departments</option>
                <option value="CSE">CSE</option>
                <option value="IT">IT</option>
                <option value="ECE">ECE</option>
                <option value="EEE">EEE</option>
                <option value="MECH">MECH</option>
                <option value="CIV">CIV</option>
            </select>

            <div class="category-label">Section</div>
            <select id="section">
                <option value="all" selected>All Sections</option>
                <option value="A">A</option>
                <option value="B">B</option>
                <option value="C">C</option>
            </select>

            <div class="category-label">Year</div>
            <select id="year">
                <option value="all" selected>All Years</option>
                <option value="I">I</option>
                <option value="II">II</option>
                <option value="III">III</option>
                <option value="IV">IV</option>
            </select>
        </div>
        <br>
        <h2>Edit Students</h2>
        <button id="bulkEditBtn">✏ Edit Student</button>

        <div id="editOptions" class="hidden">
            <label>Select Column</label>
            <select id="columnSelect" class="form-control" placeholder="Select">            
                <option value="" disabled selected>Select</option>
                <option value="Department">Department</option>
                <option value="Section">Section</option>
                <option value="Year">Year</option>
            </select>

            <label>Select New Value</label>
            <select id="valueSelect" class="form-control" placeholder="Select">
            <option value="" disabled selected>Select</option>
            </select>

            <button class="btn btn-success mt-2" id="applyChanges">Apply Changes</button>
        </div>        
    </div>

    <!-- Main Content -->
    <div class="content">
        <h2>Student Management</h2>
        <div class="top" style="display:flex ;align-items: center;justify-content: space-between">
            <input type="text" id="searchInput" placeholder="Search students..." onkeyup="searchStudents()" style="width: 300px;">

            <div class="fulledit">
                    <button id="editSelected" class="btn btn-primary">Edit Selected</button>
                    <button id="saveEditedData" class="btn btn-success">Save Changes</button>
                    <button id="deleteSelected" class="btn btn-danger">Delete Selected</button>
            </div>
        </div>
    
        <table class="table table-bordered">
            <thead class="table-dark">
                <tr>
                    <th><input type="checkbox" id="selectAll" /></th> 
                    <th>RegNo</th>
                    <th>Name</th>
                    <th>Department</th>
                    <th>Section</th>
                    <th>Year</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="studentTable">
                <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                <tr data-regno="<?= $row['RegNo']; ?>">
                    <td><input type="checkbox" name="select" value="<?= $row['RegNo']; ?>"></td>
                    <td class="editable regNo"><?= $row['RegNo']; ?></td>
                    <td class="editable name"><?= $row['Name']; ?></td>
                    <td class="editable department"><?= $row['Department']; ?></td>
                    <td class="editable section"><?= $row['Section']; ?></td>
                    <td class="editable year"><?= $row['Year']; ?></td>
                    <td>
                        <button class="btn btn-warning btn-sm edit-btn" style="background-color: #1E3A8A;color:white;border:none">Edit</button>
                        <button class="btn btn-danger btn-sm delete-btn" style="background-color: #1E3A8A;color:white;border:none">Delete</button>
                    </td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>

<script>
     function searchStudents() {
        const input = document.getElementById("searchInput").value.toLowerCase();
        const table = document.getElementById("studentTable");
        const rows = table.getElementsByTagName("tr");

        for (let i = 1; i < rows.length; i++) {
            let row = rows[i];
            const cells = row.getElementsByTagName("td");
            let found = false;

            // Loop through all cells (ID, Name, Age, Class)
            for (let j = 0; j < cells.length; j++) {
            const cell = cells[j];
            if (cell) {
                if (cell.textContent.toLowerCase().indexOf(input) > -1) {
                found = true;
                break;
                }
            }
            }

            // Show/hide row based on search match
            if (found) {
            row.style.display = "";
            } else {
            row.style.display = "none";
            }
        }
        }

document.addEventListener("DOMContentLoaded", function () {

    // Function to toggle edit mode
    document.querySelectorAll(".edit-btn").forEach(button => {
        button.addEventListener("click", function () {
            let row = this.closest("tr");
            let isEditing = this.textContent === "Save";

            row.querySelectorAll(".editable").forEach(cell => {
                if (!isEditing) {
                    let text = cell.textContent.trim();
                    cell.innerHTML = `<input type="text" class="form-control form-control-sm" value="${text}">`;
                } else {
                    let input = cell.querySelector("input");
                    if (input) cell.textContent = input.value;
                }
            });

            this.textContent = isEditing ? "Edit" : "Save";
            this.classList.toggle("btn-warning");
            this.classList.toggle("btn-success");

            if (isEditing) {
                updateUser(row);
            }
        });
    });

// Function to update user in database
function updateUser(row) {
    let regNo = row.querySelector(".regNo").textContent.trim();
    let name = row.querySelector(".name").textContent.trim();
    let department = row.querySelector(".department").textContent.trim();
    let section = row.querySelector(".section").textContent.trim();
    let year = row.querySelector(".year").textContent.trim();

    let formData = new FormData();
    formData.append("RegNo", regNo);
    formData.append("Name", name);
    formData.append("Department", department);
    formData.append("Section", section);
    formData.append("Year", year);

    fetch("Edit_Student.php", {
        method: "POST",
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert("User updated successfully!");
        } else {
            alert("Error updating user: " + data.message);
        }
    })
    .catch(error => console.error("Error:", error));
}

    // Delete Functionality
    document.querySelectorAll(".delete-btn").forEach(button => {
        button.addEventListener("click", function () {
            let row = this.closest("tr");
            let regNo = row.querySelector(".regNo").textContent.trim();

            if (confirm("Are you sure you want to delete student with Reg No: " + regNo + "?")) {
                deleteUser(regNo, row);
            }
        });
    });

        function deleteUser(regNo, row) {
            let formData = new FormData();
            formData.append("RegNo", regNo);

            fetch("Delete_Student.php", {
                method: "POST",
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    row.remove(); // Remove row from table
                    alert("User deleted successfully!");
                } else {
                    alert("Error deleting user: " + data.message);
                }
            })
            .catch(error => console.error("Error:", error));
        }
        

     // Get filter dropdown elements
    const departmentFilter = document.getElementById("department");
    const sectionFilter = document.getElementById("section");
    const yearFilter = document.getElementById("year");

    // Function to fetch and update the table
    function fetchFilteredData() {
        const department = departmentFilter.value;
        const section = sectionFilter.value;
        const year = yearFilter.value;

        // Send the filter values to the backend
        fetch("Fetch_Students.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded"
            },
            body: `department=${department}&section=${section}&year=${year}`
        })
        .then(response => response.json())
        .then(data => {
            const studentTable = document.getElementById("studentTable");
            studentTable.innerHTML = ""; // Clear the table before adding new data

            if (data.length === 0) {
                studentTable.innerHTML = "<tr><td colspan='7'>No students found</td></tr>";
                return;
            }

            // Populate the table with filtered data
            data.forEach(student => {
                const row = document.createElement("tr");
                row.setAttribute("data-regno", student.RegNo); 
               
                row.innerHTML = `
                    <td><input type="checkbox" name="select" value="${student.RegNo}"></td>
                    <td class="editable regNo">${student.RegNo}</td>
                    <td class="editable name">${student.Name}</td>
                    <td class="editable department">${student.Department}</td>
                    <td class="editable section">${student.Section}</td>
                    <td class="editable year">${student.Year}</td>
                    <td>
                        <button class="btn btn-warning btn-sm edit-btn">Edit</button>
                        <button class="btn btn-danger btn-sm delete-btn">Delete</button>
                    </td>
                `;
                studentTable.appendChild(row);
            });
        })
        .catch(error => console.error("Error fetching students:", error));
    }

    // Attach event listeners to dropdowns
    departmentFilter.addEventListener("change", fetchFilteredData);
    sectionFilter.addEventListener("change", fetchFilteredData);
    yearFilter.addEventListener("change", fetchFilteredData);


    
    /////update bulk
    const editBtn = document.getElementById("bulkEditBtn");
    const editOptions = document.getElementById("editOptions");
    const columnSelect = document.getElementById("columnSelect");
    const valueSelect = document.getElementById("valueSelect");
    const applyChangesBtn = document.getElementById("applyChanges");

        editBtn.addEventListener("click", function () {
            editOptions.classList.toggle("hidden");
        });

        columnSelect.addEventListener("change", function () {
            const selectedColumn = columnSelect.value;
            updateValueOptions(selectedColumn);
        });

        function updateValueOptions(column) {
            let values = [];

            if (column === "Department") values = ["CSE", "ECE","EEE","IT", "MECH", "CIVIL"];
            else if (column === "Section") values = ["A", "B", "C"];
            else if (column === "Year") values = ["I", "II", "III", "IV"];

            valueSelect.innerHTML = `<option value="" disabled selected>Select</option>`;

            values.forEach(value => {
                let option = document.createElement("option");
                option.value = value;
                option.textContent = value;
                valueSelect.appendChild(option);
            });

            valueSelect.style.display = "block";
        }

        applyChangesBtn.addEventListener("click", function () {
            const selectedColumn = columnSelect.value;
            const newValue = valueSelect.value;

            if (!selectedColumn || !newValue) {
                alert("Please select both a column and a new value.");
                return;
            }

            if (confirm(`Are you sure you want to update the displayed students' ${selectedColumn} to "${newValue}"?`)) {
                updateTableColumn(selectedColumn, newValue);
                updateDatabase(selectedColumn, newValue);
            }
        });

        function updateTableColumn(column, value) {
            const headers = document.querySelectorAll("thead th");
            let columnIndex = -1;

            headers.forEach((th, index) => {
                if (th.textContent.trim() === column) columnIndex = index;
            });

            if (columnIndex === -1) {
                alert("Error: Column not found.");
                return;
            }

            const updatedRows = [];
            document.querySelectorAll("tbody tr").forEach(row => {
                if (row.style.display !== "none") { 
                    let cell = row.children[columnIndex];
                    if (cell) {
                        cell.textContent = value;
                        // console.log(row.dataset.regno);
                        updatedRows.push(row.dataset.regno); 
                    }
                }
            });
            console.log("updatedRows: " + updatedRows);

            return updatedRows;
        }

        function updateDatabase(column, value) {
            const updatedRows = updateTableColumn(column, value);

            if (updatedRows.length === 0) {
                alert("No rows to update.");
                return;
            }

            fetch("Bulk_Update.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ column: column, value: value, regNos: updatedRows })
            })
            .then(response => response.json())
            .then(text => {
                console.log(text);
            })
            .then(data => {
                if (data.success) {
                    alert("Database updated successfully!");
                } else {
                    console.log("DatabaseError updating database: " + data.error);
                }
            })
            .catch(error => {
                console.log("Request failed: " + error);
            });
        }

    //select all checkboxes    
    const selectAllCheckbox = document.getElementById("selectAll");
    const studentTable = document.getElementById("studentTable");

    // Function to update "Select All" checkbox state based on individual checkboxes
    function updateSelectAllState() {
        const checkboxes = document.querySelectorAll("#studentTable input[type='checkbox']");
        const allChecked = [...checkboxes].every(checkbox => checkbox.checked);
        selectAllCheckbox.checked = allChecked;
    }

    // Handle "Select All" checkbox
    selectAllCheckbox.addEventListener("change", () => {
        const checkboxes = document.querySelectorAll("#studentTable input[type='checkbox']");
        checkboxes.forEach(checkbox => {
            checkbox.checked = selectAllCheckbox.checked;
        });
    });

    // Event delegation: Handle checkbox clicks inside the studentTable
    studentTable.addEventListener("change", (event) => {
        if (event.target.type === "checkbox") {
            updateSelectAllState();
        }
    });       

    const editSelectedBtn = document.getElementById("editSelected");
    // const studentTable = document.getElementById("studentTable");
    const deleteSelectedBtn = document.getElementById("deleteSelected");

        editSelectedBtn.addEventListener("click", () => {
            document.querySelectorAll("#studentTable input[type='checkbox']:checked").forEach(checkbox => {
                let row = checkbox.closest("tr");
                
                // Make each cell editable (except checkbox and actions)
                row.querySelectorAll(".editable").forEach(cell => {
                    let currentValue = cell.textContent.trim();
                    cell.innerHTML = `<input type="text" value="${currentValue}" class="form-control">`;
                });
            });
        });

        // Function to save edited data (you can call this when a "Save" button is clicked)
        function saveEditedData() {
            let updatedData = [];

            document.querySelectorAll("#studentTable input[type='checkbox']:checked").forEach(checkbox => {
                let row = checkbox.closest("tr");
                let regNo = row.dataset.regno;

                let updatedRow = {
                    regNo: regNo,
                    name: row.querySelector(".name input").value,
                    department: row.querySelector(".department input").value,
                    section: row.querySelector(".section input").value,
                    year: row.querySelector(".year input").value
                };

                updatedData.push(updatedRow);
            });

            console.log(updatedData); // Check the data before sending

            // Send updated data to the server
            fetch("Update_Student.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify(updatedData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert("Students updated successfully!");
                    location.reload(); 
                } else {
                    alert("Update failed!");
                }
            })
            .catch(error => console.error("Error updating students:", error));
        }

        const saveEditedDataBtn = document.getElementById("saveEditedData");
        saveEditedDataBtn.addEventListener("click", saveEditedData);
        
        //delete bulk
        deleteSelectedBtn.addEventListener("click", () => {
        const checkboxes = document.querySelectorAll("#studentTable input[type='checkbox']:checked");
        const regNos = [];

        checkboxes.forEach(checkbox => {
            let row = checkbox.closest("tr");
            let regNo = row.dataset.regno;
            regNos.push(regNo);
            // console.log("regNo: " + regNo);
        });

        if (regNos.length > 0) {
            if (confirm("Are you sure you want to delete the selected students?")) {
                fetch("Delete_Student_Bulk.php", {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json"
                            },
                            body: JSON.stringify({ regNos: regNos })
                        })
                        .then(response => {
                            return response.json(); // Parse JSON
                        })
                        .then(data => {
                            console.log(data); // Inspect the parsed data
                            if (data.success) {
                                alert("Students deleted successfully!");
                                location.reload();
                            } else {
                                alert("Delete failed: " + data.error);
                            }
                        })
                        .catch(error => {
                            console.error("Error deleting students:", error);
                        });
            }
        }   

    });
    });

        
</script>

</body>
</html>
