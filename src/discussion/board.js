/*
  Requirement: Make the "Discussion Board" page interactive.

  Instructions:
  1. Link this file to `board.html` (or `baord.html`) using:
     <script src="board.js" defer></script>
  
  2. In `board.html`, add an `id="topic-list-container"` to the 'div'
     that holds the list of topic articles.
  
  3. Implement the TODOs below.
*/

// --- Global Data Store ---
// This will hold the topics loaded from the JSON file.
let topics = [];

// --- Element Selections ---
// TODO: Select the new topic form ('#new-topic-form').
const newTopicForm = document.querySelector("#new-topic-form");

// TODO: Select the topic list container ('#topic-list-container').
const topicListContainer = document.querySelector("#topic-list-container");

// --- Functions ---

/**
 * TODO: Implement the createTopicArticle function.
 * It takes one topic object {id, subject, author, date}.
 * It should return an <article> element matching the structure in `board.html`.
 * - The main link's `href` MUST be `topic.html?id=${id}`.
 * - The footer should contain the author and date.
 * - The actions div should contain an "Edit" button and a "Delete" button.
 * - The "Delete" button should have a class "delete-btn" and `data-id="${id}"`.
 */
function createTopicArticle(topic) {
  // ... your implementation here ...
  const article = document.createElement("article");
  article.className = "topic-item";

  article.innerHTML = `
    <a href="topic.html?id=${topic.id}" class="topic-link">
      <h3>${topic.subject}</h3>
    </a>
    <footer>
      <span>Posted by <strong>${topic.author}</strong> on ${topic.date}</span>
    </footer>
    <div class="actions">
      <button class="edit-btn">Edit</button>
      <button class="delete-btn" data-id="${topic.id}">Delete</button>
    </div>
  `;

  return article;
}

/**
 * TODO: Implement the renderTopics function.
 * It should:
 * 1. Clear the `topicListContainer`.
 * 2. Loop through the global `topics` array.
 * 3. For each topic, call `createTopicArticle()`, and
 * append the resulting <article> to `topicListContainer`.
 */
function renderTopics() {
  // ... your implementation here ...
  topicListContainer.innerHTML = "";

  topics.forEach((topic) => {
    const articleElement = createTopicArticle(topic);
    topicListContainer.appendChild(articleElement);
  });
}

/**
 * TODO: Implement the handleCreateTopic function.
 * This is the event handler for the form's 'submit' event.
 * It should:
 * 1. Prevent the form's default submission.
 * 2. Get the values from the '#topic-subject' and '#topic-message' inputs.
 * 3. Create a new topic object with the structure:
 * {
 * id: `topic_${Date.now()}`,
 * subject: (subject value),
 * message: (message value),
 * author: 'Student' (use a hardcoded author for this exercise),
 * date: new Date().toISOString().split('T')[0] // Gets today's date YYYY-MM-DD
 * }
 * 4. Add this new topic object to the global `topics` array (in-memory only).
 * 5. Call `renderTopics()` to refresh the list.
 * 6. Reset the form.
 */
async function handleCreateTopic(event) {
  event.preventDefault();

  const subjectInput = document.querySelector("#topic-subject");
  const messageInput = document.querySelector("#topic-message");

  const topicId = `topic_${Date.now()}`;
  const newTopic = {
    id: topicId,
    subject: subjectInput.value,
    message: messageInput.value,
    author: "Student",
    date: new Date().toISOString().split("T")[0],
  };

  try {
    const res = await fetch("api/index.php?resource=topics", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ topic_id: topicId, subject: newTopic.subject, message: newTopic.message, author: newTopic.author }),
    });
    const jr = await res.json();
    if (jr && jr.success) {
      topics.push(newTopic);
      renderTopics();
      newTopicForm.reset();
    } else {
      console.error('Failed to create topic', jr);
    }
  } catch (err) {
    console.error('Create topic error:', err);
  }
}

/**
 * TODO: Implement the handleTopicListClick function.
 * This is an event listener on the `topicListContainer` (for delegation).
 * It should:
 * 1. Check if the clicked element (`event.target`) has the class "delete-btn".
 * 2. If it does, get the `data-id` attribute from the button.
 * 3. Update the global `topics` array by filtering out the topic
 * with the matching ID (in-memory only).
 * 4. Call `renderTopics()` to refresh the list.
 */
async function handleTopicListClick(event) {
  // Handle delete button (calls API)
  if (event.target.classList.contains("delete-btn")) {
    const topicId = event.target.getAttribute("data-id");
    if (!confirm("Are you sure you want to delete this topic?")) return;
    try {
      const res = await fetch(`api/index.php?resource=topics&id=${encodeURIComponent(topicId)}`, { method: 'DELETE' });
      const jr = await res.json();
      if (jr && jr.success) {
        topics = topics.filter((topic) => topic.id !== topicId);
        renderTopics();
      } else {
        console.error('Failed to delete topic', jr);
      }
    } catch (err) {
      console.error('Delete topic error:', err);
    }
    return;
  }

  // Handle edit button - navigate to topic page with edit mode
  if (event.target.classList.contains("edit-btn")) {
    const article = event.target.closest("article");
    const topicLink = article.querySelector("a");
    const href = topicLink.getAttribute("href");

    // Navigate to topic page with edit parameter
    window.location.href = href + "&edit=true";
  }
}

/**
 * TODO: Implement the loadAndInitialize function.
 * This function needs to be 'async'.
 * It should:
 * 1. Use `fetch()` to get data from 'topics.json'.
 * 2. Parse the JSON response and store the result in the global `topics` array.
 * 3. Call `renderTopics()` to populate the list for the first time.
 * 4. Add the 'submit' event listener to `newTopicForm` (calls `handleCreateTopic`).
 * 5. Add the 'click' event listener to `topicListContainer` (calls `handleTopicListClick`).
 */
async function loadAndInitialize() {
  try {
    const response = await fetch("api/index.php?resource=topics");
    const json = await response.json();
    const data = (json && json.success) ? json.data : json;
    topics = (data || []).map(t => ({ id: t.topic_id || t.id, subject: t.subject, author: t.author, date: t.date }));
    renderTopics();

    newTopicForm.addEventListener("submit", handleCreateTopic);
    topicListContainer.addEventListener("click", handleTopicListClick);
  } catch (error) {
    console.error("Error loading topics:", error);
    topicListContainer.innerHTML =
      "<p>Error loading topics. Please make sure the API is available.</p>";
  }
}

// --- Initial Page Load ---
// Call the main async function to start the application.
loadAndInitialize();
