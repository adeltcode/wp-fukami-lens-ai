# WP Fukami Lens AI

WP Fukami Lens AI is an advanced AI integration plugin for WordPress, enabling not only Japanese spelling and grammar checking, but also deep, AI-powered search and question-answering over your site's content. By leveraging OpenAI and Anthropic APIs, the plugin allows you to interact with your site data in natural language—summarizing, searching, and extracting insights from posts and pages. It supports both the Classic Editor and Dashboard, and includes Retrieval-Augmented Generation (RAG) features for context-aware AI assistance with **LanceDB vector database** for efficient semantic search. 

This plugin is still under active development. 

## Features
- Japanese spelling and grammar checking using OpenAI or Anthropic APIs
- Deep semantic search and Q&A over your site's content using AI
- **LanceDB vector database integration** for efficient embedding storage and retrieval
- **Smart embedding duplicate checking** to avoid unnecessary API calls and reduce costs
- Proofreading metabox in the Classic Editor
- Dashboard AI Assistant widget (with RAG support)
- Semantic HTML processing for accurate context
- Token-based chunking and advanced tokenization (Docling/tiktoken)
- Per-post debug output for AI and chunking
- Settings page for API keys, models, and RAG options
- Python backend for chunking/tokenization (Docling or tiktoken)
- **Date range filtering** for content processing and search
- **Vector database management** with embedding storage and similarity search

## Requirements
- **WordPress 5.0+** (Classic Editor recommended for full proofreading features)
- **PHP 7.4+**
- **Python 3.8+** (for advanced chunking/tokenization)
- Python dependencies (install with `pip install -r python/requirements.txt`):
  - `docling` (preferred, for advanced chunking)
  - `tiktoken` (fallback chunking/tokenization)
  - `markdownify` (fallback HTML to Markdown)
  - `requests`
  - `lancedb` (for vector database)
  - `numpy`, `pandas`, `pydantic` (for LanceDB operations)
- OpenAI or Anthropic API key (set in plugin settings)

## Installation
1. Copy the `wp-fukami-lens-ai` folder to your WordPress `wp-content/plugins/` directory.
2. Install Python dependencies:
   ```
   cd wp-content/plugins/wp-fukami-lens-ai/python
   pip install -r requirements.txt
   ```
3. Activate the plugin in the WordPress admin.
4. Go to **Settings > WP Fukami Lens AI** and enter your API key(s) and select your preferred model/provider.

## Usage
- **Proofreading:** Edit a post in the Classic Editor and use the "Proofread" button in the metabox.
- **AI Assistant:** Use the Dashboard widget to ask questions about your site content (optionally using RAG).
- **Deep Search/Q&A:** Ask complex questions about your site's content and get AI-generated answers, summaries, or insights.
- **LanceDB Management:** Use the LanceDB Manager to store embeddings and perform semantic search with automatic duplicate checking.
- **Debugging:** See per-post debug output (HTML, chunking, tokens) in the plugin's Python runner page.

## Advanced Features

### Cost Optimization with Embedding Duplicate Checking
The plugin includes smart duplicate checking for embeddings to minimize API costs:

- **Automatic Detection:** Before making OpenAI API calls, the system checks if embeddings already exist for the requested posts
- **Selective Processing:** Only generates embeddings for posts that don't already have them
- **Upsert Operations:** Uses efficient database operations to handle both new and updated embeddings
- **Detailed Reporting:** Provides feedback on how many embeddings were found vs. newly created

### LanceDB Vector Database
- **Efficient Storage:** Stores embeddings in LanceDB for fast retrieval
- **Semantic Search:** Find similar content using vector similarity
- **Date Filtering:** Filter search results by date ranges
- **Database Statistics:** Monitor embedding storage and database size

### Date Range Filtering
- **Content Processing:** Filter posts by date range for chunking and embedding
- **Search Filtering:** Apply date filters to semantic search results
- **Flexible Queries:** Support for start date, end date, or both

## Advanced
- The plugin uses a Python backend for chunking/tokenization. If Docling is not available, it falls back to tiktoken/markdownify.
- RAG (Retrieval-Augmented Generation) settings allow you to use your own data source for context-aware answers.
- LanceDB integration provides efficient vector storage and retrieval for semantic search.
- All settings can be configured in the plugin's settings page.

## Testing
The plugin includes several test files to verify functionality:
- `test-date-filtering.php` - Tests date range filtering
- `test-lancedb.php` - Tests LanceDB integration
- `test-embedding-check.php` - Tests embedding duplicate checking
- `test-separation.php` - Tests chunking service separation

## Author
Patrick James Garcia 