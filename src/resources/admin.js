/*
  Requirement: Make the "Manage Resources" page interactive.

  Instructions:
  1. Link this file to `admin.html` using:
     <script src="admin.js" defer></script>
  
  2. In `admin.html`, add an `id="resources-tbody"` to the <tbody> element
     inside your `resources-table`.
  
  3. Implement the TODOs below.
*/

// --- Global Data Store ---
// This will hold the resources loaded from the JSON file.
let resources = [];

// --- Element Selections ---
// TODO: Select the resource form ('#resource-form').
const resourceForm = document.getElementById('resource-form');

// TODO: Select the resources table body ('#resources-tbody').
const resourcesTbody = document.getElementById('resources-tbody');

// --- Functions ---

/**
 * TODO: Implement the createResourceRow function.
 * It takes one resource object {id, title, description}.
 * It should return a <tr> element with the following <td>s:
 * 1. A <td> for the `title`.
 * 2. A <td> for the `description`.
 * 3. A <td> containing two buttons:
 * - An "Edit" button with class "edit-btn" and `data-id="${id}"`.
 * - A "Delete" button with class "delete-btn" and `data-id="${id}"`.
 */
function createResourceRow(resource) {
  const row=document.createElement('tr')
  const dataTitle = document.createElement('td');
  const dataDescription = document.createElement('td');
  const buttomColumn= document.createElement('td');
  const editButton = document.createElement('button');
  const deleteButton = document.createElement('button');

  dataTitle.textContent=resource.title;
  dataDescription.textContent=resource.description;
  editButton.textContent="Edit";
  deleteButton.textContent ="Delete";
  
  editButton.dataset.id = resource.id;
  deleteButton.dataset.id = resource.id;

  editButton.classList.add("edit-btn");
  deleteButton.classList.add("delete-btn");


  row.appendChild(dataTitle);
  row.appendChild(dataDescription);
  buttomColumn.appendChild(editButton);
  buttomColumn.appendChild(deleteButton);
  row.appendChild(buttomColumn);

  return row;
}

/**
 * TODO: Implement the renderTable function.
 * It should:
 * 1. Clear the `resourcesTableBody`.
 * 2. Loop through the global `resources` array.
 * 3. For each resource, call `createResourceRow()`, and
 * append the resulting <tr> to `resourcesTableBody`.
 */
function renderTable() {
  const tableBody = document.getElementById('resources-tbody');
  tableBody.innerHTML="";

  resources.forEach(function(resource){
    tableBody.appendChild(createResourceRow(resource));
  });
}

/**
 * TODO: Implement the handleAddResource function.
 * This is the event handler for the form's 'submit' event.
 * It should:
 * 1. Prevent the form's default submission.
 * 2. Get the values from the title, description, and link inputs.
 * 3. Create a new resource object with a unique ID (e.g., `id: \`res_${Date.now()}\``).
 * 4. Add this new resource object to the global `resources` array (in-memory only).
 * 5. Call `renderTable()` to refresh the list.
 * 6. Reset the form.
 */
function handleAddResource(event) {
  event.preventDefault();

  const theTitle = document.getElementById("resource-title");
  const theDescription = document.getElementById("resource-description");
  const thelink = document.getElementById("resource-link");

  const titleValue= theTitle.value;
  const descriptionValue=theDescription.value;
  const linkValue=thelink.value;

  const newResource = {
    id: `res_${Date.now()}`,
    title: titleValue ,
    description: descriptionValue,
    link:linkValue
  };

  resources.push(newResource);

  renderTable();

  event.target.reset();
}

/**
 * TODO: Implement the handleTableClick function.
 * This is an event listener on the `resourcesTableBody` (for delegation).
 * It should:
 * 1. Check if the clicked element (`event.target`) has the class "delete-btn".
 * 2. If it does, get the `data-id` attribute from the button.
 * 3. Update the global `resources` array by filtering out the resource
 * with the matching ID (in-memory only).
 * 4. Call `renderTable()` to refresh the list.
 */
function handleTableClick(event) {

  if (event.target.classList.contains("delete-btn")) {
    const idToDelete=event.target.dataset.id;
    resources = resources.filter(row => row.id !== idToDelete);
    renderTable();
  }
  
}

/**
 * TODO: Implement the loadAndInitialize function.
 * This function needs to be 'async'.
 * It should:
 * 1. Use `fetch()` to get data from 'resources.json'.
 * 2. Parse the JSON response and store the result in the global `resources` array.
 * 3. Call `renderTable()` to populate the table for the first time.
 * 4. Add the 'submit' event listener to `resourceForm` (calls `handleAddResource`).
 * 5. Add the 'click' event listener to `resourcesTableBody` (calls `handleTableClick`).
 */
async function loadAndInitialize() {
  // ... your implementation here ...
}

// --- Initial Page Load ---
// Call the main async function to start the application.
loadAndInitialize();
