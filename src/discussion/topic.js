/*
  Requirement: Populate the single topic page and manage replies.

  Instructions:
  1. Link this file to `topic.html` using:
     <script src="topic.js" defer></script>

  2. In `topic.html`, add the following IDs:
     - To the <h1>: `id="topic-subject"`
     - To the <article id="original-post">:
       - Add a <p> with `id="op-message"` for the message text.
       - Add a <footer> with `id="op-footer"` for the metadata.
     - To the <div> for the list of replies: `id="reply-list-container"`
     - To the "Post a Reply" <form>: `id="reply-form"`

  3. Implement the TODOs below.
*/

// --- Global Data Store ---
let currentTopicId = null;
let currentReplies = []; // Will hold replies for *this* topic
let isEditMode = false; // Track if we're in edit mode

// --- Element Selections ---
// TODO: Select all the elements you added IDs for in step 2.
let topicSubject = document.getElementById('topic-subject');
let originalPost = document.getElementById('original-post');
let opMessage = document.getElementById('op-message');
let opFooter = document.getElementById('op-footer');
let replyListContainer = document.getElementById('reply-list-container');
let replyForm = document.getElementById('reply-form');
// --- Functions ---

/**
 * TODO: Implement the getTopicIdFromURL function.
 * It should:
 * 1. Get the query string from `window.location.search`.
 * 2. Use the `URLSearchParams` object to get the value of the 'id' parameter.
 * 3. Return the id.
 */
function getTopicIdFromURL() {
  // ... your implementation here ...

  let queryString = window.location.search;
  let urll=new URLSearchParams (queryString);
  isEditMode = urll.get('edit') === 'true';
  return urll.get('id');


}

/**
 * TODO: Implement the renderOriginalPost function.
 * It takes one topic object.
 * It should:
 * 1. Set the `textContent` of `topicSubject` to the topic's subject.
 * 2. Set the `textContent` of `opMessage` to the topic's message.
 * 3. Set the `textContent` of `opFooter` to "Posted by: {author} on {date}".
 * 4. (Optional) Add a "Delete" button with `data-id="${topic.id}"` to the OP.
 */
function renderOriginalPost(topic) {
  // ... your implementation here ...
  topicSubject.textContent = topic.subject;
  opMessage.textContent = topic.message;
  opFooter.textContent = `Posted by: ${topic.author} on ${topic.date}`;
  
  // If in edit mode, make content editable
  if (isEditMode) {
    topicSubject.contentEditable = true;
    opMessage.contentEditable = true;
    topicSubject.style.cursor = 'text';
    opMessage.style.cursor = 'text';
    topicSubject.style.padding = '5px';
    opMessage.style.padding = '5px';
    topicSubject.style.border = '1px solid #ccc';
    opMessage.style.border = '1px solid #ccc';
    
    // Show save button in edit mode
    const saveBtn = document.createElement('button');
    saveBtn.textContent = 'Save Changes';
    saveBtn.id = 'save-topic-btn';
    saveBtn.style.marginTop = '10px';
    saveBtn.style.backgroundColor = '#27ae60';
    saveBtn.style.color = 'white';
    saveBtn.addEventListener('click', function() {
      topic.subject = topicSubject.textContent;
      topic.message = opMessage.textContent;
      alert('Topic updated successfully!');
      window.location.href = 'baord.html';
    });
    
    originalPost.appendChild(saveBtn);
  }
}

/**
 * TODO: Implement the createReplyArticle function.
 * It takes one reply object {id, author, date, text}.
 * It should return an <article> element matching the structure in `topic.html`.
 * - Include a <p> for the `text`.
 * - Include a <footer> for the `author` and `date`.
 * - Include a "Delete" button with class "delete-reply-btn" and `data-id="${id}"`.
 */
