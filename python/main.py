import sys
from config import load_config
from models.post import load_posts_from_json
from conversion.html_to_markdown import html_to_markdown
from chunking.chunker import chunk_markdown
from chunking.tokenizer import get_tokenizer
from utils.yaml_utils import build_yaml_front_matter

def main():
    config = load_config()
    posts = load_posts_from_json(sys.argv[1])
    tokenizer = get_tokenizer(config.embeddings_model)
    for post in posts:
        md = html_to_markdown(post.content)
        yaml = build_yaml_front_matter(post)
        chunks = chunk_markdown(md, tokenizer, config.max_input_tokens)
        for i, chunk in enumerate(chunks, 1):
            print(f"{i}---")
            print(yaml)
            print(chunk.text if hasattr(chunk, 'text') else chunk)

if __name__ == "__main__":
    main() 