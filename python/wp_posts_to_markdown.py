"""
wp_posts_to_markdown.py
----------------------
Converts a list of WordPress posts (in JSON format) to Markdown with YAML front matter for chunking/embedding.

- Expects a JSON file as the first argument (from PHP/WordPress), or defaults to 'posts.json'.
- Uses docling (preferred) or markdownify (fallback) to convert HTML post content to Markdown.
- Outputs one Markdown document per post, separated by a clear delimiter.
- Supports token-based chunking for OpenAI models using tiktoken and npa_rag_max_tokens.

Usage:
    python3 wp_posts_to_markdown.py /path/to/posts.json

This script is designed to be called from a PHP integration (see runner.php), but can also be imported as a module.
"""
import sys
import json
import tempfile
import os

# Try to import docling HybridChunker and DocumentConverter
try:
    from docling.document_converter import DocumentConverter
    from docling.chunking import HybridChunker
    DOCLING_AVAILABLE = True
except ImportError:
    DOCLING_AVAILABLE = False

import tiktoken

os.environ["HF_HOME"] = "/tmp"
os.environ["HF_HUB_CACHE"] = "/tmp/huggingface"
os.environ["TRANSFORMERS_CACHE"] = "/tmp/huggingface"
os.environ["XDG_CACHE_HOME"] = "/tmp"

def html_to_markdown(html):
    """
    Convert HTML to Markdown using docling (preferred) or markdownify (fallback).
    If docling is not available, falls back to markdownify.
    If neither is available, returns an error message.
    """
    try:
        from docling.document_converter import DocumentConverter
        converter = DocumentConverter()
        with tempfile.NamedTemporaryFile('w+', suffix='.html', delete=False, encoding='utf-8') as tmp:
            tmp.write(html)
            tmp.flush()
            tmp_path = tmp.name
        try:
            result = converter.convert(tmp_path)
            return result.document.export_to_markdown()
        finally:
            os.unlink(tmp_path)
    except ImportError:
        try:
            from markdownify import markdownify as md
            return md(html)
        except ImportError:
            return "[ERROR: Neither docling nor markdownify is available]"

def yaml_escape(s):
    """Escape double quotes and backslashes for YAML."""
    if not isinstance(s, str):
        return s
    return s.replace('\\', '\\\\').replace('"', '\\"')

def main():
    if len(sys.argv) > 1:
        posts_json_path = sys.argv[1]
    else:
        posts_json_path = 'posts.json'

    try:
        with open(posts_json_path, 'r', encoding='utf-8') as f:
            posts = json.load(f)
    except Exception as e:
        print(f'Error reading posts.json: {e}')
        sys.exit(1)

    if not posts:
        print('No published posts found.')
        sys.exit(0)

    all_md = []
    for post in posts:
        title = post.get('title', {}).get('rendered', '')
        date = post.get('date', '')
        author = post.get('_embedded', {}).get('author', [{}])[0].get('name', 'Unknown')
        content_html = post.get('content', {}).get('rendered', '')
        permalink = post.get('permalink', '')
        post_id = post.get('ID', '')
        categories = post.get('categories', [])
        tags = post.get('tags', [])

        # YAML front matter
        yaml_lines = [
            "---",
            f'id: {post_id}',
            f'title: "{yaml_escape(title)}"',
            f'date: "{date}"',
            f'author: "{yaml_escape(author)}"',
            f'permalink: "{permalink}"',
            "categories:",
        ] + [f'  - "{yaml_escape(cat)}"' for cat in categories] + [
            "tags:",
        ] + [f'  - "{yaml_escape(tag)}"' for tag in tags] + [
            "---",
            "",
        ]

        # Markdown content
        md = f'# {title}\n\n'
        md += html_to_markdown(content_html)
        all_md.append('\n'.join(yaml_lines) + md)

    # Output: separate each post with a clear delimiter for chunking
    markdown = '\n\n---\n\n'.join(all_md)

    # Get max_tokens from environment or default
    max_tokens = int(os.environ.get("NPA_RAG_MAX_TOKENS", 8191))
    model = os.environ.get("NPA_RAG_MODEL", "gpt-3.5-turbo")

    if DOCLING_AVAILABLE:
        # Use docling DocumentConverter and HybridChunker for chunking
        with tempfile.NamedTemporaryFile('w+', suffix='.md', delete=False, encoding='utf-8') as tmp_md:
            tmp_md.write(markdown)
            tmp_md.flush()
            tmp_md_path = tmp_md.name
        try:
            doc = DocumentConverter().convert(source=tmp_md_path).document
            chunker = HybridChunker()
            chunk_iter = chunker.chunk(dl_doc=doc)
            for i, chunk in enumerate(chunk_iter):
                text = chunk.text
                # Try to get token count using tiktoken if possible
                try:
                    enc = tiktoken.encoding_for_model(model)
                    token_count = len(enc.encode(text))
                except Exception:
                    token_count = 'N/A'
                print(f"\n\n--- chunk {i+1} (tokens: {token_count}) ---\n\n")
                print(text)
        finally:
            os.unlink(tmp_md_path)
    else:
        # Fallback: use tiktoken-based chunking as before
        enc = tiktoken.encoding_for_model(model)
        lines = markdown.splitlines(keepends=True)
        chunks = []
        current_chunk = ""
        current_tokens = 0
        for line in lines:
            line_tokens = len(enc.encode(line))
            if current_tokens + line_tokens > max_tokens and current_chunk:
                chunks.append(current_chunk)
                current_chunk = line
                current_tokens = line_tokens
            else:
                current_chunk += line
                current_tokens += line_tokens
        if current_chunk:
            chunks.append(current_chunk)
        for i, chunk in enumerate(chunks, 1):
            token_count = len(enc.encode(chunk))
            print(f"\n\n--- chunk {i} (tokens: {token_count}) ---\n\n")
            print(chunk)

if __name__ == '__main__':
    main() 