function createReplyArticle(reply) {
  // Create the article element
  const article = document.createElement('article');
  article.className = 'reply';
  
  // Create paragraph for reply text
  const p = document.createElement('p');
  p.textContent = reply.text;
  
  // Create footer for author and date
  const footer = document.createElement('footer');
  footer.textContent = `Posted by: ${reply.author} on ${reply.date}`;
  
  // Create delete button
  const deleteBtn = document.createElement('button');
  deleteBtn.className = 'delete-reply-btn';
  deleteBtn.setAttribute('data-id', reply.id);
  deleteBtn.textContent = 'Delete';
  
  // Create action container
  const actionDiv = document.createElement('div');
  actionDiv.className = 'reply-action';
  actionDiv.appendChild(deleteBtn);
  
  // Append all elements to article
  article.appendChild(p);
  article.appendChild(footer);
  article.appendChild(actionDiv);
  
  return article;
}

/**
 * TODO: Implement the renderReplies function.
 * It should:
 * 1. Clear the `replyListContainer`.
 * 2. Loop through the global `currentReplies` array.
 * 3. For each reply, call `createReplyArticle()`, and
 * append the resulting <article> to `replyListContainer`.
 */
function renderReplies() {
  // ... your implementation here ...
  replyListContainer.innerHTML = '';
 
  currentReplies.forEach(reply => {
    let replyxx = createReplyArticle(reply);
    replyListContainer.appendChild(replyxx);
  });
}

/**
 * TODO: Implement the handleAddReply function.
 * This is the event handler for the `replyForm` 'submit' event.
 * It should:
 * 1. Prevent the form's default submission.
 * 2. Get the text from `newReplyText.value`.
 * 3. If the text is empty, return.
 * 4. Create a new reply object:
 * {
 * id: `reply_${Date.now()}`,
 * author: 'Student' (hardcoded),
 * date: new Date().toISOString().split('T')[0],
 * text: (reply text value)
 * }
 * 5. Add this new reply to the global `currentReplies` array (in-memory only).
 * 6. Call `renderReplies()` to refresh the list.
 * 7. Clear the `newReplyText` textarea.
 */
function handleAddReply(event) {
  event.preventDefault();
  
  // Get the reply text input
  const newReplyText = document.getElementById('new-reply');
  const text = newReplyText.value.trim();
  
  // Return if text is empty
  if (!text) {
    return;
  }
  
  // Create new reply object
  const replyId = `reply_${Date.now()}`;
  const newReply = {
    id: replyId,
    author: 'Student',
    date: new Date().toISOString().split('T')[0],
    text: text
  };

  // POST to API
  fetch('api/index.php?resource=replies', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ reply_id: replyId, topic_id: currentTopicId, text: newReply.text, author: newReply.author })
  }).then(r => r.json()).then(jr => {
    if (jr && jr.success) {
      currentReplies.push(newReply);
      renderReplies();
      newReplyText.value = '';
    } else {
      console.error('Failed to post reply', jr);
    }
  }).catch(err => {
    console.error('Reply POST error', err);
  });
}

/**
 * Handle edit button for the original post
 */
function handleEditPost() {
  const subject = opMessage.textContent;
  const newSubject = prompt('Edit message:', subject);
  
  if (newSubject && newSubject.trim()) {
    opMessage.textContent = newSubject.trim();
  }
}

/**
 * Handle delete button for the original post
 */
function handleDeletePost() {
  if (confirm('Are you sure you want to delete this topic?')) {
    topicSubject.textContent = 'Topic has been deleted.';
    originalPost.style.display = 'none';
  }
}

/**
 * TODO: Implement the handleReplyListClick function.
 * This is an event listener on the `replyListContainer` (for delegation).
 * It should:
 * 1. Check if the clicked element (`event.target`) has the class "delete-reply-btn".
 * 2. If it does, get the `data-id` attribute from the button.
 * 3. Update the global `currentReplies` array by filtering out the reply
 * with the matching ID (in-memory only).
 * 4. Call `renderReplies()` to refresh the list.
 */
