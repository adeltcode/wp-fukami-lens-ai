import sys
from config import load_config
from conversion.html_to_markdown import html_to_markdown
from chunking.tokenizer import get_tokenizer
import tempfile, os
import json

def main():
    config = load_config()
    print(config.max_input_tokens)
    # Retrieve HTML file and read it
    input_path = sys.argv[1]
    with open(input_path, 'r', encoding='utf-8') as f:
        html_content = f.read()

    # Retrieve model encoding and tokenizer
    tokenizer = get_tokenizer(config.embeddings_model)

    # Save HTML to a temp file for Docling
    with tempfile.NamedTemporaryFile('w+', suffix='.html', delete=False, encoding='utf-8') as tmp_html:
        tmp_html.write(html_content)
        tmp_html.flush()
        tmp_html_path = tmp_html.name
    try:
        try:
            from docling.document_converter import DocumentConverter
            from docling.chunking import HybridChunker

            doc_conv = DocumentConverter().convert(source=tmp_html_path)
            doc = doc_conv.document

            print(f"\n{doc}\n")
            # Output Docling document as JSON for debugging
            # try:
            #     doc_json = doc.export_to_dict() if hasattr(doc, 'export_to_dict') else (doc.to_dict() if hasattr(doc, 'to_dict') else None)
            #     if doc_json is not None:
            #         print(f"[JSON] {json.dumps(doc_json, ensure_ascii=False, indent=2)}")
            #     else:
            #         print("[JSON] [Error: Docling document cannot be serialized to JSON]")
            # except Exception as e:
            #     print(f"[JSON] [Docling Document JSON Error] {e}")

            chunker = HybridChunker(
                tokenizer=tokenizer, 
                max_tokens=config.max_input_tokens, 
                merge_peers=True
            )
            chunks = list(chunker.chunk(dl_doc=doc))

            for i, chunk in enumerate(chunks):
                print(f"=== {i} ===")
                txt = chunk.text if hasattr(chunk, 'text') else chunk
                txt_tokens = len(tokenizer.tokenizer.encode(txt))
                print(f"chunk.text ({txt_tokens} tokens):\n{repr(txt)}")

                ser_txt = chunker.contextualize(chunk=chunk)
                ser_tokens = len(tokenizer.tokenizer.encode(ser_txt))
                print(f"chunker.contextualize(chunk) ({ser_tokens} tokens):\n{repr(ser_txt)}")

        except ImportError:
            print("[Docling not available: using tiktoken fallback chunking]")
            try:
                import tiktoken
                enc = tiktoken.encoding_for_model(config.embeddings_model)
                lines = html_content.splitlines(keepends=True)
                max_tokens = config.max_input_tokens
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
                for i, chunk in enumerate(chunks):
                    token_count = len(enc.encode(chunk))
                    print(f"=== {i} ===")
                    print(f"chunk.text ({token_count} tokens):\n{repr(chunk)}")
            except ImportError:
                print("[Neither Docling nor tiktoken available: cannot chunk or tokenize]")
    finally:
        os.unlink(tmp_html_path)

if __name__ == "__main__":
    main() 