"""
wp_posts_to_markdown.py
----------------------
Converts a list of WordPress posts (in JSON format) to Markdown with YAML front matter for chunking/embedding.

- Expects a JSON file as the first argument (from PHP/WordPress), or defaults to 'posts.json'.
- Uses docling (preferred) or markdownify (fallback) to convert HTML post content to Markdown.
- Outputs one Markdown document per post, separated by a clear delimiter.

Usage:
    python3 wp_posts_to_markdown.py /path/to/posts.json

This script is designed to be called from a PHP integration (see runner.php).
"""
import sys
import json
import tempfile
import os

# --- HTML to Markdown Conversion ---
def html_to_markdown(html):
    """
    Convert HTML to Markdown using docling (preferred) or markdownify (fallback).
    If docling is not available, falls back to markdownify.
    If neither is available, returns an error message.
    """
    try:
        from docling.document_converter import DocumentConverter
        converter = DocumentConverter()
        # Write HTML to a temporary file and pass the file path to docling
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

# --- Main Script Logic ---
def main():
    """
    Main entry point. Reads posts from a JSON file, converts each to Markdown, and prints the result.
    """
    # Determine input file path
    if len(sys.argv) > 1:
        posts_json_path = sys.argv[1]
    else:
        posts_json_path = 'posts.json'  # Default fallback

    # Load posts from JSON file
    try:
        with open(posts_json_path, 'r', encoding='utf-8') as f:
            posts = json.load(f)
    except Exception as e:
        print(f'Error reading posts.json: {e}')
        sys.exit(1)

    if not posts:
        print('No published posts found.')
        sys.exit(0)

    # Convert each post to Markdown
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
    print('\n\n---\n\n'.join(all_md))

if __name__ == '__main__':
    main() 