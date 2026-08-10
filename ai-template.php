<?php
/**
 * Template Name: Flex Rock AI
 */

get_header();

$ai_server = esc_url(get_option('fr_ai_server_url'));
?>

<style>
/* ====================== Flex Rock AI - Subtle Light Theme ====================== */

.fr-ai {
  font-family: 'Oxanium', system-ui, sans-serif;
}

.fr-ai__notice {
  color: #fb0505;
}

/* Server info */
.fr-ai code {
  background: #f8fafc;
  color: #0050b5;
  padding: 0.2em 0.5em;
  border-radius: 4px;
  border: 1px solid #e2e8f0;
}

/* Form */
.fr-ai__form {
  display: grid;
  gap: 1.2rem;
}

.fr-ai__form textarea {
  background: #ffffff;
  border: 2px solid #cbd5e1;
  border-radius: 8px;
  color: #181818;
  transition: border-color 0.3s ease, box-shadow 0.3s ease;
}

.fr-ai__form textarea:focus {
  border-color: #00a8f6;
  box-shadow: 0 0 0 3px rgba(0, 168, 246, 0.15);
  outline: none;
}

.fr-ai__form button {
  background: linear-gradient(135deg, #000000, #00a8f6);
  color: white;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.3s ease;
  width: fit-content;
}

.fr-ai__form button:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 15px rgba(251, 5, 5, 0.25);
}

/* Markdown */
.fr-ai-markdown {
  color: #181818;
}

.fr-ai-markdown h1,
.fr-ai-markdown h2,
.fr-ai-markdown h3 {
  color: #0050b5;
}

.fr-ai-markdown code {
  background: #f1f5f9;
  padding: 0.1em 0.4em;
  border-radius: 3px;
  color: #181818;
}

/* Results */
.fr-ai-results p strong {
  color: #181818;
}

/* Table */
.fr-ai-products {
  width: 100%;
  border-collapse: collapse;
  background: #ffffff;
  border-radius: 8px;
  overflow: hidden;
}

.fr-ai-products th {
  background: #f8fafc;
  color: #181818;
  border-bottom: 2px solid #fb0505;
}

.fr-ai-products td {
  border-bottom: 1px solid #e2e8f0;
}

.fr-ai-products tr:nth-child(even) {
  background: #f8fafc;
}

.fr-ai-products tr:hover {
  background: #f0f7ff;
}

.fr-ai-products a {
  color: #0050b5;
}

.fr-ai-products a:hover {
  color: #fb0505;
}

/* Subtle Responsive Tweak */
@media (max-width: 768px) {
  .fr-ai {
    border-radius: 12px;
  }
}
</style>

<main id="primary" class="site-main">
  <section class="fr-ai">
    <h1>Flex Rock AI</h1>

    <p class="fr-ai__notice">
      * Start the local Flex Rock AI server before using this tool.
    </p>
    <p>
      Current AI Server:
      <code><?php echo esc_html($ai_server ?: 'Not set'); ?></code>
    </p>

    <?php if (!$ai_server) : ?>
      <p><strong>AI server URL has not been set yet.</strong></p>
    <?php endif; ?>

    <form class="fr-ai__form" id="fr-ai-form">
      <label for="fr-ai-question">Ask a question</label>
      <textarea id="fr-ai-question" rows="5" placeholder="Which products are missing dimensions?"></textarea>

      <button type="submit">Ask AI</button>
    </form>

    <div id="fr-ai-response" class="fr-ai__response"></div>
  </section>
</main>

<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
<script>
const FR_AI_SERVER = <?php echo wp_json_encode($ai_server); ?>;
const ASK_URL = <?php echo wp_json_encode(esc_url_raw(rest_url('fr-ai/v1/ask'))); ?>;
const PRODUCTS_URL = <?php echo wp_json_encode(esc_url_raw(rest_url('fr-ai/v1/products'))); ?>;
const WP_NONCE = <?php echo wp_json_encode(wp_create_nonce('wp_rest')); ?>;

let currentQuestion = "";
let currentOffset = 0;
let currentTotal = 0;

function productTable(products) {
  if (!products || !products.length) return "";

  return `
    <table class="fr-ai-products">
      <thead>
        <tr>
          <th>SKU</th>
          <th>Product</th>
          <th>Missing Fields</th>
          <th>Edit</th>
        </tr>
      </thead>
      <tbody>
        ${products.map(product => `
          <tr>
            <td>${product.sku}</td>
            <td>${product.name}</td>
            <td>${product.missing.join(", ")}</td>
            <td><a href="${product.edit_url}" target="_blank" rel="noopener">Edit</a></td>
          </tr>
        `).join("")}
      </tbody>
    </table>
  `;
}

