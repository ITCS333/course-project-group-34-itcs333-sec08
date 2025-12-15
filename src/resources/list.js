/*
  Requirement: Populate the "Course Resources" list page.

  Instructions:
  1. Link this file to `list.html` using:
     <script src="list.js" defer></script>

  2. In `list.html`, add an `id="resource-list-section"` to the
     <section> element that will contain the resource articles.

  3. Implement the TODOs below.
*/

// --- Element Selections ---
// TODO: Select the section for the resource list ('#resource-list-section').
const resourceSection = document.getElementById('resource-list-section');

// --- Functions ---

/**
 * TODO: Implement the createResourceArticle function.
 * It takes one resource object {id, title, description}.
 * It should return an <article> element matching the structure in `list.html`.
 * The "View Resource & Discussion" link's `href` MUST be set to `details.html?id=${id}`.
 * (This is how the detail page will know which resource to load).
 */
function createResourceArticle(resource) {
  const Article = document.createElement('article');
  const H3 = document.createElement('h3');
  const Text = document.createElement('p');
  const Link = document.createElement('a');

  H3.textContent = resource.title;
  Text.textContent = resource.description;
  Link.textContent = "View Resource & Discussion";
  Link.href = `details.html?id=${resource.id}`

  Article.appendChild(H3);
  Article.appendChild(Text);
  Article.appendChild(Link);

  return Article;
}

/**
 * TODO: Implement the loadResources function.
 * This function needs to be 'async'.
 * It should:
 * 1. Use `fetch()` to get data from 'resources.json'.
 * 2. Parse the JSON response into an array.
 * 3. Clear any existing content from `listSection`.
 * 4. Loop through the resources array. For each resource:
 * - Call `createResourceArticle()`.
 * - Append the returned <article> element to `listSection`.
 */

async function loadResources() {
  const response = await fetch('api/index.php');
  const json = await response.json();

  const data = json.data;

  resourceSection.innerHTML = "";

  data.forEach(resource => {
    resourceSection.appendChild(createResourceArticle(resource));
  });
}


// --- Initial Page Load ---
// Call the function to populate the page.
loadResources();