function handleReplyListClick(event) {
  if (event.target.classList.contains('delete-reply-btn')) {
    const replyId = event.target.getAttribute('data-id');
    if (!confirm('Delete this reply?')) return;

    // Request delete from API
    fetch(`api/index.php?resource=replies&id=${encodeURIComponent(replyId)}`, { method: 'DELETE' })
      .then(r => r.json())
      .then(jr => {
        if (jr && jr.success) {
          currentReplies = currentReplies.filter(reply => reply.id !== replyId);
          renderReplies();
        } else {
          console.error('Failed to delete reply', jr);
        }
      }).catch(err => console.error('Delete reply error', err));
  }
}

/**
 * TODO: Implement an `initializePage` function.
 * This function needs to be 'async'.
 * It should:
 * 1. Get the `currentTopicId` by calling `getTopicIdFromURL()`.
 * 2. If no ID is found, set `topicSubject.textContent = "Topic not found."` and stop.
 * 3. `fetch` both 'topics.json' and 'replies.json' (you can use `Promise.all`).
 * 4. Parse both JSON responses.
 * 5. Find the correct topic from the topics array using the `currentTopicId`.
 * 6. Get the correct replies array from the replies object using the `currentTopicId`.
 * Store this in the global `currentReplies` variable. (If no replies exist, use an empty array).
 * 7. If the topic is found:
 * - Call `renderOriginalPost()` with the topic object.
 * - Call `renderReplies()` to show the initial replies.
 * - Add the 'submit' event listener to `replyForm` (calls `handleAddReply`).
 * - Add the 'click' event listener to `replyListContainer` (calls `handleReplyListClick`).
 * 8. If the topic is not found, display an error in `topicSubject`.
 */
async function initializePage() {
  // Get topic ID from URL
  currentTopicId = getTopicIdFromURL();
  
  // Check if ID was found
  if (!currentTopicId) {
    topicSubject.textContent = 'Topic not found.';
    return;
  }
  
  try {
    // Fetch topic and replies from PHP API
    const topicResp = await fetch(`api/index.php?resource=topics&id=${encodeURIComponent(currentTopicId)}`);
    const topicJson = await topicResp.json();
    const topic = (topicJson && topicJson.success) ? topicJson.data : topicJson;

    if (!topic) {
      topicSubject.textContent = 'Topic not found.';
      return;
    }

    const repliesResp = await fetch(`api/index.php?resource=replies&topic_id=${encodeURIComponent(currentTopicId)}`);
    const repliesJson = await repliesResp.json();
    const repliesData = (repliesJson && repliesJson.success) ? repliesJson.data : repliesJson;

    // Normalize replies array (API may return array or object keyed by topic id)
    let rawReplies = [];
    if (Array.isArray(repliesData)) {
      rawReplies = repliesData;
    } else if (repliesData && repliesData[currentTopicId]) {
      rawReplies = repliesData[currentTopicId];
    }
    currentReplies = (rawReplies || []).map(r => ({ id: r.reply_id || r.id, author: r.author, date: r.date, text: r.text }));
    
    // Render the original post
    renderOriginalPost(topic);
    
    // Render the replies
    renderReplies();
    
    // Add event listeners for post actions
    const editPostBtn = document.getElementById('edit-post');
    const deletePostBtn = document.getElementById('delete-post');
    
    if (editPostBtn) {
      editPostBtn.addEventListener('click', handleEditPost);
    }
    if (deletePostBtn) {
      deletePostBtn.addEventListener('click', handleDeletePost);
    }
    
    // Add event listeners for reply form and list
    replyForm.addEventListener('submit', handleAddReply);
    replyListContainer.addEventListener('click', handleReplyListClick);
  } catch (error) {
    console.error('Error loading page:', error);
    topicSubject.textContent = 'Error loading topic.';
  }
}

// --- Initial Page Load ---
initializePage();