async function loadProducts(offset, limit = 10) {
  const response = await fetch(PRODUCTS_URL, {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      "X-WP-Nonce": WP_NONCE
    },
    body: JSON.stringify({
      question: currentQuestion,
      offset: offset,
      limit: limit
    })
  });

  const text = await response.text();

  try {
    return JSON.parse(text);
  } catch (e) {
    throw new Error(text || "Empty response from product endpoint.");
  }
}

document.getElementById("fr-ai-form").addEventListener("submit", async function(e) {
  e.preventDefault();

  const question = document.getElementById("fr-ai-question").value.trim();
  const responseBox = document.getElementById("fr-ai-response");

  currentQuestion = question;
  currentOffset = 0;
  currentTotal = 0;

  if (!FR_AI_SERVER) {
    responseBox.innerHTML = `
      <p><strong>AI server URL is missing.</strong></p>
      <p>Start the local Flex Rock AI launcher so it can update WordPress.</p>
    `;
    return;
  }

  if (!question) {
    responseBox.innerHTML = `<p><strong>Please enter a question.</strong></p>`;
    return;
  }

  responseBox.innerHTML = "Thinking...";

  try {
    const response = await fetch(ASK_URL, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "X-WP-Nonce": WP_NONCE
      },
      body: JSON.stringify({ question })
    });

    const text = await response.text();
    
    let data;
    
    try {
      data = JSON.parse(text);
    } catch (e) {
      responseBox.innerHTML = `
        <p><strong>Server returned invalid JSON.</strong></p>
        <pre>${text || "Empty response from server."}</pre>
      `;
      return;
    }

    currentOffset = data.shown || 0;
    currentTotal = data.total_matches || 0;

    const hasProducts = data.products && data.products.length;

    responseBox.innerHTML = `
      <h1 style="text-decoration: underline; color: black;">AI Response</h1>
      <div class="fr-ai-markdown">
        ${marked.parse(data.answer || data.message || "No answer returned.")}
      </div>
    
      ${hasProducts ? `
        <div class="fr-ai-results">
          <p><strong>Showing ${currentOffset} of ${currentTotal} matching products.</strong></p>
          ${productTable(data.products)}
          ${data.has_more ? `
            <button type="button" id="fr-ai-load-more">Show 10 More</button>
            <button type="button" id="fr-ai-load-all">Load All</button>
          ` : ""}
        </div>
      ` : ""}
    `;
  } catch (error) {
    responseBox.innerHTML = `
      <p><strong>AI server is not available.</strong></p>
      <p>${error.message}</p>
    `;
  }
});

document.addEventListener("click", async function(e) {
  if (e.target.id === "fr-ai-load-more") {
    const data = await loadProducts(currentOffset, 10);

    document.querySelector(".fr-ai-products tbody").insertAdjacentHTML(
      "beforeend",
      data.products.map(product => `
        <tr>
          <td>${product.sku}</td>
          <td>${product.name}</td>
          <td>${product.missing.join(", ")}</td>
          <td><a href="${product.edit_url}" target="_blank" rel="noopener">Edit</a></td>
        </tr>
      `).join("")
    );

    currentOffset += data.returned;

    document.querySelector(".fr-ai-results p").innerHTML =
      `<strong>Showing ${currentOffset} of ${data.total} matching products.</strong>`;

    if (!data.has_more) {
      document.getElementById("fr-ai-load-more")?.remove();
      document.getElementById("fr-ai-load-all")?.remove();
    }
  }
  
    if (e.target.id === "fr-ai-load-all") {
      const loadAllButton = document.getElementById("fr-ai-load-all");
      const loadMoreButton = document.getElementById("fr-ai-load-more");
      const resultsText = document.querySelector(".fr-ai-results p");
      const tableBody = document.querySelector(".fr-ai-products tbody");
    
      loadAllButton.disabled = true;
      loadAllButton.textContent = "Loading all...";
    
      if (loadMoreButton) {
        loadMoreButton.disabled = true;
      }
    
      while (true) {
        const data = await loadProducts(currentOffset, 10);
    
        if (!data.products || !data.products.length) {
          break;
        }
    
        tableBody.insertAdjacentHTML(
          "beforeend",
          data.products.map(product => `
            <tr>
              <td>${product.sku}</td>
              <td>${product.name}</td>
              <td>${product.missing.join(", ")}</td>
              <td><a href="${product.edit_url}" target="_blank" rel="noopener">Edit</a></td>
            </tr>
          `).join("")
        );
    
        currentOffset += data.returned;
        currentTotal = data.total;
    
        resultsText.innerHTML =
          `<strong>Showing ${currentOffset} of ${currentTotal} matching products.</strong>`;
    
        if (!data.has_more || currentOffset >= currentTotal) {
          break;
        }
    
        await new Promise(resolve => setTimeout(resolve, 250));
      }
    
      document.getElementById("fr-ai-load-more")?.remove();
      document.getElementById("fr-ai-load-all")?.remove();
    }
});
</script>

<?php
get_footer();