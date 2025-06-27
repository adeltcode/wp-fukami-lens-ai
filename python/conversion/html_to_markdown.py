import tempfile
import os

def html_to_markdown(html):
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