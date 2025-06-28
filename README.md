# WP Fukami Lens AI

WP Fukami Lens AI is an advanced AI integration plugin for WordPress, enabling not only Japanese spelling and grammar checking, but also deep, AI-powered search and question-answering over your site's content. By leveraging OpenAI and Anthropic APIs, the plugin allows you to interact with your site data in natural language—summarizing, searching, and extracting insights from posts and pages. It supports both the Classic Editor and Dashboard, and includes Retrieval-Augmented Generation (RAG) features for context-aware AI assistance. 

This plugin is still under active development. 

## Features
- Japanese spelling and grammar checking using OpenAI or Anthropic APIs
- Deep semantic search and Q&A over your site's content using AI
- Proofreading metabox in the Classic Editor
- Dashboard AI Assistant widget (with RAG support)
- Semantic HTML processing for accurate context
- Token-based chunking and advanced tokenization (Docling/tiktoken)
- Per-post debug output for AI and chunking
- Settings page for API keys, models, and RAG options
- Python backend for chunking/tokenization (Docling or tiktoken)

## Requirements
- **WordPress 5.0+** (Classic Editor recommended for full proofreading features)
- **PHP 7.4+**
- **Python 3.8+** (for advanced chunking/tokenization)
- Python dependencies (install with `pip install -r python/requirements.txt`):
  - `docling` (preferred, for advanced chunking)
  - `tiktoken` (fallback chunking/tokenization)
  - `markdownify` (fallback HTML to Markdown)
  - `requests`
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
- **Debugging:** See per-post debug output (HTML, chunking, tokens) in the plugin's Python runner page.

## Advanced
- The plugin uses a Python backend for chunking/tokenization. If Docling is not available, it falls back to tiktoken/markdownify.
- RAG (Retrieval-Augmented Generation) settings allow you to use your own data source for context-aware answers.
- All settings can be configured in the plugin's settings page.

## Author
Patrick James Garcia 