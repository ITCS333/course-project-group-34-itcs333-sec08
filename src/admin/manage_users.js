/*  
  Requirement: Add interactivity and data management to the Admin Portal.
*/

let students = [];

// FULL absolute path for API (fixes 404 and session issues)
const API_URL = "api/index.php";

// --- Select Elements ---
const studentTableBody = document.querySelector("#student-table tbody");
const addStudentForm = document.getElementById("add-student-form");
const changePasswordForm = document.getElementById("password-form");
const searchInput = document.getElementById("search-input");
const tableHeaders = document.querySelectorAll("#student-table thead th");


// ===========================
// Create Table Row
// ===========================
function createStudentRow(student) {
  const tr = document.createElement("tr");

  tr.innerHTML = `
    <td>${student.name}</td>
    <td>${student.student_id}</td>
    <td>${student.email}</td>
    <td>
      <button class="edit-btn" data-id="${student.student_id}">Edit</button>
      <button class="delete-btn" data-id="${student.student_id}">Delete</button>
    </td>
  `;

  return tr;
}


// ===========================
// Render Table
// ===========================
function renderTable(studentArray) {
  studentTableBody.innerHTML = "";
  studentArray.forEach(s => {
    studentTableBody.appendChild(createStudentRow(s));
  });
}


// ===========================
// Change Password
// ===========================
async function handleChangePassword(event) {
  event.preventDefault();

  const currentPassword = document.getElementById("current-password").value.trim();
  const newPassword = document.getElementById("new-password").value.trim();
  const confirmPassword = document.getElementById("confirm-password").value.trim();

  if (newPassword !== confirmPassword) {
    alert("Passwords do not match.");
    return;
  }

  const response = await fetch(`${API_URL}?action=change_password`, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    credentials: "include",
    body: JSON.stringify({
      current_password: currentPassword,
      new_password: newPassword
    })
  });

  const result = await response.json();

  if (!result.success) {
    alert(result.message);
    return;
  }

  alert("Password updated successfully!");

  document.getElementById("current-password").value = "";
  document.getElementById("new-password").value = "";
  document.getElementById("confirm-password").value = "";
}


// ===========================
// Add Student
// ===========================
async function handleAddStudent(event) {
  event.preventDefault();

  const name = document.getElementById("student-name").value.trim();
  const email = document.getElementById("student-email").value.trim();
  const password = document.getElementById("default-password").value.trim();

  if (!name || !email || !password) {
    alert("Please fill out all fields.");
    return;
  }

  const response = await fetch(`${API_URL}?type=add`, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    credentials: "include",
    body: JSON.stringify({ name, email, password })
  });

  const result = await response.json();

  if (!result.success) {
    alert(result.message);
    return;
  }

  addStudentForm.reset();
  loadStudentsAndInitialize();
}


// ===========================
// Delete / Edit via Table Click
// ===========================
async function handleTableClick(event) {
  const id = event.target.dataset.id;
  if (!id) return;

  if (event.target.classList.contains("delete-btn")) {
    const confirmed = confirm("Are you sure you want to delete this student? This action cannot be undone.");
    if (!confirmed) return;

    await fetch(`${API_URL}?student_id=${id}`, {
      method: "DELETE",
      credentials: "include"
    });
    loadStudentsAndInitialize();
  }

  if (event.target.classList.contains("edit-btn")) {
    const newName = prompt("Enter new name:");
    const newEmail = prompt("Enter new email:");

    if (!newName || !newEmail) return;

    await fetch(API_URL, {
      method: "PUT",
      headers: { "Content-Type": "application/json" },
      credentials: "include",
      body: JSON.stringify({ student_id: id, name: newName, email: newEmail })
    });

    loadStudentsAndInitialize();
  }
}


// ===========================
// Search Students
// ===========================
function handleSearch(event) {
  const term = event.target.value.toLowerCase();

  if (term === "") {
    renderTable(students);
    return;
  }

  const filtered = students.filter(s =>
    s.name.toLowerCase().includes(term) ||
    String(s.student_id).toLowerCase().includes(term)
  );

  renderTable(filtered);
}


// ===========================
// Sort Columns
// ===========================
function handleSort(event) {
  const index = event.currentTarget.cellIndex;
  const keyMap = ["name", "student_id", "email"];
  const key = keyMap[index];

  let dir = event.currentTarget.dataset.sort || "asc";
  event.currentTarget.dataset.sort = dir === "asc" ? "desc" : "asc";

  students.sort((a, b) => {
    const aVal = String(a[key]).toLowerCase();
    const bVal = String(b[key]).toLowerCase();
    return dir === "asc"
      ? aVal.localeCompare(bVal)
      : bVal.localeCompare(aVal);
  });

  renderTable(students);
}


// ===========================
// Load Students + Initialize
// ===========================
async function loadStudentsAndInitialize() {
  const response = await fetch(`${API_URL}?type=get_all`, {
    credentials: "include"
  });

  const result = await response.json();

  students = result.data;
  renderTable(students);

  changePasswordForm.addEventListener("submit", handleChangePassword);
  addStudentForm.addEventListener("submit", handleAddStudent);
  studentTableBody.addEventListener("click", handleTableClick);
  searchInput.addEventListener("input", handleSearch);
  tableHeaders.forEach(th => th.addEventListener("click", handleSort));
}


// ===========================
// Initial Page Load
// ===========================
async function initAdminPage() {
  try {
    const res = await fetch("../auth/api/session.php", {
      credentials: "include"
    });

    const session = await res.json();

    if (!session.logged_in || session.user.role !== "admin") {
      alert("You must be logged in as an admin to access this page.");
      window.location.href = "../auth/login.html";
      return;
    }

    loadStudentsAndInitialize();

  } catch (err) {
    alert("Unable to verify session. Please log in again.");
    window.location.href = "../auth/login.html";
  }
}

initAdminPage();
