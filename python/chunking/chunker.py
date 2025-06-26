import tempfile
import os

def chunk_markdown(md_text, tokenizer, max_tokens):
    try:
        from docling.chunking import HybridChunker
        from docling.document_converter import DocumentConverter
        with tempfile.NamedTemporaryFile('w+', suffix='.md', delete=False, encoding='utf-8') as tmp_md:
            tmp_md.write(md_text)
            tmp_md.flush()
            tmp_md_path = tmp_md.name
        try:
            doc = DocumentConverter().convert(source=tmp_md_path).document
            chunker = HybridChunker(tokenizer=tokenizer, max_tokens=max_tokens, merge_peers=True)
            return list(chunker.chunk(dl_doc=doc))
        finally:
            os.unlink(tmp_md_path)
    except ImportError:
        # Fallback: tiktoken-based chunking
        import tiktoken
        enc = tiktoken.encoding_for_model(tokenizer.model_name)
        lines = md_text.splitlines(keepends=True)
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
        return chunks 